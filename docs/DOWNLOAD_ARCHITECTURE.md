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

## Configuration n8n

Appeler POST /api/download/authorize avec download_request_id du webhook Mautic.
