#!/usr/bin/env bash
# Snapshot Lighthouse (mobile + desktop) — URLs critiques perf.
# Usage : ./scripts/perf-snapshot.sh [BASE_URL]
# Prérequis : npx (Node) pour lighthouse CLI.

set -euo pipefail

BASE_URL="${1:-http://127.0.0.1:8000}"
OUT_DIR="var/perf-snapshots/$(date +%Y%m%d-%H%M%S)"
mkdir -p "$OUT_DIR"

PATHS=(
  "/"
  "/login"
  "/blog"
  "/outil/ishikawa"
  "/dashboard/qse/pdca"
)

if ! command -v npx >/dev/null 2>&1; then
  echo "npx introuvable — installez Node.js pour exécuter Lighthouse." >&2
  exit 1
fi

for path in "${PATHS[@]}"; do
  slug="${path//\//-}"
  slug="${slug#/}"
  [[ -z "$slug" ]] && slug="home"
  url="${BASE_URL}${path}"
  echo "==> $url"
  npx --yes lighthouse "$url" \
    --quiet \
    --chrome-flags="--headless --no-sandbox" \
    --output=json \
    --output-path="${OUT_DIR}/${slug}-mobile.json" \
    --form-factor=mobile \
    --only-categories=performance || true
  npx --yes lighthouse "$url" \
    --quiet \
    --chrome-flags="--headless --no-sandbox" \
    --output=json \
    --output-path="${OUT_DIR}/${slug}-desktop.json" \
    --form-factor=desktop \
    --screenEmulation.disabled \
    --only-categories=performance || true
done

echo "Rapports JSON : ${OUT_DIR}/"
