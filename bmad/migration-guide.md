# Guide de migration (refactoring BMAD)

Ce document décrit les changements introduits par le plan de refactoring BMAD (Brevo, Messenger, freemium, IA) et comment migrer / déployer.

## Résumé des changements

- **Phase 0 – Brevo :** Intégration symfony/brevo-mailer, BrevoSyncService, SyncLeadToBrevoMessage, emails (confirmation outil, notification admin).
- **Phase 4 – Messenger :** LeadScoringMessage, LeadQualificationMessage, LeadEnrichmentMessage et handlers (scoring → qualification → enrichment → re-sync Brevo).
- **Migration User :** Champs `plan`, `premiumUntil`, `exportCountThisMonth`, `lastExportReset` (freemium-ready).
- **QuotaService + FeatureAccessService :** Quotas export (10/mois) et sauvegardes (5 max) pour plan free ; premium illimité.
- **Phase 5 – IA :** ToolAnalysisInterface, DTOs (IshikawaData, FiveWhyData, etc.), endpoint POST `/api/tools/{tool}/suggest` (stub).

## Déploiement

### Variables d'environnement

- **BREVO_API_KEY** (optionnel) : Clé API Brevo pour la synchronisation des contacts. Si absente, la sync Brevo est ignorée.
- **MAILER_DSN** : Pour envoyer les emails via Brevo : `MAILER_DSN=brevo+api://${BREVO_API_KEY}@default`.
- **MESSENGER_TRANSPORT_DSN** : Transport async (ex. `doctrine://default`) pour les messages Lead et Brevo.

### Migrations Doctrine

Exécuter les migrations pour ajouter les champs User (plan, premiumUntil, export_count_this_month, last_export_reset) :

```bash
php bin/console doctrine:migrations:migrate --no-interaction
```

### Workers Messenger

En production, un worker doit consommer la file async pour traiter LeadCreatedMessage, SyncLeadToBrevoMessage, LeadScoringMessage, LeadQualificationMessage, LeadEnrichmentMessage :

```bash
php bin/console messenger:consume async -vv
```

## Freemium

- **Aujourd’hui :** Tous les utilisateurs ont `plan = 'free'`. Les quotas (10 exports/mois, 5 sauvegardes) sont appliqués par QuotaService / FeatureAccessService.
- **Activation premium (plus tard) :** Mettre à jour `User.plan = 'premium'` et/ou `User.premiumUntil` (ex. après paiement Stripe) pour lever les limites.

## Points d’entrée IA

- **Endpoint :** `POST /api/tools/{tool}/suggest` avec `tool` = ishikawa | fivewhy | amdec | pareto | qqoqccp | eightd.
- **Corps :** JSON optionnel (payload de l’analyse).
- **Réponse (stub) :** `{ "tool": "...", "suggestions": [], "message": "..." }`.
- Pour brancher un vrai service IA : créer un service qui prend un `ToolAnalysisInterface` (ou les DTOs) et remplir `suggestions` dans le contrôleur.

## Références

- **Architecture :** `bmad/architecture.md`
- **Points d’intégration :** `bmad/integration-points.md`
- **Plan de refactoring :** `bmad/REFACTORING_BMAD_PLAN.md`
