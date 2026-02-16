# Guide de déploiement - Symfony sur o2switch

Ce document décrit la stratégie de déploiement actuelle : le code est hébergé sur GitHub et déployé vers o2switch via SSH/rsync grâce au workflow `Déploiement Symfony vers o2switch`.

## 🌿 Branches

- **`feat/symfony-app`** : branche de **développement actuelle**. C’est ici que se fait tout le travail (nouvelles fonctionnalités, correctifs).
- **`main`** : branche de **production**. On merge `feat/symfony-app` dans `main` lorsque tout est prêt ; le déploiement en production part de `main`.

## 📋 Vue d’ensemble

```
feat/symfony-app (dev)  ──▶  Pull Request  ──▶  merge main (prod)
                                    │
                                    ▼
                            CI tests (ci-tests.yml)
                                    │
                                    ▼
                    Deploy Symfony to o2switch (manuel ou auto)
```

- **Branche `feat/symfony-app`** : développement + push → exécution des tests CI.
- **Pull Request vers `main`** : revue + tests obligatoires.
- **Merge sur `main`** : base à jour pour la production ; déploiement o2switch (workflow manuel `workflow_dispatch` ou automatique si configuré).
- **Workflow manuel** : onglet GitHub Actions → « Déploiement Symfony vers o2switch » → Run workflow (depuis `main`).
- **Staging** : onglet GitHub Actions → « Déploiement Staging o2switch (staging.outils-qualite.com) » → Run workflow (branche par défaut : `feat/symfony-app`).

## 🔐 Pré-requis côté o2switch

1. **Accès SSH**
   - Activer dans le cPanel et tester : `ssh -p 2222 moncompte@ssh.[domaine].o2switch.net`.
   - Générer une clé dédiée pour GitHub Actions (`ssh-keygen -t ed25519 -f gh_o2switch`).
   - Ajouter la clé publique (`gh_o2switch.pub`) dans `~/.ssh/authorized_keys` sur le serveur.

2. **Structure du projet**
   - Dossier cible : `/home/moncompte/www/oq-symfony` (ou chemin équivalent).
   - Le document root du domaine doit pointer vers `…/oq-symfony/public`.

3. **Variables d’environnement**
   - Créer un fichier `.env.prod.local` côté serveur (non versionné) :
     ```dotenv
     APP_ENV=prod
     APP_SECRET=…
     DATABASE_URL=mysql://user:pass@localhost:3306/db?charset=utf8mb4
     MAILER_DSN=…
     ```

4. **Base de données**
   - Créer la base MySQL via cPanel.
   - Lors du premier déploiement, exécuter manuellement :
     ```bash
     php bin/console doctrine:migrations:migrate --env=prod --no-interaction
     ```
   - Prévoir un script de sauvegarde régulier (mysqldump ou snapshot cPanel).

## 🔑 Secrets GitHub Actions

À définir dans `Settings > Secrets and variables > Actions` :

| Secret | Description |
| --- | --- |
| `O2SWITCH_HOST` | Hôte SSH (ex. `sshXXX.o2switch.net`) |
| `O2SWITCH_PORT` | Port SSH (souvent `2222`) |
| `O2SWITCH_USER` | Identifiant o2switch |
| `O2SWITCH_SSH_KEY` | Clé privée générée (`gh_o2switch`) |
| `O2SWITCH_DEPLOY_PATH` | Dossier tampon pour les releases (ex. `/home/moncompte/deploy`) |
| `O2SWITCH_WEBROOT` | Dossier final du site **production** (ex. `/home/moncompte/www/oq-symfony`) |
| `O2SWITCH_STAGING_WEBROOT` | Dossier du site **staging** (ex. `/home/moncompte/staging.outils-qualite.com`) — pour staging.outils-qualite.com |

Optionnel : ajouter un secret `O2SWITCH_KNOWN_HOSTS` contenant la sortie de `ssh-keyscan -p 2222 sshXXX.o2switch.net` si on souhaite forcer la vérification d’hôte.

## ⚙️ Workflow `deploy-o2switch.yml`

Principales étapes :

