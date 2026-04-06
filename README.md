# Outils Qualité - Application Symfony

> Application web Symfony 7.x pour l'analyse de qualité avec diagrammes Ishikawa et méthode 5 Pourquoi.

## 🚀 Installation

### Prérequis

- PHP 8.2+
- Composer
- MySQL 10.6+ (ou MariaDB)
- Node.js 18+ (pour AssetMapper)

### Installation locale

1. **Cloner le projet**
```bash
git clone https://github.com/neliville/oq-ddwinsolutions-prod.git
cd oq-ddwinsolutions-prod
```

2. **Installer les dépendances**
```bash
composer install
npm install
```

3. **Configurer l'environnement**
```bash
cp .env .env.local
```

Éditer `.env.local` avec vos paramètres :
```env
APP_ENV=dev
APP_SECRET=your-secret-key

# MySQL o2switch (ou local)
DB_HOST=yellow.o2switch.net
DB_PORT=3306
DB_NAME=iwob6566_outils-qualite
DB_USER=iwob6566_adminOQ
DB_PASSWORD=your-password
DB_SERVER_VERSION=10.6
```

4. **Créer la base de données et exécuter les migrations**
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

5. **Créer un utilisateur administrateur**
```bash
php bin/console app:create-admin-user
```

6. **Compiler les assets (SCSS → CSS)**

