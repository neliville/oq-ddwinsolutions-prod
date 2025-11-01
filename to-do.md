# TODO - Migration vers Symfony + PostgreSQL

> **Objectif** : Transformer le site statique en application Symfony + PostgreSQL tout en conservant l'hébergement Azure App Service (PHP 8.2) et le CI/CD GitHub.

---

## 📋 Récapitulatif du projet actuel

### Structure actuelle (site statique)
- **Site statique** hébergé sur Azure App Service (PHP 8.2)
- **Pages principales** :
  - `index.html` (page d'accueil)
  - `/ishikawa/` - Outil diagramme d'Ishikawa interactif
  - `/5pourquoi/` - Outil méthode des 5 Pourquoi
  - `/outils/` - Liste des outils disponibles
  - `/blog/` - Blog avec articles (markdown)
  - `/contact/` - Formulaire de contact
  - `/politique-de-confidentialite/` - Pages légales
  - `/mentions-legales/` - Pages légales

### Ressources statiques
- **CSS** : `/css/custom.css` (styles personnalisés)
- **JavaScript** :
  - `/js/main.js` - Script principal (initialisation, navbar, AOS)
  - `/js/ishikawa.js` - Logique de l'outil Ishikawa
  - `/js/fivewhy.js` - Logique de l'outil 5 Pourquoi
  - `/js/blog-markdown.js` - Affichage des articles blog
- **Images** : `/img/` - Logo, favicons, images
- **Librairies externes** : Bootstrap 5.3.2, Lucide Icons, AOS, Font Awesome

### Fonctionnalités actuelles
1. **Outils interactifs** (client-side uniquement)
   - Diagramme Ishikawa avec export PDF/JPEG/JSON
   - Méthode 5 Pourquoi avec export PDF
   - Stockage local (localStorage) uniquement
   
2. **Blog** : Articles en markdown statiques
3. **Newsletter** : Formulaire (probablement non fonctionnel ou externe)
4. **Tracking** : Google Tag Manager, Application Insights
5. **Logging** : Azure Logic App endpoint pour les exports

### Dépendances externes identifiées
- Azure Logic App pour logging des exports (`LOG_ENDPOINT` dans `ishikawa.js`)
- Google Tag Manager
- Google Fonts
- CDN pour Bootstrap, AOS, Lucide Icons, Font Awesome, Toastify

---

## 🎯 Plan de migration - Étapes détaillées

### Phase 0 : Préparation

#### ✅ Étape 0.1 : Vérifier les prérequis
- [ ] PHP 8.2+ installé localement
- [ ] Composer installé
- [ ] PostgreSQL local (ou Docker) pour développement
- [ ] Accès au portail Azure (App Service + Postgres)
- [ ] Secrets GitHub configurés (publish profile `AZURE_WEBAPP_PUBLISH_PROFILE`)

#### ✅ Étape 0.2 : Créer la branche de travail
```bash
git checkout -b feat/symfony-app
```

---

### Phase 1 : Initialisation Symfony

#### ✅ Étape 1.1 : Initialiser Symfony dans le dépôt
> **Important** : On garde la racine du repo comme racine Symfony, et on déplace le site statique dans `public/`.

```bash
composer create-project symfony/skeleton tmp-sf
rsync -a tmp-sf/ .
rm -rf tmp-sf
```

#### ✅ Étape 1.2 : Installer les dépendances Symfony de base
```bash
composer require symfony/runtime symfony/twig-bundle symfony/asset symfony/console
composer require symfony/security-bundle symfony/uid
composer require symfony/orm-pack doctrine/doctrine-bundle
composer require symfony/mailer
composer require symfony/asset-mapper symfony/ux-turbo symfony/ux-turbo-mercure
composer require knplabs/knp-menu-bundle knplabs/knp-menu
composer require --dev symfony/maker-bundle
```

#### ✅ Étape 1.3 : Configurer `.gitignore`
- [ ] Vérifier que `.gitignore` ignore `.env.local`, `var/`, `vendor/`
- [ ] Ajouter les patterns spécifiques si nécessaire

---

### Phase 2 : Migration des assets statiques (CSS, JS, images)

> **Important** : Seuls les assets (CSS, JS, images) sont déplacés dans `public/`. Les pages HTML seront converties en templates Twig.

#### ✅ Étape 2.1 : Créer la structure `public/`
```bash
mkdir -p public
```

#### ✅ Étape 2.2 : Déplacer les assets dans `public/`
```bash
# Assets uniquement (CSS, JS, images)
git mv css public/css 2>/dev/null || mv css public/css
git mv js public/js 2>/dev/null || mv js public/js
git mv img public/img 2>/dev/null || mv img public/img

# Dossiers d'assets pour outils et bannières
git mv cookie-banner public/cookie-banner 2>/dev/null || mv cookie-banner public/cookie-banner
git mv tarteaucitron public/tarteaucitron 2>/dev/null || mv tarteaucitron public/tarteaucitron

# Fichiers racine (robots.txt, sitemap.xml peuvent rester ou être générés)
git mv robots.txt public/robots.txt 2>/dev/null || mv robots.txt public/robots.txt
git mv sitemap.xml public/sitemap.xml 2>/dev/null || mv sitemap.xml public/sitemap.xml
```

#### ✅ Étape 2.3 : Conserver les fichiers HTML source (temporairement)
> Les fichiers HTML seront utilisés comme référence pour créer les templates Twig, puis supprimés.
- [ ] Conserver `index.html` à la racine (référence pour le template)
- [ ] Conserver `ishikawa/index.html` (référence pour le template)
- [ ] Conserver `5pourquoi/index.html` (référence pour le template)
- [ ] Conserver `blog/index.html` (référence pour le template)
- [ ] Conserver `contact/index.html` (référence pour le template)
- [ ] Conserver les autres pages HTML nécessaires

#### ✅ Étape 2.4 : Vérifier les chemins des assets
- [ ] Vérifier que les chemins dans les fichiers HTML source utilisent `/css/`, `/js/`, `/img/` (absolus)
- [ ] S'assurer que ces chemins fonctionneront depuis Symfony

---

### Phase 3 : Configuration locale

#### ✅ Étape 3.1 : Créer `.env.local` (non commité)
```dotenv
APP_ENV=dev
APP_SECRET=dev-secret-change-me
DATABASE_URL="postgresql://postgres:postgres@localhost:5432/oq?serverVersion=16&charset=utf8"
```

#### ✅ Étape 3.2 : Configurer `config/packages/framework.yaml`
- [ ] Vérifier que la configuration par défaut est correcte
- [ ] Ajuster si nécessaire pour les sessions, validation, etc.

#### ✅ Étape 3.3 : Vérifier la structure Symfony
- [ ] Vérifier que la structure de base Symfony est correcte
- [ ] Vérifier que les dossiers `templates/`, `src/Controller/` existent

---

### Phase 4 : Modèle de données

#### ✅ Étape 4.1 : Créer l'entité User
```bash
php bin/console make:user
# Class: User
# email:string unique
# password:string
# createdAt:datetime_immutable
```

- [ ] Vérifier que l'entité User est créée correctement
- [ ] Ajouter `createdAt` dans le constructeur si nécessaire

#### ✅ Étape 4.2 : Créer l'entité Record (pour sauvegarder les analyses)
```bash
php bin/console make:entity Record
# title:string
# type:string (nullable) - pour distinguer 'ishikawa', 'fivewhy', etc.
# content:text (nullable) - pour stocker le JSON des analyses
# createdAt:datetime_immutable
# user: relation many-to-one -> User
```

**Note** : Le champ `type` permet de distinguer les différents types d'analyses (ishikawa, fivewhy, etc.) pour faciliter les requêtes et l'affichage.

#### ✅ Étape 4.3 : Créer la base de données locale
```bash
php bin/console doctrine:database:create
```

#### ✅ Étape 4.4 : Générer et exécuter les migrations
```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

#### ✅ Étape 4.5 : Créer les autres entités nécessaires
- [x] Entité `BlogPost` - Articles de blog (title, slug, excerpt, content, publishedAt, readTime, featured, views, category, tags)
- [x] Entité `Category` - Catégories de blog (slug, name, description, color, icon, order)
- [x] Entité `Tag` - Tags pour les articles de blog (name, slug)
- [x] Entité `ContactMessage` - Messages de contact (name, email, subject, message, read, replied, user)
- [x] Entité `NewsletterSubscriber` - Abonnés newsletter (email, subscribedAt, unsubscribedAt, unsubscribeToken, active, source)
- [x] Entité `PageView` - Tracking des visites (url, ipAddress, userAgent, referer, visitedAt, method, user, sessionId, country, city, device)
- [x] Entité `AdminLog` - Logs d'administration (user, action, entityType, entityId, description, changes, ipAddress, createdAt)

**Relations** :
- `BlogPost` → ManyToOne `Category`
- `BlogPost` → ManyToMany `Tag`
- `ContactMessage` → ManyToOne `User` (nullable)
- `PageView` → ManyToOne `User` (nullable)
- `AdminLog` → ManyToOne `User`

**Index** :
- `PageView` : indexes sur `visited_at` et `url` pour les performances
- `AdminLog` : indexes sur `action` et `created_at` pour les requêtes

---

### Phase 5 : Authentification

> **Choix retenu** : Option A - Login par formulaire (Twig + session)

#### ✅ Étape 5.1 : Implémenter l'authentification
```bash
php bin/console make:security:form-login
# SecurityController + login.html.twig
```

- [x] Créer le formulaire de connexion
- [x] Créer le contrôleur de sécurité (`SecurityController`)
- [x] Créer le template de login (`templates/security/login.html.twig`)

#### ✅ Étape 5.2 : Configurer `security.yaml`
- [x] Configurer les firewalls :
  - Firewall principal pour les pages publiques
  - Firewall pour l'administration (`/admin`)
- [x] Configurer les providers (entity provider pour User)
- [x] Configurer les access controls :
  - Routes publiques accessibles à tous
  - Routes `/admin` nécessitent `ROLE_ADMIN`
  - Routes `/api` nécessitent authentification

#### ✅ Étape 5.3 : Créer les templates d'authentification
- [x] Créer `templates/security/login.html.twig`
- [ ] Créer `templates/security/logout.html.twig` (si nécessaire)
- [x] Intégrer les templates dans le layout de base

#### ✅ Étape 5.4 : Tester l'authentification
- [ ] Tester la connexion avec un utilisateur
- [ ] Tester la déconnexion
- [ ] Vérifier la redirection après connexion
- [ ] Vérifier la protection des routes admin

---

### Phase 5.5 : Configuration de KnpMenuBundle

> **Objectif** : Configurer KnpMenuBundle pour la gestion dynamique des menus.

#### ✅ Étape 5.5.1 : Configurer KnpMenuBundle
- [x] Vérifier que le bundle est bien installé
- [x] Créer le fichier de configuration `config/packages/knp_menu.yaml`

#### ✅ Étape 5.5.2 : Créer les services de menu
- [x] Créer `src/Menu/Builder/MainMenuBuilder.php` pour le menu principal
  - Lien Accueil
  - Lien Analyse des causes (Ishikawa)
  - Lien Méthode 5 Pourquoi
  - Lien Outils
  - Lien Blog
  - Lien Contact
  - Lien Login/Logout selon l'état de connexion
  - Lien Administration (si admin)
- [x] Créer `src/Menu/Builder/AdminMenuBuilder.php` pour le menu admin
  - Lien Dashboard
  - Lien Blog (CRUD articles)
  - Lien Catégories
  - Lien Tags
  - Lien Messages de contact
  - Lien Newsletter
  - Lien Analytics
  - Lien Logs
  - Lien Utilisateurs
  - Lien Déconnexion

#### ✅ Étape 5.5.3 : Créer les templates Twig pour les menus
- [x] Créer `templates/menu/main_menu.html.twig`
- [ ] Créer `templates/menu/admin_menu.html.twig` (à faire plus tard pour l'admin)
- [x] Utiliser `knp_menu_render()` dans les templates de base

---

### Phase 6 : Configuration AssetMapper, Stimulus et Turbo

> **Objectif** : Configurer AssetMapper avec Stimulus et Turbo pour la gestion moderne des assets JavaScript.

#### ✅ Étape 6.1 : Configurer AssetMapper
- [x] Vérifier que `symfony/asset-mapper` est installé
- [x] Configurer `config/packages/asset_mapper.yaml` (créé automatiquement par le recipe)
- [x] Créer le dossier `assets/` à la racine (créé automatiquement)

#### ✅ Étape 6.2 : Installer Stimulus et Turbo
- [x] Vérifier que `symfony/ux-turbo` est installé
- [x] Stimulus installé automatiquement via `symfony/stimulus-bundle`
- [x] Turbo installé via `symfony/ux-turbo`

#### ✅ Étape 6.3 : Configurer les fichiers JavaScript principaux
- [x] Créer `assets/app.js` (point d'entrée principal)
- [x] Créer `assets/bootstrap.js` (initialisation Stimulus)
- [x] Créer `assets/controllers.json` (définition des contrôleurs Stimulus)
- [x] Créer `assets/controllers/` pour les contrôleurs Stimulus personnalisés

#### ✅ Étape 6.4 : Adapter les scripts JavaScript existants
- [ ] Convertir `public/js/main.js` en contrôleur Stimulus si possible
- [ ] Adapter `public/js/ishikawa.js` pour utiliser Stimulus/Turbo
- [ ] Adapter `public/js/fivewhy.js` pour utiliser Stimulus/Turbo
- [ ] Adapter `public/js/blog-markdown.js` pour utiliser Stimulus/Turbo
- [ ] Conserver les scripts CDN (Bootstrap, Lucide Icons, AOS) ou les intégrer via AssetMapper si nécessaire

#### ✅ Étape 6.5 : Intégrer dans les templates Twig
- [ ] Utiliser `{{ asset('app.js', 'asset_mapper') }}` dans `base.html.twig`
- [ ] Utiliser `{{ asset('app.css', 'asset_mapper') }}` pour les styles
- [ ] Intégrer Turbo pour les navigations rapides
- [ ] Utiliser les attributs Stimulus `data-controller` dans les templates

#### ✅ Étape 6.6 : Tester AssetMapper
- [ ] Vérifier que les assets se chargent correctement
- [ ] Tester Stimulus avec un contrôleur simple
- [ ] Tester Turbo pour les navigations sans rechargement de page

---

### Phase 7 : Structure Twig et conversion des templates

> **Objectif** : Créer la structure Twig et convertir les pages HTML statiques en templates Twig réutilisables.

> **Important** : Gestion de la sidebar selon l'état de connexion :
> - **Utilisateurs connectés** : Afficher une sidebar pour accéder aux différents outils et menus. Les utilisateurs connectés peuvent sauvegarder leurs créations dans leur espace personnel.
> - **Utilisateurs non connectés** : Pas de sidebar, accès aux outils en lecture seule. Les utilisateurs non connectés ne peuvent pas sauvegarder leurs créations (affichage d'un message les invitant à se connecter).

#### ✅ Étape 7.1 : Créer le layout de base (base.html.twig)
- [x] Créer `templates/base.html.twig` (layout principal)
- [x] Extraire la structure HTML de `index.html` :
  - `<head>` avec tous les meta tags, liens CSS, scripts (GTM, Analytics)
  - Structure `<body>` avec navbar utilisant KnpMenu
  - Footer (extrait en composant)
  - Scripts JavaScript à la fin
- [x] Utiliser les blocks Twig :
  - `{% block title %}` - Titre de la page
  - `{% block meta_description %}` - Description meta
  - `{% block stylesheets %}` - Styles additionnels
  - `{% block body %}` - Contenu principal
  - `{% block javascripts %}` - Scripts additionnels
- [x] Intégrer KnpMenu pour le menu principal :
  - `{{ knp_menu_render('main') }}` dans la navbar
- [x] Utiliser AssetMapper pour les assets :
  - `{{ importmap('app') }}` pour charger app.js via AssetMapper
  - Les styles CSS sont chargés via `import './styles/app.css'` dans app.js
- [x] Intégrer Turbo pour les navigations :
  - Turbo importé automatiquement via `@hotwired/turbo` dans app.js
- [x] Utiliser `asset()` pour les assets statiques (images, etc.)

#### ✅ Étape 7.1.1 : Créer le layout avec sidebar pour utilisateurs connectés
- [x] Créer `templates/base_with_sidebar.html.twig` qui étend `base.html.twig`
  - Afficher une sidebar avec les outils disponibles :
    - Accueil
    - Analyse des causes (Ishikawa)
    - Méthode 5 Pourquoi
    - Mes créations (liste des analyses sauvegardées)
    - Paramètres du compte
    - Déconnexion
  - La sidebar est visible uniquement si l'utilisateur est connecté (`{% if app.user %}`)
  - Utiliser un composant Twig `components/sidebar.html.twig` pour la sidebar
- [x] Créer `templates/components/sidebar.html.twig`
  - Menu de navigation latéral avec les outils
  - Afficher l'avatar/email de l'utilisateur connecté
  - Liens vers les outils et l'espace personnel
  - Style responsive (sidebar rétractable sur mobile)

#### ✅ Étape 7.1.2 : Adapter les templates pour gérer la sidebar conditionnellement
- [x] Modifier les templates des outils (Ishikawa, 5 Pourquoi) :
  - Utiliser `base_with_sidebar.html.twig` si l'utilisateur est connecté
  - Utiliser `base.html.twig` si l'utilisateur n'est pas connecté
  - Afficher un message d'invitation à se connecter pour sauvegarder (si non connecté)
  - Bouton "Sauvegarder" visible uniquement si connecté

#### ✅ Étape 7.2 : Convertir index.html en template Twig
- [x] Créer `templates/home/index.html.twig`
- [x] Étendre `base.html.twig`
- [x] Extraire le contenu de `index.html` :
  - Hero Section
  - Avantages Section
  - Outils Section
  - Expertise Section
  - Newsletter Section
  - Section DDWin Solutions
  - Footer (extrait en composant `components/footer.html.twig`)
- [x] Remplacer les chemins statiques par `asset()` Symfony
- [x] Adapter les structures pour utiliser les variables Twig si nécessaire
- [x] Créer `templates/components/navbar.html.twig` avec KnpMenu
- [x] Créer `templates/components/footer.html.twig`

#### ✅ Étape 7.3 : Convertir les outils en templates Twig
- [x] Créer `templates/ishikawa/index.html.twig`
  - Étendre `base_with_sidebar.html.twig` si connecté, sinon `base.html.twig`
  - Convertir `ishikawa/index.html` en template
  - Utiliser un contrôleur Stimulus : `data-controller="ishikawa"` (à faire)
  - Intégrer le script via AssetMapper ou Stimulus controller
  - Bouton "Sauvegarder" visible uniquement si connecté (`{% if app.user %}`)
  - Message d'invitation à se connecter si non connecté
- [x] Créer `templates/five_why/index.html.twig`
  - Étendre `base_with_sidebar.html.twig` si connecté, sinon `base.html.twig`
  - Convertir `5pourquoi/index.html` en template
  - Utiliser un contrôleur Stimulus : `data-controller="fivewhy"` (à faire)
  - Intégrer le script via AssetMapper ou Stimulus controller
  - Bouton "Sauvegarder" visible uniquement si connecté
  - Message d'invitation à se connecter si non connecté
- [x] Créer `templates/outils/index.html.twig`
  - Étendre `base_with_sidebar.html.twig` si connecté, sinon `base.html.twig`
  - Convertir `outils/index.html` en template

#### ✅ Étape 7.4 : Convertir les autres pages en templates Twig
- [x] Créer `templates/blog/index.html.twig`
  - Étendre `base.html.twig`
  - Convertir `blog/index.html` en template
  - Prévoir la liste des articles (sera dynamique plus tard via base de données)
- [x] Créer `templates/contact/index.html.twig`
  - Étendre `base.html.twig`
  - Convertir `contact/index.html` en template
  - Prévoir le formulaire Symfony (FormulaireType à créer plus tard)
- [ ] Créer `templates/blog/article.html.twig`
  - Étendre `base.html.twig`
  - Convertir `article-template.html` en template
  - Prévoir les paramètres de catégorie et ID (à faire plus tard avec la base de données)
- [x] Créer `templates/legal/politique-confidentialite.html.twig`
  - Étendre `base.html.twig`
  - Convertir `politique-de-confidentialite/index.html`
- [x] Créer `templates/legal/mentions-legales.html.twig`
  - Étendre `base.html.twig`
  - Convertir `mentions-legales/index.html`

#### ✅ Étape 7.5 : Créer des composants Twig réutilisables
- [x] Créer `templates/components/navbar.html.twig`
  - Utiliser KnpMenu pour générer dynamiquement la navbar
  - Afficher Login/Logout selon l'état de connexion
  - Intégrer Turbo pour les navigations rapides
- [x] Créer `templates/components/footer.html.twig`
  - Extraire le footer du base pour la rendre réutilisable
- [x] Créer `templates/components/sidebar.html.twig`
  - Menu de navigation latéral pour utilisateurs connectés
- [x] Créer `templates/components/newsletter-form.html.twig`
  - Extraire le formulaire newsletter pour la rendre réutilisable
  - Intégration avec API REST pour l'inscription
  - Gestion des messages de succès/erreur avec JavaScript

#### ✅ Étape 7.6 : Vérifier et tester les templates
- [ ] Vérifier que tous les chemins d'assets utilisent `asset()` ou AssetMapper
- [ ] Vérifier que les chemins relatifs sont corrects
- [ ] Tester que les templates se compilent sans erreur
- [ ] Vérifier que tous les scripts JavaScript sont chargés correctement via AssetMapper
- [ ] Tester que Stimulus fonctionne avec les contrôleurs
- [ ] Tester que Turbo fonctionne pour les navigations
- [ ] Vérifier que les menus KnpMenu s'affichent correctement

#### ✅ Étape 7.7 : Nettoyer les fichiers HTML source (après conversion)
> **Important** : Ne supprimer les fichiers HTML qu'après avoir vérifié que tout fonctionne avec Twig.

- [ ] Supprimer `index.html` (remplacé par `templates/home/index.html.twig`)
- [ ] Supprimer `ishikawa/index.html` (remplacé par `templates/ishikawa/index.html.twig`)
- [ ] Supprimer `5pourquoi/index.html` (remplacé par `templates/fivewhy/index.html.twig`)
- [ ] Supprimer `outils/index.html` (remplacé par `templates/outils/index.html.twig`)
- [ ] Supprimer `blog/index.html` (remplacé par `templates/blog/index.html.twig`)
- [ ] Supprimer `contact/index.html` (remplacé par `templates/contact/index.html.twig`)
- [ ] Supprimer `article-template.html` (remplacé par `templates/blog/article.html.twig`)
- [ ] Supprimer les autres pages HTML converties

---

### Phase 8 : Contrôleurs Symfony et routes

> **Objectif** : Créer les contrôleurs Symfony qui rendent les templates Twig et gèrent les routes.

#### ✅ Étape 8.1 : Créer les contrôleurs pour les pages Twig
```bash
php bin/console make:controller HomeController
php bin/console make:controller IshikawaController
php bin/console make:controller FiveWhyController
php bin/console make:controller OutilsController
php bin/console make:controller BlogController
php bin/console make:controller ContactController
php bin/console make:controller LegalController
```

#### ✅ Étape 8.2 : Implémenter HomeController (route `/`)
- [x] Route `GET /` dans `HomeController` (nommée `app_home_index`)
- [x] Rendre le template `templates/home/index.html.twig`
- [x] Transmettre les variables nécessaires au template
- [ ] Tester que la page d'accueil s'affiche correctement

#### ✅ Étape 8.3 : Implémenter IshikawaController
- [x] Route `GET /ishikawa/` dans `IshikawaController` (nommée `app_ishikawa_index`)
- [x] Rendre le template `templates/ishikawa/index.html.twig`
- [ ] Transmettre les données nécessaires (catégories par défaut, etc.) (à faire plus tard)
- [ ] Tester que la page Ishikawa s'affiche correctement

#### ✅ Étape 8.4 : Implémenter FiveWhyController
- [x] Route `GET /5pourquoi/` dans `FiveWhyController` (nommée `app_fivewhy_index`)
- [x] Rendre le template `templates/five_why/index.html.twig`
- [ ] Transmettre les données nécessaires (à faire plus tard)
- [ ] Tester que la page 5 Pourquoi s'affiche correctement

#### ✅ Étape 8.5 : Implémenter OutilsController
- [x] Route `GET /outils/` dans `OutilsController` (nommée `app_outils_index`)
- [x] Rendre le template `templates/outils/index.html.twig`
- [ ] Tester que la page outils s'affiche correctement

#### ✅ Étape 8.6 : Implémenter BlogController
- [x] Route `GET /blog` dans `BlogController` (nommée `app_blog_index`) - Liste des articles
- [x] Rendre le template `templates/blog/index.html.twig`
- [ ] Route `GET /blog/{category}/{id}` dans `BlogController` - Article individuel (à faire plus tard)
- [ ] Créer le template `templates/blog/article.html.twig` (à faire plus tard)
- [ ] Transmettre les données des articles (base de données) (à faire plus tard)
- [ ] Tester que les pages blog s'affichent correctement

#### ✅ Étape 8.7 : Implémenter ContactController
- [x] Route `GET /contact/` dans `ContactController` (nommée `app_contact_index`) - Afficher le formulaire
- [x] Route `POST /contact/` dans `ContactController` - Traiter le formulaire
- [x] Créer un `ContactFormType` avec Symfony Forms
- [x] Gérer la soumission du formulaire (sauvegarde en base, messages flash)
- [x] Pré-remplir l'email si utilisateur connecté
- [x] Rendre le template `templates/contact/index.html.twig` avec le formulaire Symfony
- [ ] Tester que le formulaire fonctionne

#### ✅ Étape 8.8 : Implémenter LegalController
- [x] Route `GET /politique-de-confidentialite/` dans `LegalController` (nommée `app_legal_politique_confidentialite`)
- [x] Route `GET /mentions-legales/` dans `LegalController` (nommée `app_legal_mentions_legales`)
- [x] Rendre les templates correspondants
- [ ] Tester que les pages légales s'affichent correctement

#### ✅ Étape 8.9 : Vérifier toutes les routes
- [ ] Tester toutes les routes avec `php bin/console debug:router`
- [ ] Vérifier que toutes les routes sont accessibles
- [ ] Vérifier que les templates sont bien rendus
- [ ] Vérifier que les assets sont chargés correctement

---

### Phase 9 : Formulaires Symfony

> **Objectif** : Créer les formulaires Symfony pour les fonctionnalités interactives.

#### ✅ Étape 9.1 : Créer ContactFormType
- [x] Installer `symfony/form` et `symfony/validator`
- [x] Créer `src/Form/ContactFormType.php`
- [x] Ajouter les champs : `name`, `email`, `subject` (ChoiceType), `message`
- [x] Ajouter les validations : NotBlank, Email
- [x] Configurer les labels et attributs HTML

#### ✅ Étape 9.2 : Créer NewsletterFormType
- [x] Créer `src/Form/NewsletterFormType.php`
- [x] Ajouter le champ `email` avec validations
- [x] Configurer les labels et attributs HTML

#### ✅ Étape 9.3 : Mettre à jour ContactController
- [x] Intégrer le formulaire dans `ContactController`
- [x] Gérer la soumission du formulaire (GET et POST)
- [x] Sauvegarder en base de données (`ContactMessage`)
- [x] Ajouter les messages flash pour la confirmation
- [x] Pré-remplir l'email si utilisateur connecté

#### ✅ Étape 9.4 : Créer NewsletterController
- [x] Créer `src/Controller/NewsletterController.php`
- [x] Route `POST /api/newsletter/subscribe` - API REST pour l'inscription
- [x] Route `GET /newsletter/unsubscribe/{token}` - Désabonnement
- [x] Gérer les erreurs (email déjà existant, validation)
- [x] Réactiver les abonnements si utilisateur déjà désabonné

#### ✅ Étape 9.5 : Mettre à jour les templates
- [x] Mettre à jour `templates/contact/index.html.twig` pour utiliser le formulaire Symfony
- [x] Afficher les messages flash de succès/erreur
- [x] Créer `templates/components/newsletter-form.html.twig`
- [x] Intégrer le composant newsletter dans `templates/blog/index.html.twig`
- [x] Ajouter le JavaScript pour gérer l'inscription via API REST

#### ✅ Étape 9.6 : Tester les formulaires
- [ ] Tester le formulaire de contact (validation, soumission, messages)
- [ ] Tester l'inscription à la newsletter (API REST, gestion des erreurs)
- [ ] Tester le désabonnement avec token
- [ ] Vérifier la sauvegarde en base de données

---

### Phase 10 : Contrôleurs API pour les fonctionnalités dynamiques

#### ✅ Étape 10.1 : Créer les contrôleurs pour les API
```bash
php bin/console make:controller Api/RecordController
php bin/console make:controller Api/IshikawaController
php bin/console make:controller Api/FiveWhyController
```

#### ✅ Étape 10.2 : Implémenter les routes API pour les Records
- [x] `GET /api/records` - Liste des records de l'utilisateur connecté
- [x] `POST /api/records` - Créer un record `{title, type, content}`
- [x] `GET /api/records/{id}` - Récupérer un record
- [x] `PUT /api/records/{id}` - Modifier un record
- [x] `DELETE /api/records/{id}` - Supprimer un record
- [x] Vérifier l'authentification sur toutes les routes API (ROLE_USER requis)

#### ✅ Étape 10.3 : Implémenter les routes API pour Ishikawa
- [x] `POST /api/ishikawa/save` - Sauvegarder un diagramme Ishikawa
  - Recevoir le JSON du diagramme
  - Créer un `Record` avec `type='ishikawa'` et le JSON en `content`
  - Associer à l'utilisateur connecté
- [x] `GET /api/ishikawa/{id}` - Récupérer un diagramme Ishikawa
  - Vérifier que le record appartient à l'utilisateur
  - Retourner le JSON du diagramme
- [x] `GET /api/ishikawa/list` - Liste des diagrammes Ishikawa de l'utilisateur

#### ✅ Étape 10.4 : Implémenter les routes API pour 5 Pourquoi
- [x] `POST /api/fivewhy/save` - Sauvegarder une analyse 5 Pourquoi
  - Recevoir le JSON de l'analyse
  - Créer un `Record` avec `type='fivewhy'` et le JSON en `content`
  - Associer à l'utilisateur connecté
- [x] `GET /api/fivewhy/{id}` - Récupérer une analyse 5 Pourquoi
  - Vérifier que le record appartient à l'utilisateur
  - Retourner le JSON de l'analyse
- [x] `GET /api/fivewhy/list` - Liste des analyses 5 Pourquoi de l'utilisateur

#### ✅ Étape 10.5 : Tester les API
- [ ] Tester toutes les routes API avec Postman ou curl
- [ ] Vérifier l'authentification sur toutes les routes
- [ ] Vérifier que les données sont bien sauvegardées en base
- [ ] Vérifier que les données sont bien récupérées depuis la base

---

### Phase 11 : Intégration du front-end

#### ✅ Étape 11.1 : Adapter les scripts JavaScript existants avec Stimulus
- [ ] Créer un contrôleur Stimulus `ishikawa_controller.js` pour gérer l'outil Ishikawa
  - Utiliser l'API au lieu du localStorage
  - Intégrer Turbo pour les mises à jour
- [ ] Créer un contrôleur Stimulus `fivewhy_controller.js` pour gérer l'outil 5 Pourquoi
  - Utiliser l'API au lieu du localStorage
  - Intégrer Turbo pour les mises à jour
- [ ] Créer un contrôleur Stimulus `blog_controller.js` pour gérer le blog
  - Adapter `blog-markdown.js` en contrôleur Stimulus
- [ ] Créer un contrôleur Stimulus `newsletter_controller.js` pour le formulaire newsletter
- [ ] Créer un contrôleur Stimulus `contact_controller.js` pour le formulaire de contact
- [ ] Utiliser Turbo pour les soumissions de formulaires
- [ ] Ajouter des fonctions `fetch()` pour sauvegarder/charger depuis l'API

#### ✅ Étape 11.2 : Ajouter la gestion d'authentification côté client
- [ ] Formulaire de connexion
- [ ] Gestion du token/session
- [ ] Redirection après connexion

#### ✅ Étape 11.3 : Tester l'intégration complète
- [ ] Créer un diagramme Ishikawa et vérifier qu'il se sauvegarde en base
- [ ] Créer une analyse 5 Pourquoi et vérifier qu'elle se sauvegarde en base
- [ ] Vérifier que les listes de records fonctionnent

---

### Phase 12 : CI/CD GitHub Actions

#### ✅ Étape 12.1 : Créer `.github/workflows/deploy.yml`
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

#### ✅ Étape 12.2 : Vérifier que le secret GitHub est configuré
- [ ] `AZURE_WEBAPP_PUBLISH_PROFILE` est présent dans les secrets GitHub

---

### Phase 13 : Configuration Azure App Service

#### ✅ Étape 13.1 : Configurer la pile PHP
- [ ] Vérifier que la pile est **PHP 8.2**

#### ✅ Étape 13.2 : Configurer le chemin racine web
- [ ] Définir le **chemin racine web** : `public/`

#### ✅ Étape 13.3 : Configurer les variables d'application Azure
- [ ] `APP_ENV=prod`
- [ ] `APP_SECRET=<secret>` (générer un secret fort)
- [ ] `DATABASE_URL=postgresql://<user>:<pass>@<host>:5432/<db>?sslmode=require`
- [ ] `SCM_DO_BUILD_DURING_DEPLOYMENT=true`

#### ✅ Étape 13.4 : Vérifier la connexion PostgreSQL Azure
- [ ] Tester la connexion depuis Azure App Service vers PostgreSQL
- [ ] Vérifier que les règles de pare-feu permettent la connexion

---

### Phase 14 : Base de données PostgreSQL (production)

#### ✅ Étape 14.1 : Exécuter les migrations en production
```bash
php bin/console doctrine:migrations:migrate --no-interaction --env=prod
```

**Note** : Cette commande devra être exécutée soit :
- Via Azure Cloud Shell
- Via SSH depuis Azure App Service
- Via un script dans le workflow GitHub Actions (avec connexion SSH)

#### ✅ Étape 14.2 : Vérifier que les migrations sont appliquées
- [ ] Vérifier dans PostgreSQL Azure que les tables existent
- [ ] Vérifier la structure des tables

---

### Phase 15 : Tests et validation

#### ✅ Étape 15.1 : Tests fonctionnels
- [ ] Site accessible depuis `/` (template Twig `home/index.html.twig`)
- [ ] Toutes les routes Twig fonctionnent (`/ishikawa`, `/5pourquoi`, `/blog`, `/contact`, etc.)
- [ ] Authentification fonctionnelle (connexion/déconnexion)
- [ ] CRUD `Record` OK (créer, lire, modifier, supprimer)
- [ ] Sauvegarde des diagrammes Ishikawa en base via API
- [ ] Sauvegarde des analyses 5 Pourquoi en base via API
- [ ] Export PDF/JPEG fonctionne toujours (JavaScript côté client)
- [ ] Blog accessible et fonctionnel (templates Twig)
- [ ] Formulaire de contact fonctionne (Symfony Forms)
- [ ] Tous les assets (CSS, JS, images) se chargent correctement
- [ ] Les templates Twig s'affichent correctement avec le layout de base

#### ✅ Étape 15.2 : Tests de déploiement
- [ ] CI/CD passe au vert
- [ ] Déploiement sur Azure réussi
- [ ] Migrations exécutées en production
- [ ] Site accessible en production
- [ ] Base de données opérationnelle en production

#### ✅ Étape 15.3 : Tests de performance
- [ ] Temps de chargement acceptable
- [ ] Optimisations si nécessaire (cache, CDN)

---

### Phase 16 : Espace d'administration

> **Objectif** : Créer un espace d'administration pour gérer le contenu et suivre l'activité.

#### ✅ Étape 16.1 : Configuration de l'authentification admin
- [ ] Ajouter le rôle `ROLE_ADMIN` aux utilisateurs administrateurs
- [ ] Configurer les routes admin dans `security.yaml`
- [ ] Créer un firewall dédié pour `/admin`

#### ✅ Étape 16.2 : Créer les contrôleurs d'administration
```bash
php bin/console make:controller Admin/DashboardController
php bin/console make:controller Admin/BlogController
php bin/console make:controller Admin/ContactController
php bin/console make:controller Admin/NewsletterController
php bin/console make:controller Admin/AnalyticsController
php bin/console make:controller Admin/UserController
```

#### ✅ Étape 16.3 : Dashboard d'administration
- [ ] Route `GET /admin` - Tableau de bord
- [ ] Afficher les statistiques :
  - Nombre de messages de contact non lus
  - Nombre de nouveaux abonnés newsletter
  - Statistiques de visites (PageView)
  - Articles les plus vus
  - Activité récente (AdminLog)
- [ ] Rendre le template `templates/admin/dashboard.html.twig`

#### ✅ Étape 16.4 : Gestion des articles de blog (Admin)
- [ ] Route `GET /admin/blog` - Liste des articles
- [ ] Route `GET /admin/blog/new` - Créer un article
- [ ] Route `GET /admin/blog/{id}/edit` - Modifier un article
- [ ] Route `POST /admin/blog` - Sauvegarder un article
- [ ] Route `DELETE /admin/blog/{id}` - Supprimer un article
- [ ] Créer `BlogPostFormType` pour le formulaire
- [ ] Gérer les uploads d'images pour les articles
- [ ] Logger les actions dans `AdminLog`

#### ✅ Étape 16.5 : Gestion des catégories et tags
- [ ] Route `GET /admin/categories` - Liste des catégories
- [ ] Route `GET /admin/tags` - Liste des tags
- [ ] CRUD pour les catégories
- [ ] CRUD pour les tags
- [ ] Logger les actions dans `AdminLog`

#### ✅ Étape 16.6 : Gestion des messages de contact
- [ ] Route `GET /admin/contact` - Liste des messages
- [ ] Route `GET /admin/contact/{id}` - Voir un message
- [ ] Route `POST /admin/contact/{id}/mark-read` - Marquer comme lu
- [ ] Route `POST /admin/contact/{id}/reply` - Répondre à un message
- [ ] Filtres : non lus, lus, répondus
- [ ] Logger les actions dans `AdminLog`

#### ✅ Étape 16.7 : Gestion de la newsletter
- [ ] Route `GET /admin/newsletter` - Liste des abonnés
- [ ] Route `GET /admin/newsletter/export` - Exporter la liste
- [ ] Afficher les statistiques (actifs, désinscrits)
- [ ] Filtrer par statut (actif/inactif)

#### ✅ Étape 16.8 : Analytics et tracking
- [ ] Route `GET /admin/analytics` - Statistiques de visites
- [ ] Afficher les statistiques :
  - Pages les plus visitées
  - Référents les plus fréquents
  - Données géographiques (country, city)
  - Appareils et navigateurs
  - Utilisateurs connectés vs anonymes
- [ ] Graphiques de tendances (nombre de visites par jour/mois)
- [ ] Filtres par période

#### ✅ Étape 16.9 : Logs d'administration
- [ ] Route `GET /admin/logs` - Liste des logs
- [ ] Afficher les actions d'administration :
  - Utilisateur qui a effectué l'action
  - Type d'action (CREATE, UPDATE, DELETE)
  - Entité concernée
  - Changements effectués
  - Date et heure
  - Adresse IP
- [ ] Filtres : utilisateur, action, entité, période
- [ ] Export des logs

#### ✅ Étape 16.10 : Gestion des utilisateurs
- [ ] Route `GET /admin/users` - Liste des utilisateurs
- [ ] Route `GET /admin/users/{id}/edit` - Modifier un utilisateur
- [ ] Gérer les rôles (ROLE_USER, ROLE_ADMIN)
- [ ] Activer/désactiver des comptes
- [ ] Logger les actions dans `AdminLog`

#### ✅ Étape 16.11 : Créer les templates d'administration
- [ ] Créer `templates/admin/base.html.twig` - Layout admin
- [ ] Créer `templates/admin/dashboard.html.twig`
- [ ] Créer `templates/admin/blog/index.html.twig`
- [ ] Créer `templates/admin/blog/form.html.twig`
- [ ] Créer `templates/admin/contact/index.html.twig`
- [ ] Créer `templates/admin/contact/show.html.twig`
- [ ] Créer `templates/admin/newsletter/index.html.twig`
- [ ] Créer `templates/admin/analytics/index.html.twig`
- [ ] Créer `templates/admin/logs/index.html.twig`
- [ ] Créer `templates/admin/users/index.html.twig`

#### ✅ Étape 16.12 : Sécuriser l'espace admin
- [ ] Vérifier que seuls les utilisateurs avec `ROLE_ADMIN` peuvent accéder
- [ ] Vérifier les permissions sur toutes les routes admin
- [ ] Protéger contre les injections SQL et XSS
- [ ] Valider tous les formulaires

---

### Phase 17 : Améliorations optionnelles

#### ✅ Étape 17.1 : Slot staging (si utilisé)
- [ ] Configurer un slot de déploiement staging
- [ ] Tester sur staging avant production

#### ✅ Étape 17.2 : Newsletter fonctionnelle
- [ ] Intégrer avec un service d'email (Symfony Mailer)
- [ ] Envoyer des campagnes email
- [ ] Gérer les désinscriptions via token

#### ✅ Étape 17.3 : API REST pour l'administration (optionnel)
- [ ] Créer des endpoints API pour les opérations admin
- [ ] Authentification API pour les outils externes

---

## 📝 Notes importantes

### Points d'attention
1. **AssetMapper** : Utiliser AssetMapper pour tous les assets JavaScript modernes (Stimulus, Turbo)
2. **Stimulus** : Convertir les scripts JavaScript en contrôleurs Stimulus pour une meilleure organisation
3. **Turbo** : Utiliser Turbo pour les navigations rapides sans rechargement de page
4. **KnpMenu** : Utiliser KnpMenuBundle pour la gestion dynamique des menus (principal et admin)
5. **Conversion HTML → Twig** : Utiliser `asset()` pour les assets statiques (images) et AssetMapper pour les JS modernes
6. **Layout de base** : Créer un `base.html.twig` réutilisable avec tous les blocks nécessaires
7. **Chemins relatifs** : Vérifier que tous les chemins dans les templates Twig utilisent `asset()` ou AssetMapper
8. **Composants Twig** : Extraire la navbar et le footer dans des composants réutilisables
9. **Sessions** : Configurer les sessions Symfony pour Azure App Service
10. **Cache** : Configurer le cache Symfony pour la production (Redis ou fichiers)
11. **Secrets** : Ne jamais commiter `.env.local` ou les secrets
12. **Migrations** : Toujours tester les migrations en local avant production
13. **Type Record** : L'entité `Record` doit avoir un champ `type` pour distinguer les différents types d'analyses (ishikawa, fivewhy, etc.)

### Questions à clarifier
- [ ] Le service Azure Logic App pour le logging doit-il être conservé ?
- [ ] Faut-il conserver le tracking Google Tag Manager / Application Insights ?
- [ ] Les articles du blog doivent-ils être migrés en base de données ou rester en markdown statique ?
- [ ] Faut-il implémenter un système de sauvegarde automatique des analyses en cours (auto-save) ?
- [ ] Y a-t-il des utilisateurs existants à migrer ?

---

## 🚀 Checklist finale

### Front-end
- [ ] Site accessible depuis `/` (templates Twig)
- [ ] Toutes les routes Twig fonctionnent
- [ ] Tous les templates Twig s'affichent correctement
- [ ] Tous les assets (CSS, JS, images) se chargent correctement
- [ ] Blog fonctionne (affichage des articles)
- [ ] Formulaire de contact fonctionne
- [ ] Newsletter fonctionne

### Authentification et API
- [ ] Auth fonctionnelle (connexion/déconnexion)
- [ ] CRUD `Record` OK (créer, lire, modifier, supprimer)
- [ ] Sauvegarde/chargement des analyses Ishikawa via API
- [ ] Sauvegarde/chargement des analyses 5 Pourquoi via API
- [ ] Tous les exports (PDF/JPEG/JSON) fonctionnent

### Administration
- [ ] Espace admin accessible (`/admin`)
- [ ] Dashboard admin avec statistiques
- [ ] Gestion des articles de blog (CRUD)
- [ ] Gestion des messages de contact
- [ ] Gestion de la newsletter
- [ ] Analytics et tracking fonctionnels
- [ ] Logs d'administration consultables
- [ ] Gestion des utilisateurs et rôles

### Base de données
- [ ] Toutes les entités créées (User, Record, BlogPost, Category, Tag, ContactMessage, NewsletterSubscriber, PageView, AdminLog)
- [ ] Migrations générées et exécutées en local
- [ ] Migrations exécutées en production
- [ ] Base de données opérationnelle
- [ ] Index configurés pour les performances

### Déploiement
- [ ] CI/CD passe au vert
- [ ] Site accessible en production
- [ ] Tracking des visites fonctionnel
- [ ] Slot staging (si utilisé)
- [ ] Documentation mise à jour

---

**Dernière mise à jour** : 2024-12-20
**Statut global** : 🟡 En cours

### Progrès récent
- ✅ Phase 5 : Authentification complétée
- ✅ Phase 5.5 : KnpMenuBundle configuré et corrigé
- ✅ Phase 6 : AssetMapper, Stimulus et Turbo configurés
- ✅ Phase 7.1-7.2 : Layout de base et page d'accueil convertis en Twig
- ✅ Phase 7.1.1-7.1.2 : Layout avec sidebar et gestion conditionnelle créés
- ✅ Phase 7.3-7.4 : Toutes les pages principales converties en templates Twig :
  - ✅ Page Ishikawa (avec sidebar conditionnelle)
  - ✅ Page 5 Pourquoi (avec sidebar conditionnelle)
  - ✅ Page Outils (avec sidebar conditionnelle)
  - ✅ Page Blog
  - ✅ Page Contact
  - ✅ Pages légales (Politique de confidentialité, Mentions légales)
- ✅ Phase 7.5 : Composants Twig réutilisables créés (navbar, footer, sidebar, newsletter-form)
- ✅ Phase 8.1-8.8 : Tous les contrôleurs créés et routes configurées
- ✅ Phase 9 : Formulaires Symfony créés et intégrés :
  - ✅ ContactFormType avec validation
  - ✅ NewsletterFormType avec validation
  - ✅ ContactController mis à jour avec gestion du formulaire
  - ✅ NewsletterController créé avec API REST
  - ✅ Templates mis à jour pour utiliser les formulaires Symfony
- ✅ Phase 10 : API REST créées pour sauvegarder/charger les analyses :
  - ✅ RecordController avec CRUD complet (GET, POST, PUT, DELETE)
  - ✅ IshikawaController avec save/get/list
  - ✅ FiveWhyController avec save/get/list
  - ✅ Authentification requise (ROLE_USER) sur toutes les routes API
  - ✅ Validation des permissions (uniquement les records de l'utilisateur)
- ✅ Phase 18 : Modal de confirmation et amélioration des notifications :
  - ✅ Installer `@stimulus-components/dialog` via npm
  - ✅ Créer le composant modal de confirmation (`templates/components/delete-confirmation-modal.html.twig`)
  - ✅ Créer le contrôleur Stimulus `delete_confirmation_controller.js`
  - ✅ Remplacer les `confirm()` par le modal Stimulus Dialog
  - ✅ Corriger les notifications d'export PDF/image/JSON pour qu'elles disparaissent après succès
  - ✅ Stocker la référence de la notification actuelle (`currentToast`) et fermer la précédente avant d'afficher une nouvelle
  - ✅ Corriger le bug dans `exportJSON()` de fivewhy.js où `filename` n'était pas défini
  - ✅ Améliorer l'UX/UI de la page 5 Pourquoi :
    - ✅ Réduire les espacements en haut (padding-top et margin-top) pour éviter trop de scroll
    - ✅ Rendre le header plus compact avec moins de padding
    - ✅ Optimiser les marges entre les sections (header, alerts, controls, container)
    - ✅ Améliorer l'accessibilité :
      - ✅ Ajouter des labels aria-label et aria-describedby
      - ✅ Ajouter des rôles ARIA (role="main", role="region", role="toolbar")
      - ✅ Améliorer le focus visible pour la navigation au clavier
      - ✅ Ajouter des descriptions visuelles avec visually-hidden
      - ✅ Améliorer le contraste des alertes
    - ✅ Rendre les contrôles plus compacts avec des boutons de taille réduite (btn-sm)
    - ✅ Responsive amélioré pour mobile
    - ✅ Header plus compact avec moins d'espace vertical
  - ✅ Garantir l'accès public aux pages Ishikawa et 5 Pourquoi :
    - ✅ Ajouter explicitement les routes `/ishikawa` et `/5pourquoi` à `access_control` avec `PUBLIC_ACCESS` dans `security.yaml`
    - ✅ Les utilisateurs non connectés peuvent accéder aux outils en lecture seule
    - ✅ Message d'invitation à se connecter affiché pour sauvegarder
  - ✅ Améliorer l'UX/UI de la page Ishikawa :
    - ✅ Réduire les espacements en haut (padding-top et margin-top) pour éviter trop de scroll
    - ✅ Rendre le header plus compact avec moins de padding
    - ✅ Optimiser les marges entre les sections (header, alerts, controls, container)
    - ✅ Améliorer l'accessibilité :
      - ✅ Ajouter des labels aria-label et aria-describedby
      - ✅ Ajouter des rôles ARIA (role="main", role="toolbar", role="region")
      - ✅ Améliorer le focus visible pour la navigation au clavier
      - ✅ Ajouter des descriptions visuelles avec visually-hidden
      - ✅ Améliorer le contraste des alertes
    - ✅ Rendre les contrôles plus compacts avec des boutons de taille réduite (btn-sm)
    - ✅ Responsive amélioré pour mobile
    - ✅ Réduire le margin-top du diagram-container de 8.5rem à 0
    - ✅ Header plus compact avec moins d'espace vertical
  - ✅ Phase 19 : Améliorations responsive et UX/UI :
    - ✅ Correction de la responsivité de la sidebar :
      - ✅ Ajout d'un bouton hamburger dans la navbar pour mobile
      - ✅ Implémentation d'un overlay pour fermer la sidebar sur mobile
      - ✅ Ajustement du margin-left du contenu principal (0 sur mobile, 280px sur desktop)
      - ✅ JavaScript pour gérer le toggle de la sidebar avec support Turbo
      - ✅ Amélioration de l'accessibilité (aria-label, gestion des événements)
    - ✅ Correction du chevauchement de la navbar sur mobile :
      - ✅ Augmentation du padding-top à 80px sur mobile pour éviter le chevauchement
      - ✅ Définition d'une min-height pour la navbar sur mobile
      - ✅ Ajout de règles CSS globales dans custom.css
    - ✅ Simplification du footer pour utilisateurs connectés :
      - ✅ Footer simplifié avec uniquement copyright et liens légaux pour utilisateurs connectés
      - ✅ Footer complet conservé pour utilisateurs non connectés
    - ✅ Amélioration de la page Ishikawa :
      - ✅ Message d'avertissement mobile/tablette plus visible (alert Bootstrap) et affiché sur mobile ET tablette (d-md-none)
      - ✅ Correction de l'initialisation des catégories (support amélioré pour Turbo et chargement asynchrone)
      - ✅ Amélioration de la visibilité avec icône d'avertissement Lucide

---

## 📊 Résumé des tâches restantes

### Priorité haute - Fonctionnalités essentielles

1. **Tests et validation fonctionnels** :
   - [ ] Tester toutes les routes avec `php bin/console debug:router`
   - [ ] Tester l'authentification (connexion/déconnexion)
   - [ ] Tester toutes les routes API avec Postman ou curl
   - [ ] Tester les formulaires (contact, newsletter)
   - [ ] Vérifier la sauvegarde en base de données

2. **Intégration JavaScript avec Stimulus** :
   - [ ] Créer contrôleurs Stimulus pour ishikawa, fivewhy, blog, newsletter, contact
   - [ ] Adapter les scripts existants pour utiliser l'API au lieu de localStorage
   - [ ] Intégrer Turbo pour les soumissions de formulaires

3. **Espace d'administration** (Phase 16) - **Priorité haute** :
   - [ ] Configuration de l'authentification admin (ROLE_ADMIN)
   - [ ] Dashboard d'administration avec statistiques
   - [ ] Gestion CRUD des articles de blog
   - [ ] Gestion des messages de contact
   - [ ] Gestion de la newsletter
   - [ ] Analytics et tracking
   - [ ] Logs d'administration
   - [ ] Gestion des utilisateurs et rôles

4. **Base de données** :
   - [ ] Vérifier que toutes les entités sont créées et migrées
   - [ ] Exécuter les migrations en production
   - [ ] Vérifier les index pour les performances

### Priorité moyenne - Améliorations et nettoyage

5. **Blog dynamique** :
   - [ ] Route et template pour articles individuels (`/blog/{category}/{id}`)
   - [ ] Intégration avec la base de données pour les articles

6. **Nettoyage** :
   - [ ] Supprimer les fichiers HTML source après vérification
   - [ ] Vérifier que tous les chemins d'assets utilisent `asset()` ou AssetMapper

### Priorité basse - Déploiement et optimisations

7. **CI/CD et déploiement** :
   - [ ] Configurer GitHub Actions pour le déploiement
   - [ ] Configurer Azure App Service (PHP 8.2, chemin racine, variables d'environnement)
   - [ ] Exécuter les migrations en production
   - [ ] Tester le site en production

8. **Optimisations** :
   - [ ] Tests de performance
   - [ ] Optimisations cache/CDN si nécessaire

### Notes importantes

- **La plupart des fonctionnalités front-end sont en place** : templates Twig, formulaires Symfony, API REST, responsive design
- **Les fonctionnalités backend critiques sont en place** : authentification, entités, contrôleurs, routes
- **Il reste principalement à faire** : tests, administration, intégration Stimulus, déploiement

