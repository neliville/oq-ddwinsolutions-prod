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

if grep -q $'\r' .env 2>/dev/null; then
    echo "ATTENTION: .env contient des fins de ligne Windows (CRLF)." >&2
    echo "  Corriger sur le serveur : sed -i 's/\\r\$//' .env" >&2
fi

MYSQL_CNF=$(mktemp)
trap 'rm -f "${MYSQL_CNF}"' EXIT

DB_NAME=$(php scripts/lib/mysql-creds-from-env.php "${MYSQL_CNF}")

echo "==> Dump MySQL (${DB_NAME})"
mysqldump --defaults-extra-file="${MYSQL_CNF}" --single-transaction --routines --triggers \
    "${DB_NAME}" | gzip > "${BACKUP_DIR}/db-pre-premium-${STAMP}.sql.gz"

echo "==> Archive filesystem critique"
tar -czf "${BACKUP_DIR}/fs-pre-premium-${STAMP}.tar.gz" \
    .env public/assets/ public/uploads/ var/log/ 2>/dev/null || true

echo "==> Snapshot Git HEAD"
git rev-parse HEAD > "${BACKUP_DIR}/last_known_good_${STAMP}.sha"
ln -sf "${BACKUP_DIR}/last_known_good_${STAMP}.sha" "${BACKUP_DIR}/last_known_good_latest.sha"

echo "OK backups dans ${BACKUP_DIR}"
ls -lh "${BACKUP_DIR}/db-pre-premium-${STAMP}.sql.gz" "${BACKUP_DIR}/fs-pre-premium-${STAMP}.tar.gz"
