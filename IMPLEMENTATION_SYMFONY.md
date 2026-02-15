# IMPLEMENTATION_SYMFONY.md

> **Objectif** : Transformer le site statique (index.html à la racine) en application **Symfony + MySQL** tout en conservant l'hébergement **Azure App Service (PHP 8.2)** et le CI/CD GitHub.

> **Dernière mise à jour** : 2025-11-07  
> **Statut** : 🟢 En cours - Migration vers Symfony terminée, espace admin 100% fonctionnel, SEO complet, blog dynamique, tests 100% passent, page Ishikawa améliorée (UX/UI, accessibilité, responsive), **CMS légal éditable (pages Privacy / Terms / Mentions)**

---

## 0) Pré-requis

- PHP 8.2+, Composer, Git
- MySQL local (ou Docker) pour dev
- Accès au portail Azure (App Service + MySQL)
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
DATABASE_URL="mysql://root:root@127.0.0.1:3306/oq?serverVersion=8.0&charset=utf8mb4"
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

### Mot de passe oublié / Réinitialisation

1. Installer le bundle officiel :

```bash
composer require symfonycasts/reset-password-bundle
php bin/console make:reset-password --with-tests
```

2. Personnaliser les templates générés (`templates/reset_password/*.html.twig`) pour respecter la charte UI/UX.

3. Configurer l’expéditeur dans `ResetPasswordController` (ex. `support@outils-qualite.com`).

4. Ajouter le lien « Mot de passe oublié ? » dans la page de connexion (`templates/security/login.html.twig`).

5. Relancer la compilation des assets si des styles dédiés sont créés : en local `php bin/console sass:build` (ou `sass:build --watch`), en prod `php bin/console asset-map:compile`.

6. Exécuter le test fonctionnel généré/adapté (`php bin/phpunit tests/ResetPasswordControllerTest.php`).

### Pages d’erreur personnalisées

1. Créer les templates Twig dans `templates/bundles/TwigBundle/Exception/` (`error.html.twig`, `error404.html.twig`, `error403.html.twig`) pour harmoniser l’expérience.
2. Importer les routes d’aperçu en dev **et** en test (`config/routes/framework.yaml`) afin de pouvoir les prévisualiser et les tester (`/_error/{code}`).
3. Ajouter des tests fonctionnels ciblés (`php bin/phpunit tests/Functional/ErrorPageTest.php`).
4. Penser à nettoyer le cache test (`php bin/console cache:clear --env=test`) après modification des routes.

### Déploiement vers o2switch

1. Secrets GitHub nécessaires : `O2SWITCH_HOST`, `O2SWITCH_PORT`, `O2SWITCH_USER`, `O2SWITCH_SSH_KEY`, `O2SWITCH_DEPLOY_PATH`, `O2SWITCH_WEBROOT`.
2. Workflow actif : `.github/workflows/deploy-o2switch.yml` (composer install + asset-map:compile + tests + SCP/rsync).
3. Côté serveur : document root vers `public/`, `.env.prod.local`, accès SSH, base MySQL prête.
4. Lors du premier déploiement, lancer les migrations en SSH : `php bin/console doctrine:migrations:migrate --env=prod`.
5. Anciennes pipelines Azure conservées mais désactivées (`if: false`).

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
          extensions: mbstring, intl, pdo_mysql, opcache
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
  - `DATABASE_URL=mysql://<user>:<pass>@<host>:3306/<db>?serverVersion=8.0&charset=utf8mb4`
  - `SCM_DO_BUILD_DURING_DEPLOYMENT=true`

---

## 11) Base MySQL (prod)

```bash
php bin/console doctrine:migrations:migrate --no-interaction --env=prod
```

---

## 12) Checklist finale

- [x] Site accessible depuis `/` (templates Twig)
- [x] Auth fonctionnelle (formulaire de connexion)
- [x] Flux de réinitialisation de mot de passe opérationnel (email, pages dédiées, test automatisé)
- [x] CRUD `Record` OK (via API REST)
- [x] Espace d'administration complet (Dashboard, Blog, Contact, Newsletter, Analytics, Logs, Users, Catégories/Tags)
- [x] SEO complet (meta tags, Open Graph, Schema.org, Sitemap dynamique)
- [x] Blog dynamique (articles en base de données avec Markdown)
- [x] Pages d’erreur personnalisées (403/404/500) + tests fonctionnels
- [x] Page Ishikawa : UX/UI améliorée, accessibilité, responsive, CSS nettoyé
- [x] Tests unitaires : 42/42 passent (100%)
- [ ] CI/CD passe au vert
- [ ] Migrations exécutées en production
- [ ] Slot staging (si utilisé)

## 13) Améliorations récentes (2025-01-03)

### Page Ishikawa - Améliorations UX/UI et accessibilité

- ✅ **Visibilité des boutons** : Tous les boutons (édition, suppression, ajout) sont toujours visibles avec bon contraste
- ✅ **Masquage automatique** : Les boutons d'action des catégories sont masqués quand un modal est ouvert
- ✅ **Nettoyage CSS** : Suppression des doublons entre `custom.css` et `ishikawa.css`, utilisation exclusive des variables CSS de `custom.css`
- ✅ **Accessibilité** : Boutons avec taille minimale WCAG (44px), contraste vérifié, focus visible
- ✅ **Canvas responsive** : Redimensionnement automatique selon le conteneur avec adaptation mobile
- ✅ **Grille horizontale** : Catégories affichées en grille responsive au lieu de colonne verticale
- ✅ **Visibilité du texte** : Texte du problème toujours visible avec bon contraste

### Architecture actuelle

- ✅ **Templates Twig** : Toutes les pages converties en templates Twig
- ✅ **API REST** : Endpoints pour sauvegarder/charger les analyses (Ishikawa, 5 Pourquoi)
- ✅ **Espace admin** : 100% fonctionnel avec toutes les fonctionnalités
- ✅ **JavaScript** : Page Ishikawa utilise vanilla JS avec IIFE pour éviter les conflits Turbo
- ✅ **CSS** : Organisation propre avec `custom.css` (variables globales) et `ishikawa.css` (styles spécifiques)

### Mise à jour 2025-11-05 — Ishikawa Live Component

- ✅ Normalisation côté serveur des catégories (propriétés Canvas `spineX`, `angle`, `branchLength`) pour aligner la sauvegarde base/localStorage avec le rendu Canvas.
- ✅ Ajout des actions Live `reorderCategories` et extension de `updateCategoryPosition()` pour prendre en compte les coordonnées Canvas envoyées par Stimulus.
- ✅ Renforcement de `updatedCategories()` et `calculateNextCanvasPosition()` pour les nouvelles catégories créées côté client.
- 🔄 **À finaliser** : le formulaire d'édition de catégorie ne persiste toujours pas la mise à jour (l'ID d'édition n'est pas récupéré côté PHP). Le contrôleur Stimulus `bootstrap-modal` définit désormais `data-live-action-param-editing-category-id`, mais la LiveAction `updateCategory()` reçoit encore `null`. Diagnostic & correctif à prévoir.
- 🔄 **À vérifier** : synchronisation des nouvelles propriétés Canvas avec le front (drag & drop, export) et tests manuels connectés/déconnectés.
- 🔄 **À planifier** : tests fonctionnels automatisés (Live Components) ou au minimum un plan de tests manuel documenté couvrant : ajout/édition/suppression catégories et causes, rechargement localStorage vs utilisateur connecté, drag & drop, reorder.
