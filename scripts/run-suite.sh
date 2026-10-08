#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

if command -v docker >/dev/null 2>&1 && docker info >/dev/null 2>&1; then
  RUNTIME=docker
elif command -v podman >/dev/null 2>&1; then
  RUNTIME=podman
else
  echo "Neither docker nor podman is available" >&2
  exit 1
fi

PHP_72_IMAGE='docker.io/library/php:7.2-cli@sha256:42ffbc0798e4449bbd1e14fc4dcb87774aa1ad1900a09ef6a965bc0880aa2161'
PHP_81_IMAGE='docker.io/library/php:8.1-cli@sha256:76e563191d1ade120313a8736df24154d21da5155c0756f147c0b01bd19d9087'
PHP_83_IMAGE='docker.io/library/php:8.3-cli@sha256:aafe21201943a8a6e497ddfb471e2ce68ada76adede38c7d61fede8918b24319'

COMPOSER_VERSION='2.2.24'
COMPOSER_PHAR_SHA256='b0c383b1f430a80a74c006f20199d1e0226848a0a90afa5c0a7d01fb90ee9075'
RANDOM_SEED='20261008'

run_image_gate() {
  local image="$1"
  local ver="$2"

  echo "=== PHP ${ver} (setup + 4 PHPUnit runs) ==="
  "$RUNTIME" run --rm -v "$ROOT:/app:Z" -w /app "$image" bash -lc "
set -euo pipefail
export DEBIAN_FRONTEND=noninteractive COMPOSER_ALLOW_SUPERUSER=1
if grep -q buster /etc/os-release 2>/dev/null; then
  printf '%s\n' 'Acquire::Check-Valid-Until \"false\";' > /etc/apt/apt.conf.d/99no-check-valid-until
  sed -i -e 's/deb.debian.org/archive.debian.org/g' \
         -e 's|security.debian.org/debian-security|archive.debian.org/debian-security|g' \
         /etc/apt/sources.list || true
fi
apt-get update -qq
apt-get install -y --no-install-recommends libicu-dev zlib1g-dev ca-certificates curl unzip git >/dev/null
docker-php-ext-install -j\"\$(nproc)\" intl bcmath >/dev/null 2>&1 || true
if ! php -m | grep -q '^mbstring$'; then
  docker-php-ext-install -j\"\$(nproc)\" mbstring >/dev/null
fi
curl -fsSL -o composer.phar https://getcomposer.org/download/${COMPOSER_VERSION}/composer.phar
echo '${COMPOSER_PHAR_SHA256} composer.phar' | sha256sum -c -
rm -rf vendor composer.lock
php composer.phar update --no-interaction --prefer-dist --no-progress
for run in default1 default2 random1 random2; do
  case \"\$run\" in
    default1|default2) order_args='' ;;
    random1|random2) order_args='--order-by=random --random-order-seed=${RANDOM_SEED}' ;;
  esac
  echo \"-- phpunit ${ver} \$run --\"
  vendor/bin/phpunit --configuration phpunit.xml.dist \$order_args
done
"
}

MAIN_RESULTS=()
FAIL=0

for spec in "7.2|${PHP_72_IMAGE}" "8.1|${PHP_81_IMAGE}" "8.3|${PHP_83_IMAGE}"; do
  ver="${spec%%|*}"
  image="${spec#*|}"
  log="$(mktemp)"
  if run_image_gate "$image" "$ver" >"$log" 2>&1; then
    while IFS= read -r line; do
      case "$line" in
        OK*) MAIN_RESULTS+=("PHP ${ver}: ${line}") ;;
      esac
    done <"$log"
  else
    FAIL=1
    MAIN_RESULTS+=("PHP ${ver}: FAIL")
    tail -60 "$log" >&2 || true
  fi
  rm -f "$log"
done

ARTIFACT_DIR=/opt/cursor/artifacts
mkdir -p "$ARTIFACT_DIR"
SHA="$(git -C "$ROOT" rev-parse HEAD)"
{
  echo "# suite-results"
  echo
  echo "commit_sha: ${SHA}"
  echo "branch: $(git -C "$ROOT" rev-parse --abbrev-ref HEAD)"
  echo "gate: each image runs default x2 then random seed ${RANDOM_SEED} x2"
  echo
  for line in "${MAIN_RESULTS[@]}"; do
    echo "- ${line}"
  done
  echo
  echo "## PRODUCTION CHANGES"
  echo "- 4.1: grapheme_str_split rejects length 0; ValueError message without trailing period; Resources/stubs/ValueError.php for PHP 7"
  echo "- 4.3: mb_trim/mb_ltrim/mb_rtrim return input unchanged when character mask is empty"
  echo "- 4.5: not applied (PHP 7.2 container: 0**negative is float INF/-INF with no warnings)"
  echo
  echo "## DEFERRED (no production change)"
  echo "- 4.2 mbstring PHP7 warning/false contract"
  echo "- 4.4 bcdivmod DivisionByZeroError on PHP 7"
  echo "- 4.6 cURL HTTP/3 constant detection (curl_version()[version] string compared to int 0x074200)"
  echo "- grapheme cluster accuracy, ill-formed UTF-8 trim, bcdivmod malformed throws, etc."
  echo
  echo "## DEVIATIONS"
  echo "- Container runtime: ${RUNTIME} (host docker daemon unavailable)"
  echo "- Composer: 2.2.24 PHAR with published SHA-256 (installer 2.2.25 returned 404 from getcomposer.org)"
} > "${ARTIFACT_DIR}/suite-results.md"

exit "$FAIL"
