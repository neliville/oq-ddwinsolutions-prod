# Fichiers dans `public/downloads/`

## Parcours principal (modèle 5M / Ishikawa)

Le téléchargement du modèle pour les utilisateurs est documenté dans **[docs/DOWNLOAD_MAUTIC.md](../../docs/DOWNLOAD_MAUTIC.md)** : **formulaire Mautic embarqué** sur `/telechargement-modele-5m` ; la ressource est **gérée dans Mautic**.

Ce dossier `public/downloads/` n’est **pas** requis pour ce flux.

---

## Route optionnelle : PDF direct

Si le fichier **`modele-5m.pdf`** est présent ici, la route legacy **`GET /telechargement-modele-5m/fichier`** peut le proposer en téléchargement direct (hors parcours Mautic). Voir [docs/DOWNLOAD_MAUTIC.md](../../docs/DOWNLOAD_MAUTIC.md) (section « Route legacy »).

Nom attendu : `modele-5m.pdf`
