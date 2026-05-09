# Spécification de design — Ishikawa v2 (ReactFlow + Zustand)

**Date :** 2026-05-09  
**Projet :** oq-ddwinsolutions-prod (Symfony 7, Asset Mapper, Stimulus, Tailwind)  
**Référence d’implémentation :** `REACTFLOW_IMPLEMENTATION_GUIDE.md` (mai 2026), en intégrant les corrections et clarifications ci-dessous.

---

## 1. Objectif et périmètre

### Objectif

Exposer une route **`/ishikawa-v2`** réservée aux utilisateurs authentifiés, avec un éditeur **ReactFlow** et un état **Zustand**, pour valider la nouvelle expérience **sans modifier** le comportement attendu de la page **`/ishikawa`** (canvas actuel, templates et JS existants).

### Périmètre inclus

- Installation **`symfony/ux-react`**, configuration `config/packages/react.yaml`, entrée CSS **`@xyflow/react/dist/style.css`** dans `importmap.php`.
- Pipeline **Babel** sous `assets/react-src/build/` (deps npm, scripts `build` / `watch`), sortie vers **`assets/react/controllers/`** (fichiers compilés **versionnés**).
- Arborescence sources **`assets/react-src/IshikawaEditor/`** conforme au guide (store en slices, composants nodes/edges/panels, canvas, serializer, layout, constantes).
- Contrôleur page **`App\Tools\Controller\IshikawaV2Controller`** et template **`templates/ishikawa_v2/index.html.twig`**.
- **Quota** : `QuotaService::canSaveNew` pour une **création** (pas de `?record=` dans la query) ; en cas de refus : flash d’avertissement et redirection vers **`app_dashboard_index`** (décision produit validée).
- **Fusion des routes API dupliquées** sous `/api/ishikawa` : un seul jeu de handlers effectifs pour les chemins en conflit, en préservant la sémantique métier et les **noms de routes** utilisés par les templates existants.

### Hors périmètre

- Remplacement de `/ishikawa` par la v2 ou bascule utilisateur automatique.
- Interopérabilité des données **v1 canvas** ↔ **v2 ReactFlow** dans l’interface : les deux formats peuvent coexister en base (même champ `data`) mais ne sont pas affichables de façon interchangeable sans travail ultérieur (comme documenté dans le guide).

---

## 2. Décisions validées (brainstorming)

| Sujet | Décision |
|--------|-----------|
| UI page v2 | **Tailwind** : même structure sémantique que le guide, classes alignées sur `base.html.twig` et les autres outils (pas de bloc `<style>` dupliquant le snippet du guide). |
| API | **Fusion** : éliminer la double définition Symfony pour `POST /save`, `GET /list`, `GET/DELETE /{id}` ; conserver le comportement **invité + ValidToolData** et la logique métier actuelle. |
| Quota (nouveau diagramme sans `record`) | Redirection **`app_dashboard_index`** + flash. |
| Hébergement | **O2switch** ; déploiement après `git push` : **SSH** dans la racine du projet, exécution de **`./deploy.sh`**. |

---

## 3. Architecture technique

### 3.1 Couches Symfony

- **`IshikawaV2Controller`** (`App\Tools\Controller`, préfixe `#[Route('/ishikawa-v2', name: 'app_ishikawa_v2_')]` et action `#[Route('', name: 'index')]` → nom de route Symfony **`app_ishikawa_v2_index`** pour la page v2).
- **`App\Controller\Tool\IshikawaController`** : **contrôleur canonique** pour `/api/ishikawa` après fusion. Il conserve notamment :
  - `POST /save` avec branches **invité** (rate limit, lead, réponse guest) et **utilisateur** (persistance `IshikawaAnalysis`, `json_encode` sur `content` → `data`).
  - `GET /list`, `GET /{id}`, `DELETE /{id}` avec les réponses enveloppées **`{ success, data: … }`** là où elles existent déjà.
- **Action partage** : logique actuelle de **`App\Tools\Api\IshikawaController::share`** déplacée vers le contrôleur Tool (ou service dédié appelé depuis Tool), avec la route nommée **`app_api_ishikawa_share`** inchangée pour **`templates/ishikawa/index.html.twig`**.
- **`App\Tools\Api\IshikawaController`** : **supprimé** ou vidé de toute route qui chevauche le Tool ; aucune paire (méthode, chemin) dupliquée ne doit subsister dans `debug:router`.

### 3.2 Front React

