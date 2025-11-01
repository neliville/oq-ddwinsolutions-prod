# IMPLEMENTATION_SYMFONY.md

> **Objectif** : Transformer le site statique (index.html à la racine) en application **Symfony + PostgreSQL** tout en conservant l’hébergement **Azure App Service (PHP 8.2)** et le CI/CD GitHub.

---

## 0) Pré-requis

- PHP 8.2+, Composer, Git
- PostgreSQL local (ou Docker) pour dev
- Accès au portail Azure (App Service + Postgres)
- Secrets GitHub (publish profile)

---

## 1) Démarrer une branche de chantier

```bash
git checkout -b feat/symfony-app
```

---

## 2) Initialiser Symfony dans le dépôt

> On garde la **racine** du repo comme racine Symfony, et on déplace le site statique dans `public/`.

```bash
composer create-project symfony/skeleton tmp-sf
rsync -a tmp-sf/ .
rm -rf tmp-sf
composer require symfony/runtime symfony/twig-bundle symfony/asset symfony/console
composer require symfony/security-bundle symfony/uid
composer require symfony/orm-pack doctrine/doctrine-bundle
composer require symfony/mailer
composer require --dev symfony/maker-bundle
```

---

## 3) Déplacer le site statique dans `public/`

```bash
mkdir -p public
git mv index.html public/index.html 2>/dev/null || mv index.html public/index.html
# Adapte si tu as d'autres dossiers :
# git mv css public/css ; git mv js public/js ; git mv assets public/assets ; etc.
```

> ⚠️ Si tes chemins relatifs cassent, vérifie les `<link src>`/`<script src>` (préfixe `/` ou chemins relatifs corrects).

---

## 4) Config locale & structure minimale

1) Créer `.env.local` (non commité) :
```dotenv
APP_ENV=dev
APP_SECRET=dev-secret-change-me
DATABASE_URL="postgresql://postgres:postgres@localhost:5432/oq?serverVersion=16&charset=utf8"
```

2) Vérifier que `public/` est servi par Symfony (déjà par défaut).

3) Lancer un serveur local :
```bash
php -S 127.0.0.1:8000 -t public
# ou
symfony serve
```

---

## 5) Modèle de données & migrations (User, Record)

```bash
php bin/console make:user
# Class: User ; email:string unique ; password:string ; createdAt:datetime_immutable

php bin/console make:entity Record
# title:string ; content:text (nullable) ; createdAt:datetime_immutable ; user: relation many-to-one -> User

php bin/console doctrine:database:create
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

> Conseil: ajouter `createdAt` par défaut dans les entités via le constructeur.

---

## 6) Authentification (choix rapide)

### Option A — Login par formulaire (Twig + session)  
Simple si tu gardes des pages côté serveur.

```bash
php bin/console make:auth
# LoginFormAuthenticator + SecurityController
```

### Option B — API JWT (si front JS pur)

```bash
composer require lexik/jwt-authentication-bundle
php bin/console lexik:jwt:generate-keypair
```

---

## 7) Contrôleurs & routes (exemple API)

```bash
php bin/console make:controller Api/RecordController
```

`src/Controller/Api/RecordController.php` :
```php
#[Route('/api/records', name: 'api_records_', methods: ['GET', 'POST'])]
// GET  -> liste des records de l'utilisateur
// POST -> créer {title, content}
```

---

## 8) Intégrer le front actuel

- Garder **public/index.html** + assets existants.
- Ajouter du JS minimal pour consommer l’API (fetch).

---

## 9) CI/CD GitHub → Azure (PHP 8.2)

Créer `.github/workflows/deploy.yml` :

```yaml
name: Deploy Symfony to Azure Web App

on:
  push:
    branches: [ main, feat/symfony-app ]

jobs:
  build-and-deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: mbstring, intl, pdo_pgsql, opcache
          tools: composer

      - name: Install dependencies
        run: |
          composer install --no-dev --optimize-autoloader
          php bin/console cache:clear --env=prod

      - name: Zip artifact
        run: zip -r release.zip . -x ".git/*" ".github/*" "tests/*" ".env*"

      - name: Deploy to Azure WebApp
        uses: azure/webapps-deploy@v3
        with:
          app-name: outils-qualite-gratuit
          publish-profile: ${{ secrets.AZURE_WEBAPP_PUBLISH_PROFILE }}
          package: release.zip
```

---

## 10) Paramétrage Azure App Service

- **Pile** : PHP 8.2  
- **Chemin racine web** : `public/`
- **Variables d’application** :
  - `APP_ENV=prod`
  - `APP_SECRET=<secret>`
  - `DATABASE_URL=postgresql://<user>:<pass>@<host>:5432/<db>?sslmode=require`
  - `SCM_DO_BUILD_DURING_DEPLOYMENT=true`

---

## 11) Base PostgreSQL (prod)

```bash
php bin/console doctrine:migrations:migrate --no-interaction --env=prod
```

---

## 12) Checklist finale

- [ ] Site statique accessible depuis `/`
- [ ] Auth fonctionnelle
- [ ] CRUD `Record` OK
- [ ] CI/CD passe au vert
- [ ] Migrations exécutées
- [ ] Slot staging (si utilisé)
