# Spécification de design — Ishikawa v2 (ReactFlow + Zustand)

**Date :** 2026-05-09  
**Projet :** oq-ddwinsolutions-prod (Symfony 7, Asset Mapper, Stimulus, Tailwind)  
**Référence d’implémentation :** `REACTFLOW_IMPLEMENTATION_GUIDE.md` (mai 2026), en intégrant les corrections et clarifications ci-dessous.

---

## 1. Objectif et périmètre

### Objectif

Exposer une route **`/ishikawa-v2`** réservée aux utilisateurs authentifiés, avec un éditeur **ReactFlow** et un état **Zustand**, pour valider la nouvelle expérience **sans modifier** le comportement attendu de la page **`/ishikawa`** (canvas actuel, templates et JS existants).

### Périmètre inclus

- Installation **`symfony/ux-react`**, configuration `config/packages/react.yaml` ; styles React Flow : **`assets/styles/ishikawa-reactflow.css`** (copie versionnée du thème `@xyflow/react`, servie par l’Asset Mapper comme tout CSS sous `assets/styles/`).
- **Exécution** : uniquement **Asset Mapper + `importmap.php`** (React, `react-dom/client`, `@xyflow/react`, Zustand, `html-to-image`, etc.) ; le composant UX est **`assets/react/controllers/IshikawaEditor.js`** (ES module déjà bundlé, enregistré comme les autres contrôleurs React).
- **Regénération du bundle (optionnelle)** : sources **`assets/react-src/IshikawaEditor/`** + **`npm run build`** dans **`assets/react-src/build/`** (esbuild) — à lancer **en CI**, sur un poste qui a Node, ou avant commit quand on touche au JSX ; **pas** nécessaire sur le serveur O2switch si le `.js` à jour est **versionné** (`deploy.sh` fait déjà `asset-map:compile`).
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
- **`composer install`** est lancé en prod avec **`--no-scripts`** : les scripts Composer (dont le script `compile-assets` qui enchaîne Tailwind + Asset Mapper) **ne s’exécutent pas** sur le serveur. Le script **`deploy.sh`** doit donc rejouer explicitement la chaîne d’assets attendue, dans le **même ordre** qu’en local / CI (`composer.json` → `compile-assets`).

### Ordre des étapes assets (dans `deploy.sh`)

1. **`sass:build`** (si la commande existe, ex. **symfonycasts/sass-bundle** installé) — sinon l’étape est **ignorée** pour ne pas bloquer les dépôts entièrement passés au CSS Tailwind.
2. **`tailwind:build --minify`** (**obligatoire** si le bundle Tailwind est présent) — régénère le CSS agrégé vers `var/tailwind/`. **Sans cette étape**, le rendu en prod peut diverger fortement du local.
3. **`importmap:install`** — paquets JS déclarés dans `importmap.php` (vendor) ; **échec bloquant** si la commande retourne une erreur.
4. **`asset-map:compile`** — Asset Mapper (fingerprints, manifest) ; **toujours après** Tailwind ; code de sortie Symfony vérifié (pas celui de `grep` sur la sortie).

Les étapes **Git**, **Composer** (`--no-dev`, `--no-scripts`), **autoloader**, **cache**, **migrations** restent celles déjà présentes dans `deploy.sh` avant cette séquence.

### React / Babel (inchangé)

- Le script **n’exécute pas** Node/npm ni **`build:react`**. Les artefacts **`assets/react/controllers/*.js`** produits par Babel doivent être **générés avant le commit** (machine de développement ou CI), puis versionnés dans Git pour que le déploiement O2switch reste fiable.
- Nouvelles deps Composer (ex. `symfony/ux-react`) : prises en charge au `composer install` du script.

### CSS `@xyflow/react`

- Déclaré dans **`importmap.php`**, installé via **`importmap:install`**, pris en compte lors de **`asset-map:compile`** (après Tailwind comme le reste des entrées Asset Mapper).

### Évolution optionnelle

- Ajouter une étape Node dans `deploy.sh` si un binaire Node stable est garanti sur l’hébergement (build React sur le serveur) — **non requis** par cette spec ; le build Babel reste hors serveur.

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
