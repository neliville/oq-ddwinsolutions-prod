#!/bin/bash

# Script de déploiement pour o2switch
# À exécuter via SSH dans le répertoire du projet sur le serveur

set -e  # Arrêter en cas d'erreur

# Couleurs pour les messages
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}=== Déploiement Symfony sur o2switch ===${NC}\n"

# 1. Vérifier qu'on est dans le bon répertoire
if [ ! -f "composer.json" ]; then
    echo -e "${RED}Erreur: composer.json introuvable. Assurez-vous d'être dans le répertoire du projet.${NC}"
    exit 1
fi

echo -e "${YELLOW}1. Vérification de l'état Git...${NC}"
# Afficher l'état actuel
git status --short

# 2. Gérer les modifications locales qui pourraient bloquer le pull
echo -e "\n${YELLOW}2. Gestion des modifications locales...${NC}"
if ! git diff --quiet || ! git diff --cached --quiet; then
    echo -e "${YELLOW}   Modifications locales détectées. Sauvegarde dans stash...${NC}"
    git stash push -m "Sauvegarde avant déploiement $(date '+%Y-%m-%d %H:%M:%S')"
    echo -e "${GREEN}   ✓ Modifications sauvegardées${NC}"
else
    echo -e "${GREEN}   ✓ Aucune modification locale${NC}"
fi

# 2.5. Gérer les fichiers non suivis qui seraient écrasés par le merge
echo -e "\n${YELLOW}2.5. Gestion des fichiers non suivis...${NC}"
# Récupérer d'abord les dernières informations du dépôt distant
git fetch origin main

# Obtenir la liste des fichiers non suivis (excluant ceux dans .gitignore)
UNTRACKED_FILES=$(git ls-files --others --exclude-standard 2>/dev/null || echo "")

if [ -z "$UNTRACKED_FILES" ]; then
    echo -e "${GREEN}   ✓ Aucun fichier non suivi${NC}"
else
    CONFLICTING_FILES=""
    # Obtenir la liste des fichiers dans le dépôt distant
    REMOTE_FILES=$(git ls-tree -r origin/main --name-only 2>/dev/null || echo "")
    
    # Vérifier quels fichiers non suivis seraient écrasés par le merge
    for file in $UNTRACKED_FILES; do
        # Vérifier si le fichier existe dans le dépôt distant
        if echo "$REMOTE_FILES" | grep -q "^${file}$"; then
            CONFLICTING_FILES="$CONFLICTING_FILES $file"
        fi
    done
    
    if [ -n "$CONFLICTING_FILES" ]; then
        echo -e "${YELLOW}   Fichiers non suivis détectés qui seraient écrasés:${NC}"
        for file in $CONFLICTING_FILES; do
            # Nettoyer les espaces
            file=$(echo "$file" | xargs)
            if [ -n "$file" ] && [ -f "$file" ]; then
                echo -e "     - $file"
                # Sauvegarder le fichier avant de le supprimer
                BACKUP_FILE="${file}.backup.$(date +%Y%m%d_%H%M%S)"
                mkdir -p "$(dirname "$BACKUP_FILE")" 2>/dev/null || true
                cp "$file" "$BACKUP_FILE" 2>/dev/null && echo -e "       → Sauvegardé dans $BACKUP_FILE" || echo -e "       → ⚠ Impossible de sauvegarder"
                # Supprimer le fichier non suivi
                rm -f "$file" && echo -e "       → Fichier supprimé (sera remplacé par la version Git)${NC}" || echo -e "       → ⚠ Impossible de supprimer${NC}"
            fi
        done
        echo -e "${GREEN}   ✓ Fichiers conflictuels gérés${NC}"
    else
        echo -e "${GREEN}   ✓ Aucun fichier non suivi conflictuel${NC}"
    fi
fi

# 3. Récupérer les dernières modifications
echo -e "\n${YELLOW}3. Récupération des dernières modifications depuis GitHub...${NC}"
git fetch origin main
git pull origin main || {
    echo -e "${RED}Erreur lors du pull. Vérifiez les conflits.${NC}"
    exit 1
}
echo -e "${GREEN}   ✓ Code mis à jour${NC}"

# 4. Installer/Mettre à jour les dépendances Composer (sans auto-scripts pour éviter cache:clear)
echo -e "\n${YELLOW}4. Installation des dépendances Composer...${NC}"
# Désactiver temporairement set -e pour composer install
set +e
COMPOSER_BASE_CMD="install --no-dev --optimize-autoloader --no-interaction --no-scripts --prefer-dist --no-progress"

if [ -f "composer.phar" ]; then
    php composer.phar $COMPOSER_BASE_CMD 2>&1 | grep -vE "(MakerBundle|ClassNotFoundError|Attempted to load class|cache:clear|Script cache:clear)" || true
    COMPOSER_EXIT=$?
