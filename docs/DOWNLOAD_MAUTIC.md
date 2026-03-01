# Téléchargement de ressources avec formulaire Mautic

Les téléchargements de ressources (ex. Modèle 5M) nécessitent que l’utilisateur remplisse un formulaire avant d’accéder au fichier. Les données sont envoyées à Mautic via soumission de formulaire.

## Configuration Mautic

1. Créez un formulaire dans Mautic avec les champs :
   - **Email** (alias : `email`, type email)
   - **First Name** (alias : `firstname`, type texte)
   - **Ressource** (alias : `ressource_id`, type texte, généralement prérempli côté serveur)
   - Champ caché **download_request_id** (pour le webhook n8n)

2. Récupérez l’**ID du formulaire** dans l’URL Mautic :  
   `https://mautic.outils-qualite.com/s/forms/edit/XX` → `XX` est l’ID.

3. Ajoutez-le dans `.env` :
   ```env
   MAUTIC_FORM_DOWNLOAD_5M_ID=XX
   ```

## Flux utilisateur

1. L’utilisateur ouvre la page de téléchargement.
2. Il remplit le formulaire (email obligatoire, prénom optionnel).
3. Au clic sur « Télécharger », les données sont envoyées à l’API Symfony.
4. L’API transmet les données au formulaire Mautic (`POST /form/submit?formId=XX`).
5. En cas de succès, le téléchargement est lancé et l’utilisateur est redirigé vers la page de remerciement.

## Fichiers

Les fichiers sont dans `var/downloads/` (ex. `modele-5m.pdf`). Créez le dossier et placez le fichier.

## Endpoints

- `POST /api/download/create-from-mautic` : utilisé par n8n après réception du webhook Mautic.
  - Header : `X-Api-Key: <DOWNLOAD_AUTHORIZE_API_KEY>`
  - Body : `{ "email": "...", "ressource_id": "modele-5m" }`
  - Réponse : `{ "success": true, "download_url": "...", "token": "..." }`

## Aliases Mautic

Les champs envoyés correspondent aux alias configurés dans Mautic :

| Alias                | Exemple valeur | Description                    |
|----------------------|----------------|--------------------------------|
| `email`              | user@example.com | Email (requis)                 |
| `firstname`          | Jean           | Prénom                         |
| `ressource_id`       | modele-5m      | Slug de la ressource           |
| `ressource_id`       | modele-5m      | Slug (champ caché, valeur par défaut) |
