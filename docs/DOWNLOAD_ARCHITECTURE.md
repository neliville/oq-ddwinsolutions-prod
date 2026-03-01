# Architecture event-driven des téléchargements

## Vue d'ensemble

- Symfony = gatekeeper (validation + sécurité)
- Mautic = source de vérité marketing
- n8n = moteur d'automatisation
- Aucun appel direct Symfony vers n8n

## Flux

1. User → POST /api/download/modele-5m/request → Symfony crée DownloadRequest (UUID) → envoie à Mautic
2. Mautic webhook → n8n
3. n8n → POST /api/download/authorize (X-Api-Key) → Symfony génère token
4. n8n envoie email avec lien GET /download/access/{token}
5. User clique → Symfony valide token → stream fichier

## Champs Mautic à ajouter (cachés)

- ressource_id (slug de la ressource)
- download_request_id

## Emplacement des fichiers

Les fichiers sont stockés dans `var/downloads/` (hors du dossier public). Créez ce répertoire si nécessaire et placez `modele-5m.pdf` dedans.

## Configuration n8n

**Flux formulaire Mautic intégré (script generate.js)** :
1. L'utilisateur remplit le formulaire Mautic (soumission directe vers Mautic)
2. Webhook Mautic → n8n
3. n8n appelle `POST /api/download/create-from-mautic` avec `email`, `ressource_id` (X-Api-Key requis)
4. Symfony crée la demande, génère le token, retourne `download_url`
5. n8n envoie l'email avec le lien

**Flux API Symfony (formulaire custom)** :
- n8n appelle `POST /api/download/authorize` avec `download_request_id` du webhook Mautic.
