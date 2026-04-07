#!/usr/bin/env bash
set -Eeuo pipefail

REMOTE="${DEPLOY_REMOTE:-origin}"
BRANCH="${DEPLOY_BRANCH:-main}"
TARGET_REF="${REMOTE}/${BRANCH}"
APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
COMPOSER_BIN="${DEPLOY_COMPOSER_BIN:-}"
NODE_BIN_DIR="${DEPLOY_NODE_BIN_DIR:-}"
BUILD_FRONTEND="${DEPLOY_BUILD_FRONTEND:-1}"
KEEP_FILES=(
    "public/favicon.ico"
    "public/favicon.png"
)

log() {
    printf "[%s] %s\n" "$(date '+%F %T')" "$*"
}

fail() {
    printf "ERROR: %s\n" "$*" >&2
    exit 1
}

require_cmd() {
    command -v "$1" >/dev/null 2>&1 || fail "Missing required command: $1"
}

require_bin() {
    local bin="$1"
    local label="${2:-$1}"

    if [[ "$bin" == */* ]]; then
        [[ -x "$bin" ]] || fail "Missing required executable for ${label}: ${bin}"
        return 0
    fi

    command -v "$bin" >/dev/null 2>&1 || fail "Missing required command: ${label}"
}

require_cmd git
require_cmd php

if [[ -z "$NODE_BIN_DIR" ]]; then
    for candidate in \
        "/opt/alt/alt-nodejs22/root/usr/bin" \
        "/opt/alt/alt-nodejs20/root/usr/bin" \
        "/opt/alt/alt-nodejs18/root/usr/bin"; do
        if [[ -x "${candidate}/node" && -x "${candidate}/npm" ]]; then
            NODE_BIN_DIR="$candidate"
            break
        fi
    done
fi

if [[ -n "$NODE_BIN_DIR" ]]; then
    [[ -x "${NODE_BIN_DIR}/node" ]] || fail "Configured DEPLOY_NODE_BIN_DIR has no node binary: ${NODE_BIN_DIR}"
    [[ -x "${NODE_BIN_DIR}/npm" ]] || fail "Configured DEPLOY_NODE_BIN_DIR has no npm binary: ${NODE_BIN_DIR}"
    export PATH="${NODE_BIN_DIR}:${PATH}"
    log "Using Node.js toolchain from ${NODE_BIN_DIR}"
else
    log "Node.js toolchain not found via DEPLOY_NODE_BIN_DIR or /opt/alt/alt-nodejs*/root/usr/bin"
fi

if [[ "$BUILD_FRONTEND" == "1" ]]; then
    require_cmd node
    require_cmd npm
fi

if [[ -z "$COMPOSER_BIN" ]]; then
    if command -v composer >/dev/null 2>&1; then
        COMPOSER_BIN="composer"
    elif [[ -x "${HOME}/composer.phar" ]]; then
        COMPOSER_BIN="${HOME}/composer.phar"
    elif [[ -x "/home/skdubravahr/composer.phar" ]]; then
        COMPOSER_BIN="/home/skdubravahr/composer.phar"
    else
        fail "Composer not found. Install 'composer' in PATH or set DEPLOY_COMPOSER_BIN=/path/to/composer.phar"
    fi
fi

require_bin "$COMPOSER_BIN" composer

cd "$APP_DIR"

[[ -f artisan ]] || fail "artisan not found. Run this script from project root."
[[ -f .env ]] || fail ".env not found."

git rev-parse --is-inside-work-tree >/dev/null 2>&1 || fail "This directory is not a git repository."
git remote get-url "$REMOTE" >/dev/null 2>&1 || fail "Git remote '$REMOTE' is not configured."

is_keep_file() {
    local path="$1"
    local keep
    for keep in "${KEEP_FILES[@]}"; do
        if [[ "$path" == "$keep" ]]; then
            return 0
        fi
    done
    return 1
}

CHANGED_LIST_FILE="$(mktemp)"
{
    git diff --name-only
    git diff --cached --name-only
    git ls-files --others --exclude-standard
} | sed '/^$/d' | sort -u > "$CHANGED_LIST_FILE"

HAS_DISALLOWED=0
while IFS= read -r path; do
    [[ -z "$path" ]] && continue
    if ! is_keep_file "$path"; then
        printf "Disallowed local change: %s\n" "$path" >&2
        HAS_DISALLOWED=1
    fi
done < "$CHANGED_LIST_FILE"
rm -f "$CHANGED_LIST_FILE"

if [[ "$HAS_DISALLOWED" -ne 0 ]]; then
    fail "Working tree has local changes outside allowed favicon overrides."
fi

BACKUP_DIR="$(mktemp -d)"
trap 'rm -rf "$BACKUP_DIR"' EXIT

for file in "${KEEP_FILES[@]}"; do
    if [[ -f "$file" ]]; then
        mkdir -p "$BACKUP_DIR/$(dirname "$file")"
        cp -p "$file" "$BACKUP_DIR/$file"
        log "Preserved local override: $file"
    fi
done

log "Fetching latest changes from ${REMOTE}..."
git fetch "$REMOTE" --prune
git rev-parse --verify "$TARGET_REF" >/dev/null 2>&1 || fail "Target ref '$TARGET_REF' does not exist."

CURRENT_SHA="$(git rev-parse --short HEAD)"
TARGET_SHA="$(git rev-parse --short "$TARGET_REF")"
log "Updating code ${CURRENT_SHA} -> ${TARGET_SHA}"
git reset --hard "$TARGET_REF"

for file in "${KEEP_FILES[@]}"; do
    if [[ -f "$BACKUP_DIR/$file" ]]; then
        mkdir -p "$(dirname "$file")"
        cp -p "$BACKUP_DIR/$file" "$file"
        log "Restored local override: $file"
    fi
done

if [[ "$BUILD_FRONTEND" == "1" ]]; then
    [[ -f package.json ]] || fail "package.json not found; cannot build frontend assets."
    [[ -f package-lock.json ]] || fail "package-lock.json not found; cannot run npm ci."

    log "Installing Node.js dependencies (including dev dependencies for Vite build)..."
    npm ci --include=dev --no-audit --no-fund

    log "Building frontend assets with Vite..."
    npm run build
else
    log "Skipping frontend build (DEPLOY_BUILD_FRONTEND=${BUILD_FRONTEND})."
fi

log "Installing Composer dependencies (production)..."
"$COMPOSER_BIN" install --no-dev --optimize-autoloader --no-interaction

log "Running database migrations..."
php artisan migrate --force

log "Rebuilding Laravel caches..."
php artisan optimize:clear
php artisan config:cache
php artisan view:cache

log "Restarting queue workers..."
php artisan queue:restart

log "Deploy finished."
