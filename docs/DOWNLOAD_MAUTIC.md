# Téléchargement du modèle 5M (Ishikawa) — flux Mautic

## Principe retenu

Le **téléchargement de la ressource** (fichier, email de confirmation, page de remerciement éventuelle) est **entièrement géré dans Mautic** : la ressource y est **hébergée et distribuée** (formulaire avec action « télécharger l’asset », campagne, message post-soumission, etc.).

**Symfony ne sert pas le fichier** pour ce parcours : il fournit uniquement une **page vitrine** avec **embed du formulaire Mautic** (`form/generate.js`).

---

## Rôle de Symfony

| Élément | Rôle |
|--------|------|
| Route `GET /telechargement-modele-5m` | Affiche la page et charge le script d’embed Mautic. |
| `Modele5MController` | Passe l’URL `…/form/generate.js?id=<ID>` et un `ressourceId` optionnel pour le JS. |
| Template `templates/site/telechargement_modele_5m.html.twig` | Conteneur `#mautic-form-container` + script d’injection optionnelle du champ caché `ressource_id`. |

Aucune obligation d’utiliser les API `POST /api/download/*` ni n8n pour ce flux.

---

## Configuration côté Symfony (`.env`)

```env
# URL de l’instance Mautic (sans slash final)
MAUTIC_URL=https://mautic.outils-qualite.com

# ID du formulaire Mautic dédié au téléchargement modèle 5M (voir Forms dans Mautic)
MAUTIC_FORM_DOWNLOAD_5M_ID=3
```

Le script chargé sur la page est :  
`{MAUTIC_URL}/form/generate.js?id={MAUTIC_FORM_DOWNLOAD_5M_ID}`.

Les variables **`MAUTIC_USER` / `MAUTIC_PASSWORD`** (API REST) restent utiles pour d’autres fonctionnalités (newsletter, sync contacts, etc.), **pas** pour afficher l’embed du formulaire de téléchargement.

---

## Configuration côté Mautic (à faire dans l’admin Mautic)

1. **Formulaire** : créer ou utiliser le formulaire dont l’ID correspond à `MAUTIC_FORM_DOWNLOAD_5M_ID`.
2. **Ressource** : attacher le fichier / configurer l’action de téléchargement **dans Mautic** (comportement du formulaire après soumission, asset, email avec lien, etc.).
3. **Champs** : email (obligatoire), prénom si besoin — selon votre scénario marketing.
4. **Optionnel — traçabilité** : champ caché avec alias `ressource_id` et valeur par défaut `ishikawa-5m-template` si vous segmentez ou synchronisez encore des contacts sur ce slug. La page Symfony tente d’injecter ce champ caché dans le DOM si le formulaire généré ne l’expose pas (voir template).

---

## Page concernée

- **URL** : `/telechargement-modele-5m`
- **Lien depuis l’outil** : la page Ishikawa pointe vers cette route (`templates/ishikawa/index.html.twig`).

---

## CORS et environnement local

En **local** (`127.0.0.1`), le navigateur peut bloquer le chargement de `generate.js` depuis un autre domaine (CORS). En **production**, le domaine du site doit être autorisé côté Mautic pour l’embed. Voir la configuration Mautic (domaines autorisés / CORS).

---

## Route legacy : PDF direct (hors Mautic)

La route **`GET /telechargement-modele-5m/fichier`** sert un fichier **`public/downloads/modele-5m.pdf`** s’il est présent. Ce n’est **pas** le flux principal documenté ici ; le parcours de référence est **uniquement** l’embed Mautic ci-dessus.

---

## Annexe — Intégration avancée Symfony / n8n (optionnelle)

Des endpoints (`POST /api/download/create-from-mautic`, `POST /api/download/modele-5m/request`, tokens, fichiers sous `var/downloads/`) existent encore dans le code pour des scénarios où **Symfony** livrerait le fichier ou orchestrerait des liens signés avec **n8n**. Ce n’est **pas** nécessaire si la ressource et le téléchargement sont **100 % dans Mautic**.

Détails techniques de cette intégration avancée : [DOWNLOAD_ARCHITECTURE.md](DOWNLOAD_ARCHITECTURE.md).
