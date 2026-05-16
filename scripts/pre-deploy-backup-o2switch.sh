#!/usr/bin/env bash
# Sauvegarde avant déploiement — à exécuter sur o2switch AVANT ./deploy.sh
set -euo pipefail

BACKUP_DIR="${HOME}/backups"
mkdir -p "${BACKUP_DIR}"
STAMP=$(date +%Y%m%d-%H%M)

if [[ ! -f composer.json ]]; then
    echo "Exécuter depuis la racine du projet." >&2
    exit 1
fi

if [[ ! -f .env ]]; then
    echo "Fichier .env introuvable." >&2
    exit 1
fi

set -a && source .env && set +a
DB_NAME=$(php -r 'preg_match("#/([^?]+)#", getenv("DATABASE_URL"), $m); echo $m[1];')

echo "==> Dump MySQL"
mysqldump --single-transaction --routines --triggers \
    -h"${DB_HOST:-127.0.0.1}" -u"${DB_USER}" -p"${DB_PASSWORD}" "${DB_NAME}" \
    | gzip > "${BACKUP_DIR}/db-pre-premium-${STAMP}.sql.gz"

echo "==> Archive filesystem critique"
tar -czf "${BACKUP_DIR}/fs-pre-premium-${STAMP}.tar.gz" \
    .env public/assets/ public/uploads/ var/log/ 2>/dev/null || true

echo "==> Snapshot Git HEAD"
git rev-parse HEAD > "${BACKUP_DIR}/last_known_good_${STAMP}.sha"
ln -sf "${BACKUP_DIR}/last_known_good_${STAMP}.sha" "${BACKUP_DIR}/last_known_good_latest.sha"

echo "OK backups dans ${BACKUP_DIR}"
ls -lh "${BACKUP_DIR}/db-pre-premium-${STAMP}.sql.gz" "${BACKUP_DIR}/fs-pre-premium-${STAMP}.tar.gz"
