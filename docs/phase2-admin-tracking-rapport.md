# Phase 2 — Instrumentation métier, cockpit admin, séparation des vues

## Existant (avant / conservé)

- **Admin Symfony custom** : contrôleurs sous `src/Controller/Admin/`, pas EasyAdmin.
- **Analytics historiques** : `TrackingService` (PageView + `ToolUsedEvent`), `AnalyticsRepository`, `ExportLogger` + endpoint `POST /analytics/track-export`.
- **Sécurité** : `access_control` avec `^/admin` réservé à `ROLE_ADMIN` (`config/packages/security.yaml`).

## Ajouts réalisés

### Données

- Entité **`TrackingEvent`** (`src/Entity/TrackingEvent.php`) : utilisateur optionnel, type (`TrackingEventType` enum), `tool`, `action`, `source` (`web` | `api` | `admin`), `context`, `metadata` JSON, `createdAt`, **`ip_hash`** et **`session_key`** (SHA-256 tronqué à 64 octets avec `kernel.secret`, pas d’IP en clair).
- Migration **`Version20260510160000`** : table `tracking_event` + index `(event_type, created_at)`, `(user_id, created_at)`, `created_at`.
- **`TrackingEventRepository`** : agrégations 7 jours (comptages par type, utilisateurs distincts, top `tool_opened`), derniers événements.

### Écriture

- **`TrackingEventRecorder`** : hydrate depuis `RequestStack` + utilisateur (`Security` si non passé), persiste en transaction courte (`flush` immédiat — acceptable phase 2 ; Messenger possible plus tard).

### Points d’accroche (événements)

| Événement | Déclencheur |
|-----------|-------------|
| `account_created` | `RegistrationController` après création du compte |
| `login_return` | `LoginSuccessListener` si connexion **non** première (`lastLoginAt` déjà renseigné avant login) |
| `dashboard_opened` | `DashboardController::index` |
| `capa_created` | `QseCapaPrefillController` après `flush` du brouillon |
| `audit_created` | `QseAuditController::new` (POST succès) |
| `risk_created` | `QseRiskController::new` (POST succès) |
| `export_triggered` | `ExportTrackingController` + `ExportLogger` existant |
| `tool_opened` | `TrackingService::trackToolUsed` (complément aux PageView outil) |

**PageView** : conservées pour le trafic brut ; `TrackingEvent` apporte la sémantique métier et la corrélation utilisateur sans tout doubler sur chaque hit anonyme.

### Cockpit admin

- **Layout dédié** : `templates/layout/admin_base.html.twig` (sidebar cockpit + topbar admin, distinct du `base_with_sidebar` utilisateur pour les pages sous `templates/admin/**`).
- **Sidebar** : `templates/components/admin_cockpit_sidebar.html.twig` (sections : Cockpit, Utilisateurs, Outils, CAPA à venir, Audits ISO admin, Risques à venir, Contenus, Analytics, Messages).
- **Placeholders** : `ComingSoonController` (`/admin/a-venir/{topic}`) pour CAPA / risques **sans lien mort** vers des routes inexistantes.
- **Dashboard admin** : blocs KPI 7 jours (TE + volumes entités QSE + `getGlobalCreationCountsByTool`) + tableau des derniers `TrackingEvent`.
- **`AdminMenuBuilder`** : liste plate de routes valides (Knp `admin`), alignée sur la sidebar.

### Switch Admin / Utilisateur

- **Topbar admin** : bouton Shadcn « Vue utilisateur » → `app_dashboard_index` (`templates/components/admin_topbar.html.twig`).
- **Topbar utilisateur** : bouton « Administration » → `app_admin_dashboard_index` pour `ROLE_ADMIN` (`templates/components/dashboard_topbar.html.twig`). Le menu profil conserve aussi le lien Administration.

## Manques / phase suivante

- **Rétention / purge** : pas de commande `app:purge-old-tracking` (à planifier ; mention RGPD dans DPA interne).
- **Async** : pas de bus Messenger pour `TrackingEvent` (volumétrie élevée → à introduire avec file + retry).
- **Seuils d’alertes** (baisse créations, pics) : non implémentés (v1 = lecture seule des agrégats).
- **Admin CAPA / risques** : pages « à venir » uniquement ; pas de liste admin dédiée.

## RGPD

- Pas d’IP brute dans `tracking_event` ; hachage **salté** par `kernel.secret`.
- Métadonnées d’inscription : **domaine e-mail** uniquement (`email_domain`), pas l’adresse complète dans `metadata`.
- Politique de durée de conservation à formaliser + droit à l’effacement (cascade `user_id` → `SET NULL` sur suppression utilisateur).

## Risques

| Risque | Mitigation actuelle |
|--------|---------------------|
| Volume DB | Index sur type + date ; purge à prévoir |
| Double comptage TE vs PageView | Règle documentée : TE = événements métier, PageView = trafic |
| Perf `flush` synchrone | Acceptable charge actuelle ; passer en async si besoin |

## Ordre d’implémentation suivi

1. Entité + migration + repository + recorder  
2. Instrumentation contrôleurs + `TrackingService`  
3. Dashboard admin + agrégats  
4. Menu / sidebar cockpit + layout admin  
5. Switch + tests  
6. Ce rapport  

---

*Document généré dans le cadre de la Phase 2 — mai 2026.*
