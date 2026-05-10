#!/usr/bin/env bash
# Importe les onglets 14001 et 45001 du classeur « Exigences ISO 9001v2015__en_cours.xlsx »
# (même logique que l’UI admin une fois le fichier détecté comme classeur 3 feuilles).
#
# Usage :
#   ./scripts/import-audit-exigences-workbook.sh
#   ./scripts/import-audit-exigences-workbook.sh --tabs=9001,14001,45001
#   ./scripts/import-audit-exigences-workbook.sh --env=prod
#
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"
exec php bin/console app:qse:import-audit-exigences-workbook -n "$@"
