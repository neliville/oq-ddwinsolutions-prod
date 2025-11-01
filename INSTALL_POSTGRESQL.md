# Installation de PostgreSQL pour le développement

## Problème
L'erreur "could not find driver" se produit car le pilote PostgreSQL (`pdo_pgsql`) n'est pas installé dans PHP.

## Solution 1 : Installer le pilote PostgreSQL (recommandé)

### Sur Ubuntu/Debian (WSL)

```bash
# Mettre à jour les paquets
sudo apt update

# Installer le pilote PostgreSQL pour PHP 8.3
sudo apt install -y php-pgsql php8.3-pgsql

# Vérifier l'installation
php -m | grep -i pgsql

# Redémarrer le serveur PHP-FPM si nécessaire
sudo systemctl restart php8.3-fpm  # Si vous utilisez PHP-FPM
```

### Après l'installation

1. Copier le fichier de configuration PostgreSQL :
   ```bash
   cp .env.local.postgresql .env.local
   ```

2. Démarrer PostgreSQL (Docker Compose) :
   ```bash
   docker compose up -d database
   ```

3. Créer la base de données :
   ```bash
   php bin/console doctrine:database:create --if-not-exists
   ```

4. Exécuter les migrations :
   ```bash
   php bin/console doctrine:migrations:migrate --no-interaction
   ```

5. Charger les fixtures (utilisateurs de test) :
   ```bash
   php bin/console doctrine:fixtures:load --no-interaction
   ```

## Solution 2 : Utiliser SQLite temporairement (déjà configuré)

Pour tester rapidement l'authentification sans PostgreSQL, SQLite est déjà configuré dans `.env.local`.

**Utilisateurs de test :**
- Email: `test@outils-qualite.com` / Mot de passe: `test123`
- Email: `contact@outils-qualite.com` / Mot de passe: `admin123` (ROLE_ADMIN)

## Vérification

Après installation, vérifiez que tout fonctionne :
```bash
# Vérifier que le pilote est installé
php -m | grep pgsql

# Tester la connexion
php bin/console doctrine:query:sql "SELECT 1"
```

## Remarque

SQLite convient pour le développement local, mais PostgreSQL est requis pour la production sur Azure App Service.

