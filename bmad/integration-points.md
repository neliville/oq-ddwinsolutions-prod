# Points d’intégration

## APIs internes (Symfony)

| Point d’entrée | Méthode | Rôle |
| -------------- | ------- | ----- |
| `/api/lead` | POST | Création de lead (formulaires, newsletter, contact, démo, UTM) |
| `/api/record/*` | GET/POST/etc. | CRUD des enregistrements (analyses) |
| `/api/ishikawa/*`, `/api/5pourquoi/*`, etc. | Selon contrôleur | Sauvegarde / chargement des analyses par outil |
| `/api/export-tracking` | POST (ou équivalent) | Envoi des événements d’export pour analytics |

## Services externes

- **Mail :** symfony/mailer (SMTP ou Brevo configuré via `.env` : MAILER_DSN). Pour Brevo : `MAILER_DSN=brevo+api://${BREVO_API_KEY}@default`.
- **Brevo (marketing automation) :** API Contacts v3 pour synchroniser les leads. Clé API dans `BREVO_API_KEY`. Service `App\Lead\Infrastructure\Brevo\BrevoSyncService` : création/mise à jour des contacts (attributs : NOM, SOURCE, OUTIL, SCORE, TYPE, UTM_*). Listes et templates Brevo optionnels (à configurer dans le compte Brevo).
- **Hébergement :** o2switch (MySQL distant, SSH/rsync pour déploiement) ou Azure App Service (selon config).
- **CI/CD :** GitHub Actions (tests, déploiement ; secrets pour o2switch/Azure).

## Messagerie asynchrone (Symfony Messenger)

- **Transport :** `async` (Doctrine ou autre selon config dans `config/packages/messenger.yaml`).
- **Messages :** `LeadCreatedMessage` (notifications + dispatch sync Brevo + pipeline scoring), `SyncLeadToBrevoMessage` (sync async Brevo), `LeadScoringMessage` (calcul score + notification admin si > 50), `LeadQualificationMessage` (B2B/B2C), `LeadEnrichmentMessage` (re-sync Brevo).
- **Routing :** Tous ces messages sont routés vers le transport async avec retry.

## Frontend / tiers

- **Google Tag Manager :** Intégré dans `templates/base.html.twig` (GTM id).
- **Cookie / consentement :** Silktide Consent Manager (ou équivalent) pour bandeau cookies (`cookie-banner/`).
- **Fonts :** Google Fonts (Inter) ; préconnect dans la base.
- **Librairies JS :** Lucide (icônes), AOS (animations), Toastify (toasts selon pages).

## Déploiement

- **Build :** `composer install --no-dev`, `php bin/console asset-map:compile --env=prod`, `php bin/console doctrine:migrations:migrate --no-interaction --env=prod`.
- **Secrets :** Variables d’environnement (APP_SECRET, DATABASE_URL, MAILER_DSN, etc.) ; pas de clés en dur dans le code.
