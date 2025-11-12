# Guide de déploiement sur o2switch

## Prérequis

1. **Accès SSH activé** sur votre hébergement o2switch
   - Via cPanel : Outils → Autorisation SSH
   - Ajoutez votre adresse IP à la liste blanche

2. **Git installé** sur le serveur o2switch
   - Généralement disponible par défaut

3. **Composer installé** sur le serveur
   - Vérifiez avec : `composer --version`
   - Si absent, installez-le selon la [documentation o2switch](https://faq.o2switch.fr/guides/php/installer-composer/)

## Connexion SSH

1. Connectez-vous à votre cPanel o2switch
2. Ouvrez l'outil "Terminal" ou utilisez un client SSH (PuTTY, Terminal, etc.)
3. Connectez-vous avec vos identifiants SSH

## Utilisation du script de déploiement

### Étape 1 : Naviguer vers le répertoire du projet

```bash
# Trouvez d'abord le chemin de votre projet
pwd

# Naviguez vers votre projet (exemples courants)
cd ~/public_html
# OU
cd ~/domains/votre-domaine.com/public_html
# OU le chemin indiqué dans cPanel → Fichiers
```

### Étape 2 : Vérifier que vous êtes au bon endroit

```bash
# Vérifiez que composer.json existe
ls -la composer.json

# Vérifiez l'état Git
git status
```

### Étape 3 : Télécharger le script (si pas déjà présent)

Si le script `deploy.sh` n'est pas déjà dans votre projet sur o2switch :

```bash
# Option 1 : Via Git (si le script est commité)
git pull origin main

# Option 2 : Télécharger manuellement via cPanel → Gestionnaire de fichiers
# Puis rendre exécutable :
chmod +x deploy.sh
```

### Étape 4 : Exécuter le script

```bash
./deploy.sh
```

Le script va automatiquement :
1. ✅ Sauvegarder vos modifications locales (dans `git stash`)
2. ✅ Récupérer les dernières modifications depuis GitHub
3. ✅ Installer/mettre à jour les dépendances Composer
4. ✅ Exécuter les migrations de base de données
5. ✅ Compiler les assets avec Asset Mapper
6. ✅ Vider le cache Symfony
7. ✅ Optimiser l'autoloader

## Résolution du problème de conflit Git

Si vous rencontrez l'erreur :
```
erreur: Vos modifications locales aux fichiers suivants seraient écrasées par la fusion: public/js/ishikawa.js
```

### Solution rapide (écraser les modifications locales)

```bash
# Sauvegarder les modifications locales
git stash

# Récupérer les dernières versions
git pull origin main
```

### Solution alternative (si les modifications locales sont importantes)

```bash
# Commiter les modifications locales
git add public/js/ishikawa.js
git commit -m "fix: modifications locales o2switch"

# Puis faire le pull
git pull origin main

# Résoudre les conflits si nécessaire, puis push
git push origin main
```

## Commandes manuelles (si le script ne fonctionne pas)

Si vous préférez exécuter les commandes manuellement :

```bash
# 1. Gérer les modifications locales
git stash

# 2. Récupérer les modifications
git pull origin main

# 3. Installer les dépendances
composer install --no-dev --optimize-autoloader

# 4. Migrations
php bin/console doctrine:migrations:migrate --no-interaction

# 5. Compiler les assets
php bin/console asset-map:compile

# 6. Vider le cache
php bin/console cache:clear --env=prod

# 7. Optimiser l'autoloader
composer dump-autoload --optimize --no-dev
```

## Vérification après déploiement

1. **Vérifier que le site fonctionne** : Visitez votre site web
2. **Vérifier les logs** : `tail -f var/log/prod.log` (si accessible)
3. **Vérifier les erreurs PHP** : Activez l'affichage temporairement via cPanel si nécessaire

## Récupérer les modifications sauvegardées

Si le script a sauvegardé vos modifications locales dans `git stash` :

```bash
# Lister les sauvegardes
git stash list

# Récupérer la dernière sauvegarde
git stash pop

# OU récupérer une sauvegarde spécifique
git stash apply stash@{0}
```

## Dépannage

### Erreur "composer: command not found"
```bash
# Utiliser composer.phar si présent
php composer.phar install --no-dev --optimize-autoloader
```

### Erreur de permissions
```bash
# Vérifier les permissions (généralement gérées par o2switch)
ls -la var/
ls -la public/
```

### Erreur "ClassNotFoundError: MakerBundle"

Si vous rencontrez l'erreur :
```
Attempted to load class "MakerBundle" from namespace "Symfony\Bundle\MakerBundle"
```

**Cause** : Le cache Symfony contient encore des références à `MakerBundle` (bundle de développement) alors qu'il n'est pas installé en production.

**Solution** : Le script `deploy.sh` gère automatiquement ce problème en :
1. Supprimant manuellement l'ancien cache avant de le régénérer
2. Filtrant les messages d'erreur liés à MakerBundle
3. Régénérant l'autoloader avant de vider le cache

Si le problème persiste, exécutez manuellement :

```bash
# Supprimer complètement le cache
rm -rf var/cache/*

# Régénérer l'autoloader
composer dump-autoload --optimize --no-dev

# Réchauffer le cache en production
php bin/console cache:warmup --env=prod --no-debug
```

### Erreur lors de la compilation des assets
```bash
# Vérifier que Node.js/Sass est disponible (si nécessaire)
# Asset Mapper utilise généralement PHP uniquement
php bin/console asset-map:compile -v --env=prod
```

### Web Profiler visible en production

Si le Web Profiler de Symfony apparaît en production (barre d'outils en bas de page), cela indique que l'environnement n'est pas correctement configuré.

**Solution immédiate sur o2switch :**

```bash
# 1. Vérifier/modifier le fichier .env
nano .env  # ou votre éditeur préféré

# 2. S'assurer que ces lignes sont présentes et correctes :
APP_ENV=prod
APP_DEBUG=0

# 3. Vider complètement le cache
rm -rf var/cache/*

# 4. Régénérer le cache en production
php bin/console cache:warmup --env=prod --no-debug
```

**Le script `deploy.sh` gère automatiquement cette vérification** depuis la version mise à jour.

**Important :** Ne créez **PAS** de fichier `config/packages/prod/web_profiler.yaml` en production. Le `WebProfilerBundle` n'est pas installé en production (il est dans `require-dev`), donc Symfony ne peut pas charger sa configuration. Le bundle est déjà désactivé dans `config/bundles.php` pour l'environnement `prod`, ce qui est suffisant.

**Erreur "Did you forget to install or enable the WebProfilerBundle?" :**

Si vous voyez cette erreur lors du déploiement, c'est qu'un fichier `config/packages/prod/web_profiler.yaml` existe et tente de charger la configuration du Web Profiler alors que le bundle n'est pas installé en production.

**Solution :**
```bash
# Supprimer le fichier problématique
rm -f config/packages/prod/web_profiler.yaml

# Vider le cache
rm -rf var/cache/*

# Réchauffer le cache
php bin/console cache:warmup --env=prod --no-debug
```

**Vérification :**
- Le Web Profiler ne doit **jamais** apparaître en production
- Il expose des informations sensibles (routes, requêtes, données)
- Il ralentit les performances

### Erreur "ClassNotFoundError: MakerBundle" en production

Cette erreur survient lorsque Symfony essaie de charger `MakerBundle` qui n'est pas installé en production (il est dans `require-dev`).

**Solution immédiate sur o2switch :**

```bash
# 1. Vérifier que les dépendances dev ne sont PAS installées
# IMPORTANT: Utiliser --no-scripts pour éviter l'exécution automatique de cache:clear
composer install --no-dev --optimize-autoloader --no-scripts

# 2. Vider COMPLÈTEMENT le cache (critique)
rm -rf var/cache/*

# 3. Régénérer l'autoloader
composer dump-autoload --optimize --no-dev

# 4. Vérifier l'environnement dans .env
# APP_ENV=prod
# APP_DEBUG=0

# 5. Réchauffer le cache en production
php bin/console cache:warmup --env=prod --no-debug

# 6. Maintenant compiler les assets
php bin/console asset-map:compile --env=prod --no-debug
```

**Le script `deploy.sh` gère automatiquement cette séquence** depuis la version mise à jour, en utilisant `--no-scripts` pour éviter l'exécution automatique de `cache:clear` lors de `composer install`.

**Causes courantes :**
- Cache contenant des références à MakerBundle
- Dépendances dev installées en production
- Environnement mal configuré (APP_ENV != prod)
- **Auto-scripts de Composer exécutant `cache:clear` automatiquement** (résolu avec `--no-scripts`)

### Erreur "Script cache:clear returned with error code 255"

Cette erreur survient lorsque `composer install` exécute automatiquement les `auto-scripts` définis dans `composer.json`, notamment `cache:clear`, qui échoue à cause de MakerBundle.

**Solution :**

Le script `deploy.sh` utilise maintenant `--no-scripts` lors de `composer install` pour éviter l'exécution automatique des scripts. Si vous exécutez manuellement `composer install`, utilisez :

```bash
composer install --no-dev --optimize-autoloader --no-scripts
```

Puis exécutez manuellement les commandes nécessaires après avoir vidé le cache.

## Support

- Documentation o2switch : https://faq.o2switch.fr/
- Support o2switch : support@o2switch.fr (24/7)