- Composant UX React **`IshikawaEditor`** ; props minimales alignées sur le guide : `recordId`, `apiBase`, `csrfToken` (noms exacts à harmoniser avec l’API Twig `react_component`).
- **`ReactFlowProvider`**, `nodeTypes` / `edgeTypes` **constants** hors arbre de rendu instable (recommandation ReactFlow).
- Store **Zustand** en slices + actions **`saveDiagram`** / **`loadDiagram`** comme dans le guide.

### 3.3 Contrat JSON critique (correction par rapport à un extrait du guide)

- **GET `/api/ishikawa/{id}`** (contrôleur Tool) : corps de réponse de la forme  
  `{ "success": true, "data": { "id", "title", "problem", "content", "createdAt", … } }`.  
  Le client doit parser **`response.data`** (objet analyse), puis utiliser **`data.content`** comme charge utile diagramme (structure v1 ou v2 selon l’enregistrement).
- **`loadFromRecord` / `deserializeFromRecord`** : ne pas supposer que l’objet racine du `fetch` est directement l’analyse ; normaliser en extrayant d’abord **`payload.data`** quand présent.
- **POST `/api/ishikawa/save`** : réponse `{ success, message, data: { id, title, createdAt, updatedAt } }` ; après succès, mettre à jour le **`recordId`** depuis **`json.data.id`**.

### 3.4 Serializer (payload)

- Alignement sur le contrôleur existant : `title`, `content` (objet ou structure acceptée puis `json_encode` vers l’entité), `problem`, `id` optionnel pour mise à jour.
- Le guide décrit le mapping `meta` ↔ champs ; l’implémentation doit respecter **`IshikawaAnalysis`** (`setData`, `setProblem`, etc.) sans modifier le contrat HTTP existant sauf nécessité prouvée et testée.

---

## 4. Sécurité et CSRF

- **`/ishikawa-v2`** : **`ROLE_USER`** uniquement.
- **CSRF** : jeton intention **`ishikawa_record`** (comme dans le guide) passé au React ; en-tête côté `fetch` aligné sur la configuration **Symfony** et les patterns du projet pour les requêtes JSON state-changing.
- **API après fusion** : ne pas affaiblir les garde-fous existants (invité, propriété des analyses, codes HTTP).

---

## 5. Déploiement O2switch (`deploy.sh`)

- Le fichier **`deploy.sh`** à la racine du dépôt est le **script officiel** post-push : exécution **sur le serveur** en SSH dans le répertoire du projet.
- Étapes pertinentes pour cette fonctionnalité : `git pull origin main`, `composer install` (prod, `--no-scripts`), autoloader optimisé, `cache:clear` / `cache:warmup`, migrations Doctrine, **`sass:build`**, **`importmap:install`**, **`asset-map:compile`**.
- Le script **n’exécute pas** Node/npm ni de **`build:react`**. Les artefacts **`assets/react/controllers/*.js`** produits par Babel doivent être **générés avant le commit** (machine de développement ou CI), puis suivis par Git pour que le déploiement O2switch reste fiable.
- Nouvelles deps Composer (ex. `symfony/ux-react`) : prises en charge au `composer install` du script.
- CSS `@xyflow/react` : déclaré dans `importmap.php`, installé via `importmap:install`, compilé via `asset-map:compile`.

Évolution optionnelle ultérieure : ajouter une étape Node dans `deploy.sh` si un binaire Node stable est garanti sur l’hébergement — non requis par cette spec.

---

## 6. Tests et non-régression

- Reprendre les points des **phases 6 à 7** du `REACTFLOW_IMPLEMENTATION_GUIDE.md` (routes, build, cache, scénarios v2).
- **Non-régression v1** : `/ishikawa`, sauvegarde invité et connecté, liste, chargement `?load=`, suppression, **partage** (`app_api_ishikawa_share`).
- **`php bin/console debug:router | grep ishikawa`** : une seule route par combinaison méthode + chemin pour les endpoints fusionnés (`save`, `list`, `GET/DELETE` sur `{id}`, `share`).

---

## 7. Références

- `REACTFLOW_IMPLEMENTATION_GUIDE.md` — checklist phases, arborescence fichiers, extraits de code à adapter (serializer, store, Twig `react_component`).
- `deploy.sh` — procédure de déploiement O2switch décrite en section 5.

---

## 8. Prochaine étape (hors spec)

Après validation de ce document par le demandeur : invoquer la compétence **writing-plans** pour produire le plan d’implémentation pas à pas, puis implémenter.
