# Guide d'implémentation — ReactFlow + Zustand pour le diagramme Ishikawa
> **Version corrigée** — Vérifiée contre le vrai codebase (mai 2026)  
> **Projet :** outils-qualite.com — Symfony 7 / AssetMapper / Stimulus  
> **Objectif :** Route parallèle `/ishikawa-v2` avec éditeur ReactFlow + Zustand pour validation avant migration définitive.  
> **Contrainte absolue :** Le code existant en production ne doit pas être touché.

---

## ⚠️ Corrections critiques par rapport au document original

Le document `Implementation reactflow ishikawa.md` contient plusieurs hypothèses incorrectes par rapport au vrai codebase. Ces corrections **doivent** être appliquées :

| # | Ce que dit le document | Réalité du codebase | Impact |
|---|------------------------|---------------------|--------|
| 1 | `symfony/ux-react` est installé | **PAS installé** — absent de `composer.json`, `bundles.php`, `importmap.php` | Étape 1.1 bloquante avant tout le reste |
| 2 | Entité `Record` avec champ `content` | Entité `IshikawaAnalysis` avec champ **`data`** (TEXT) | Sérialiser/désérialiser différemment |
| 3 | `POST /api/records` + `PUT /api/records/{id}` | `POST /api/ishikawa/save` pour créer ET mettre à jour | Pas de PUT — la présence de `id` dans le payload détecte la mise à jour |
| 4 | `GET /api/records/{id}` | `GET /api/ishikawa/{id}` | URL différente |
| 5 | Payload `{ title, type, content: { nodes, edges, meta } }` | Payload `{ title, content, problem, id? }` — `content` = JSON stringify des nodes+edges, `problem` = champ séparé | Structure JSON à adapter |
| 6 | Réponse API `{ id, title, content, createdAt, updatedAt }` | Réponse API `{ success, message, data: { id, title, createdAt, updatedAt } }` | Lire `data.id` pas `id` |
| 7 | `QuotaService::canCreateRecord($user, 'ishikawa')` | `QuotaService::canSaveNew(User $user): bool` | Méthode inexistante dans l'original |
| 8 | Contrôleur dans `src/Controller/IshikawaV2Controller.php` | Doit être dans **`src/Tools/Controller/IshikawaV2Controller.php`** | Cohérence namespace avec le reste des outils |
| 9 | Route existante `ishikawa_index` | Route existante `app_ishikawa_index` | Nom de route incorrect |
| 10 | Route `pricing` supposée exister | Non vérifiée — à confirmer ou remplacer | Redirection quota à adapter |

---

## Todo — Checklist de mise en place

### Phase 1 — Environnement de build

- [ ] **1.1** `composer require symfony/ux-react` — vérifier que `ReactBundle::class => ['all' => true]` est ajouté dans `config/bundles.php`
- [ ] **1.2** Créer `assets/react-src/build/package.json` (build Babel pour JSX)
- [ ] **1.3** Créer `assets/react-src/build/.babelrc`
- [ ] **1.4** Créer `config/packages/react.yaml` avec `controllers_path`
- [ ] **1.5** Ajouter scripts `build:react` / `watch:react` dans le `package.json` racine
- [ ] **1.6** Ajouter `assets/react-src/build/node_modules/` dans `.gitignore`

### Phase 2 — Dépendances npm

- [ ] **2.1** Ajouter `@xyflow/react`, `react`, `react-dom`, `zustand`, `html-to-image` dans les deps de `assets/react-src/build/package.json`
- [ ] **2.2** `cd assets/react-src/build && npm install`
- [ ] **2.3** Ajouter le CSS ReactFlow dans `importmap.php` (`@xyflow/react/dist/style.css`)

### Phase 3 — Structure des fichiers source JSX

- [ ] **3.1** Créer l'arborescence `assets/react-src/IshikawaEditor/` (voir section Architecture)
- [ ] **3.2** Créer `utils/constants.js`
- [ ] **3.3** Créer `utils/ishikawaLayout.js`
- [ ] **3.4** Créer `utils/ishikawaSerializer.js` **(version corrigée — voir §9)**
- [ ] **3.5** Créer `store/slices/nodesSlice.js`
- [ ] **3.6** Créer `store/slices/edgesSlice.js`
- [ ] **3.7** Créer `store/slices/metaSlice.js`
- [ ] **3.8** Créer `store/slices/uiSlice.js`
- [ ] **3.9** Créer `store/useIshikawaStore.js` **(avec `loadFromRecord` corrigé — voir §8)**

