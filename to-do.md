# TODO - Migration vers Symfony + MySQL

> **Objectif** : Transformer le site statique en application Symfony + MySQL tout en conservant l'hébergement Azure App Service (PHP 8.2) et le CI/CD GitHub.

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
- **CSS** : 
  - `/css/custom.css` - Variables CSS globales et styles communs
  - `/css/ishikawa.css` - Styles spécifiques à la page Ishikawa (sans doublons)
- **JavaScript** :
  - `/js/main.js` - Script principal (initialisation, navbar, AOS)
  - `/js/ishikawa.js` - Logique de l'outil Ishikawa (vanilla JS avec IIFE, compatible Turbo)
  - `/js/fivewhy.js` - Logique de l'outil 5 Pourquoi
  - `/js/blog-markdown.js` - Affichage des articles blog
- **Images** : `/img/` - Logo, favicons, images
- **Librairies externes** : Bootstrap 5.3.2, Lucide Icons, AOS, Font Awesome

### Fonctionnalités actuelles
1. **Outils interactifs** :
   - Diagramme Ishikawa avec export PDF/JPEG/JSON
     - ✅ Canvas responsive avec redimensionnement automatique
     - ✅ Drag & drop pour catégories et causes
     - ✅ Boutons toujours visibles avec bon contraste
     - ✅ Masquage automatique des boutons quand modal ouvert
     - ✅ Grille horizontale responsive pour les catégories
     - ✅ Accessibilité complète (WCAG)
   - Méthode 5 Pourquoi avec export PDF
   - Stockage : localStorage pour utilisateurs non connectés, API REST pour utilisateurs connectés
   
2. **Blog** : Articles dynamiques en base de données avec support Markdown
3. **Newsletter** : Formulaire fonctionnel avec API REST et emails automatiques
4. **Tracking** : Google Tag Manager, Application Insights, PageView en base de données
5. **Logging** : Azure Logic App endpoint pour les exports (optionnel)

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
- [ ] MySQL local (ou Docker) pour développement
- [ ] Accès au portail Azure (App Service + MySQL)
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
DATABASE_URL="mysql://root:root@127.0.0.1:3306/oq?serverVersion=8.0&charset=utf8mb4"
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

#### ✅ Étape 5.5 : Réinitialisation de mot de passe
- [x] Installer SymfonyCasts ResetPasswordBundle (`composer require symfonycasts/reset-password-bundle`)
- [x] Générer l'infrastructure via `make:reset-password` (entité, contrôleur, formulaires, templates)
- [x] Personnaliser les pages Twig (demande, confirmation, saisie du nouveau mot de passe) selon la charte UI/UX
- [x] Configurer l'envoi d'email avec Symfony Mailer (expéditeur `support@outils-qualite.com`)
- [x] Mettre à jour la page de connexion avec le lien « Mot de passe oublié ? »
- [x] Créer les tests fonctionnels (`tests/ResetPasswordControllerTest.php`) pour couvrir le scénario complet
- [x] Compiler les assets après ajout des styles propres aux pages d'authentification

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
- [x] Adapter `public/js/ishikawa.js` pour utiliser vanilla JS avec IIFE (évite les conflits Turbo)
  - ✅ Code encapsulé dans IIFE pour éviter la pollution globale
  - ✅ Fonctions exposées via `window.ishikawaApp` pour les attributs onclick
  - ✅ Attribut `data-turbo-eval="false"` sur le script pour éviter la réévaluation Turbo
  - ✅ Gestion des modals avec classe `modal-open` pour masquer les boutons
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
  - Utiliser vanilla JS (`ishikawa.js` avec IIFE) pour éviter les conflits Turbo
  - Intégrer le script via `<script src="{{ asset('js/ishikawa.js') }}" data-turbo-eval="false">`
  - Bouton "Sauvegarder" visible uniquement si connecté (`{% if app.user %}`)
  - Message d'invitation à se connecter si non connecté
  - ✅ **Améliorations UX/UI** : Boutons toujours visibles, masquage automatique quand modal ouvert, canvas responsive, grille horizontale pour catégories
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
- [x] Vérifier que tous les chemins d'assets utilisent `asset()` ou AssetMapper
- [x] Vérifier que les chemins relatifs sont corrects
- [x] Tester que les templates se compilent sans erreur
- [x] Vérifier que tous les scripts JavaScript sont chargés correctement (AssetMapper ou vanilla JS)
- [x] Tester que Turbo fonctionne pour les navigations (pas de conflit avec ishikawa.js grâce à IIFE)
- [x] Vérifier que les menus KnpMenu s'affichent correctement
- [x] **Page Ishikawa** : Vérifier que tous les boutons sont visibles et fonctionnels
- [x] **Page Ishikawa** : Vérifier que les modals masquent correctement les boutons d'action
- [x] **Page Ishikawa** : Vérifier que le canvas est responsive et s'adapte aux différentes tailles d'écran

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
- [ ] `DATABASE_URL=mysql://<user>:<pass>@<host>:3306/<db>?serverVersion=8.0&charset=utf8mb4`
- [ ] `SCM_DO_BUILD_DURING_DEPLOYMENT=true`

