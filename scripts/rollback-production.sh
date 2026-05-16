#!/usr/bin/env bash
# Rollback production o2switch — à exécuter sur le serveur après backup (voir DEPLOY_O2SWITCH.md).
set -euo pipefail

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

if [[ ! -f composer.json ]]; then
    echo -e "${RED}Exécuter depuis la racine du projet Symfony.${NC}" >&2
    exit 1
fi

BACKUP_DIR="${HOME}/backups"
LAST_GOOD=$(ls -t "${BACKUP_DIR}"/last_known_good_*.sha 2>/dev/null | head -1 || true)
LATEST_DB=$(ls -t "${BACKUP_DIR}"/db-pre-premium-*.sql.gz 2>/dev/null | head -1 || true)

if [[ -z "${LAST_GOOD}" ]]; then
    echo -e "${RED}Aucun fichier last_known_good_*.sha dans ${BACKUP_DIR}${NC}" >&2
    exit 1
fi

SHA=$(cat "${LAST_GOOD}")
echo -e "${YELLOW}Rollback Git vers ${SHA}${NC}"
git fetch origin main
git reset --hard "${SHA}"

if [[ -n "${LATEST_DB}" && -f .env ]]; then
    echo -e "${YELLOW}Restauration DB depuis ${LATEST_DB}${NC}"
    MYSQL_CNF=$(mktemp)
    trap 'rm -f "${MYSQL_CNF}"' EXIT
    DB_NAME=$(php scripts/lib/mysql-creds-from-env.php "${MYSQL_CNF}")
    gunzip -c "${LATEST_DB}" | mysql --defaults-extra-file="${MYSQL_CNF}" "${DB_NAME}"
else
    echo -e "${YELLOW}Pas de dump DB — rollback code uniquement.${NC}"
fi

echo -e "${YELLOW}Cache + assets${NC}"
rm -rf var/cache/*
php bin/console cache:warmup --env=prod --no-debug
export TMPDIR="${TMPDIR:-$(pwd)/var/tmp}"
mkdir -p "${TMPDIR}"
php bin/console tailwind:build --minify --env=prod --no-debug --no-interaction
php bin/console asset-map:compile --env=prod --no-debug

echo -e "${GREEN}Rollback terminé. Vérifier : tail -n 100 var/log/prod.log${NC}"
