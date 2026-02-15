# Stack technique

## Backend

| Composant | Version / choix |
| --------- | ----------------- |
| PHP | 8.2+ |
| Framework | Symfony 7.3.x |
| ORM | Doctrine (doctrine/orm, doctrine/doctrine-bundle) |
| Migrations | doctrine/doctrine-migrations-bundle ^3.5 |
| Base de données | MySQL 10.6 (prod o2switch) ; SQLite en mémoire pour tests |
| Authentification | symfony/security-bundle (form login, rôles) |
| Réinitialisation mot de passe | symfonycasts/reset-password-bundle |
| Messagerie asynchrone | symfony/messenger (transport async pour leads/notifications) |
| Mail | symfony/mailer |
| Validation | symfony/validator |
| Formulaires | symfony/form |
| Menu (navbar, admin) | knplabs/knp-menu, knplabs/knp-menu-bundle |
| Markdown (blog) | league/commonmark |
| Images (redimensionnement) | liip/imagine-bundle |
| UID | symfony/uid |

## Frontend

| Composant | Usage |
| --------- | ----- |
| Templates | Twig |
| CSS | SCSS (assets/styles/) ; variables dans core/_variables.scss |
| Compilation SCSS | symfonycasts/sass-bundle (`php bin/console sass:build` ou `sass:build --watch`) |
| Assets | symfony/asset-mapper (pas de Node build par défaut pour le CSS) |
| UI / composants | Bootstrap 5.3 (twbs/bootstrap), composants Twig (UX) |
| Interactivité | Symfony UX Turbo, Symfony UX Live Component, Stimulus (selon pages) |
| JS custom | public/js/ (ishikawa.js, fivewhy.js, etc.) — chargés par page |

## Outils qualité & dev

| Outil | Usage |
| ----- | ----- |
| Tests | PHPUnit 12.x, dama/doctrine-test-bundle (rollback DB), fixtures |
| Profiler | symfony/web-profiler-bundle (dev) |
| Maker | symfony/maker-bundle (dev) |
| CI/CD | GitHub Actions (tests, déploiement o2switch / Azure) |

## Hébergement & déploiement

- **Prod :** o2switch (ou Azure App Service selon config).
- **Build :** `composer install --no-dev`, `php bin/console asset-map:compile --env=prod`, migrations.
- **Script :** `deploy.sh` pour séquence complète (cache, assets, migrations).
