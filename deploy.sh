#!/usr/bin/env bash
set -Eeuo pipefail

REMOTE="${DEPLOY_REMOTE:-origin}"
BRANCH="${DEPLOY_BRANCH:-main}"
TARGET_REF="${REMOTE}/${BRANCH}"
APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
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

require_cmd git
require_cmd php
require_cmd composer

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

mapfile -t CHANGED_FILES < <(
    {
        git diff --name-only
        git diff --cached --name-only
        git ls-files --others --exclude-standard
    } | sed '/^$/d' | sort -u
)

HAS_DISALLOWED=0
for path in "${CHANGED_FILES[@]}"; do
    if ! is_keep_file "$path"; then
        printf "Disallowed local change: %s\n" "$path" >&2
        HAS_DISALLOWED=1
    fi
done

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

log "Installing Composer dependencies (production)..."
composer install --no-dev --optimize-autoloader --no-interaction

log "Running database migrations..."
php artisan migrate --force

log "Rebuilding Laravel caches..."
php artisan optimize:clear
php artisan config:cache
php artisan view:cache

log "Restarting queue workers..."
php artisan queue:restart

log "Deploy finished."