#### ✅ Étape 13.4 : Vérifier la connexion MySQL Azure
- [ ] Tester la connexion depuis Azure App Service vers MySQL
- [ ] Vérifier que les règles de pare-feu permettent la connexion

---

### Phase 14 : Base de données MySQL (production)

#### ✅ Étape 14.1 : Exécuter les migrations en production
```bash
php bin/console doctrine:migrations:migrate --no-interaction --env=prod
```

**Note** : Cette commande devra être exécutée soit :
- Via Azure Cloud Shell
- Via SSH depuis Azure App Service
- Via un script dans le workflow GitHub Actions (avec connexion SSH)

#### ✅ Étape 14.2 : Vérifier que les migrations sont appliquées
- [ ] Vérifier dans MySQL Azure que les tables existent
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
- [x] Route `GET /admin/blog` - Liste des articles
- [x] Route `GET /admin/blog/new` - Créer un article
- [x] Route `GET /admin/blog/{id}/edit` - Modifier un article
- [x] Route `GET /admin/blog/{id}` - Voir un article
- [x] Route `POST /admin/blog` - Sauvegarder un article (via formulaire)
- [x] Route `POST /admin/blog/{id}/publish` - Publier un article
- [x] Route `POST /admin/blog/{id}/unpublish` - Dépublier un article
- [x] Route `POST /admin/blog/{id}/delete` - Supprimer un article
- [x] Créer `BlogPostFormType` pour le formulaire
- [x] Génération automatique de slug depuis le titre
- [x] Gestion des catégories et tags dans le formulaire
- [x] Filtres : tous, publiés, brouillons, mis en avant
- [x] Pagination des articles
- [x] Statistiques : publiés, brouillons, mis en avant
- [x] Logger les actions dans `AdminLog`
- [x] Templates Twig : `index.html.twig`, `new.html.twig`, `edit.html.twig`, `show.html.twig`

#### ✅ Étape 16.5 : Gestion des catégories et tags
- [x] Route `GET /admin/categories` - Liste des catégories
- [x] Route `GET /admin/categories/new` - Créer une catégorie
- [x] Route `GET /admin/categories/{id}/edit` - Modifier une catégorie
- [x] Route `POST /admin/categories/{id}/delete` - Supprimer une catégorie
- [x] Route `GET /admin/tags` - Liste des tags
- [x] Route `GET /admin/tags/new` - Créer un tag
- [x] Route `GET /admin/tags/{id}/edit` - Modifier un tag
- [x] Route `POST /admin/tags/{id}/delete` - Supprimer un tag
- [x] CRUD pour les catégories avec CategoryFormType
- [x] CRUD pour les tags avec TagFormType
- [x] Génération automatique de slug
- [x] Validation : impossible de supprimer si utilisé par des articles
- [x] Logger les actions dans `AdminLog`
- [x] Templates Twig complets (index, new, edit) pour catégories et tags

#### ✅ Étape 16.6 : Gestion des messages de contact
- [x] Route `GET /admin/contact` - Liste des messages
- [x] Route `GET /admin/contact/{id}` - Voir un message
- [x] Route `POST /admin/contact/{id}/mark-read` - Marquer comme lu
- [x] Route `POST /admin/contact/{id}/mark-unread` - Marquer comme non lu
- [x] Route `POST /admin/contact/{id}/reply` - Répondre à un message
- [x] Route `POST /admin/contact/{id}/delete` - Supprimer un message
- [x] Filtres : non lus, lus, répondus, non répondus
- [x] Pagination des messages
- [x] Logger les actions dans `AdminLog`
- [x] Templates Twig : `index.html.twig` et `show.html.twig`

#### ✅ Étape 16.7 : Gestion de la newsletter
- [x] Route `GET /admin/newsletter` - Liste des abonnés
- [x] Route `GET /admin/newsletter/export` - Exporter la liste (CSV)
- [x] Route `POST /admin/newsletter/{id}/unsubscribe` - Désabonner
- [x] Route `POST /admin/newsletter/{id}/delete` - Supprimer
- [x] Afficher les statistiques (actifs, désinscrits, total)
- [x] Filtrer par statut (actif/inactif)
- [x] Pagination des abonnés
- [x] Template Twig : `index.html.twig`
- [x] NewsletterService pour l'envoi d'emails
- [x] Email de bienvenue automatique lors de l'inscription
- [x] Template d'email : `templates/emails/newsletter/welcome.html.twig`
- [x] Configuration email via variables d'environnement (Cloudflare ready)

#### ✅ Étape 16.8 : Analytics et tracking
- [x] Route `GET /admin/analytics` - Statistiques de visites
- [x] Afficher les statistiques :
  - [x] Pages les plus visitées
  - [x] Référents les plus fréquents
  - [x] Données géographiques (country, city)
  - [x] Appareils et navigateurs
  - [x] Utilisateurs connectés vs anonymes