1. **Composer install** (prod, sans dev) + `asset-map:compile` + `cache:clear`.
2. **PHPUnit** (`php bin/phpunit --testdox`).
3. **Compression** (`release.zip`).
4. **SCP** de l’archive vers le dossier tampon (`O2SWITCH_DEPLOY_PATH`).
5. **Déploiement serveur** : unzip dans un dossier temporaire, `rsync --delete` vers `O2SWITCH_WEBROOT`, purge du cache prod.

> Migrations Doctrine sont commentées dans le script : les lancer manuellement ou décommenter une fois validé.

## 🧪 Workflow `deploy-o2switch-staging.yml` (staging.outils-qualite.com)

1. Déclenchement manuel (`workflow_dispatch`) avec choix de la branche (défaut : `feat/symfony-app`).
2. Mêmes étapes que le déploiement production, avec en plus `sass:build` pour les styles compilés.
3. Déploiement vers **O2SWITCH_STAGING_WEBROOT** (pas vers O2SWITCH_WEBROOT).
4. À faire côté o2switch pour que staging.outils-qualite.com fonctionne :
   - Créer le sous-domaine **staging.outils-qualite.com** dans cPanel (Sous-domaines).
   - Pointer le document root du sous-domaine vers le dossier staging (ex. `~/staging.outils-qualite.com/public` ou `~/www/staging/public` si la structure est `public/` à la racine du projet).
   - Y placer un `.env.local` ou `.env.prod.local` avec `APP_ENV=prod` et les mêmes variables que la prod (ou une copie de base de données de test).

## 🔄 Règles de branche & CI

- **Développement** : travailler sur `feat/symfony-app` ; les pushes déclenchent les tests CI (`ci-tests.yml`).
- **Mise en production** : ouvrir une Pull Request `feat/symfony-app` → `main` ; une fois mergé, déployer depuis `main`.
- `ci-tests.yml` reste la référence pour les tests automatiques (unitaires, fonctionnels, intégration). Il doit passer avant tout merge vers `main`.
- Protéger la branche `main` (GitHub Settings > Branches) :
  - Require PR reviews.
  - Require status checks (`ci-tests`).
  - Interdire le push direct sans tests.

## ✅ Checklist avant déploiement

1. PR approuvée, tests locaux + CI verts.
2. Secrets GitHub + accès SSH validés.
3. `.env.prod.local` présent côté serveur.
4. Résultat du premier déploiement : exécuter `doctrine:migrations:migrate` et vérifier `var/log/prod.log`.
5. Tester manuellement les parcours critiques :
   - Accueil, Ishikawa, 5 Pourquoi, Outils, Blog, Contact, Pages légales.
   - Authentification + “mot de passe oublié”.
   - Sauvegarde/chargement d’analyses (Ishikawa, 5 Pourquoi).
   - Formulaires contact/newsletter, exports PDF/JSON.
   - Responsive (navbar, sidebar, hero).

## 🔁 Rollback

1. Conserver la release précédente sur le serveur (copie `release.zip` ou dossier backup `oq-symfony-YYYYMMDD`).
2. En cas de bug critique :
   ```bash
   rsync -a --delete /home/moncompte/backups/oq-symfony-YYYYMMDD/ $O2SWITCH_WEBROOT/
   php bin/console cache:clear --env=prod
   ```
3. Rétablir l’ancienne base de données si nécessaire (restauration cPanel ou dump SQL).

## 🧹 Workflows Azure (legacy)

Les workflows GitHub historiques (`deploy-symfony-staging.yml`, `deploy-symfony-production.yml`, `main_outils-qualite-gratuit.yml`) sont conservés pour archive mais désactivés (`if: ${{ false }}`). Ils peuvent être supprimés ultérieurement.

## 📚 Références

- [o2switch – Documentation SSH](https://faq.o2switch.fr/category/ssh/)  
- [appleboy/scp-action](https://github.com/appleboy/scp-action) / [appleboy/ssh-action](https://github.com/appleboy/ssh-action)  
- [Symfony 7.3 – Déploiement](https://symfony.com/doc/current/deployment.html)

