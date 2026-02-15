# Architecture

## Structure des répertoires

```
src/
├── Application/          # Use cases et services applicatifs
│   ├── Analytics/        # TrackingService
│   ├── Lead/             # LeadService, CreateLead, CreateLeadRequest/Response
│   └── Notification/     # NotificationService
├── Command/              # Commandes console (create-admin-user, import-blog, etc.)
├── Controller/
│   ├── Admin/            # Dashboard, Blog, Cms, Contact, Leads, Newsletter, User, etc.
│   ├── Api/              # (vide — API déplacée dans Tools/Api)
│   ├── Tool/             # IshikawaController, FiveWhyController (API outils, noms app_tool_*)
│   └── [autres]          # Dashboard, Creations, Profile, Registration, ResetPassword, Security
├── Site/Controller/      # Home, Blog, Legal, Sitemap, Outils, ToolSeo
├── Tools/Controller/     # Ishikawa, FiveWhy, Qqoqccp, Amdec, Pareto, MethodEightD (pages outils)
├── Tools/Api/            # Amdec, EightD, ExportTracking, FiveWhy, Ishikawa, Pareto, Qqoqccp, Record, ToolSuggestController
├── Tools/Share/          # AmdecShare, EightDShare, FiveWhyShare, IshikawaShare, ParetoShare, QqoqccpShare
├── Lead/Controller/      # Lead (POST /api/lead), Newsletter, Contact
├── Domain/               # Modèles métier (DDD)
│   ├── Analytics/        # ToolUsedEvent, LeadConvertedEvent
│   └── Lead/             # Lead (modèle domaine)
├── Entity/               # Entités Doctrine (User, Record, BlogPost, Lead, *Analysis, *Share, etc.)
├── Form/                 # FormTypes (Registration, Contact, Newsletter, Blog, Cms, etc.)
├── Lead/                 # Domaine Lead (Infrastructure, Service)
│   ├── Infrastructure/Brevo/  # BrevoSyncService (sync contacts API Brevo)
│   └── Service/          # QuotaService (freemium : exports, sauvegardes)
├── Menu/Builder/         # NavbarMenuBuilder, MainMenuBuilder, AdminMenuBuilder
├── Repository/           # Repositories Doctrine
├── Service/              # MailerService, NewsletterService, FeatureAccessService, Analytics (ExportLogger, PageViewLogger), etc.
├── Tools/                # Points d'entrée IA
│   ├── ToolAnalysisInterface.php
│   └── Dto/              # IshikawaData, FiveWhyData, AmdecData, ParetoData, QqoqccpData, EightDData
├── EventListener/        # LoginSuccessListener
├── EventSubscriber/      # PageViewSubscriber
├── Twig/                 # Extensions (Markdown, Version), composants (modals, etc.)
├── Dto/                  # UnsubscribeReasonDto
├── DataFixtures/         # AppFixtures
└── Infrastructure/       # Implémentations (Mail, Messenger)
    └── Mail/             # LeadCreatedMessage, SyncLeadToBrevoMessage, LeadScoringMessage, etc. + Handlers
```

## Contrôleurs principaux

| Zone | Contrôleurs | Rôle |
| ---- | ----------- | ----- |
| Public | HomeController, BlogController, ContactController, NewsletterController, OutilsController, LegalController, SitemapController | Page d’accueil, blog, contact, newsletter, liste outils, pages légales, sitemap |
| Outils | IshikawaController, FiveWhyController, QqoqccpController, AmdecController, ParetoController, MethodEightDController | Pages et logique des outils interactifs |
| Partage | *ShareController (Ishikawa, FiveWhy, Qqoqccp, Amdec, Pareto, EightD) | Pages de partage par lien |
| API | Api\RecordController, Api\IshikawaController, Api\FiveWhyController, etc. | CRUD / sauvegarde analyses, export tracking |
| Public / SEO | Public\ToolSeoController, Public\LeadController | Pages SEO par outil, création de leads |
| Admin | Admin\DashboardController, BlogController, CmsController, LeadsController, UserController, etc. | Back-office |

## Modèle de données (entités)

- **Utilisateurs & contenu :** User (plan, premiumUntil, exportCountThisMonth, lastExportReset pour freemium), Record (créations génériques), BlogPost, Category, Tag, CmsPage, ContactMessage, NewsletterSubscriber, ResetPasswordRequest.
- **Outils & partage :** IshikawaAnalysis, IshikawaShare, IshikawaShareVisit ; FiveWhyAnalysis, FiveWhyShare, FiveWhyShareVisit ; idem pour Qqoqccp, Amdec, Pareto, EightD.
- **Métier / leads :** Lead (captation, score, type B2B/B2C), PageView, ExportLog, AdminLog.

## Patterns utilisés

- **MVC :** Contrôleurs → services / repositories → templates Twig.
- **Use cases (Application/) :** CreateLead, LeadService, NotificationService, TrackingService.
- **Domain (Domain/) :** Modèles métier et événements (ToolUsedEvent, LeadConvertedEvent).
- **Infrastructure :** Messenger (LeadCreatedMessage → SyncLeadToBrevoMessage, LeadScoringMessage → LeadQualificationMessage → LeadEnrichmentMessage ; handlers + BrevoSyncService), persistance via Entity/Repository.
- **Freemium :** FeatureAccessService (canExport, canSave, canUseAI) branché sur QuotaService ; User.plan / quotas.
- **IA (stub) :** ToolAnalysisInterface, DTOs par outil (IshikawaData, etc.), endpoint POST /api/tools/{tool}/suggest.
- **Menu :** KnpMenu (NavbarMenuBuilder pour la navbar publique, AdminMenuBuilder pour l’admin).

## Routes

- **Attributs PHP :** Routes définies dans `src/Controller/`, `src/Site/Controller/`, `src/Tools/Controller/`, `src/Tools/Api/`, `src/Tools/Share/`, `src/Lead/Controller/` via attributs `#[Route]`.
- **Fichiers de routes :** `config/routes.yaml` (plusieurs entrées : controllers, site_controllers, tools_controllers, tools_api, tools_share, lead_controllers).
- **Pages SEO outils :** `/outil/ishikawa`, `/outil/5-pourquoi`, etc. (ToolSeoController).
- **API leads :** `POST /api/lead` (LeadController).
- **API IA (stub) :** `POST /api/tools/{tool}/suggest` (ToolSuggestController), tool = ishikawa | fivewhy | amdec | pareto | qqoqccp | eightd.

## Assets & styles

- **SCSS :** `assets/styles/app.scss` (point d’entrée global), `assets/styles/site/*.scss` et `assets/styles/tools/*.scss` (home, outils, ishikawa, five-why, etc.).
- **Compilation :** `php bin/console sass:build` (ou `--watch`) ; sortie dans `var/sass/*.output.css`.
- **Templates :** Chargent `asset('styles/app.scss')` (base) et éventuellement `asset('styles/site/...')` ou `asset('styles/tools/...')` (bloc `stylesheets`).
