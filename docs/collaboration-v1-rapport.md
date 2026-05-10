# Rapport — Collaboration légère V1

Date : 2026-05-10  
Projet : Outils-Qualite.com (Symfony)

## Ce qui a été intégré

### Invitations (`UserInvitation`)

- Entité avec jeton **hashé** (SHA-256 du secret opaque), statuts (envoyée, acceptée, expirée, annulée), expiration paramétrable (`app.collaboration.invitation_ttl_days`, défaut 14 jours), type général ou contextuel (métadonnées JSON : source, `auditId`, `capaId`).
- API : `POST /api/collaboration/invite` (authentifié) — envoi e-mail optionnel, ou création + copie du lien d’acceptation.
- Parcours public : `GET /collaboration/invitation/accept?t=…` — page d’atterrissage (inscription / connexion), acceptation automatique si l’utilisateur connecté correspond à l’e-mail invité.
- Inscription : paramètre `?invitation=` + session lorsque disponible ; après création de compte, **acceptation** si l’e-mail correspond.
- Connexion : `LoginSuccessListener` consomme le jeton en session et appelle l’acceptation si l’e-mail correspond.

### Partage audit / CAPA (`SharedAccess`)

- Entité avec `targetType` (audit | capa), `targetId`, `tokenHash`, niveau d’accès (persisté : lecture seule, commentaire, contribution légère), e-mail invité optionnel, expiration (7 ou 30 jours côté API), statuts actif / révoqué / expiré.
- Un nouveau lien **révoque** les partages actifs précédents pour la même ressource (un lien actif à la fois).
- API : `POST /api/collaboration/share`, `POST /api/collaboration/share/{id}/revoke`.
- Vue publique lecture seule : `GET /share/qse/{type}/{token}` — templates dédiés **sans** réutilisation des routes propriétaires ; cohérence `owner` ↔ entité cible vérifiée côté serveur.

### Tableau de bord

- Carte « Collaboration » : invitations en attente, acceptées, liens actifs.
- Jeton CSRF exposé pour les appels JS (`#app-collaboration-csrf` sur le dashboard).

### E-mails transactionnels

Templates Twig (HTML + texte) : invitation collaborateur, accès audit partagé, accès CAPA partagé, invitation acceptée (vers le propriétaire), invitation expirée (invité, si lien expiré côté acceptation).

### UI

- Dialogs Shadcn (`twig:Dialog`, `Button`, `Input`, `Select`, `Label`, `Card`, `Badge`, `Alert`) : invitation et partage sur **dashboard**, **fiche audit**, **fiche CAPA**, **fiche risque** (invitation seule).
- Suggestion douce sur le dashboard (voir ci-dessous).

## Logique de sécurité

- **Ownership** : création invitation / partage et révocation uniquement pour le propriétaire authentifié ; chargement des audits/CAPA via les dépôts existants (`findOneOwnedBy` / `findOneBy` owner).
- **Jetons** : génération cryptographique ; stockage du hash ; comparaison via `hash_equals` indirecte (hash du jeton fourni).
- **Expiration** : invitations et partages ; partage expiré marqué `expire` à la résolution si la date est dépassée.
- **Révocation** : statut `revoque` + `revokedAt` ; accès public refusé (404).
- **Vues invitées** : aucune élévation de session ; pas d’accès aux routes dashboard propriétaires depuis le lien public.
- **CSRF** : `POST /api/collaboration/*` avec jeton `collaboration` (header `X-CSRF-TOKEN` ou champ `csrf_token` JSON).

## Événements détectés (`TrackingEventType`)

Ajouts / usage :

- `AUDIT_COMPLETED` — enregistré lors de l’affichage d’un audit au statut **terminé** ou **validé** (signal métier ; volume modéré selon usage).
- `SHARED_ACCESS_OPENED` — ouverture d’un lien partagé (sans utilisateur connecté dans les métadonnées utilisateur).
- Types réservés pour évolutions : `AUDIT_SHARE_INTENT`, `PREMIUM_COLLABORATION_SIGNAL`, `COLLABORATION_SUGGESTION_SHOWN`, `COLLABORATION_SUGGESTION_DISMISSED` (enum prêt ; persistance suggestion via préférences).

Enrichissement existant :

- `EXPORT_TRIGGERED` sur export JSON audit avec `resource_type: qse_audit`, `resource_id`, `format: json`.

## Suggestions automatiques (`CollaborationSuggestionEngine`)

Règles déterministes (fenêtre 14 jours d’événements + compteurs CAPA) avec **cooldown** (5 jours après affichage, 14 jours après « Masquer ») stocké dans `user_preferences.collaboration_ui_state` :

1. Au moins **3 exports JSON** d’audit → proposer de partager.
2. Au moins **5 ouvertures** de tableau de bord → proposer d’inviter le responsable QSE.
3. Au moins **2 CAPA** ouvertes à criticité **high / critical** → proposer le partage avec le manager.
4. Au moins **1** événement `AUDIT_COMPLETED` sur 10 jours → proposer l’envoi au responsable.

API dismiss : `POST /api/collaboration/suggestion/dismiss`.

## Quick wins futurs

- Vues « commentaire » / « contribution légère » réellement branchées sur stockage et permissions.
- Table `UserCollaborator` ou lien organisationnel si besoin multi-compte.
- Cron : marquer invitations / partages expirés + e-mail « invitation expirée » proactive.
- Rate limiting sur `/share/qse/` et `/collaboration/invitation/accept`.
- Partage ciblé **fiche risque** (hors V1).

## Limites V1 assumées

- Partage invité = **lecture seule** uniquement (niveaux supérieurs stockés pour la suite).
- Rôles d’invitation (lecteur / contributeur / responsable) **sans** différenciation de droits effectifs.
- Pas de fil de commentaires, pas d’édition invité.
- Pas de multi-organisation ni RBAC avancé.

## Fichiers principaux

- Entités : `src/Entity/UserInvitation.php`, `src/Entity/SharedAccess.php`, préférences `collaboration_ui_state`.
- Services : `src/Collaboration/UserInvitationService.php`, `SharedAccessService.php`, `CollaborationSuggestionEngine.php`, `CollaborationToken.php`.
- Contrôleurs : `src/Controller/Collaboration/*`, enrichissements `DashboardController`, `QseAuditController`, `RegistrationController`, `LoginSuccessListener`.
- Migration : `migrations/Version20260512140000.php`.
- Tests : `tests/Functional/CollaborationFunctionalTest.php`.