Les styles (dont la page d'accueil et la FAQ) sont en SCSS et doivent être compilés pour s'appliquer en local :

```bash
php bin/console sass:build
```

Pour recompiler automatiquement à chaque modification des fichiers `.scss` :

```bash
php bin/console sass:build --watch
```

(Si vous utilisez `symfony server:start`, vous pouvez ajouter un worker SASS dans `.symfony.local.yaml` pour lancer le watcher automatiquement.)

**Après toute modification des assets (SCSS dans `assets/styles/`, JS dans `assets/`)**, recompiler pour appliquer les changements (AssetMapper + SASS) :

```bash
php bin/console sass:build
php bin/console asset-map:compile
```

En dev avec rechargement automatique des styles : `php bin/console sass:build --watch` (dans un terminal dédié).

7. **Lancer le serveur de développement**
```bash
symfony server:start
# ou
php -S localhost:8000 -t public/
```

## 🏗️ Architecture

L’application suit une structure inspirée BMAD (Site, Tools, Lead) avec :

- **Application/** : Use cases (LeadService, CreateLead, TrackingService, etc.).
- **Controller/** : API (Record, Ishikawa, FiveWhy, Pareto, Amdec, Qqoqccp, EightD, Lead, ToolSuggest), Admin, Public (pages SEO, lead).
- **Lead/** : Domaine lead (BrevoSyncService, QuotaService) et freemium (User.plan, quotas).
- **Tools/** : Points d’entrée IA (ToolAnalysisInterface, DTOs, endpoint `/api/tools/{tool}/suggest`).
- **Infrastructure/Mail** : Messenger (LeadCreatedMessage, SyncLeadToBrevoMessage, scoring, qualification, enrichment).

Détails : [bmad/architecture.md](bmad/architecture.md). Migration et déploiement : [bmad/migration-guide.md](bmad/migration-guide.md).

**Téléchargement modèle 5M (Ishikawa)** : flux documenté dans [docs/DOWNLOAD_MAUTIC.md](docs/DOWNLOAD_MAUTIC.md) (formulaire Mautic embarqué ; la ressource est gérée dans Mautic). Intégration Symfony/n8n optionnelle : [docs/DOWNLOAD_ARCHITECTURE.md](docs/DOWNLOAD_ARCHITECTURE.md).

## 📦 Déploiement

### Déploiement sur Azure App Service (PHP 8.2)

#### Prérequis Azure

- Compte Azure avec App Service créé
- Base de données MySQL (Azure Database for MySQL ou o2switch)
- Secrets GitHub configurés pour le CI/CD

#### Configuration Azure App Service

1. **Variables d'environnement** (à configurer dans le portail Azure) :
```
APP_ENV=prod
APP_SECRET=<secret-fort-généré>
DB_HOST=yellow.o2switch.net
DB_PORT=3306
DB_NAME=iwob6566_outils-qualite
DB_USER=iwob6566_adminOQ
DB_PASSWORD=<password>
DB_SERVER_VERSION=10.6
SCM_DO_BUILD_DURING_DEPLOYMENT=true
```

2. **Autoriser l'accès MySQL distant** :
   - Ajouter l'IP publique de l'App Service dans cPanel o2switch → MySQL distant → Remote MySQL

#### Déploiement via GitHub Actions

Le projet utilise GitHub Actions pour le déploiement automatique :

- **Branch `main`** → Déploiement automatique en production
- **Branch `staging`** → Déploiement automatique en staging

Workflows disponibles :
- `.github/workflows/deploy-symfony-production.yml` : Déploiement production
- `.github/workflows/deploy-symfony-staging.yml` : Déploiement staging

#### Étapes de déploiement manuel

1. **Créer le profil de publication Azure**
```bash
az webapp deployment list-publishing-profiles --name <app-name> --resource-group <resource-group> --xml
```

2. **Configurer le secret GitHub `AZURE_WEBAPP_PUBLISH_PROFILE`**
   - Aller dans Settings → Secrets and variables → Actions
   - Ajouter le secret `AZURE_WEBAPP_PUBLISH_PROFILE` avec le contenu XML du profil

3. **Push sur la branch `main`**
```bash
git push origin main
```

4. **Exécuter les migrations en production**
```bash
php bin/console doctrine:migrations:migrate --no-interaction --env=prod
```

## 🗄️ Base de données

### Structure

- **User** : Utilisateurs de l'application
- **Record** : Analyses sauvegardées (Ishikawa, 5 Pourquoi)
- **BlogPost** : Articles de blog
- **Category** : Catégories de blog
- **Tag** : Tags pour articles
- **ContactMessage** : Messages de contact
- **NewsletterSubscriber** : Abonnés newsletter
- **PageView** : Tracking des visites
- **AdminLog** : Logs d'administration

### Migrations

Générer une migration :
```bash
php bin/console make:migration
```

Exécuter les migrations :
```bash
php bin/console doctrine:migrations:migrate
```

## 🧪 Tests

### Lancer les tests

```bash
# Tous les tests
php bin/phpunit

# Tests spécifiques
php bin/phpunit tests/Functional/HomeControllerTest.php
```

### Configuration des tests

- Base de données de test : SQLite en mémoire (configurée automatiquement)
- Fixtures : Doctrine Fixtures Bundle

## 🛠️ Commandes utiles

```bash
# Créer un utilisateur admin
php bin/console app:create-admin-user

# Importer les articles de blog
php bin/console app:import-blog-articles

# Vider le cache
php bin/console cache:clear

# Vérifier le schéma de base de données
php bin/console doctrine:schema:validate
```

## 📁 Structure du projet

```
├── config/              # Configuration Symfony
├── migrations/          # Migrations Doctrine
├── public/             # Point d'entrée web
├── src/
│   ├── Command/       # Commandes console
│   ├── Controller/    # Contrôleurs
│   ├── Entity/        # Entités Doctrine
│   ├── Form/          # Formulaires Symfony
│   ├── Repository/    # Repositories Doctrine
│   ├── Security/      # Configuration sécurité
│   └── Twig/          # Extensions Twig
├── templates/         # Templates Twig
├── tests/             # Tests PHPUnit
└── var/               # Cache, logs
```

## 🔒 Sécurité

- Authentification : Symfony Security Component
- Rôles : `ROLE_USER`, `ROLE_ADMIN`
- CSRF Protection : Activée sur tous les formulaires
- Validation : Symfony Validator Component

## 🌐 SEO

Le projet intègre un système SEO complet :

- Meta tags dynamiques
- Open Graph / Twitter Cards
- Schema.org JSON-LD
- Sitemap.xml généré automatiquement
- Robots.txt configuré

## 📝 Technologies

- **Framework** : Symfony 7.x
- **Base de données** : MySQL 10.6 (o2switch)
- **ORM** : Doctrine
- **Templates** : Twig
- **Assets** : AssetMapper, Stimulus, Turbo
- **Tests** : PHPUnit
- **CI/CD** : GitHub Actions
- **Hébergement** : Azure App Service

## 📄 Licence

Ce projet est propriétaire.

## 👤 Auteur

Neli Ville - neliddk@gmail.com

## 🔗 Liens

- Site web : [À définir]
- Documentation : [À définir]
