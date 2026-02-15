# Documentation projet — Index BMAD

**Projet :** Outils Qualité (oq-ddwinsolutions-prod)  
**Type :** Brownfield — Application Symfony 7.x  
**Dernière mise à jour :** 2026-01-28

Cette documentation suit la méthode BMAD pour les projets existants (brownfield). Elle sert de référence pour le développement assisté par IA et l’onboarding.

---

## Sections

| Document | Description |
| -------- | ----------- |
| [Vue d’ensemble du projet](./project-overview.md) | Objectifs, utilisateurs, périmètre fonctionnel |
| [Stack technique](./technology-stack.md) | Frameworks, librairies, outils, base de données |
| [Architecture](./architecture.md) | Structure du code, contrôleurs, entités, patterns |
| [Règles métier](./business-rules.md) | Logique métier, domaines, use cases |
| [Points d’intégration](./integration-points.md) | APIs, services externes, messagerie |
| [Glossaire](./glossary.md) | Termes clés et acronymes |

---

## Parcours recommandés

**Commencer sur le projet (brownfield)**  
→ Lire [Vue d’ensemble](./project-overview.md) puis [Architecture](./architecture.md).

**Implémenter une fonctionnalité**  
→ Consulter [Règles métier](./business-rules.md) et [Points d’intégration](./integration-points.md).

**Comprendre la stack**  
→ Lire [Stack technique](./technology-stack.md).

---

## Conventions

- **Répertoire racine :** `/home/neliville/dev/LAB/QSE/oq-ddwinsolutions-prod` (ou équivalent selon l’environnement).
- **Namespace PHP :** `App\`.
- **Templates :** `templates/` (Twig).
- **Assets :** `assets/` (SCSS, JS) ; compilation via `php bin/console sass:build`.