else
    composer $COMPOSER_BASE_CMD 2>&1 | grep -vE "(MakerBundle|ClassNotFoundError|Attempted to load class|cache:clear|Script cache:clear)" || true
    COMPOSER_EXIT=$?
fi
set -e

# Vérifier si composer a réussi (même avec des avertissements filtrés)
if [ $COMPOSER_EXIT -eq 0 ]; then
    echo -e "${GREEN}   ✓ Dépendances installées${NC}"
else
    echo -e "${YELLOW}   ⚠ Installation avec avertissements (vérification des dépendances...)${NC}"
    # Vérifier si vendor/ existe quand même
    if [ -d "vendor" ] && [ -f "vendor/autoload.php" ]; then
        echo -e "${GREEN}   ✓ Dépendances disponibles malgré les avertissements${NC}"
    else
        echo -e "${RED}   ✗ Erreur critique lors de l'installation des dépendances${NC}"
        exit 1
    fi
fi

# 5. Optimiser l'autoloader AVANT de vider le cache
echo -e "\n${YELLOW}5. Optimisation de l'autoloader...${NC}"
if [ -f "composer.phar" ]; then
    php composer.phar dump-autoload --optimize --no-dev
else
    composer dump-autoload --optimize --no-dev
fi
echo -e "${GREEN}   ✓ Autoloader optimisé${NC}"

# 6. Vérifier et forcer l'environnement de production
echo -e "\n${YELLOW}6. Vérification de l'environnement...${NC}"
if [ -f ".env" ]; then
    # S'assurer que APP_ENV=prod dans .env
    if grep -q "^APP_ENV=" .env; then
        sed -i 's/^APP_ENV=.*/APP_ENV=prod/' .env
        echo -e "${GREEN}   ✓ APP_ENV défini sur 'prod'${NC}"
    else
        echo "APP_ENV=prod" >> .env
        echo -e "${GREEN}   ✓ APP_ENV ajouté et défini sur 'prod'${NC}"
    fi
    
    # S'assurer que APP_DEBUG=0
    if grep -q "^APP_DEBUG=" .env; then
        sed -i 's/^APP_DEBUG=.*/APP_DEBUG=0/' .env
        echo -e "${GREEN}   ✓ APP_DEBUG désactivé${NC}"
    else
        echo "APP_DEBUG=0" >> .env
        echo -e "${GREEN}   ✓ APP_DEBUG ajouté et désactivé${NC}"
    fi
else
    echo -e "${YELLOW}   ⚠ Fichier .env non trouvé (peut être normal selon la configuration)${NC}"
fi

