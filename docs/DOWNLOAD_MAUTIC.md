# Téléchargement de ressources avec Mautic CRM

Les téléchargements de ressources (ex. Modèle 5M) nécessitent que l’utilisateur remplisse un formulaire. **Symfony est l’autorité backend** : les contacts sont créés/mis à jour dans Mautic via l’**API REST** (`POST /api/contacts/new`), pas via soumission de formulaire Mautic.

## Configuration Mautic (API REST)

1. Dans Mautic, créez des champs de contact avec les alias suivants (s’ils n’existent pas) :
   - `email`, `firstname`, `download_request_id`, `ressource_id`

2. Dans `.env`, configurez l’accès API :
   ```env
   MAUTIC_BASE_URL=https://mautic.outils-qualite.com
   MAUTIC_USERNAME=api_symfony
   MAUTIC_PASSWORD=votre_mot_de_passe
   ```

3. (Optionnel) Pour l’embed de formulaire sur la page : `MAUTIC_FORM_DOWNLOAD_5M_ID=XX` et `MAUTIC_URL` pour le script generate.js.

## Flux utilisateur

1. L’utilisateur ouvre la page de téléchargement.
2. Il remplit le formulaire (email obligatoire, prénom optionnel).
3. Au clic sur « Télécharger », les données sont envoyées à l’API Symfony.
4. L’API crée/met à jour le contact dans Mautic via l’API REST (`POST /api/contacts/new`). Aucun formulaire Mautic n’est soumis.
5. En cas de succès, le téléchargement est lancé et l’utilisateur est redirigé vers la page de remerciement.

## Fichiers

La ressource de la page 5M est le fichier `var/downloads/Ishikawa_5M_Template_Outil-Qualite.xlsx`. Créez le dossier `var/downloads/` et placez le fichier.

## Endpoints

- `POST /api/download/create-from-mautic` : utilisé par n8n après réception du webhook Mautic.
  - Header : `X-Api-Key: <DOWNLOAD_AUTHORIZE_API_KEY>`
  - Body : `{ "email": "...", "ressource_id": "ishikawa-5m-template" }`
  - Réponse : `{ "success": true, "download_url": "...", "token": "..." }`

## Pourquoi `ressource_id` et `download_request_id` sont null dans le webhook

Le webhook Mautic est déclenché **à la soumission du formulaire**, avant tout appel à Symfony.

- **`download_request_id`** : n’existe pas encore à ce moment (il est créé quand n8n appelle `POST /api/download/create-from-mautic`). Donc il sera **toujours null** dans le body du webhook. **À utiliser côté n8n** : récupérer `download_request_id` (et `download_url`, `ressource_id`) dans la **réponse** de l’appel à `create-from-mautic`, pas dans le webhook.
- **`ressource_id`** : si le formulaire Mautic est chargé en iframe, le champ caché injecté par notre page n’est pas soumis, donc Mautic reçoit null. **Solution** : dans Mautic, sur le formulaire de téléchargement 5M, ajouter un champ caché « Ressource » (alias `ressource_id`) et définir sa **valeur par défaut** à `ishikawa-5m-template`. Ainsi chaque soumission enverra ce slug et le webhook contiendra `ressource_id`.

Après l’appel à `create-from-mautic`, Symfony met à jour le contact dans Mautic via l’API REST avec `download_request_id` et `ressource_id`, donc le contact Mautic sera à jour pour la suite.

## Workflow n8n recommandé

1. Déclencher sur le webhook Mautic (body avec `mautic.lead_post_save_new` ou `mautic.lead_post_save_update`).
2. Appeler `POST /api/download/create-from-mautic` avec le **body entier** du webhook (ou `body` si n8n enveloppe dans `{ body: ... }`). Header : `X-Api-Key: <DOWNLOAD_AUTHORIZE_API_KEY>`.
3. Utiliser la **réponse** de cet appel pour :
   - `download_url` → lien à envoyer par email ;
   - `download_request_id` → identifiant de la demande ;
   - `ressource_id` → déjà connu (par défaut `ishikawa-5m-template` si absent du webhook).

**URL du lien de téléchargement** : utiliser la forme suivante (et non plus `download.php`) :

```
https://outils-qualite.com/ressources/download?file=...&expires=...&token=...
```

- `file` : chemin relatif sous `DOWNLOAD_BASE_PATH` (ex. `templates/Ishikawa_5M_Template_Outil-Qualite.xlsx`)
- `expires` : timestamp Unix d’expiration (ex. `time() + 86400` pour 24 h)
- `token` : `hash_hmac('sha256', file . expires, DOWNLOAD_SECRET)` (même secret que dans `.env`)

L’API retourne directement `download_url` avec un token valide ; n8n doit utiliser cette URL sans la reconstruire.

Ne pas utiliser les champs `contact.fields.core.download_request_id` ou `ressource_id` du webhook pour le flux téléchargement : ils peuvent être null.

## Aliases Mautic

Les champs envoyés par Symfony (API REST) correspondent aux alias configurés dans Mautic :

| Alias                  | Exemple valeur       | Description                                                                 |
|------------------------|----------------------|-----------------------------------------------------------------------------|
| `email`                | user@example.com     | Email (requis)                                                             |
| `firstname`            | Jean                 | Prénom                                                                     |
| `ressource_id`         | ishikawa-5m-template | Slug de la ressource (fichier var/downloads/Ishikawa_5M_Template_Outil-Qualite.xlsx) |
| `download_request_id` | UUID                 | ID de la demande ; renseigné par Symfony après création, pas par le formulaire. |
