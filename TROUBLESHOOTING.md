# Guide de dépannage - Erreurs de déploiement

## Erreur 500 sur la page d'accueil

### Causes possibles

1. **Cache Symfony corrompu**
2. **Assets non compilés**
3. **Problème avec AssetMapper**
4. **Problème avec les dépendances Composer**

### Solutions

#### 1. Vider complètement le cache

```bash
# Sur le serveur o2switch
rm -rf var/cache/*
php bin/console cache:clear --env=prod --no-debug
php bin/console cache:warmup --env=prod --no-debug
```

#### 2. Recompiler les assets

```bash
# Sur le serveur o2switch
php bin/console asset-map:compile --env=prod --no-debug
```

#### 3. Vérifier les logs d'erreur

```bash
# Sur le serveur o2switch
tail -n 100 var/log/prod.log
# ou
tail -n 100 var/log/dev.log
```

#### 4. Vérifier les permissions

```bash
# Sur le serveur o2switch
chmod -R 755 var/
chmod -R 755 public/
```

#### 5. Vérifier que les dépendances sont installées

```bash
# Sur le serveur o2switch
composer install --no-dev --optimize-autoloader --no-interaction
```

#### 6. Vérifier le fichier .env

Assurez-vous que `.env` contient :
```
APP_ENV=prod
APP_DEBUG=0
```

### Commandes de diagnostic rapide

```bash
# Vérifier l'état Git
git status

# Vérifier les fichiers manquants
ls -la vendor/autoload.php
ls -la var/cache/prod/
ls -la public/assets/

# Tester la compilation des assets
php bin/console asset-map:compile --env=prod --no-debug -v

# Tester le cache
php bin/console cache:warmup --env=prod --no-debug -v
```

### Solution complète (à exécuter dans l'ordre)

```bash
# 1. Aller dans le répertoire du projet
cd /chemin/vers/votre/projet

# 2. Vider le cache
rm -rf var/cache/*

# 3. Réinstaller les dépendances
composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# 4. Optimiser l'autoloader
composer dump-autoload --optimize --no-dev

# 5. Vérifier .env
echo "APP_ENV=prod" >> .env
echo "APP_DEBUG=0" >> .env

# 6. Vider et régénérer le cache
php bin/console cache:clear --env=prod --no-debug --no-warmup
php bin/console cache:warmup --env=prod --no-debug

# 7. Compiler les assets
php bin/console asset-map:compile --env=prod --no-debug

# 8. Vérifier les permissions
chmod -R 755 var/
chmod -R 755 public/
```

## Erreur lors du pull Git (fichiers non suivis)

### Solution

Le script `deploy.sh` gère maintenant automatiquement ce cas. Si vous avez encore des problèmes :

```bash
# Supprimer manuellement les fichiers conflictuels
rm public/.htaccess
git pull origin main
```

## Erreur MakerBundle en production

### Solution

Cette erreur est normale et filtrée par le script de déploiement. Si elle apparaît dans les logs, elle peut être ignorée car MakerBundle n'est pas utilisé en production.

