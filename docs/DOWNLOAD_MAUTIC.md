# Téléchargement de ressources avec formulaire Mautic

Les téléchargements de ressources (ex. Modèle 5M) nécessitent que l’utilisateur remplisse un formulaire avant d’accéder au fichier. Les données sont envoyées à Mautic via soumission de formulaire.

## Configuration Mautic

1. Créez un formulaire dans Mautic avec les champs :
   - **Votre email** (alias : `votre_email`, type email)
   - **Votre prénom** (alias : `votre_prenom`, type texte)
   - **Ressource** (alias : `ressource`, type texte, généralement prérempli côté serveur)

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

## Endpoints

- `POST /api/download/modele-5m/request` : demande de téléchargement du Modèle 5M.
  - Body JSON : `{ "email": "...", "firstname": "..." }`
  - Réponse : `{ "success": true, "downloadUrl": "..." }` ou erreur.

## Aliases Mautic

Les champs envoyés doivent correspondre aux alias configurés dans Mautic :

| Alias        | Exemple valeur | Description     |
|-------------|----------------|-----------------|
| `votre_email`   | user@example.com | Email (requis)  |
| `votre_prenom`  | Jean             | Prénom          |
| `ressource`     | Modèle 5M        | Nom de la ressource |
