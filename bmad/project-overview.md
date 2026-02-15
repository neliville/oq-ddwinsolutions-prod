# Vue d’ensemble du projet

## Objectif

**Outils Qualité** (outils-qualite.com) est une application web Symfony proposant des **outils QSE gratuits** (Qualité, Sécurité, Environnement) pour les TPE/PME : diagrammes Ishikawa, méthode 5 Pourquoi, QQOQCCP, AMDEC, Pareto, Méthode 8D. L’objectif est de fournir une suite d’outils professionnels, conformes aux normes ISO et Lean, sans installation, avec inscription gratuite et possibilité de sauvegarde et d’export (PDF, JSON).

## Utilisateurs cibles

| Persona | Description | Besoins principaux |
| ------- | ----------- | -------------------- |
| Professionnel QSE | Responsable qualité / HSE en TPE/PME | Outils prêts à l’emploi, exports pour audits |
| Équipe terrain | Opérationnels, chefs de projet | Simplicité, pas de formation lourde |
| Admin / DDWin | Gestion du site, leads, contenu | Dashboard, leads, blog, CMS |

## Périmètre fonctionnel

- **Public :** Page d’accueil, présentation des outils, FAQ, newsletter, contact, pages légales (RGPD, mentions, CGU), blog, pages SEO par outil (`/outil/ishikawa`, `/outil/5-pourquoi`, etc.).
- **Outils interactifs :** Ishikawa, 5 Pourquoi, QQOQCCP, AMDEC, Pareto, Méthode 8D (avec partage par lien et sauvegarde pour utilisateurs connectés ou invités).
- **Compte utilisateur :** Inscription, connexion, profil, « Mes créations » (liste des analyses), réinitialisation mot de passe.
- **Administration :** Dashboard, utilisateurs, blog (articles, catégories, tags), CMS (pages légales), contact, newsletter, leads, logs, analytics.

## Objectifs métier (machine à leads)

Le projet vise à être une **machine à leads** : captation d’emails (newsletter, contact, démo), scoring des leads, notifications asynchrones, suivi dans l’admin. L’architecture inclut Application/Domain/Infrastructure (leads, notifications, tracking) en plus des contrôleurs et entités Symfony classiques.

## Statut actuel

- Migration Symfony terminée, hébergement Azure / o2switch, CI/CD GitHub Actions.
- Assets : Asset Mapper + Symfonycasts SASS (compilation SCSS via `sass:build`).
- Tests : PHPUnit (fonctionnels, intégration, unitaires), base SQLite en mémoire pour les tests.
