# Règles métier

## Utilisateurs et rôles

- **ROLE_USER :** Accès aux outils, « Mes créations », profil, réinitialisation mot de passe.
- **ROLE_ADMIN :** Accès au back-office (dashboard, utilisateurs, blog, CMS, contact, newsletter, leads, logs, analytics).
- **Invité :** Accès aux outils en lecture/création sans compte ; sauvegarde possible (localStorage ou API selon outil) ; incitation à s’inscrire (modale après export / sauvegarde).

## Outils qualité

- Chaque outil (Ishikawa, 5 Pourquoi, QQOQCCP, AMDEC, Pareto, 8D) a une page interactive, des entités d’analyse et de partage, et des APIs de sauvegarde/export.
- **Partage :** Liens publics de type `/ishikawa/share/{uuid}` (et équivalents) ; visites optionnellement tracées (*ShareVisit).
- **Export :** PDF, PNG, JSON selon l’outil ; logs d’export (ExportLog) pour analytics.

## Leads (machine à leads)

- **Création :** Via `POST /api/lead` (formulaires, newsletter, contact, démo, utilisation d’outil).
- **Scoring :** Score 0–100 (email, nom, outil utilisé, source, UTM, consentement RGPD).
- **Type :** B2B / B2C selon le domaine email.
- **Notifications :** Message Messenger asynchrone (LeadCreatedMessage) ; notification admin pour leads qualifiés.

## Newsletter

- Inscription avec email ; désinscription possible (page dédiée, raison optionnelle via UnsubscribeReasonDto).
- Gestion des abonnés dans l’admin (NewsletterController).

## Blog

- Articles avec catégories et tags ; Markdown (league/commonmark) pour le contenu.
- Admin : CRUD articles, catégories, tags.

## CMS

- Pages éditable en admin (CmsController) : politique de confidentialité, mentions légales, conditions d’utilisation.

## Sécurité & conformité

- CSRF sur tous les formulaires.
- Validation des entrées (Symfony Validator).
- RGPD : consentement, politique de confidentialité, export/suppression des données (selon implémentation).

## Tracking & analytics

- **PageView :** Enregistrement des pages vues (PageViewSubscriber ou équivalent).
- **ExportLog :** Log des exports (ExportLogger).
- **AdminLog :** Log des actions admin.
