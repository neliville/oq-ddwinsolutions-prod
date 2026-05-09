# Plan d'implémentation — ReactFlow + Zustand pour le diagramme Ishikawa
> **Projet :** outils-qualite.com — Application Symfony 7 / AssetMapper / Stimulus  
> **Objectif :** Remplacer le canvas Ishikawa existant par une version ReactFlow + Zustand, accessible via une **route parallèle** `/ishikawa-v2` pour validation avant migration définitive.  
> **Contrainte absolue :** Le code existant en production ne doit pas être touché pendant la phase de validation.

---

## Table des matières

1. [Vue d'ensemble de la stratégie](#1-vue-densemble-de-la-stratégie)
2. [Analyse de l'existant à respecter](#2-analyse-de-lexistant-à-respecter)
3. [Stack technique retenue](#3-stack-technique-retenue)
4. [Architecture des fichiers à créer](#4-architecture-des-fichiers-à-créer)
5. [Étape 1 — Setup de l'environnement de build React](#étape-1--setup-de-lenvironnement-de-build-react)
6. [Étape 2 — Installation des dépendances](#étape-2--installation-des-dépendances)
7. [Étape 3 — Création du store Zustand](#étape-3--création-du-store-zustand)
8. [Étape 4 — Création des composants React](#étape-4--création-des-composants-react)
9. [Étape 5 — Logique métier Ishikawa](#étape-5--logique-métier-ishikawa)
10. [Étape 6 — Création du Controller Symfony + Route v2](#étape-6--création-du-controller-symfony--route-v2)
11. [Étape 7 — Template Twig d'accueil du composant](#étape-7--template-twig-daccueil-du-composant)
12. [Étape 8 — Bridge Symfony → React (données initiales)](#étape-8--bridge-symfony--react-données-initiales)
13. [Étape 9 — Sauvegarde et chargement API](#étape-9--sauvegarde-et-chargement-api)
14. [Étape 10 — Compilation et déploiement](#étape-10--compilation-et-déploiement)
15. [Étape 11 — Critères de validation pour migration définitive](#étape-11--critères-de-validation-pour-migration-définitive)
16. [Référence des patterns ReactFlow + Zustand](#16-référence-des-patterns-reactflow--zustand)
17. [Checklist de non-régression](#17-checklist-de-non-régression)

---

## 1. Vue d'ensemble de la stratégie

### Principe de coexistence
```
Route existante  : /ishikawa        → IshikawaController::index()    [INTOUCHABLE]
Route nouvelle   : /ishikawa-v2     → IshikawaV2Controller::index()  [NOUVELLE]
```

Les deux routes coexistent indéfiniment jusqu'à validation. Aucun fichier existant n'est modifié. La décision de migration (remplacement de la route `/ishikawa` par la v2) est prise **après validation humaine**.

### Flux de validation
```
Développement v2 → Tests locaux → Déploiement prod (route /ishikawa-v2) 
→ Tests utilisateurs réels → Décision : migrer ou abandonner
```

---

## 2. Analyse de l'existant à respecter

### Entité `Record` (Doctrine — ne pas modifier)
```
src/Entity/Record.php
```
L'entité `Record` stocke les analyses. La v2 **réutilise exactement les mêmes endpoints API** que la v1 pour les opérations CRUD. La structure JSON du contenu (champ `content` ou équivalent) doit rester identique entre v1 et v2 pour permettre l'interopérabilité des données sauvegardées.

### Endpoints API existants à réutiliser (ne pas dupliquer)
```
POST   /api/records           → Créer un enregistrement
GET    /api/records/{id}      → Charger un enregistrement
PUT    /api/records/{id}      → Mettre à jour un enregistrement
DELETE /api/records/{id}      → Supprimer un enregistrement
```
> **⚠️ Cursor : ne pas créer de nouveaux endpoints API.** La v2 consomme les mêmes routes que la v1.

### Entité `User` et système de quotas (ne pas modifier)
Le service `QuotaService` et la logique freemium (plan `FREE`/`STARTER`) s'appliquent identiquement à la v2. Le `IshikawaV2Controller` doit appeler `QuotaService::checkQuota()` de la même façon que le contrôleur v1 existant.

---

## 3. Stack technique retenue

| Composant | Package | Version cible | Raison |
|-----------|---------|---------------|--------|
| Diagramme interactif | `@xyflow/react` | `^12.x` | MIT, renommage de `reactflow` en v12 |
| State management | `zustand` | `^5.x` | Utilisé en interne par ReactFlow, pattern slices officiel |
| React | `react` + `react-dom` | `^18.x` | Stable, compatibil avec @xyflow/react |
| Transpilation JSX | `@babel/core` + `@babel/preset-react` | `^7.x` | Requis par Symfony AssetMapper pour JSX |
| Intégration Symfony | `symfony/ux-react` | `^2.x` | Pont officiel Symfony ↔ React avec AssetMapper |
| Export SVG/PDF | `html-to-image` | `^1.x` | Export côté client sans serveur |

---

## 4. Architecture des fichiers à créer

```
assets/
├── react/
│   └── controllers/
│       └── IshikawaEditor.jsx          ← Composant racine (UX React controller)
│
├── react-src/                          ← Sources JSX à compiler
│   ├── IshikawaEditor/
│   │   ├── index.jsx                   ← Point d'entrée du composant
│   │   ├── store/
│   │   │   ├── useIshikawaStore.js     ← Store Zustand principal
│   │   │   ├── slices/
│   │   │   │   ├── nodesSlice.js       ← Slice nœuds ReactFlow
│   │   │   │   ├── edgesSlice.js       ← Slice arêtes ReactFlow
│   │   │   │   ├── metaSlice.js        ← Slice métadonnées (titre, problème, auteur)
│   │   │   │   └── uiSlice.js          ← Slice état UI (panel sélection, zoom, mode édition)
│   │   │   └── selectors.js            ← Sélecteurs mémoïsés
│   │   ├── components/
│   │   │   ├── IshikawaCanvas.jsx      ← Wrapper ReactFlow principal
│   │   │   ├── nodes/
│   │   │   │   ├── EffectNode.jsx      ← Nœud "problème principal" (tête du poisson)
│   │   │   │   ├── CategoryNode.jsx    ← Nœud catégorie 5M/6M (arête principale)
│   │   │   │   └── CauseNode.jsx       ← Nœud cause (arête secondaire/tertiaire)
│   │   │   ├── edges/
│   │   │   │   ├── SpineEdge.jsx       ← Arête épine dorsale (horizontale)
│   │   │   │   └── BoneEdge.jsx        ← Arête arête diagonale
│   │   │   ├── panels/
│   │   │   │   ├── ToolbarPanel.jsx    ← Barre d'outils (ajouter catégorie, cause, export)
│   │   │   │   ├── PropertiesPanel.jsx ← Panneau propriétés du nœud sélectionné
│   │   │   │   └── MetaPanel.jsx       ← Édition titre/problème/auteur/date
│   │   │   └── controls/
│   │   │       ├── CategoryPicker.jsx  ← Sélecteur des 6 catégories 5M/6M
│   │   │       └── ExportButton.jsx    ← Bouton export PNG/PDF
│   │   └── utils/
│   │       ├── ishikawaLayout.js       ← Algorithme de positionnement automatique
│   │       ├── ishikawaSerializer.js   ← Sérialisation/désérialisation JSON ↔ Record
│   │       └── constants.js            ← Constantes (catégories 5M, couleurs, dimensions)
│
src/
├── Controller/
│   └── IshikawaV2Controller.php        ← Nouveau contrôleur (route /ishikawa-v2)
│
templates/
├── ishikawa_v2/
│   └── index.html.twig                 ← Template de la nouvelle route
│
assets/react-src/
└── build/
    └── package.json                    ← Config Babel pour compilation JSX
```

---

## Étape 1 — Setup de l'environnement de build React

### 1.1 — Installer symfony/ux-react

```bash
composer require symfony/ux-react
```

Symfony Flex configure automatiquement le bundle. Vérifier que `config/bundles.php` contient :
```php
Symfony\UX\React\ReactBundle::class => ['all' => true],
```

### 1.2 — Créer le package.json de compilation JSX

Créer le fichier `assets/react-src/build/package.json` :

```json
{
  "name": "outils-qualite-react-build",
  "private": true,
  "scripts": {
    "build": "babel ../IshikawaEditor --out-dir ../../react/controllers --extensions .jsx,.js --copy-files",
    "watch": "babel ../IshikawaEditor --out-dir ../../react/controllers --extensions .jsx,.js --copy-files --watch"
  },
  "devDependencies": {
    "@babel/cli": "^7.23.0",
    "@babel/core": "^7.23.0",
    "@babel/preset-env": "^7.23.0",
    "@babel/preset-react": "^7.23.0"
  }
}
```

Créer le fichier `assets/react-src/build/.babelrc` :

```json
{
  "presets": [
    "@babel/preset-env",
    ["@babel/preset-react", { "runtime": "automatic" }]
  ]
}
```

### 1.3 — Configurer le chemin des controllers React

Modifier (ou créer) `config/packages/react.yaml` :

```yaml
react:
  controllers_path: '%kernel.project_dir%/assets/react/controllers'
```

### 1.4 — Ajouter le script de build dans package.json racine

Dans le `package.json` racine du projet, ajouter :

```json
{
  "scripts": {
    "build:react": "cd assets/react-src/build && npm install && npm run build",
    "watch:react": "cd assets/react-src/build && npm install && npm run watch"
  }
}
```

---

## Étape 2 — Installation des dépendances

### 2.1 — Dépendances npm dans le répertoire de build

Dans `assets/react-src/build/package.json`, ajouter les dépendances de production :

```json
{
  "dependencies": {
    "@xyflow/react": "^12.3.0",
    "react": "^18.3.0",
    "react-dom": "^18.3.0",
    "zustand": "^5.0.0",
    "html-to-image": "^1.11.0"
  }
}
```

> **Note :** Ces dépendances sont bundlées dans le code compilé. Elles ne sont **pas** à ajouter dans `importmap.php` (incompatibilité des modules ESM complexes de ReactFlow avec AssetMapper sans build).

### 2.2 — Ajouter le CSS ReactFlow dans importmap.php

```bash
php bin/console importmap:require @xyflow/react/dist/style.css
```

Ou ajouter manuellement dans `importmap.php` :

```php
'@xyflow/react/dist/style.css' => [
    'version' => '12.3.0',
    'type' => 'css',
],
```

---

## Étape 3 — Création du store Zustand

### 3.1 — Constantes métier Ishikawa

Créer `assets/react-src/IshikawaEditor/utils/constants.js` :

```javascript
// Catégories standards méthode 5M / 6M Ishikawa
export const ISHIKAWA_CATEGORIES_5M = [
  { id: 'matiere',   label: 'Matière',      color: '#E53E3E', shortLabel: 'MAT' },
  { id: 'milieu',    label: 'Milieu',        color: '#38A169', shortLabel: 'MIL' },
  { id: 'methode',   label: 'Méthode',       color: '#3182CE', shortLabel: 'MET' },
  { id: 'materiel',  label: 'Matériel',      color: '#D69E2E', shortLabel: 'MAT' },
  { id: 'mainoeuvre',label: 'Main-d\'œuvre', color: '#805AD5', shortLabel: 'MO'  },
];

export const ISHIKAWA_CATEGORIES_6M = [
  ...ISHIKAWA_CATEGORIES_5M,
  { id: 'management', label: 'Management', color: '#ED8936', shortLabel: 'MGT' },
];

// Dimensions des nœuds
export const NODE_DIMENSIONS = {
  EFFECT:   { width: 180, height: 60 },
  CATEGORY: { width: 140, height: 40 },
  CAUSE:    { width: 120, height: 30 },
};

// Espacement de layout
export const LAYOUT_CONFIG = {
  SPINE_Y:          300,   // Y de l'épine dorsale
  SPINE_START_X:     80,   // X de départ de l'épine
  SPINE_END_X:      900,   // X de fin de l'épine (tête du poisson)
  CATEGORY_OFFSET_Y: 180,  // Distance verticale des catégories à l'épine
  CAUSE_OFFSET:       80,  // Espacement entre causes
};

// Type de nœuds ReactFlow
export const NODE_TYPES_KEYS = {
  EFFECT:   'effectNode',
  CATEGORY: 'categoryNode',
  CAUSE:    'causeNode',
};

// Type d'arêtes ReactFlow  
export const EDGE_TYPES_KEYS = {
  SPINE: 'spineEdge',
  BONE:  'boneEdge',
};
```

### 3.2 — Slice des nœuds

Créer `assets/react-src/IshikawaEditor/store/slices/nodesSlice.js` :

```javascript
import { applyNodeChanges } from '@xyflow/react';
import { NODE_TYPES_KEYS, NODE_DIMENSIONS, LAYOUT_CONFIG, ISHIKAWA_CATEGORIES_5M } from '../../utils/constants.js';
import { generateId } from '../../utils/ishikawaLayout.js';

export const createNodesSlice = (set, get) => ({
  nodes: [],

  // Appelé par ReactFlow sur chaque interaction (drag, select, delete)
  onNodesChange: (changes) => {
    set({ nodes: applyNodeChanges(changes, get().nodes) });
  },

  // Initialise un nouveau diagramme vierge avec la structure 5M par défaut
  initDefaultDiagram: (problemLabel = 'Problème à analyser') => {
    const effectNodeId = 'effect-main';
    const nodes = [];

    // Nœud effet (tête du poisson)
    nodes.push({
      id: effectNodeId,
      type: NODE_TYPES_KEYS.EFFECT,
      position: { x: LAYOUT_CONFIG.SPINE_END_X, y: LAYOUT_CONFIG.SPINE_Y - NODE_DIMENSIONS.EFFECT.height / 2 },
      data: {
        label: problemLabel,
        isEditing: false,
      },
      width:  NODE_DIMENSIONS.EFFECT.width,
      height: NODE_DIMENSIONS.EFFECT.height,
      draggable: false, // La tête est fixe
    });

    // Nœuds catégories 5M avec positionnement alterné haut/bas
    ISHIKAWA_CATEGORIES_5M.forEach((cat, index) => {
      const isTop = index % 2 === 0;
      const xPos  = LAYOUT_CONFIG.SPINE_START_X + 120 + index * 150;
      const yPos  = isTop
        ? LAYOUT_CONFIG.SPINE_Y - LAYOUT_CONFIG.CATEGORY_OFFSET_Y
        : LAYOUT_CONFIG.SPINE_Y + LAYOUT_CONFIG.CATEGORY_OFFSET_Y;

      nodes.push({
        id:   `cat-${cat.id}`,
        type: NODE_TYPES_KEYS.CATEGORY,
        position: { x: xPos, y: yPos },
        data: {
          label:      cat.label,
          categoryId: cat.id,
          color:      cat.color,
          isTop,
          isEditing:  false,
        },
        width:  NODE_DIMENSIONS.CATEGORY.width,
        height: NODE_DIMENSIONS.CATEGORY.height,
      });
    });

    set({ nodes });
    get().initDefaultEdges(effectNodeId);
  },

  // Ajouter une cause à une catégorie
  addCause: (categoryNodeId, label = 'Nouvelle cause') => {
    const state    = get();
    const catNode  = state.nodes.find(n => n.id === categoryNodeId);
    if (!catNode) return;

    // Compter les causes existantes pour cette catégorie
    const existingCauses = state.nodes.filter(
      n => n.type === NODE_TYPES_KEYS.CAUSE && n.data.parentCategoryId === categoryNodeId
    );

    const newId    = `cause-${generateId()}`;
    const isTop    = catNode.data.isTop;
    const xOffset  = existingCauses.length * (NODE_DIMENSIONS.CAUSE.width + 10);

    const newNode = {
      id:   newId,
      type: NODE_TYPES_KEYS.CAUSE,
      position: {
        x: catNode.position.x - 50 + xOffset,
        y: isTop
          ? catNode.position.y - LAYOUT_CONFIG.CAUSE_OFFSET
          : catNode.position.y + NODE_DIMENSIONS.CATEGORY.height + 10,
      },
      data: {
        label,
        parentCategoryId: categoryNodeId,
        isEditing:        true, // Passe en mode édition immédiatement
        isNew:            true,
      },
      width:  NODE_DIMENSIONS.CAUSE.width,
      height: NODE_DIMENSIONS.CAUSE.height,
    };

    set({ nodes: [...state.nodes, newNode] });
    get().addCauseEdge(categoryNodeId, newId);
  },

  // Mettre à jour le label d'un nœud
  updateNodeLabel: (nodeId, label) => {
    set({
      nodes: get().nodes.map(n =>
        n.id === nodeId
          ? { ...n, data: { ...n.data, label, isEditing: false, isNew: false } }
          : n
      ),
    });
  },

  // Passer un nœud en mode édition
  setNodeEditing: (nodeId, isEditing) => {
    set({
      nodes: get().nodes.map(n =>
        n.id === nodeId
          ? { ...n, data: { ...n.data, isEditing } }
          : n
      ),
    });
  },

  // Supprimer un nœud et ses arêtes associées
  deleteNode: (nodeId) => {
    const state = get();
    // Récupérer les causes enfants si c'est une catégorie
    const childIds = state.nodes
      .filter(n => n.data?.parentCategoryId === nodeId)
      .map(n => n.id);
    const idsToRemove = [nodeId, ...childIds];

    set({
      nodes: state.nodes.filter(n => !idsToRemove.includes(n.id)),
      edges: state.edges.filter(e =>
        !idsToRemove.includes(e.source) && !idsToRemove.includes(e.target)
      ),
    });
  },

  // Mettre à jour la couleur d'une catégorie
  updateCategoryColor: (categoryNodeId, color) => {
    set({
      nodes: get().nodes.map(n =>
        n.id === categoryNodeId
          ? { ...n, data: { ...n.data, color } }
          : n
      ),
    });
  },
});
```

### 3.3 — Slice des arêtes

Créer `assets/react-src/IshikawaEditor/store/slices/edgesSlice.js` :

```javascript
import { applyEdgeChanges, addEdge } from '@xyflow/react';
import { EDGE_TYPES_KEYS } from '../../utils/constants.js';

export const createEdgesSlice = (set, get) => ({
  edges: [],

  // Appelé par ReactFlow sur chaque modification d'arête
  onEdgesChange: (changes) => {
    set({ edges: applyEdgeChanges(changes, get().edges) });
  },

  // Connexion manuelle entre deux nœuds (drag de handle)
  onConnect: (connection) => {
    set({
      edges: addEdge(
        { ...connection, type: EDGE_TYPES_KEYS.BONE, animated: false },
        get().edges
      ),
    });
  },

  // Initialise les arêtes de base (épine + catégories → tête)
  initDefaultEdges: (effectNodeId) => {
    const nodes  = get().nodes;
    const catNodes = nodes.filter(n => n.type === 'categoryNode');
    const edges  = [];

    // Épine dorsale : point de départ → tête
    edges.push({
      id:     'spine-main',
      source: 'spine-start', // Nœud virtuel ou le premier nœud
      target: effectNodeId,
      type:   EDGE_TYPES_KEYS.SPINE,
      data:   { isSpine: true },
    });

    // Chaque catégorie → épine dorsale
    catNodes.forEach((cat) => {
      edges.push({
        id:     `edge-${cat.id}-spine`,
        source: cat.id,
        target: effectNodeId,
        type:   EDGE_TYPES_KEYS.BONE,
        data:   { isBone: true, color: cat.data.color },
      });
    });

    set({ edges });
  },

  // Ajouter une arête cause → catégorie
  addCauseEdge: (categoryId, causeId) => {
    const catNode = get().nodes.find(n => n.id === categoryId);
    const color   = catNode?.data?.color ?? '#666';

    set({
      edges: [
        ...get().edges,
        {
          id:     `edge-${causeId}-${categoryId}`,
          source: causeId,
          target: categoryId,
          type:   EDGE_TYPES_KEYS.BONE,
          data:   { isBone: true, color },
        },
      ],
    });
  },
});
```

### 3.4 — Slice des métadonnées

Créer `assets/react-src/IshikawaEditor/store/slices/metaSlice.js` :

```javascript
export const createMetaSlice = (set) => ({
  meta: {
    title:        'Nouvelle analyse Ishikawa',
    problem:      '',
    author:       '',
    date:         new Date().toISOString().split('T')[0],
    recordId:     null,   // ID Symfony de l'enregistrement sauvegardé
    isDirty:      false,  // true = modifications non sauvegardées
    lastSavedAt:  null,
  },

  updateMeta: (partial) => {
    set(state => ({
      meta: { ...state.meta, ...partial, isDirty: true },
    }));
  },

  setRecordId: (id) => {
    set(state => ({
      meta: { ...state.meta, recordId: id, isDirty: false, lastSavedAt: new Date() },
    }));
  },

  markClean: () => {
    set(state => ({
      meta: { ...state.meta, isDirty: false, lastSavedAt: new Date() },
    }));
  },
});
```

### 3.5 — Slice UI

Créer `assets/react-src/IshikawaEditor/store/slices/uiSlice.js` :

```javascript
export const createUiSlice = (set) => ({
  ui: {
    selectedNodeId:    null,
    isPropertiesPanelOpen: false,
    isMetaPanelOpen:   false,
    isSaving:          false,
    saveError:         null,
    isExporting:       false,
    zoomLevel:         1,
    mode:              'view', // 'view' | 'edit'
    showMinimap:       true,
  },

  selectNode: (nodeId) => {
    set(state => ({
      ui: {
        ...state.ui,
        selectedNodeId:        nodeId,
        isPropertiesPanelOpen: nodeId !== null,
      },
    }));
  },

  setSaving: (isSaving, error = null) => {
    set(state => ({
      ui: { ...state.ui, isSaving, saveError: error },
    }));
  },

  setExporting: (isExporting) => {
    set(state => ({ ui: { ...state.ui, isExporting } }));
  },

  toggleMinimap: () => {
    set(state => ({
      ui: { ...state.ui, showMinimap: !state.ui.showMinimap },
    }));
  },

  setMode: (mode) => {
    set(state => ({ ui: { ...state.ui, mode } }));
  },

  setZoom: (zoomLevel) => {
    set(state => ({ ui: { ...state.ui, zoomLevel } }));
  },
});
```

### 3.6 — Store principal (composition des slices)

Créer `assets/react-src/IshikawaEditor/store/useIshikawaStore.js` :

```javascript
import { create } from 'zustand';
import { devtools } from 'zustand/middleware';
import { createNodesSlice } from './slices/nodesSlice.js';
import { createEdgesSlice } from './slices/edgesSlice.js';
import { createMetaSlice }  from './slices/metaSlice.js';
import { createUiSlice }    from './slices/uiSlice.js';

/**
 * Store Zustand principal — Pattern Slices officiel
 * Référence : https://docs.pmnd.rs/zustand/guides/slices-pattern
 * Utilisé par ReactFlow en interne, co-localisé avec le composant.
 */
const useIshikawaStore = create(
  devtools(
    (set, get) => ({
      ...createNodesSlice(set, get),
      ...createEdgesSlice(set, get),
      ...createMetaSlice(set),
      ...createUiSlice(set),

      // Action de reset global
      resetDiagram: () => {
        set({
          nodes: [],
          edges: [],
          meta: {
            title:       'Nouvelle analyse Ishikawa',
            problem:     '',
            author:      '',
            date:        new Date().toISOString().split('T')[0],
            recordId:    null,
            isDirty:     false,
            lastSavedAt: null,
          },
          ui: {
            selectedNodeId:        null,
            isPropertiesPanelOpen: false,
            isMetaPanelOpen:       false,
            isSaving:              false,
            saveError:             null,
            isExporting:           false,
            zoomLevel:             1,
            mode:                  'edit',
            showMinimap:           true,
          },
        });
      },

      // Hydrate le store depuis un objet JSON (chargement d'un Record existant)
      loadFromRecord: (record) => {
        const { nodes, edges, meta } = record.content ?? {};
        set({
          nodes: nodes ?? [],
          edges: edges ?? [],
          meta: {
            title:       record.title        ?? 'Sans titre',
            problem:     meta?.problem       ?? '',
            author:      meta?.author        ?? '',
            date:        record.createdAt    ?? new Date().toISOString().split('T')[0],
            recordId:    record.id,
            isDirty:     false,
            lastSavedAt: record.updatedAt    ?? null,
          },
        });
      },
    }),
    { name: 'IshikawaStore' } // Label dans Redux DevTools
  )
);

export default useIshikawaStore;
```

---

## Étape 4 — Création des composants React

### 4.1 — Nœud Effet (tête du poisson)

Créer `assets/react-src/IshikawaEditor/components/nodes/EffectNode.jsx` :

```jsx
import { Handle, Position } from '@xyflow/react';
import { useState } from 'react';
import useIshikawaStore from '../../store/useIshikawaStore.js';

export default function EffectNode({ id, data }) {
  const [editValue, setEditValue]    = useState(data.label);
  const { updateNodeLabel, setNodeEditing } = useIshikawaStore();

  const handleDoubleClick = () => setNodeEditing(id, true);

  const handleBlur = () => {
    updateNodeLabel(id, editValue);
  };

  const handleKeyDown = (e) => {
    if (e.key === 'Enter') updateNodeLabel(id, editValue);
    if (e.key === 'Escape') { setEditValue(data.label); setNodeEditing(id, false); }
  };

  return (
    <div
      style={{
        background: '#E53E3E',
        color: 'white',
        borderRadius: 8,
        padding: '8px 16px',
        minWidth: 160,
        textAlign: 'center',
        fontWeight: 700,
        fontSize: 13,
        boxShadow: '0 2px 8px rgba(0,0,0,0.2)',
        cursor: 'pointer',
      }}
      onDoubleClick={handleDoubleClick}
      title="Double-clic pour modifier"
    >
      {data.isEditing ? (
        <input
          autoFocus
          value={editValue}
          onChange={e => setEditValue(e.target.value)}
          onBlur={handleBlur}
          onKeyDown={handleKeyDown}
          style={{
            background: 'transparent',
            border: 'none',
            color: 'white',
            fontWeight: 700,
            fontSize: 13,
            textAlign: 'center',
            width: '100%',
            outline: 'none',
          }}
        />
      ) : (
        <span>{data.label || 'Problème principal'}</span>
      )}
      <Handle type="target" position={Position.Left} style={{ background: 'white' }} />
    </div>
  );
}
```

### 4.2 — Nœud Catégorie

Créer `assets/react-src/IshikawaEditor/components/nodes/CategoryNode.jsx` :

```jsx
import { Handle, Position } from '@xyflow/react';
import { useState } from 'react';
import useIshikawaStore from '../../store/useIshikawaStore.js';

export default function CategoryNode({ id, data }) {
  const [editValue, setEditValue] = useState(data.label);
  const { updateNodeLabel, setNodeEditing, addCause } = useIshikawaStore();

  const handleDoubleClick = () => setNodeEditing(id, true);
  const handleBlur        = () => updateNodeLabel(id, editValue);
  const handleKeyDown = (e) => {
    if (e.key === 'Enter')  updateNodeLabel(id, editValue);
    if (e.key === 'Escape') { setEditValue(data.label); setNodeEditing(id, false); }
  };

  const handleAddCause = (e) => {
    e.stopPropagation();
    addCause(id);
  };

  return (
    <div
      style={{
        background: data.color ?? '#666',
        color: 'white',
        borderRadius: 6,
        padding: '4px 12px',
        minWidth: 120,
        textAlign: 'center',
        fontWeight: 600,
        fontSize: 12,
        position: 'relative',
        cursor: 'pointer',
      }}
      onDoubleClick={handleDoubleClick}
      title="Double-clic pour modifier — Clic + sur le bouton pour ajouter une cause"
    >
      {data.isEditing ? (
        <input
          autoFocus
          value={editValue}
          onChange={e => setEditValue(e.target.value)}
          onBlur={handleBlur}
          onKeyDown={handleKeyDown}
          style={{
            background: 'transparent',
            border: 'none',
            color: 'white',
            fontWeight: 600,
            fontSize: 12,
            textAlign: 'center',
            width: '100%',
            outline: 'none',
          }}
        />
      ) : (
        <span>{data.label}</span>
      )}

      {/* Bouton d'ajout de cause */}
      <button
        onClick={handleAddCause}
        style={{
          position: 'absolute',
          right: -10,
          top: '50%',
          transform: 'translateY(-50%)',
          width: 20,
          height: 20,
          borderRadius: '50%',
          background: 'white',
          color: data.color ?? '#666',
          border: 'none',
          fontWeight: 900,
          fontSize: 14,
          cursor: 'pointer',
          lineHeight: '20px',
          textAlign: 'center',
          padding: 0,
        }}
        title="Ajouter une cause"
      >
        +
      </button>

      <Handle type="source" position={data.isTop ? Position.Bottom : Position.Top} />
      <Handle type="target" position={Position.Right} />
    </div>
  );
}
```

### 4.3 — Nœud Cause

Créer `assets/react-src/IshikawaEditor/components/nodes/CauseNode.jsx` :

```jsx
import { Handle, Position } from '@xyflow/react';
import { useState, useEffect, useRef } from 'react';
import useIshikawaStore from '../../store/useIshikawaStore.js';

export default function CauseNode({ id, data }) {
  const [editValue, setEditValue] = useState(data.label);
  const inputRef = useRef(null);
  const { updateNodeLabel, setNodeEditing, deleteNode } = useIshikawaStore();

  // Focus automatique si nœud nouvellement créé
  useEffect(() => {
    if (data.isNew && data.isEditing && inputRef.current) {
      inputRef.current.focus();
      inputRef.current.select();
    }
  }, [data.isNew, data.isEditing]);

  const handleBlur    = () => { if (editValue.trim()) updateNodeLabel(id, editValue); else deleteNode(id); };
  const handleKeyDown = (e) => {
    if (e.key === 'Enter')  { if (editValue.trim()) updateNodeLabel(id, editValue); else deleteNode(id); }
    if (e.key === 'Escape') { if (data.isNew) deleteNode(id); else { setEditValue(data.label); setNodeEditing(id, false); } }
    if (e.key === 'Delete' && !data.isEditing) deleteNode(id);
  };

  return (
    <div
      style={{
        background: 'white',
        border: `2px solid #CBD5E0`,
        borderRadius: 4,
        padding: '3px 8px',
        minWidth: 100,
        fontSize: 11,
        cursor: 'pointer',
      }}
      onDoubleClick={() => setNodeEditing(id, true)}
      onKeyDown={handleKeyDown}
      tabIndex={0}
    >
      {data.isEditing ? (
        <input
          ref={inputRef}
          value={editValue}
          onChange={e => setEditValue(e.target.value)}
          onBlur={handleBlur}
          onKeyDown={handleKeyDown}
          style={{
            border: 'none',
            outline: 'none',
            fontSize: 11,
            width: '100%',
          }}
        />
      ) : (
        <span>{data.label}</span>
      )}

      <Handle type="source" position={Position.Right} />
      <Handle type="target" position={Position.Left} />
    </div>
  );
}
```

### 4.4 — Canvas principal

Créer `assets/react-src/IshikawaEditor/components/IshikawaCanvas.jsx` :

```jsx
import { ReactFlow, Background, Controls, MiniMap, Panel } from '@xyflow/react';
import { useShallow } from 'zustand/react/shallow';
import useIshikawaStore from '../store/useIshikawaStore.js';
import EffectNode   from './nodes/EffectNode.jsx';
import CategoryNode from './nodes/CategoryNode.jsx';
import CauseNode    from './nodes/CauseNode.jsx';
import ToolbarPanel from './panels/ToolbarPanel.jsx';
import PropertiesPanel from './panels/PropertiesPanel.jsx';
import { NODE_TYPES_KEYS } from '../utils/constants.js';

// Déclaration des types de nœuds custom — DOIT être hors du composant (stabilité référentielle)
const nodeTypes = {
  [NODE_TYPES_KEYS.EFFECT]:   EffectNode,
  [NODE_TYPES_KEYS.CATEGORY]: CategoryNode,
  [NODE_TYPES_KEYS.CAUSE]:    CauseNode,
};

export default function IshikawaCanvas() {
  // Sélection granulaire avec useShallow pour éviter les re-renders inutiles
  const { nodes, edges, onNodesChange, onEdgesChange, onConnect, selectNode, ui } =
    useIshikawaStore(
      useShallow(state => ({
        nodes:         state.nodes,
        edges:         state.edges,
        onNodesChange: state.onNodesChange,
        onEdgesChange: state.onEdgesChange,
        onConnect:     state.onConnect,
        selectNode:    state.selectNode,
        ui:            state.ui,
      }))
    );

  return (
    <div style={{ width: '100%', height: '75vh', background: '#F7FAFC' }}>
      <ReactFlow
        nodes={nodes}
        edges={edges}
        nodeTypes={nodeTypes}
        onNodesChange={onNodesChange}
        onEdgesChange={onEdgesChange}
        onConnect={onConnect}
        onNodeClick={(_, node) => selectNode(node.id)}
        onPaneClick={() => selectNode(null)}
        fitView
        attributionPosition="bottom-left"
        proOptions={{ hideAttribution: false }}
      >
        <Background variant="dots" gap={20} size={1} color="#CBD5E0" />
        <Controls position="bottom-right" />
        {ui.showMinimap && <MiniMap nodeStrokeWidth={3} pannable zoomable />}

        {/* Barre d'outils en haut du canvas */}
        <Panel position="top-left">
          <ToolbarPanel />
        </Panel>

        {/* Panneau propriétés à droite si nœud sélectionné */}
        {ui.isPropertiesPanelOpen && (
          <Panel position="top-right">
            <PropertiesPanel />
          </Panel>
        )}
      </ReactFlow>
    </div>
  );
}
```

### 4.5 — Panneau de barre d'outils

Créer `assets/react-src/IshikawaEditor/components/panels/ToolbarPanel.jsx` :

```jsx
import { useShallow } from 'zustand/react/shallow';
import useIshikawaStore from '../../store/useIshikawaStore.js';
import ExportButton from '../controls/ExportButton.jsx';

export default function ToolbarPanel() {
  const { meta, ui, updateMeta, saveDiagram, toggleMinimap } = useIshikawaStore(
    useShallow(state => ({
      meta:         state.meta,
      ui:           state.ui,
      updateMeta:   state.updateMeta,
      saveDiagram:  state.saveDiagram,
      toggleMinimap: state.toggleMinimap,
    }))
  );

  return (
    <div style={{
      background: 'white',
      borderRadius: 8,
      padding: '8px 16px',
      boxShadow: '0 2px 8px rgba(0,0,0,0.1)',
      display: 'flex',
      alignItems: 'center',
      gap: 12,
      minWidth: 400,
    }}>
      {/* Titre du diagramme */}
      <input
        value={meta.title}
        onChange={e => updateMeta({ title: e.target.value })}
        placeholder="Titre de l'analyse..."
        style={{
          border: '1px solid #E2E8F0',
          borderRadius: 4,
          padding: '4px 8px',
          fontSize: 13,
          flex: 1,
        }}
      />

      {/* Statut de sauvegarde */}
      <span style={{ fontSize: 11, color: meta.isDirty ? '#E53E3E' : '#38A169', whiteSpace: 'nowrap' }}>
        {ui.isSaving ? '⏳ Sauvegarde…' : meta.isDirty ? '● Non sauvegardé' : '✓ Sauvegardé'}
      </span>

      {/* Bouton sauvegarde */}
      <button
        onClick={saveDiagram}
        disabled={ui.isSaving || !meta.isDirty}
        style={{
          background: '#3182CE',
          color: 'white',
          border: 'none',
          borderRadius: 4,
          padding: '4px 12px',
          fontSize: 12,
          cursor: 'pointer',
          opacity: (!meta.isDirty || ui.isSaving) ? 0.5 : 1,
        }}
      >
        Sauvegarder
      </button>

      {/* Export */}
      <ExportButton />

      {/* Minimap toggle */}
      <button
        onClick={toggleMinimap}
        style={{
          background: 'transparent',
          border: '1px solid #CBD5E0',
          borderRadius: 4,
          padding: '4px 8px',
          fontSize: 11,
          cursor: 'pointer',
        }}
        title="Afficher/masquer la minimap"
      >
        🗺
      </button>
    </div>
  );
}
```

### 4.6 — Bouton d'export

Créer `assets/react-src/IshikawaEditor/components/controls/ExportButton.jsx` :

```jsx
import { useReactFlow } from '@xyflow/react';
import { toPng } from 'html-to-image';
import useIshikawaStore from '../../store/useIshikawaStore.js';

export default function ExportButton() {
  const { getNodes }   = useReactFlow();
  const { setExporting, meta } = useIshikawaStore(state => ({
    setExporting: state.setExporting,
    meta:         state.meta,
  }));

  const handleExport = async () => {
    setExporting(true);
    try {
      const canvas = document.querySelector('.react-flow__viewport');
      if (!canvas) return;

      const dataUrl = await toPng(canvas, { backgroundColor: '#ffffff', pixelRatio: 2 });

      const link = document.createElement('a');
      link.download = `ishikawa-${meta.title.replace(/\s+/g, '-').toLowerCase()}.png`;
      link.href     = dataUrl;
      link.click();
    } catch (err) {
      console.error('Erreur export:', err);
    } finally {
      setExporting(false);
    }
  };

  return (
    <button
      onClick={handleExport}
      style={{
        background: '#38A169',
        color: 'white',
        border: 'none',
        borderRadius: 4,
        padding: '4px 12px',
        fontSize: 12,
        cursor: 'pointer',
      }}
    >
      Export PNG
    </button>
  );
}
```

---

## Étape 5 — Logique métier Ishikawa

### 5.1 — Utilitaire de génération d'ID

Créer `assets/react-src/IshikawaEditor/utils/ishikawaLayout.js` :

```javascript
/**
 * Génère un ID unique court
 */
export function generateId() {
  return Math.random().toString(36).substring(2, 9);
}

/**
 * Recalcule automatiquement les positions de tous les nœuds
 * pour maintenir la structure en arête de poisson.
 * À appeler après ajout/suppression massif de nœuds.
 *
 * @param {Array} nodes - Tableau de nœuds ReactFlow
 * @param {Object} config - Configuration de layout (LAYOUT_CONFIG)
 * @returns {Array} - Tableau de nœuds avec positions recalculées
 */
export function recalculateLayout(nodes, config) {
  const effectNode   = nodes.find(n => n.type === 'effectNode');
  const categoryNodes = nodes.filter(n => n.type === 'categoryNode');
  const causeNodes   = nodes.filter(n => n.type === 'causeNode');

  if (!effectNode) return nodes;

  const updatedNodes = nodes.map(node => ({ ...node }));

  // Repositionner les catégories équitablement sur l'épine
  categoryNodes.forEach((cat, index) => {
    const isTop  = index % 2 === 0;
    const totalW = config.SPINE_END_X - config.SPINE_START_X;
    const step   = totalW / (categoryNodes.length + 1);
    const xPos   = config.SPINE_START_X + step * (index + 1);
    const yPos   = isTop
      ? config.SPINE_Y - config.CATEGORY_OFFSET_Y
      : config.SPINE_Y + config.CATEGORY_OFFSET_Y;

    const nodeIndex = updatedNodes.findIndex(n => n.id === cat.id);
    if (nodeIndex !== -1) {
      updatedNodes[nodeIndex] = {
        ...updatedNodes[nodeIndex],
        position: { x: xPos, y: yPos },
        data: { ...updatedNodes[nodeIndex].data, isTop },
      };
    }
  });

  return updatedNodes;
}
```

### 5.2 — Sérialisation JSON ↔ Record Symfony

Créer `assets/react-src/IshikawaEditor/utils/ishikawaSerializer.js` :

```javascript
/**
 * Convertit l'état du store en objet JSON compatible avec l'entité Record Symfony.
 * La structure doit correspondre exactement à ce qu'attend RecordController.
 *
 * @param {Object} storeState - État complet du store Zustand
 * @returns {Object} - Payload prêt pour l'API
 */
export function serializeToRecord(storeState) {
  const { nodes, edges, meta } = storeState;

  return {
    title: meta.title,
    type:  'ishikawa',
    content: {
      nodes: nodes.map(node => ({
        id:       node.id,
        type:     node.type,
        position: node.position,
        data:     {
          label:            node.data.label,
          categoryId:       node.data.categoryId      ?? null,
          color:            node.data.color           ?? null,
          isTop:            node.data.isTop           ?? null,
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
        problem: meta.problem,
        author:  meta.author,
        date:    meta.date,
      },
    },
  };
}

/**
 * Convertit un Record Symfony en état prêt à charger dans le store.
 * Gère la compatibilité avec les données sauvegardées par la v1.
 *
 * @param {Object} record - Objet Record brut de l'API Symfony
 * @returns {Object} - État compatible avec loadFromRecord()
 */
export function deserializeFromRecord(record) {
  const content = record.content ?? {};

  return {
    id:        record.id,
    title:     record.title ?? 'Sans titre',
    createdAt: record.createdAt,
    updatedAt: record.updatedAt,
    content: {
      nodes: content.nodes ?? [],
      edges: content.edges ?? [],
      meta:  content.meta  ?? {},
    },
  };
}
```

### 5.3 — Action de sauvegarde (à ajouter dans le store)

Ajouter cette action dans `useIshikawaStore.js`, dans le bloc principal du store, **après** les slices :

```javascript
// Dans le create() principal, après les spreads de slices :

saveDiagram: async () => {
  const state = get();
  get().setSaving(true);

  try {
    const payload = serializeToRecord(state);
    const isNew   = !state.meta.recordId;
    const url     = isNew ? '/api/records' : `/api/records/${state.meta.recordId}`;
    const method  = isNew ? 'POST' : 'PUT';

    const response = await fetch(url, {
      method,
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify(payload),
    });

    if (!response.ok) throw new Error(`HTTP ${response.status}`);

    const data = await response.json();
    get().setRecordId(data.id);
    get().setSaving(false);
  } catch (err) {
    get().setSaving(false, err.message);
    console.error('Erreur sauvegarde:', err);
  }
},

loadDiagram: async (recordId) => {
  try {
    const response = await fetch(`/api/records/${recordId}`, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });
    if (!response.ok) throw new Error(`HTTP ${response.status}`);

    const data   = await response.json();
    const record = deserializeFromRecord(data);
    get().loadFromRecord(record);
  } catch (err) {
    console.error('Erreur chargement:', err);
  }
},
```

Ajouter l'import en haut du fichier `useIshikawaStore.js` :
```javascript
import { serializeToRecord, deserializeFromRecord } from '../utils/ishikawaSerializer.js';
```

---

## Étape 6 — Création du Controller Symfony + Route v2

Créer `src/Controller/IshikawaV2Controller.php` :

```php
<?php

namespace App\Controller;

use App\Lead\QuotaService;   // Réutiliser le service existant
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur de validation — Route parallèle pour la v2 ReactFlow.
 * NE PAS MODIFIER IshikawaController existant.
 * Ce contrôleur sera supprimé une fois la migration décidée
 * (ou IshikawaController sera remplacé par celui-ci).
 */
#[Route('/ishikawa-v2', name: 'ishikawa_v2_')]
class IshikawaV2Controller extends AbstractController
{
    public function __construct(
        private readonly QuotaService $quotaService,
    ) {}

    /**
     * Page principale — éditeur ReactFlow.
     * Accessible à /ishikawa-v2 (nouveau diagramme)
     * ou /ishikawa-v2?record={id} (chargement d'un existant)
     */
    #[Route('', name: 'index', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(Request $request): Response
    {
        // Vérification quota identique à la v1
        $user = $this->getUser();
        if (!$this->quotaService->canCreateRecord($user, 'ishikawa')) {
            $this->addFlash('warning', 'Vous avez atteint votre quota d\'analyses. Passez au plan Starter pour continuer.');
            return $this->redirectToRoute('pricing');
        }

        // ID d'un Record existant à charger (optionnel)
        $recordId = $request->query->getInt('record', 0) ?: null;

        return $this->render('ishikawa_v2/index.html.twig', [
            'record_id'  => $recordId,
            'page_title' => 'Diagramme Ishikawa (Nouvelle version)',
            'is_v2'      => true, // Flag pour navigation
        ]);
    }
}
```

> **Note pour Cursor :** Adapter l'import `Request` avec `use Symfony\Component\HttpFoundation\Request;` et vérifier le nom exact de la méthode `canCreateRecord` dans `QuotaService` — adapter si différent.

---

## Étape 7 — Template Twig d'accueil du composant

Créer `templates/ishikawa_v2/index.html.twig` :

```twig
{% extends 'base.html.twig' %}

{% block title %}{{ page_title }} — Outils Qualité{% endblock %}

{% block stylesheets %}
    {{ parent() }}
    {# CSS ReactFlow — ajouté via importmap #}
    <style>
        .ishikawa-v2-container {
            padding: 0;
            max-width: 100%;
        }
        .ishikawa-v2-header {
            padding: 12px 20px;
            background: #2D3748;
            color: white;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .ishikawa-v2-badge {
            background: #F6E05E;
            color: #744210;
            font-size: 11px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 12px;
        }
        .ishikawa-v2-back {
            color: #90CDF4;
            text-decoration: none;
            font-size: 13px;
        }
        .ishikawa-v2-back:hover { text-decoration: underline; }
    </style>
{% endblock %}

{% block body %}
<div class="ishikawa-v2-container">

    {# En-tête de validation — visible uniquement en v2 #}
    <div class="ishikawa-v2-header">
        <a href="{{ path('ishikawa_index') }}" class="ishikawa-v2-back">
            ← Version actuelle
        </a>
        <span>Diagramme Ishikawa</span>
        <span class="ishikawa-v2-badge">NOUVELLE VERSION — Test</span>
        <span style="font-size:12px; color:#CBD5E0; margin-left:auto;">
            Donnez votre avis à votre équipe avant migration définitive
        </span>
    </div>

    {# Montage du composant React via symfony/ux-react #}
    {{ react_component('IshikawaEditor', {
        recordId: record_id,
        apiBase: '/api',
        csrfToken: csrf_token('ishikawa_record'),
    }) }}

</div>
{% endblock %}

{% block javascripts %}
    {{ parent() }}
    {# Les assets React sont compilés et chargés automatiquement via UX React #}
{% endblock %}
```

---

## Étape 8 — Bridge Symfony → React (données initiales)

### 8.1 — Point d'entrée du composant React

Créer `assets/react-src/IshikawaEditor/index.jsx` :

```jsx
import { ReactFlowProvider } from '@xyflow/react';
import { useEffect }         from 'react';
import IshikawaCanvas        from './components/IshikawaCanvas.jsx';
import MetaPanel             from './components/panels/MetaPanel.jsx';
import useIshikawaStore      from './store/useIshikawaStore.js';

/**
 * Point d'entrée du composant React.
 * Props transmises depuis Twig via react_component().
 *
 * @param {number|null} recordId - ID d'un Record existant à charger
 * @param {string}      apiBase  - Base URL de l'API (/api)
 * @param {string}      csrfToken - Token CSRF Symfony pour les mutations
 */
export default function IshikawaEditor({ recordId, apiBase, csrfToken }) {
  const { initDefaultDiagram, loadDiagram, updateMeta } = useIshikawaStore();

  useEffect(() => {
    // Stocker la config API dans le store (disponible pour saveDiagram)
    updateMeta({ _apiBase: apiBase, _csrfToken: csrfToken });

    if (recordId) {
      // Chargement d'un diagramme existant
      loadDiagram(recordId);
    } else {
      // Nouveau diagramme vierge avec structure 5M par défaut
      initDefaultDiagram();
    }
  }, [recordId]); // Ne se réexécute qu'au changement de recordId

  return (
    // ReactFlowProvider est obligatoire pour useReactFlow() dans les sous-composants
    <ReactFlowProvider>
      <MetaPanel />
      <IshikawaCanvas />
    </ReactFlowProvider>
  );
}
```

---

## Étape 9 — Sauvegarde et chargement API

### 9.1 — Vérifier la compatibilité du RecordController existant

Avant d'implémenter la sauvegarde, s'assurer que `src/Controller/Api/RecordController.php` (ou son équivalent) :

1. Accepte le champ `type: 'ishikawa'` dans le payload POST/PUT
2. Retourne `{ id: int, title: string, content: object, createdAt: string, updatedAt: string }` dans la réponse JSON
3. Vérifie le token CSRF ou utilise la session Symfony

Si le contrôleur retourne un format différent, adapter uniquement `ishikawaSerializer.js` côté client — **ne pas modifier le contrôleur**.

### 9.2 — Mise à jour de saveDiagram pour inclure le CSRF

Dans `useIshikawaStore.js`, la méthode `saveDiagram` doit inclure le token CSRF :

```javascript
// Ajouter dans les headers de la requête fetch :
'X-CSRF-Token': state.meta._csrfToken ?? '',
```

---

## Étape 10 — Compilation et déploiement

### 10.1 — Commandes de build

```bash
# Installer les dépendances de build
cd assets/react-src/build
npm install

# Build de production (une fois)
npm run build

# Ou en mode watch pendant le développement
npm run watch
```

### 10.2 — Commandes Symfony post-build

```bash
# Vider le cache
php bin/console cache:clear

# Recompiler les assets Symfony (SCSS + AssetMapper)
php bin/console sass:build
php bin/console asset-map:compile

# Vérifier que le composant React est bien détecté
php bin/console debug:container react.component_renderer
```

### 10.3 — Vérification en local

1. Accéder à `http://localhost:8000/ishikawa` → **doit fonctionner comme avant**
2. Accéder à `http://localhost:8000/ishikawa-v2` → **doit afficher le nouveau composant**
3. Vérifier la console navigateur : aucune erreur React/Zustand

### 10.4 — Déploiement sur Azure App Service

Les fichiers compilés (`assets/react/controllers/*.js`) doivent être **committés dans Git** (pas dans `.gitignore`) pour que le déploiement Azure via GitHub Actions les intègre sans re-builder.

Ajouter dans `.gitignore` uniquement les sources non compilées :
```
assets/react-src/build/node_modules/
```

Committer les fichiers compilés :
```
assets/react/controllers/IshikawaEditor.js   ← Compilé, à committer
assets/react/controllers/components/         ← Compilés, à committer
```

---

## Étape 11 — Critères de validation pour migration définitive

La migration définitive (remplacement de `/ishikawa` par la v2) sera décidée **uniquement** si tous ces critères sont validés sur la route `/ishikawa-v2` en production :

### Critères fonctionnels
- [ ] Création d'un nouveau diagramme avec les 5 catégories 5M
- [ ] Ajout de causes sur chaque catégorie (double-clic, bouton +)
- [ ] Édition inline des labels (nœuds et catégories)
- [ ] Suppression d'un nœud cause (touche Delete ou bouton)
- [ ] Sauvegarde via l'API existante (POST `/api/records`)
- [ ] Chargement d'un enregistrement existant (`?record={id}`)
- [ ] Mise à jour d'un enregistrement (PUT `/api/records/{id}`)
- [ ] Export PNG fonctionnel

### Critères de performance
- [ ] Lighthouse Performance Score ≥ 80 sur la page v2
- [ ] Pas de re-render excessif sur `console.log` en dev mode
- [ ] Canvas réactif jusqu'à 50 nœuds (test avec `initDefaultDiagram` + 30 causes)

### Critères de compatibilité
- [ ] Les données sauvegardées par la v2 sont lisibles par la v1 (même structure JSON)
- [ ] Le système de quota fonctionne (utilisateur FREE limité)
- [ ] Session Symfony maintenue (pas de déconnexion après sauvegarde)

### Critères UX
- [ ] Feedback visuel sur l'état de sauvegarde (isDirty / isSaving)
- [ ] Double-clic pour éditer fonctionne sur tous les types de nœuds
- [ ] Zoom et pan fluides sur le canvas
- [ ] Minimap affiche correctement la structure

---

## 16. Référence des patterns ReactFlow + Zustand

### Pattern officiel ReactFlow + Zustand
Source : https://reactflow.dev/learn/advanced-use/state-management

```javascript
// ✅ Pattern correct — store centralisé
const useStore = create((set, get) => ({
  nodes: [],
  edges: [],
  onNodesChange: (changes) => set({ nodes: applyNodeChanges(changes, get().nodes) }),
  onEdgesChange: (changes) => set({ edges: applyEdgeChanges(changes, get().edges) }),
  onConnect:     (connection) => set({ edges: addEdge(connection, get().edges) }),
}));

// ✅ Sélection avec useShallow pour éviter les re-renders
const { nodes, edges } = useStore(useShallow(state => ({
  nodes: state.nodes,
  edges: state.edges,
})));

// ❌ Anti-pattern — sélection de l'objet entier
const state = useStore(state => state); // Re-render à chaque mutation
```

### Pattern Slices Zustand
Source : https://docs.pmnd.rs/zustand/guides/slices-pattern

```javascript
// ✅ Pattern slices — état modulaire
const createFishSlice = (set) => ({ fishes: 0, addFish: () => set(state => ({ fishes: state.fishes + 1 })) });
const createBearSlice = (set) => ({ bears: 0,  addBear: () => set(state => ({ bears:  state.bears  + 1 })) });

const useBoundStore = create((...args) => ({
  ...createFishSlice(...args),
  ...createBearSlice(...args),
}));
```

### nodeTypes hors composant (règle ReactFlow critique)
```javascript
// ✅ HORS du composant (référence stable)
const nodeTypes = { effectNode: EffectNode, categoryNode: CategoryNode };
function MyFlow() { return <ReactFlow nodeTypes={nodeTypes} />; }

// ❌ DANS le composant (nouvelle référence à chaque render → recalcul complet)
function MyFlow() {
  const nodeTypes = { effectNode: EffectNode }; // Cause des re-renders infinis
  return <ReactFlow nodeTypes={nodeTypes} />;
}
```

### ReactFlowProvider obligatoire pour useReactFlow()
```javascript
// ✅ Correct — Provider wrappant les sous-composants qui utilisent useReactFlow()
export default function App() {
  return (
    <ReactFlowProvider>
      <MyFlowComponent />  {/* peut utiliser useReactFlow() */}
    </ReactFlowProvider>
  );
}
```

---

## 17. Checklist de non-régression

À exécuter **avant et après** chaque déploiement en production.

### Routes existantes à vérifier (ne doivent pas être affectées)
- [ ] `GET  /ishikawa`           → IshikawaController existant
- [ ] `GET  /5-pourquoi`         → FiveWhyController existant
- [ ] `GET  /pareto`             → ParetoController existant
- [ ] `GET  /amdec`              → AmdecController existant
- [ ] `GET  /qqoqccp`            → QqoqccpController existant
- [ ] `GET  /8d`                 → EightDController existant
- [ ] `GET  /api/records`               → RecordController (CRUD)
- [ ] `POST /api/tools/{tool}/suggest`  → ToolAnalysisController (IA)

### Base de données
- [ ] Les migrations ne sont pas nécessaires (aucune nouvelle entité)
- [ ] Les enregistrements existants en base ne sont pas altérés

### Performance
- [ ] `php bin/console cache:warmup --env=prod` sans erreur
- [ ] `php bin/console doctrine:schema:validate` sans erreur

---

*Document généré le 09/05/2026 — DDWin Solutions / Outils-Qualité*  
*À soumettre à Cursor IDE pour implémentation. Toutes les instructions avec* ⚠️ *ou* **ne pas modifier** *sont des contraintes strictes.*