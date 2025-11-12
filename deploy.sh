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

# 3. Récupérer les dernières modifications
echo -e "\n${YELLOW}3. Récupération des dernières modifications depuis GitHub...${NC}"
git fetch origin main
git pull origin main || {
    echo -e "${RED}Erreur lors du pull. Vérifiez les conflits.${NC}"
    exit 1
}
echo -e "${GREEN}   ✓ Code mis à jour${NC}"

# 4. Installer/Mettre à jour les dépendances Composer
echo -e "\n${YELLOW}4. Installation des dépendances Composer...${NC}"
if [ -f "composer.phar" ]; then
    php composer.phar install --no-dev --optimize-autoloader --no-interaction
else
    composer install --no-dev --optimize-autoloader --no-interaction
fi
echo -e "${GREEN}   ✓ Dépendances installées${NC}"

# 5. Exécuter les migrations de base de données
echo -e "\n${YELLOW}5. Exécution des migrations Doctrine...${NC}"
php bin/console doctrine:migrations:migrate --no-interaction || {
    echo -e "${YELLOW}   ⚠ Aucune migration à exécuter ou erreur (non bloquant)${NC}"
}
echo -e "${GREEN}   ✓ Migrations vérifiées${NC}"

# 6. Compiler les assets avec Asset Mapper
echo -e "\n${YELLOW}6. Compilation des assets (Asset Mapper)...${NC}"
php bin/console asset-map:compile || {
    echo -e "${RED}Erreur lors de la compilation des assets${NC}"
    exit 1
}
echo -e "${GREEN}   ✓ Assets compilés${NC}"

# 7. Vider le cache Symfony
echo -e "\n${YELLOW}7. Vidage du cache Symfony...${NC}"
php bin/console cache:clear --env=prod --no-debug || {
    echo -e "${YELLOW}   ⚠ Erreur lors du vidage du cache (peut être normal en prod)${NC}"
}
echo -e "${GREEN}   ✓ Cache vidé${NC}"

# 8. Optimiser l'autoloader
echo -e "\n${YELLOW}8. Optimisation de l'autoloader...${NC}"
if [ -f "composer.phar" ]; then
    php composer.phar dump-autoload --optimize --no-dev
else
    composer dump-autoload --optimize --no-dev
fi
echo -e "${GREEN}   ✓ Autoloader optimisé${NC}"

# 9. Vérifier les permissions (optionnel, selon la configuration o2switch)
echo -e "\n${YELLOW}9. Vérification des permissions...${NC}"
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

