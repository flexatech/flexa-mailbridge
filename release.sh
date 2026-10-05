#!/usr/bin/env bash
# Build a production zip of flexa-mailbridge into ./dist/.
set -euo pipefail

PLUGIN_SLUG="flexa-mailbridge"
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DIST_DIR="${ROOT_DIR}/dist"
STAGE_ROOT="/tmp/${PLUGIN_SLUG}-release"
STAGE_DIR="${STAGE_ROOT}/${PLUGIN_SLUG}"

cd "${ROOT_DIR}"

# Read plugin version from the main file.
VERSION="$(grep -E '^[[:space:]]*\*[[:space:]]*Version:' "${PLUGIN_SLUG}.php" | head -1 | sed -E 's/.*Version:[[:space:]]*([^[:space:]]+).*/\1/')"
if [[ -z "${VERSION}" ]]; then
    echo "Could not read Version from ${PLUGIN_SLUG}.php" >&2
    exit 1
fi
echo "Building ${PLUGIN_SLUG} v${VERSION}"

# The plugin has no runtime composer deps (require is only php) and ships the
# bootstrap fallback autoloader, so we do NOT run composer here — vendor/ is
# excluded from the zip anyway. Build the admin bundle when a client app exists.
if [[ -f package.json ]]; then
    pnpm install --frozen-lockfile
    pnpm build
    if [[ ! -f assets/dist/.vite/manifest.json ]]; then
        echo "Build did not produce assets/dist/.vite/manifest.json — Enqueue needs it." >&2
        exit 1
    fi
fi

# Stage outside the plugin so earlier zips in dist/ are left alone.
rm -rf "${STAGE_ROOT}"
mkdir -p "${STAGE_DIR}"

# NOTE: the leading slash is stripped here, so every .distignore entry becomes
# an UNANCHORED rsync pattern that matches at any depth. Never put "/dist" in
# .distignore: it would also drop assets/dist/, the built bundle the plugin
# needs to run. Anchored excludes belong on the rsync line below.
EXCLUDES=()
if [[ -f .distignore ]]; then
    while IFS= read -r line; do
        line="${line%%#*}"
        line="${line## }"
        line="${line%% }"
        [[ -z "${line}" ]] && continue
        EXCLUDES+=(--exclude="${line#/}")
    done < .distignore
fi

# These three are anchored with a leading slash on purpose: assets/dist/ holds
# the built bundle and has to ship, so a bare "dist" would gut the plugin.
rsync -a "${EXCLUDES[@]}" --exclude="/build" --exclude="/dist" --exclude="/.git" "${ROOT_DIR}/" "${STAGE_DIR}/"

mkdir -p "${DIST_DIR}"
ZIP_PATH="${DIST_DIR}/${PLUGIN_SLUG}-${VERSION}.zip"
rm -f "${ZIP_PATH}"
cd "${STAGE_ROOT}"
zip -rq "${ZIP_PATH}" "${PLUGIN_SLUG}"

# Drop the staging tree - only the zip needs to stay.
rm -rf "${STAGE_ROOT}"

echo ""
echo "Built ${ZIP_PATH}"