- [x] Graphiques de tendances (nombre de visites par jour/mois)
- [x] Filtres par période (aujourd'hui, semaine, mois, année)
- [x] Statistiques comparatives (hier, semaine dernière, mois dernier)
- [x] PageViewRepository enrichi avec méthodes de statistiques
- [x] Template Twig avec tableaux et graphiques de tendances

#### ✅ Étape 16.9 : Logs d'administration
- [x] Route `GET /admin/logs` - Liste des logs
- [x] Route `GET /admin/logs/export` - Export CSV des logs
- [x] Afficher les actions d'administration :
  - [x] Utilisateur qui a effectué l'action
  - [x] Type d'action (CREATE, UPDATE, DELETE)
  - [x] Entité concernée
  - [x] Changements effectués
  - [x] Date et heure
  - [x] Adresse IP
- [x] Filtres : utilisateur, action, entité, période
- [x] Pagination des logs
- [x] Export CSV des logs
- [x] AdminLogRepository enrichi avec méthodes de filtrage
- [x] Template Twig avec filtres et pagination

#### ✅ Étape 16.10 : Gestion des utilisateurs
- [x] Route `GET /admin/users` - Liste des utilisateurs
- [x] Route `GET /admin/users/{id}` - Voir un utilisateur
- [x] Route `GET /admin/users/{id}/edit` - Modifier un utilisateur
- [x] Route `POST /admin/users/{id}/delete` - Supprimer un utilisateur
- [x] Gérer les rôles (ROLE_USER, ROLE_ADMIN)
- [x] Modification de mot de passe
- [x] Filtres par rôle (all, admin, user)
- [x] Pagination
- [x] Statistiques par rôle
- [x] Protection : impossible de supprimer/modifier son propre compte
- [x] Logger les actions dans `AdminLog`
- [x] Templates Twig complets (index, show, edit)
- [x] UserRepository enrichi avec méthodes de filtrage

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
- [x] Site accessible depuis `/` (templates Twig)
- [x] Toutes les routes Twig fonctionnent
- [x] Tous les templates Twig s'affichent correctement
- [x] Tous les assets (CSS, JS, images) se chargent correctement
- [x] Blog fonctionne (affichage des articles)
- [x] Formulaire de contact fonctionne
- [x] Newsletter fonctionne
- [x] **Page Ishikawa** : UX/UI améliorée, accessibilité complète, responsive
- [x] **Page Ishikawa** : Boutons toujours visibles avec bon contraste
- [x] **Page Ishikawa** : Masquage automatique des boutons quand modal ouvert
- [x] **Page Ishikawa** : Canvas responsive avec redimensionnement automatique

### Authentification et API
- [ ] Auth fonctionnelle (connexion/déconnexion)
- [ ] CRUD `Record` OK (créer, lire, modifier, supprimer)
- [ ] Sauvegarde/chargement des analyses Ishikawa via API
- [ ] Sauvegarde/chargement des analyses 5 Pourquoi via API
- [ ] Tous les exports (PDF/JPEG/JSON) fonctionnent

### Administration
- [x] Espace admin accessible (`/admin`)
- [x] Dashboard admin avec statistiques
- [x] Gestion des articles de blog (CRUD)
- [x] Gestion des messages de contact
- [x] Gestion de la newsletter
- [x] Analytics et tracking fonctionnels
- [x] Logs d'administration consultables
- [x] Gestion des utilisateurs et rôles
- [x] Gestion des catégories et tags (CRUD)

### Base de données MySQL
- [x] Toutes les entités créées (User, BlogPost, Category, Tag, ContactMessage, NewsletterSubscriber, PageView, AdminLog, IshikawaAnalysis, FiveWhyAnalysis)
- [x] Migrations générées et exécutées en local
- [ ] Migrations exécutées en production
- [x] Base de données MySQL opérationnelle en local
- [x] Index configurés pour les performances (PageView, AdminLog)

### Déploiement
- [ ] CI/CD passe au vert
- [ ] Site accessible en production
- [ ] Tracking des visites fonctionnel
- [ ] Slot staging (si utilisé)
- [ ] Documentation mise à jour

---

**Dernière mise à jour** : 2025-11-07
**Statut global** : 🟢 En cours - SEO et Blog dynamique terminés, migration vers MySQL effectuée, **tests terminés (42/42 passent - 100%)**, Page Ishikawa améliorée (UX/UI, accessibilité, responsive), Stimulus restant, **CMS légal éditable depuis le back-office**

### Progrès récent
- ✅ Phase 5 : Authentification complétée
- ✅ Fonction « Mot de passe oublié » : bundle installé, pages personnalisées, email expédié, test fonctionnel en place
- ✅ Phase 5.6 : KnpMenuBundle configuré et corrigé
- ✅ Pages d’erreur 403/404/500 personnalisées avec design harmonisé et tests fonctionnels dédiés
- ✅ Workflow GitHub Actions `deploy-o2switch.yml` configuré (Composer + asset-map + tests + rsync vers o2switch)