### Phase 4 — Composants React

- [ ] **4.1** Créer `components/nodes/EffectNode.jsx`
- [ ] **4.2** Créer `components/nodes/CategoryNode.jsx`
- [ ] **4.3** Créer `components/nodes/CauseNode.jsx`
- [ ] **4.4** Créer `components/edges/SpineEdge.jsx`
- [ ] **4.5** Créer `components/edges/BoneEdge.jsx`
- [ ] **4.6** Créer `components/panels/ToolbarPanel.jsx`
- [ ] **4.7** Créer `components/panels/PropertiesPanel.jsx`
- [ ] **4.8** Créer `components/panels/MetaPanel.jsx`
- [ ] **4.9** Créer `components/IshikawaCanvas.jsx` (wrapper ReactFlow principal)
- [ ] **4.10** Créer `index.jsx` (point d'entrée du composant React)

### Phase 5 — Symfony

- [ ] **5.1** Créer `src/Tools/Controller/IshikawaV2Controller.php` **(namespace corrigé, QuotaService::canSaveNew)**
- [ ] **5.2** Créer `templates/ishikawa_v2/index.html.twig`

### Phase 6 — Build et validation

- [ ] **6.1** `npm run build` dans `assets/react-src/build/` — vérifier que `assets/react/controllers/IshikawaEditor.js` est créé
- [ ] **6.2** `php bin/console cache:clear`
- [ ] **6.3** `php bin/console debug:router | grep ishikawa` — vérifier les deux routes (`app_ishikawa_index` + `ishikawa_v2_index`)
- [ ] **6.4** Tester `/ishikawa` → doit fonctionner comme avant (non-régression)
- [ ] **6.5** Tester `/ishikawa-v2` → doit afficher le composant React
- [ ] **6.6** Tester la sauvegarde et le rechargement via `?record={id}`

### Phase 7 — Validation fonctionnelle

- [ ] **7.1** Nouveau diagramme avec 5 catégories 5M par défaut
- [ ] **7.2** Double-clic pour éditer les labels (nœuds + catégories)
- [ ] **7.3** Bouton + sur catégorie pour ajouter une cause
- [ ] **7.4** Suppression d'une cause (Delete ou bouton)
- [ ] **7.5** Sauvegarde via `POST /api/ishikawa/save` — vérifier `{ success: true }` en réponse
- [ ] **7.6** Chargement d'un enregistrement existant via `GET /api/ishikawa/{id}`
- [ ] **7.7** Mise à jour (save avec `id` dans le payload → même endpoint POST)
- [ ] **7.8** Export PNG via `html-to-image`
- [ ] **7.9** Vérifier que l'utilisateur FREE est bloqué après quota (via `canSaveNew`)

---

## Architecture des fichiers

```
assets/
├── react/
│   └── controllers/
│       └── IshikawaEditor.js          ← Compilé par Babel (ne pas éditer manuellement)
│
├── react-src/
│   ├── build/
│   │   ├── package.json               ← Babel + deps npm
│   │   └── .babelrc                   ← Preset env + preset react
│   │
│   └── IshikawaEditor/
│       ├── index.jsx                  ← Point d'entrée (props: recordId, apiBase, csrfToken)
│       ├── store/
│       │   ├── useIshikawaStore.js    ← Store Zustand (compose les slices)
│       │   └── slices/
│       │       ├── nodesSlice.js
│       │       ├── edgesSlice.js
│       │       ├── metaSlice.js
│       │       └── uiSlice.js
│       ├── components/
│       │   ├── IshikawaCanvas.jsx     ← <ReactFlow> principal
│       │   ├── nodes/
│       │   │   ├── EffectNode.jsx
│       │   │   ├── CategoryNode.jsx
│       │   │   └── CauseNode.jsx
│       │   ├── edges/
│       │   │   ├── SpineEdge.jsx
│       │   │   └── BoneEdge.jsx
│       │   ├── panels/
│       │   │   ├── ToolbarPanel.jsx
│       │   │   ├── PropertiesPanel.jsx
│       │   │   └── MetaPanel.jsx
│       │   └── controls/
│       │       └── ExportButton.jsx
│       └── utils/
│           ├── constants.js
│           ├── ishikawaLayout.js
│           └── ishikawaSerializer.js  ← ⚠️ Version corrigée requise

src/Tools/Controller/
└── IshikawaV2Controller.php           ← ⚠️ Namespace Tools\Controller, pas Controller\

templates/ishikawa_v2/
└── index.html.twig
```

---

## Étape 1 — Build Babel pour JSX

### 1.2 — `assets/react-src/build/package.json`

```json
{
  "name": "outils-qualite-react-build",
  "private": true,
  "scripts": {
    "build": "babel ../IshikawaEditor --out-dir ../../react/controllers --extensions .jsx,.js --copy-files",
    "watch": "babel ../IshikawaEditor --out-dir ../../react/controllers --extensions .jsx,.js --copy-files --watch"
  },
  "dependencies": {
    "@xyflow/react": "^12.3.0",
    "react": "^18.3.0",
    "react-dom": "^18.3.0",
    "zustand": "^5.0.0",
    "html-to-image": "^1.11.0"
  },
  "devDependencies": {
    "@babel/cli": "^7.23.0",
    "@babel/core": "^7.23.0",
    "@babel/preset-env": "^7.23.0",
    "@babel/preset-react": "^7.23.0"
  }
}
```

### 1.3 — `assets/react-src/build/.babelrc`

```json
{
  "presets": [
    "@babel/preset-env",
    ["@babel/preset-react", { "runtime": "automatic" }]
  ]
}
```

### 1.4 — `config/packages/react.yaml`

```yaml
react:
  controllers_path: '%kernel.project_dir%/assets/react/controllers'
```

### 1.5 — Ajouter dans `package.json` racine

```json
"scripts": {
  "build:react": "cd assets/react-src/build && npm install && npm run build",
  "watch:react": "cd assets/react-src/build && npm install && npm run watch"
}
```

### 1.6 — `.gitignore`

```
assets/react-src/build/node_modules/
```

> Les fichiers compilés `assets/react/controllers/` **doivent être committés** pour le déploiement Azure (pas de re-build en CI).

---

## Étape 2 — CSS ReactFlow dans importmap.php

Ajouter dans `importmap.php` :

```php
'@xyflow/react/dist/style.css' => [
    'version' => '12.3.0',
    'type' => 'css',
],
```

Et dans le template `templates/ishikawa_v2/index.html.twig`, importer ce CSS :

```twig
{% block stylesheets %}
    {{ parent() }}
    {{ importmap('@xyflow/react/dist/style.css') }}
{% endblock %}
```

---

## Étape 5 — Contrôleur Symfony v2 (version corrigée)

Créer `src/Tools/Controller/IshikawaV2Controller.php` :

```php
<?php

namespace App\Tools\Controller;

use App\Lead\Service\QuotaService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Route parallèle /ishikawa-v2 — éditeur ReactFlow en validation.
 * Ne pas modifier IshikawaController existant (src/Tools/Controller/IshikawaController.php).
 */
#[Route('/ishikawa-v2', name: 'app_ishikawa_v2_')]
class IshikawaV2Controller extends AbstractController
{
    public function __construct(
        private readonly QuotaService $quotaService,
    ) {}

    #[Route('', name: 'index', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(Request $request): Response
    {
        $user = $this->getUser();

        // Quota identique à la v1 — méthode réelle : canSaveNew(User)
        // Ne bloquer QUE si c'est un nouveau diagramme (pas de recordId)
        $recordId = $request->query->getInt('record', 0) ?: null;

        if (!$recordId && !$this->quotaService->canSaveNew($user)) {
            $this->addFlash('warning', 'Vous avez atteint votre quota d\'analyses. Passez au plan Starter pour continuer.');
            // TODO: Remplacer 'app_dashboard_index' par la route pricing si elle existe
            return $this->redirectToRoute('app_dashboard_index');
        }

        return $this->render('ishikawa_v2/index.html.twig', [
            'record_id'  => $recordId,
            'page_title' => 'Diagramme Ishikawa (Nouvelle version)',
        ]);
    }
}
```

---

## Étape 6 — Template Twig (version corrigée)

Créer `templates/ishikawa_v2/index.html.twig` :

```twig
{% extends 'base.html.twig' %}

{% block title %}{{ page_title }} — Outils Qualité{% endblock %}

{% block stylesheets %}
    {{ parent() }}
    <style>
        .ishikawa-v2-container { padding: 0; max-width: 100%; }
        .ishikawa-v2-header {
            padding: 10px 20px; background: #1e293b; color: white;
            display: flex; align-items: center; gap: 16px; font-size: 13px;
        }
        .ishikawa-v2-badge {
            background: #fef08a; color: #713f12; font-size: 11px;
            font-weight: 700; padding: 2px 8px; border-radius: 12px;
        }
        .ishikawa-v2-back { color: #7dd3fc; text-decoration: none; }
        .ishikawa-v2-back:hover { text-decoration: underline; }
    </style>
{% endblock %}

{% block body %}
<div class="ishikawa-v2-container">

    <div class="ishikawa-v2-header">
        <a href="{{ path('app_ishikawa_index') }}" class="ishikawa-v2-back">← Version actuelle</a>
        <span>Diagramme Ishikawa</span>
        <span class="ishikawa-v2-badge">NOUVELLE VERSION — Test</span>
        <span style="font-size:12px; color:#94a3b8; margin-left:auto;">
            Validez avant migration définitive
        </span>
    </div>

    {{ react_component('IshikawaEditor', {
        recordId: record_id,
        apiBase: '/api/ishikawa',
        csrfToken: csrf_token('ishikawa_record'),
    }) }}

</div>
{% endblock %}
```

---

## Étape 7 — Sérialiser/Désérialiser (version corrigée)

L'API réelle `/api/ishikawa/save` attend :
```json
{
  "title": "Mon analyse",
  "content": "{\"nodes\":[...],\"edges\":[...]}",
  "problem": "Description du problème",
  "id": 42
}
```

La réponse est :
```json
{
  "success": true,
  "message": "Analyse sauvegardée",
  "data": { "id": 42, "title": "Mon analyse", "createdAt": "...", "updatedAt": "..." }
}
```

Créer `assets/react-src/IshikawaEditor/utils/ishikawaSerializer.js` :

```javascript
/**
 * Convertit l'état du store en payload pour POST /api/ishikawa/save.
 * L'entité IshikawaAnalysis stocke :
 *   - title  (VARCHAR 255)
 *   - problem (TEXT, séparé)
 *   - data   (TEXT JSON — mappe sur le champ "content" du payload API)
 * Le contrôleur API fait : json_encode($data['content']) → entity->setData()
 */
export function serializeToRecord(storeState) {
  const { nodes, edges, meta } = storeState;

  const contentObject = {
    nodes: nodes.map(node => ({
      id:       node.id,
      type:     node.type,
      position: node.position,
      data: {
        label:            node.data.label,
        categoryId:       node.data.categoryId       ?? null,
        color:            node.data.color            ?? null,
        isTop:            node.data.isTop            ?? null,
        parentCategoryId: node.data.parentCategoryId ?? null,
      },
      width:  node.width  ?? null,
      height: node.height ?? null,
    })),
    edges: edges.map(edge => ({
      id:     edge.id,
      source: edge.source,
      target: edge.target,
      type:   edge.type,
      data:   edge.data ?? {},
    })),
    meta: {
      author: meta.author,
      date:   meta.date,
    },
  };

  const payload = {
    title:   meta.title,
    content: contentObject,  // Le contrôleur API json_encode ce champ → entity.data
    problem: meta.problem,   // Champ séparé dans l'entité IshikawaAnalysis
  };

  // Si mise à jour d'un enregistrement existant, inclure l'id
  if (meta.recordId) {
    payload.id = meta.recordId;
  }

  return payload;
}

/**
 * Convertit la réponse GET /api/ishikawa/{id} en état prêt pour loadFromRecord().
 * La réponse JSON contient le champ `data` (JSON string parsé par le contrôleur).
 */
export function deserializeFromRecord(apiResponse) {
  // Le contrôleur retourne le champ `data` déjà décodé (json_decode dans le serializer)
  const content = apiResponse.data ?? {};

  return {
    id:        apiResponse.id,
    title:     apiResponse.title ?? 'Sans titre',
    problem:   apiResponse.problem ?? '',
    createdAt: apiResponse.createdAt,
    updatedAt: apiResponse.updatedAt,
    content: {
      nodes: content.nodes ?? [],
      edges: content.edges ?? [],
      meta:  content.meta  ?? {},
    },
  };
}
```

---

## Étape 8 — Actions API dans le store (version corrigée)

Dans `store/useIshikawaStore.js`, ajouter ces deux actions **après** les spreads de slices :

```javascript
import { serializeToRecord, deserializeFromRecord } from '../utils/ishikawaSerializer.js';

// Dans le create() principal, après les spreads de slices :

saveDiagram: async () => {
  const state = get();
  get().setSaving(true);

  try {
    const payload  = serializeToRecord(state);
    // L'API utilise toujours POST — la présence de payload.id détecte la mise à jour
    const response = await fetch('/api/ishikawa/save', {
      method: 'POST',
      headers: {
        'Content-Type':    'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-Token':    state.meta._csrfToken ?? '',
      },
      body: JSON.stringify(payload),
    });

    if (!response.ok) throw new Error(`HTTP ${response.status}`);

    const json = await response.json();

    if (!json.success) throw new Error(json.message ?? 'Erreur inconnue');

    // La réponse est { success, data: { id, title, createdAt, updatedAt } }
    get().setRecordId(json.data.id);
    get().setSaving(false);
  } catch (err) {
    get().setSaving(false, err.message);
    console.error('Erreur sauvegarde:', err);
  }
},

loadDiagram: async (recordId) => {
  try {
    const response = await fetch(`/api/ishikawa/${recordId}`, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });
    if (!response.ok) throw new Error(`HTTP ${response.status}`);

    const apiResponse = await response.json();
    // La réponse du GET /api/ishikawa/{id} retourne directement l'objet analyse
    const record = deserializeFromRecord(apiResponse);
    get().loadFromRecord(record);
  } catch (err) {
    console.error('Erreur chargement:', err);
  }
},
```

---

## Étape 9 — `loadFromRecord` dans le store (version corrigée)

Dans `store/useIshikawaStore.js`, la fonction `loadFromRecord` doit lire `problem` au niveau racine (pas dans `meta`) car l'entité `IshikawaAnalysis` stocke `problem` comme champ séparé :

```javascript
// Remplacer la version du document par :
loadFromRecord: (record) => {
  const { nodes, edges, meta: contentMeta } = record.content ?? {};
  set({
    nodes: nodes ?? [],
    edges: edges ?? [],
    meta: {
      title:       record.title        ?? 'Sans titre',
      problem:     record.problem      ?? '',   // ← champ racine, pas dans content.meta
      author:      contentMeta?.author ?? '',
      date:        record.createdAt    ?? new Date().toISOString().split('T')[0],
      recordId:    record.id,
      isDirty:     false,
      lastSavedAt: record.updatedAt    ?? null,
      _apiBase:    get().meta?._apiBase,
      _csrfToken:  get().meta?._csrfToken,
    },
  });
},
```

---

## Commandes de build

```bash
# 1. Installer symfony/ux-react
composer require symfony/ux-react

# 2. Installer les deps npm de build
cd assets/react-src/build
npm install

# 3. Build en mode watch pendant le développement
npm run watch

# --- ou ---

# Build de production (une fois)
npm run build

# 4. Post-build Symfony
cd ../../../  # retour à la racine
php bin/console cache:clear

# 5. Vérifier les routes
php bin/console debug:router | grep ishikawa
# Doit afficher : app_ishikawa_index + app_ishikawa_v2_index

# 6. Vérifier la détection du composant React
php bin/console debug:container react.component_renderer
```

---

## Non-régression

Avant chaque déploiement, vérifier que ces routes fonctionnent toujours :

- `GET /ishikawa` → `app_ishikawa_index` (IshikawaController — INTOUCHABLE)
- `POST /api/ishikawa/save` — sauvegarde v1 inchangée
- `GET /api/ishikawa/{id}` — chargement v1 inchangé
- `DELETE /api/ishikawa/{id}` — suppression v1 inchangée

---

## Notes sur la compatibilité des données

- La v1 sauvegarde les données via `IshikawaAnalysis::data` (JSON string) avec la structure interne propre à sa logique canvas (probablement segments/positions spécifiques au canvas HTML5 existant).
- La v2 va écrire dans le **même champ `data`** mais avec une structure ReactFlow (nodes/edges arrays).
- **Les deux formats ne sont pas cross-lisibles par défaut** — les données sauvegardées par la v2 ne s'afficheront pas correctement dans la v1, et vice versa.
- C'est acceptable pendant la phase de validation (route parallèle) mais devra être résolu avant la migration définitive (soit via un format commun, soit en décidant que les anciens enregistrements restent sur la v1).

---

*Guide généré le 09/05/2026 — Vérification croisée avec le vrai codebase de oq-ddwinsolutions-prod*
