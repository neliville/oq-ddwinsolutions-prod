# Mapping import des exigences d’audit (JSON / Excel)

Les lignes sont normalisées par `App\Qse\Import\AuditRequirementRowNormalizer` : aucun texte normatif n’est inventé ; les champs obligatoires doivent être présents dans la source.

## Champs JSON (recommandé)

| Champ | Obligatoire | Description |
|--------|-------------|-------------|
| `legacy_key` | oui | Identifiant stable unique **par référentiel** (avec la contrainte `(audit_standard_id, legacy_key)`). |
| `chapter` | oui | Intitulé de chapitre (affichage / regroupement). |
| `sub_chapter` | non | Sous-chapitre. |
| `article` ou `iso_article` | non* | Référence article ; si vide, stocké comme `—`. |
| `requirement_text` (ou `exigence`) | oui | Texte d’exigence. |
| `iso_comment` | non | Commentaire métier / note. |
| `business_link` | non | URL. |
| `pdca_phase` | non | Une des valeurs : `plan`, `do`, `check`, `act`. |
| `display_order` | non | Entier ; si `0`, l’ordre existant est conservé à la mise à jour. |

\*Le normalisateur impose un texte d’exigence ; l’article peut être vide et est alors remplacé par `—`.

## Racine JSON (CLI `app:qse:import-audit-requirements-json`)

- **Format DDWin (ISO 14001 / 45001)** : `{ "norme": "...", "onglet": "14001"|"45001", "exigences": [{ "article", "paragraphe", "exigence", "commentaire" }] }` — le code référentiel est déduit de `onglet` (`14001` → `iso_14001`, `45001` → `iso_45001`). Les lignes sans `paragraphe`/`article` reprennent le dernier paragraphe connu ; les chapitres affichés sont des libellés du type `4. Contexte de l'organisation`, triés dans l’ordre numérique des clauses (4 … 10).
- Tableau de lignes : `[{...}, {...}]`
- Ou objet avec clé `rows` : `{ "rows": [{...}] }`
- **Format ISO 9001 (chapitres nommés)** : `{ "4. Contexte": [{ "id", "article", "exigence", "commentaire", "numero" }] }` — réservé à `iso_9001` (commande dédiée `app:qse:import-iso9001-requirements` recommandée).

## Excel (admin — première feuille)

- Ligne 1 : en-têtes reconnus après normalisation (casse / accents neutralisés).
- Lignes suivantes : données.

En-têtes reconnus (alias → champ interne) :

| En-tête Excel (exemples) | Champ |
|--------------------------|--------|
| Chapitre | `chapter` |
| Sous-chapitre, Sous_chapitre | `sub_chapter` |
| Article, ISO_article | `article` |
| Exigence, Texte_exigence, requirement_text | `requirement_text` |
| Clé, Clé_stable, Identifiant, legacy_key | `legacy_key` |
| Commentaire, Commentaire_métier | `iso_comment` |
| Lien, Lien_métier | `business_link` |
| PDCA, Phase_PDCA | `pdca_phase` |
| Ordre, display_order | `display_order` |

Après lecture Excel, chaque ligne est un tableau associatif passé au même normalisateur que le JSON.

## Fichiers d’exemple

- `data/fixtures/iso_14001_requirements_sample.json`
- `data/fixtures/iso_45001_requirements_sample.json`

Commande d’import (référentiel par code) :

```bash
# Purge ciblée 14001/45001 (ne touche pas iso_9001) puis import des JSON complets
php bin/console app:qse:purge-audit-requirements --standards=iso_14001,iso_45001 --force
php bin/console app:qse:import-audit-requirements-json data/fixtures/iso_14001.json.json
php bin/console app:qse:import-audit-requirements-json data/fixtures/iso_45001.json.json

php bin/console app:qse:import-audit-requirements-json data/fixtures/iso_14001_requirements_sample.json --standard=iso_14001
php bin/console app:qse:import-audit-requirements-json data/fixtures/iso_45001_requirements_sample.json --standard=iso_45001
```
