#!/usr/bin/env bash
#
# Post-deployment tasks, run on the server over SSH by the deploy workflow
# once the files are synchronized:
#   ssh user@server "cd /path/to/api && bash bin/deploy.sh"
#
# Environment:
#   APP_ENV  application environment (default: production)
#   PHP_BIN  PHP CLI binary (default: php)

set -euo pipefail

cd "$(dirname "$0")/.."

export APP_ENV="${APP_ENV:-production}"
PHP_BIN="${PHP_BIN:-php}"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*"
}

log "Deploying (APP_ENV=$APP_ENV, $("$PHP_BIN" -r 'echo "PHP ", PHP_VERSION;'))"

log "Running database migrations"
"$PHP_BIN" vendor/bin/phinx migrate --configuration phinx.php

# Cleared after the migrations so entity, route and service caches are
# rebuilt from the new code
log "Clearing cache"
"$PHP_BIN" bin/console cache:clear

echo "$(date '+%Y-%m-%d %H:%M:%S') - Update completed" >> deploy.log
log "Update completed"
