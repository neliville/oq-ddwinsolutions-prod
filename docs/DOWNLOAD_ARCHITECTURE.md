# Annexe — Intégration Symfony / n8n pour téléchargements sécurisés

> **Statut** : documentation **historique / optionnelle**. Le parcours **officiel** pour le modèle 5M (Ishikawa) est décrit dans [DOWNLOAD_MAUTIC.md](DOWNLOAD_MAUTIC.md) : **téléchargement entièrement géré dans Mautic** via le formulaire embarqué.

Cette page décrit l’architecture **event-driven** encore présente dans le code pour les cas où Symfony **sert** le fichier (répertoire `var/downloads/`), génère des **tokens** ou est appelé par **n8n** après un webhook Mautic.

---

## Vue d’ensemble (flux optionnel)

- **Symfony** : gatekeeper (validation, `DownloadRequest`, génération de liens / tokens, streaming fichier).
- **Mautic** : capture marketing et webhooks.
- **n8n** : orchestration (appel des API Symfony après webhook).
- Aucun appel direct Symfony → n8n.

---

## Flux possible (non requis si Mautic livre seul la ressource)

1. Utilisateur → `POST /api/download/modele-5m/request` → Symfony crée un `DownloadRequest` (UUID) → envoi vers Mautic via API REST.
2. Webhook Mautic → n8n.
3. n8n → `POST /api/download/authorize` (en-tête `X-Api-Key`) → Symfony génère un token.
4. n8n envoie un email avec un lien `GET /download/access/{token}`.
5. Clic utilisateur → Symfony valide le token → envoi du fichier depuis `var/downloads/`.

**Flux alternatif** : formulaire Mautic embarqué → webhook → n8n → `POST /api/download/create-from-mautic` → réponse avec `download_url` (voir implémentation dans `ProcessWebhookDownloadRequest`).

---

## Fichiers côté Symfony (flux optionnel)

Les fichiers servis par Symfony sont référencés dans `ResourceRegistry` (`src/Download/Infrastructure/Resource/ResourceRegistry.php`), typiquement sous **`var/downloads/`** (hors webroot).

---

## Champs Mautic utiles pour l’intégration avancée

- `ressource_id` (slug de la ressource)
- `download_request_id` (UUID côté Symfony après création de la demande)

---

## Configuration n8n (si utilisé)

1. Déclencher sur le webhook Mautic (`mautic.lead_post_save_new` / `update`).
2. Appeler `POST /api/download/create-from-mautic` avec le corps du webhook (ou champs extraits), en-tête `X-Api-Key: <DOWNLOAD_AUTHORIZE_API_KEY>`.
3. Utiliser `download_url` renvoyée pour l’email.

Variables d’environnement associées : `DOWNLOAD_AUTHORIZE_API_KEY`, `DOWNLOAD_SECRET`, chemins `DOWNLOAD_BASE_PATH` selon le déploiement.

---

Pour le **modèle 5M en production** avec ressource **déjà dans Mautic**, se référer à **[DOWNLOAD_MAUTIC.md](DOWNLOAD_MAUTIC.md)**.
