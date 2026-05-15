#!/usr/bin/env bash
# Échoue si des classes Bootstrap évidentes réapparaissent sous templates/admin.
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ADMIN="$ROOT/templates/admin"

if [[ ! -d "$ADMIN" ]]; then
  echo "Missing $ADMIN"
  exit 1
fi

PATTERN='btn-primary|btn-secondary|btn-outline-secondary|btn-outline-primary|btn-danger|btn-warning|container-fluid|col-md-|navbar-expand'

matches=$(grep -rEin "$PATTERN" "$ADMIN" --include='*.twig' || true)
if [[ -n "${matches}" ]]; then
  echo "${matches}"
  echo ""
  echo "❌ Bootstrap-like markup sous templates/admin — nouveau code admin = Tailwind + UX Toolkit uniquement."
  exit 1
fi

echo "✓ Admin Twig : aucune classe Bootstrap évidente."