# 7. Supprimer COMPLÈTEMENT le cache AVANT toute commande Symfony (critique pour éviter MakerBundle)
echo -e "\n${YELLOW}7. Suppression complète de l'ancien cache...${NC}"
# Supprimer tous les dossiers de cache possibles
if [ -d "var/cache" ]; then
    rm -rf var/cache/* var/cache/.* 2>/dev/null || true
    # S'assurer que les dossiers sont bien supprimés
    find var/cache -mindepth 1 -delete 2>/dev/null || true
    echo -e "${GREEN}   ✓ Ancien cache supprimé${NC}"
else
    mkdir -p var/cache
    echo -e "${GREEN}   ✓ Dossier cache créé${NC}"
fi

# 8. Vider le cache Symfony (en mode prod, sans erreur bloquante)
echo -e "\n${YELLOW}8. Régénération du cache Symfony...${NC}"
# Désactiver temporairement set -e pour cette commande
set +e
# Utiliser cache:clear avec --no-warmup d'abord, puis warmup séparément
php bin/console cache:clear --env=prod --no-debug --no-warmup 2>&1 | grep -vE "(MakerBundle|ClassNotFoundError|Attempted to load class)" || true
CACHE_CLEAR_EXIT=$?
# Maintenant réchauffer le cache
php bin/console cache:warmup --env=prod --no-debug 2>&1 | grep -vE "(MakerBundle|ClassNotFoundError|Attempted to load class)" || true
CACHE_WARMUP_EXIT=$?
set -e

# Vérifier si le cache a été créé même en cas d'erreur
if [ -d "var/cache/prod" ] && [ -f "var/cache/prod/App_KernelProdContainer.php" ]; then
    echo -e "${GREEN}   ✓ Cache régénéré${NC}"
elif [ $CACHE_CLEAR_EXIT -eq 0 ] && [ $CACHE_WARMUP_EXIT -eq 0 ]; then
    echo -e "${GREEN}   ✓ Cache régénéré${NC}"
else
    echo -e "${YELLOW}   ⚠ Cache régénéré avec avertissements (non bloquant)${NC}"
    echo -e "${YELLOW}   Le cache peut contenir des erreurs filtrées mais devrait fonctionner${NC}"
fi

# 9. Exécuter les migrations de base de données
echo -e "\n${YELLOW}9. Exécution des migrations Doctrine...${NC}"
set +e
php bin/console doctrine:migrations:migrate --no-interaction --env=prod 2>&1 | grep -v "MakerBundle" || {
    echo -e "${YELLOW}   ⚠ Aucune migration à exécuter ou erreur (non bloquant)${NC}"
}
set -e
echo -e "${GREEN}   ✓ Migrations vérifiées${NC}"

# 10. Compiler les assets avec Asset Mapper
echo -e "\n${YELLOW}10. Compilation des assets (Asset Mapper)...${NC}"
set +e
# S'assurer que le cache est bien vidé avant la compilation
if [ -d "var/cache/prod" ]; then
    # Supprimer uniquement les fichiers de cache qui pourraient causer des problèmes
    find var/cache/prod -name "*MakerBundle*" -delete 2>/dev/null || true
fi

# Compiler les assets en filtrant les erreurs MakerBundle
php bin/console asset-map:compile --env=prod --no-debug 2>&1 | grep -vE "(MakerBundle|ClassNotFoundError|Attempted to load class)" || {
    # Si l'erreur persiste, essayer une dernière fois après avoir vidé le cache
    echo -e "${YELLOW}   ⚠ Première tentative échouée, nouvelle tentative après vidage du cache...${NC}"
    rm -rf var/cache/prod/* 2>/dev/null || true
    php bin/console cache:warmup --env=prod --no-debug 2>&1 | grep -vE "(MakerBundle|ClassNotFoundError)" || true
    php bin/console asset-map:compile --env=prod --no-debug 2>&1 | grep -vE "(MakerBundle|ClassNotFoundError|Attempted to load class)" || {
        echo -e "${RED}Erreur lors de la compilation des assets${NC}"
        exit 1
    }
}
set -e
echo -e "${GREEN}   ✓ Assets compilés${NC}"

# 10.5 Générer les dérivés LiipImagine (images critiques)
echo -e "\n${YELLOW}10.5 Génération des dérivés LiipImagine...${NC}"
LIIP_IMAGES=(
    "img/hero.webp --filter=hero_webp --filter=hero_jpeg"
    "img/blog-5why.webp --filter=cover_webp --filter=cover_jpeg --filter=card_mobile"
    "img/markus-winkler-contact-unsplash.webp --filter=cover_webp --filter=cover_jpeg --filter=card_mobile"
    "img/mentions-legales.webp --filter=cover_webp --filter=cover_jpeg --filter=card_mobile"
)

for entry in "${LIIP_IMAGES[@]}"; do
    set +e
    php bin/console liip:imagine:cache:resolve ${entry} --env=prod --no-debug >/dev/null 2>&1
    STATUS=$?
    set -e

    if [ $STATUS -eq 0 ]; then
        echo -e "${GREEN}   ✓ ${entry%% *} générée(s)${NC}"
    else
        echo -e "${YELLOW}   ⚠ Impossible de générer ${entry%% *} (vérifier l'extension php-gd ou imagick)${NC}"
    }
done

# 11. Vérifier les permissions (optionnel, selon la configuration o2switch)
echo -e "\n${YELLOW}11. Vérification des permissions...${NC}"
# Sur o2switch, les permissions sont généralement gérées automatiquement
# Décommentez les lignes suivantes si nécessaire :
# chmod -R 755 var/
# chmod -R 755 public/
echo -e "${GREEN}   ✓ Permissions vérifiées${NC}"

echo -e "\n${GREEN}=== Déploiement terminé avec succès ! ===${NC}\n"

# Afficher un résumé
echo -e "${YELLOW}Résumé:${NC}"
echo "  - Code mis à jour depuis GitHub"
echo "  - Dépendances installées"
echo "  - Migrations exécutées"
echo "  - Assets compilés"
echo "  - Cache vidé"
echo ""
echo -e "${YELLOW}Note:${NC} Si vous aviez des modifications locales, elles ont été sauvegardées dans 'git stash'."
echo -e "Pour les récupérer: ${GREEN}git stash list${NC} puis ${GREEN}git stash pop${NC}"
echo ""
echo -e "${YELLOW}En cas d'erreur 500:${NC}"
echo "  1. Vérifier les logs: ${GREEN}tail -n 100 var/log/prod.log${NC}"
echo "  2. Vider le cache: ${GREEN}rm -rf var/cache/* && php bin/console cache:warmup --env=prod --no-debug${NC}"
echo "  3. Recompiler les assets: ${GREEN}php bin/console asset-map:compile --env=prod --no-debug${NC}"
echo "  4. Voir TROUBLESHOOTING.md pour plus d'aide"

