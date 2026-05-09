# Ishikawa v2 (ReactFlow + Zustand) — Plan d’implémentation

> **Pour agents :** sous-compétence recommandée : `superpowers:subagent-driven-development` ou `superpowers:executing-plans` pour enchaîner les tâches dans l’ordre. Cocher chaque étape (`- [ ]` → `- [x]`).

**Objectif :** livrer la route `/ishikawa-v2` avec l’éditeur ReactFlow + Zustand (UX React + Babel), fusionner les routes API `/api/ishikawa` en double, sans régression de `/ishikawa` ni des endpoints utilisés par la v1.

**Architecture :** Symfony sert la page v2 et l’API existante ; le JS ReactFlow est **bundlé** par Babel vers `assets/react/controllers/` (fichiers versionnés pour O2switch + `deploy.sh`). Les corrections **payload / réponses JSON** et **`loadFromRecord`** viennent de `REACTFLOW_IMPLEMENTATION_GUIDE.md` et de la spec `docs/superpowers/specs/2026-05-09-ishikawa-v2-reactflow-design.md`. Le corps des composants / slices est tiré de `Implementation reactflow ishikawa.md` avec les **écarts listés en tâche 12** (API réelle, enveloppe GET, arêtes initiales).

**Stack :** Symfony 7, `symfony/ux-react`, Asset Mapper, `@xyflow/react` ^12, React 18, Zustand 5, `html-to-image`, Babel 7, PHPUnit.

**Références obligatoires :**

- `docs/superpowers/specs/2026-05-09-ishikawa-v2-reactflow-design.md`
- `REACTFLOW_IMPLEMENTATION_GUIDE.md`
- `Implementation reactflow ishikawa.md` (source JSX détaillée ; **ne pas** copier les étapes 6–9 utilisant `/api/records` ni le `QuotaService` obsolète du même fichier)

---

## Carte des fichiers (création / modification)

| Fichier | Rôle |
|---------|------|
| `composer.json` | Ajout `symfony/ux-react` |
| `config/bundles.php` | Enregistrement `ReactBundle` |
| `config/packages/react.yaml` | `controllers_path` |
| `importmap.php` | CSS `@xyflow/react/dist/style.css` |
| `package.json` (racine) | Scripts `build:react` / `watch:react` |
| `.gitignore` | `assets/react-src/build/node_modules/` |
| `assets/react-src/build/package.json`, `.babelrc` | Build Babel |
| `assets/react-src/IshikawaEditor/**` | Sources JSX |
| `assets/react/controllers/IshikawaEditor.js` (+ sous-dossiers copiés) | Sortie Babel **versionnée** |
| `src/Controller/Tool/IshikawaController.php` | Fusion méthode **share** + imports |
| `src/Tools/Api/IshikawaController.php` | **Suppression** après fusion |
| `src/Tools/Controller/IshikawaV2Controller.php` | Page v2 |
| `templates/ishikawa_v2/index.html.twig` | Twig + Tailwind + `react_component` |
| `tests/Integration/ApiIshikawaControllerTest.php` | Ajuster si besoin après fusion |
| `tests/Integration/IshikawaV2ControllerTest.php` | **Créer** — auth + page v2 |
| `tests/Integration/IshikawaApiRouteDuplicationTest.php` | **Créer** — une route par chemin critique |

---

### Task 1 : Test d’échec — aucune route dupliquée pour `/api/ishikawa/save`

**Fichiers :**

- Créer : `tests/Integration/IshikawaApiRouteDuplicationTest.php`

- [ ] **Étape 1 : Écrire le test (échoue tant que deux routes `POST` existent)**

```php
<?php

namespace App\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\RouterInterface;

final class IshikawaApiRouteDuplicationTest extends KernelTestCase
{
    public function testPostSaveRouteIsRegisteredOnce(): void
    {
        self::bootKernel();
        $router = self::getContainer()->get(RouterInterface::class);
        $collection = $router->getRouteCollection();

        $matches = [];
        foreach ($collection->all() as $name => $route) {
            if ($route->getPath() === '/api/ishikawa/save' && \in_array('POST', $route->getMethods(), true)) {
                $matches[] = $name;
            }
        }

        self::assertCount(
            1,
            $matches,
            'Une seule route POST /api/ishikawa/save attendue ; trouvées : ' . implode(', ', $matches)
        );
    }
}
```

- [ ] **Étape 2 : Lancer le test**

Run : `cd /home/neliville/dev/LAB/QSE/oq-ddwinsolutions-prod && php bin/phpunit tests/Integration/IshikawaApiRouteDuplicationTest.php --filter testPostSaveRouteIsRegisteredOnce`

Attendu avant fusion : **FAIL** (2 routes) ou message listant 2 noms.

- [ ] **Étape 3 : Commit**

```bash
git add tests/Integration/IshikawaApiRouteDuplicationTest.php
git commit -m "test: assert single POST route for /api/ishikawa/save"
```

---

### Task 2 : Installer `symfony/ux-react`

**Fichiers :**

- Modifier : `composer.json`, `composer.lock`
- Modifier : `config/bundles.php`

- [ ] **Étape 1 : Composer**

Run :

```bash
cd /home/neliville/dev/LAB/QSE/oq-ddwinsolutions-prod && composer require symfony/ux-react --no-interaction
```

Attendu : dépendance ajoutée ; Flex ajoute `Symfony\UX\React\ReactBundle::class => ['all' => true]` dans `config/bundles.php` (vérifier manuellement si besoin).

- [ ] **Étape 2 : Vérifier le bundle**

Run : `php bin/console debug:config react 2>/dev/null | head -5`

Attendu : pas d’erreur fatale (config peut être absente jusqu’à Task 3).

- [ ] **Étape 3 : Commit**

```bash
git add composer.json composer.lock config/bundles.php symfony.lock
git commit -m "chore: add symfony/ux-react"
```

---

### Task 3 : Config React + importmap CSS + scripts racine

**Fichiers :**

- Créer : `config/packages/react.yaml`
- Modifier : `importmap.php`
- Modifier : `package.json` (racine)
- Modifier : `.gitignore`

- [ ] **Étape 1 : Créer `config/packages/react.yaml`**

```yaml
react:
  controllers_path: '%kernel.project_dir%/assets/react/controllers'
```

- [ ] **Étape 2 : Ajouter le CSS dans `importmap.php` (tableau retourné)**

Ajouter une entrée (ajuster la clé si `importmap:require` a déjà été utilisé) :

```php
'@xyflow/react/dist/style.css' => [
    'version' => '12.3.0',
    'type' => 'css',
],
```

- [ ] **Étape 3 : Étendre le `package.json` racine**

Fusionner dans la clé `"scripts"` existante :

```json
"build:react": "cd assets/react-src/build && npm install && npm run build",
"watch:react": "cd assets/react-src/build && npm install && npm run watch"
```

- [ ] **Étape 4 : `.gitignore`**

Ajouter une ligne :

```
assets/react-src/build/node_modules/
```

- [ ] **Étape 5 : Vérification**

Run : `php bin/console importmap:install --no-interaction`

Run : `php bin/console debug:asset-map | head -3`

- [ ] **Étape 6 : Commit**

```bash
git add config/packages/react.yaml importmap.php package.json .gitignore
git commit -m "chore: react.yaml, xyflow css importmap, npm build:react scripts"
```

---

### Task 4 : Toolchain Babel (`assets/react-src/build`)

**Fichiers :**

- Créer : `assets/react-src/build/package.json`
- Créer : `assets/react-src/build/.babelrc`

- [ ] **Étape 1 : `assets/react-src/build/package.json`**

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

- [ ] **Étape 2 : `assets/react-src/build/.babelrc`**

```json
{
  "presets": [
    "@babel/preset-env",
    ["@babel/preset-react", { "runtime": "automatic" }]
  ]
}
```

- [ ] **Étape 3 : `npm install` dans le dossier build**

Run :

```bash
cd /home/neliville/dev/LAB/QSE/oq-ddwinsolutions-prod/assets/react-src/build && npm install
```

Attendu : exit code 0.

- [ ] **Étape 4 : Commit**

```bash
git add assets/react-src/build/package.json assets/react-src/build/.babelrc assets/react-src/build/package-lock.json
git commit -m "chore: babel build toolchain for IshikawaEditor"
```

---

### Task 5 : Fusion API — `share` dans le contrôleur Tool, suppression du doublon

**Fichiers :**

- Modifier : `src/Controller/Tool/IshikawaController.php`
- Supprimer : `src/Tools/Api/IshikawaController.php`

- [ ] **Étape 1 : Dans `App\Controller\Tool\IshikawaController`, ajouter les `use`**

```php
use App\Entity\IshikawaShare;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
```

- [ ] **Étape 2 : Ajouter la méthode `share` (copie logique depuis l’ancien `App\Tools\Api\IshikawaController::share`)**

Signature et attributs :

```php
#[Route('/share', name: 'app_api_ishikawa_share', methods: ['POST'])]
public function share(
    Request $request,
    IshikawaAnalysisRepository $analysisRepository,
    EntityManagerInterface $entityManager,
    UrlGeneratorInterface $urlGenerator,
): JsonResponse {
```

Corps : identique à `src/Tools/Api/IshikawaController.php` lignes 85–126 (validation `id`, propriété utilisateur, création `IshikawaShare`, `JsonResponse` avec `url` et `expiresAt`). Conserver `#[IsGranted('ROLE_USER')]` **sur la méthode** si le contrôleur Tool reste accessible sans auth pour d’autres actions — ici la méthode doit exiger un utilisateur connecté (comme l’ancien contrôleur Api).

- [ ] **Étape 3 : Supprimer le fichier `src/Tools/Api/IshikawaController.php`**

- [ ] **Étape 4 : Lancer les tests API existants**

Run : `php bin/phpunit tests/Integration/ApiIshikawaControllerTest.php`

Attendu : **PASS** (comportement save/list/get/delete inchangé).

- [ ] **Étape 5 : Lancer le test de duplication**

Run : `php bin/phpunit tests/Integration/IshikawaApiRouteDuplicationTest.php`

Attendu : **PASS** (une seule route POST save).

- [ ] **Étape 6 : Commit**

```bash
git add src/Controller/Tool/IshikawaController.php && git add -u src/Tools/Api/IshikawaController.php
git commit -m "refactor(api): single Ishikawa API controller, merge share into Tool"
```

---

### Task 6 : Contrôleur page v2 + test fonctionnel

**Fichiers :**

- Créer : `src/Tools/Controller/IshikawaV2Controller.php`
- Créer : `tests/Integration/IshikawaV2ControllerTest.php`

- [ ] **Étape 1 : Créer `src/Tools/Controller/IshikawaV2Controller.php`**

```php
<?php

namespace App\Tools\Controller;

use App\Entity\User;
use App\Lead\Service\QuotaService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/ishikawa-v2', name: 'app_ishikawa_v2_')]
final class IshikawaV2Controller extends AbstractController
{
    public function __construct(
        private readonly QuotaService $quotaService,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $recordId = $request->query->getInt('record', 0) ?: null;

        if (!$recordId && !$this->quotaService->canSaveNew($user)) {
            $this->addFlash('warning', 'Vous avez atteint votre quota d\'analyses. Passez au plan Starter pour continuer.');

            return $this->redirectToRoute('app_dashboard_index');
        }

        return $this->render('ishikawa_v2/index.html.twig', [
            'record_id' => $recordId,
            'page_title' => 'Diagramme Ishikawa (Nouvelle version)',
        ]);
    }
}
```

- [ ] **Étape 2 : Créer `tests/Integration/IshikawaV2ControllerTest.php`**

```php
<?php

namespace App\Tests\Integration;

use App\Tests\TestCase\WebTestCaseWithDatabase;

final class IshikawaV2ControllerTest extends WebTestCaseWithDatabase
{
    public function testIshikawaV2RedirectsWhenAnonymous(): void
    {
        $this->client->request('GET', '/ishikawa-v2');
        self::assertResponseRedirects();
    }

    public function testIshikawaV2OkWhenAuthenticated(): void
    {
        $user = $this->createTestUser();
        $this->client->loginUser($user);
        $this->client->request('GET', '/ishikawa-v2');
        self::assertResponseIsSuccessful();
    }
}
```

- [ ] **Étape 3 : Lancer les tests**

Run : `php bin/phpunit tests/Integration/IshikawaV2ControllerTest.php`

Attendu : **PASS**.

- [ ] **Étape 4 : Commit**

```bash
git add src/Tools/Controller/IshikawaV2Controller.php tests/Integration/IshikawaV2ControllerTest.php
git commit -m "feat(ishikawa): v2 page controller and tests"
```

---

### Task 7 : Template Twig v2 (Tailwind + React + CSS importmap)

**Fichiers :**

- Créer : `templates/ishikawa_v2/index.html.twig`

- [ ] **Étape 1 : Créer le template**

```twig
{% extends 'base.html.twig' %}

{% block title %}{{ page_title }} — Outils Qualité{% endblock %}

{% block stylesheets %}
    {{ parent() }}
    {{ importmap('@xyflow/react/dist/style.css') }}
{% endblock %}

{% block body %}
<div class="min-h-screen flex flex-col bg-background">
    <header class="flex flex-wrap items-center gap-4 px-5 py-3 bg-slate-800 text-white border-b border-slate-700">
        <a href="{{ path('app_ishikawa_index') }}" class="text-sky-300 hover:underline text-sm no-underline">
            ← Version actuelle
        </a>
        <span class="font-medium text-sm">Diagramme Ishikawa</span>
        <span class="inline-flex items-center rounded-full bg-yellow-200 text-yellow-900 text-xs font-bold px-2.5 py-0.5">
            NOUVELLE VERSION — Test
        </span>
        <span class="text-xs text-slate-400 ml-auto">
            Validez avant migration définitive
        </span>
    </header>

    <div class="flex-1 min-h-[70vh] w-full">
        {{ react_component('IshikawaEditor', {
            recordId: record_id,
            apiBase: '/api/ishikawa',
            csrfToken: csrf_token('ishikawa_record'),
        }) }}
    </div>
</div>
{% endblock %}
```

- [ ] **Étape 2 : Vérification manuelle rapide**

Run : `php bin/console debug:router app_ishikawa_v2_index`

Attendu : route `GET` `/ishikawa-v2`.

- [ ] **Étape 3 : Commit**

```bash
git add templates/ishikawa_v2/index.html.twig
git commit -m "feat(ishikawa): v2 twig template with Tailwind and react_component"
```

---

### Task 8 : Sources JSX — copier depuis `Implementation reactflow ishikawa.md` + dossier `assets/react-src/IshikawaEditor/`

**Fichiers :**

- Créer l’arborescence listée dans `REACTFLOW_IMPLEMENTATION_GUIDE.md` (section Architecture), en recopiant le code depuis **`Implementation reactflow ishikawa.md`** :
  - `utils/constants.js` (Étape 3.1, ~L248)
  - `utils/ishikawaLayout.js` (Étape 5.1, ~L1155)
  - `store/slices/nodesSlice.js` (Étape 3.2, ~L297)
  - `store/slices/metaSlice.js` (Étape 3.4, ~L527)
  - `store/slices/uiSlice.js` (Étape 3.5, ~L562)
  - `store/slices/edgesSlice.js` (Étape 3.3, ~L451) — **remplacer le corps par la version de la tâche 9** avant build
  - `components/nodes/EffectNode.jsx`, `CategoryNode.jsx`, `CauseNode.jsx` (Étape 4.1–4.3)
  - `components/IshikawaCanvas.jsx` (Étape 4.4)
  - `components/panels/ToolbarPanel.jsx` (Étape 4.5)
  - `components/controls/ExportButton.jsx` (Étape 4.6)
  - Créer **s’ils manquent** dans le doc : `components/panels/PropertiesPanel.jsx`, `components/panels/MetaPanel.jsx`, `components/edges/SpineEdge.jsx`, `components/edges/BoneEdge.jsx` avec les implémentations **minimales** ci-dessous.

- [ ] **Étape 1 : `components/panels/MetaPanel.jsx`**

```jsx
import useIshikawaStore from '../../store/useIshikawaStore.js';

export default function MetaPanel() {
  const meta = useIshikawaStore((s) => s.meta);
  const updateMeta = useIshikawaStore((s) => s.updateMeta);

  return (
    <div className="border-b border-slate-200 bg-white px-5 py-3 flex flex-wrap gap-4 items-center">
      <label className="text-sm text-slate-600 flex flex-col gap-1">
        Problème
        <textarea
          className="border border-slate-300 rounded px-2 py-1 min-w-[240px] text-sm"
          rows={2}
          value={meta.problem}
          onChange={(e) => updateMeta({ problem: e.target.value })}
        />
      </label>
      <label className="text-sm text-slate-600 flex flex-col gap-1">
        Auteur
        <input
          type="text"
          className="border border-slate-300 rounded px-2 py-1 text-sm"
          value={meta.author}
          onChange={(e) => updateMeta({ author: e.target.value })}
        />
      </label>
    </div>
  );
}
```

- [ ] **Étape 2 : `components/panels/PropertiesPanel.jsx`**

```jsx
import useIshikawaStore from '../../store/useIshikawaStore.js';

export default function PropertiesPanel() {
  const selectedId = useIshikawaStore((s) => s.ui.selectedNodeId);
  const nodes = useIshikawaStore((s) => s.nodes);
  const updateNodeLabel = useIshikawaStore((s) => s.updateNodeLabel);

  if (!selectedId) {
    return null;
  }

  const node = nodes.find((n) => n.id === selectedId);
  if (!node) {
    return null;
  }

  return (
    <div className="bg-white border border-slate-200 rounded-lg shadow p-4 w-64 text-sm">
      <h3 className="font-semibold mb-2">Nœud sélectionné</h3>
      <label className="block text-slate-600 mb-1">Libellé</label>
      <input
        type="text"
        className="w-full border border-slate-300 rounded px-2 py-1"
        value={node.data.label ?? ''}
        onChange={(e) => updateNodeLabel(selectedId, e.target.value)}
      />
    </div>
  );
}
```

- [ ] **Étape 3 : `components/edges/SpineEdge.jsx`**

```jsx
import { BaseEdge, getBezierPath } from '@xyflow/react';

export default function SpineEdge({ id, sourceX, sourceY, targetX, targetY, sourcePosition, targetPosition, style }) {
  const [edgePath] = getBezierPath({ sourceX, sourceY, sourcePosition, targetX, targetY, targetPosition });
  return <BaseEdge id={id} path={edgePath} style={{ ...style, strokeWidth: 3, stroke: '#64748b' }} />;
}
```

- [ ] **Étape 4 : `components/edges/BoneEdge.jsx`**

```jsx
import { BaseEdge, getBezierPath } from '@xyflow/react';

export default function BoneEdge({ id, sourceX, sourceY, targetX, targetY, sourcePosition, targetPosition, data, style }) {
  const [edgePath] = getBezierPath({ sourceX, sourceY, sourcePosition, targetX, targetY, targetPosition });
  const stroke = data?.color ?? '#94a3b8';
  return <BaseEdge id={id} path={edgePath} style={{ ...style, strokeWidth: 2, stroke }} />;
}
```

- [ ] **Étape 5 : Dans `IshikawaCanvas.jsx`, enregistrer `edgeTypes` hors du composant**

```jsx
import { EDGE_TYPES_KEYS } from '../utils/constants.js';
import SpineEdge from './edges/SpineEdge.jsx';
import BoneEdge from './edges/BoneEdge.jsx';

const edgeTypes = {
  [EDGE_TYPES_KEYS.SPINE]: SpineEdge,
  [EDGE_TYPES_KEYS.BONE]: BoneEdge,
};
```

Passer `edgeTypes={edgeTypes}` à `<ReactFlow …>`.

- [ ] **Étape 6 : Commit intermédiaire (sources brutes + panneaux manquants)**

```bash
git add assets/react-src/IshikawaEditor
git commit -m "feat(ishikawa): React editor sources from implementation doc + minimal panels/edges"
```

---

### Task 9 : `edgesSlice.js` — retirer l’arête vers un nœud inexistant `spine-start`

**Fichiers :**

- Modifier : `assets/react-src/IshikawaEditor/store/slices/edgesSlice.js`

- [ ] **Étape 0 : En tête de `edgesSlice.js`, importer aussi `NODE_TYPES_KEYS`**

```javascript
import { EDGE_TYPES_KEYS, NODE_TYPES_KEYS } from '../../utils/constants.js';
```

- [ ] **Étape 1 : Remplacer `initDefaultEdges` par**

```javascript
  initDefaultEdges: (effectNodeId) => {
    const nodes = get().nodes;
    const catNodes = nodes.filter((n) => n.type === NODE_TYPES_KEYS.CATEGORY);
    const edges = [];

    catNodes.forEach((cat) => {
      edges.push({
        id: `edge-${cat.id}-effect`,
        source: cat.id,
        target: effectNodeId,
        type: EDGE_TYPES_KEYS.BONE,
        data: { isBone: true, color: cat.data.color },
      });
    });

    set({ edges });
  },
```

(Supprimer tout bloc qui créait `spine-main` avec `source: 'spine-start'`.)

- [ ] **Étape 2 : Commit**

```bash
git add assets/react-src/IshikawaEditor/store/slices/edgesSlice.js
git commit -m "fix(ishikawa): default edges without spine-start ghost node"
```

---

### Task 10 : Serializer + store — API réelle et enveloppe GET

**Fichiers :**

- Modifier : `assets/react-src/IshikawaEditor/utils/ishikawaSerializer.js`
- Modifier : `assets/react-src/IshikawaEditor/store/useIshikawaStore.js`

- [ ] **Étape 1 : Contenu complet de `ishikawaSerializer.js`**

```javascript
/**
 * Payload POST /api/ishikawa/save — aligné sur App\Controller\Tool\IshikawaController::save
 */
export function serializeToRecord(storeState) {
  const { nodes, edges, meta } = storeState;

  const contentObject = {
    nodes: nodes.map((node) => ({
      id: node.id,
      type: node.type,
      position: node.position,
      data: {
        label: node.data.label,
        categoryId: node.data.categoryId ?? null,
        color: node.data.color ?? null,
        isTop: node.data.isTop ?? null,
        parentCategoryId: node.data.parentCategoryId ?? null,
      },
      width: node.width ?? null,
      height: node.height ?? null,
    })),
    edges: edges.map((edge) => ({
      id: edge.id,
      source: edge.source,
      target: edge.target,
      type: edge.type,
      data: edge.data ?? {},
    })),
    meta: {
      author: meta.author,
      date: meta.date,
    },
  };

  const payload = {
    title: meta.title,
    content: contentObject,
    problem: meta.problem,
  };

  if (meta.recordId) {
    payload.id = meta.recordId;
  }

  return payload;
}

/**
 * Normalise la réponse GET (enveloppe { success, data }) ou charge utile direct.
 */
export function deserializeFromRecord(apiResponse) {
  const envelope =
    apiResponse && typeof apiResponse.success === 'boolean' && apiResponse.data !== undefined
      ? apiResponse.data
      : apiResponse;

  const inner = envelope.content && typeof envelope.content === 'object' ? envelope.content : {};

  return {
    id: envelope.id,
    title: envelope.title ?? 'Sans titre',
    problem: envelope.problem ?? '',
    createdAt: envelope.createdAt,
    updatedAt: envelope.updatedAt ?? null,
    content: {
      nodes: inner.nodes ?? [],
      edges: inner.edges ?? [],
      meta: inner.meta ?? {},
    },
  };
}
```

- [ ] **Étape 2 : Dans `useIshikawaStore.js` — imports**

```javascript
import { serializeToRecord, deserializeFromRecord } from '../utils/ishikawaSerializer.js';
```

- [ ] **Étape 3 : Remplacer `loadFromRecord`**

```javascript
      loadFromRecord: (record) => {
        const { nodes, edges, meta: contentMeta } = record.content ?? {};
        set({
          nodes: nodes ?? [],
          edges: edges ?? [],
          meta: {
            title: record.title ?? 'Sans titre',
            problem: record.problem ?? '',
            author: contentMeta?.author ?? '',
            date: record.createdAt
              ? String(record.createdAt).slice(0, 10)
              : new Date().toISOString().split('T')[0],
            recordId: record.id,
            isDirty: false,
            lastSavedAt: record.updatedAt ?? null,
            _apiBase: get().meta?._apiBase,
            _csrfToken: get().meta?._csrfToken,
          },
        });
      },
```

- [ ] **Étape 4 : Ajouter `saveDiagram` et `loadDiagram` (URLs `/api/ishikawa/...`)**

```javascript
      saveDiagram: async () => {
        const state = get();
        get().setSaving(true, null);

        try {
          const payload = serializeToRecord(state);
          const response = await fetch('/api/ishikawa/save', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-Requested-With': 'XMLHttpRequest',
              'X-CSRF-Token': state.meta._csrfToken ?? '',
            },
            body: JSON.stringify(payload),
          });

          if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
          }

          const json = await response.json();
          if (!json.success) {
            throw new Error(json.message ?? 'Erreur inconnue');
          }

          get().setRecordId(json.data.id);
          get().setSaving(false, null);
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
          if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
          }
          const apiResponse = await response.json();
          const record = deserializeFromRecord(apiResponse);
          get().loadFromRecord(record);
        } catch (err) {
          console.error('Erreur chargement:', err);
        }
      },
```

- [ ] **Étape 5 : Vérifier `setSaving` dans `uiSlice`**

Si la signature n’est qu’à un argument, la remplacer par :

```javascript
  setSaving: (isSaving, error = null) => {
    set((state) => ({
      ui: { ...state.ui, isSaving, saveError: error },
    }));
  },
```

(Souvent déjà présente ainsi dans le fichier copié depuis `Implementation reactflow ishikawa.md`.)

- [ ] **Étape 6 : Commit**

```bash
git add assets/react-src/IshikawaEditor/utils/ishikawaSerializer.js assets/react-src/IshikawaEditor/store/useIshikawaStore.js assets/react-src/IshikawaEditor/store/slices/uiSlice.js
git commit -m "feat(ishikawa): serializer and store wired to real Ishikawa API"
```

---

### Task 11 : Point d’entrée `index.jsx` + UX React default export

**Fichiers :**

- Créer ou modifier : `assets/react-src/IshikawaEditor/index.jsx`

- [ ] **Étape 1 : Contenu de `index.jsx` (props camelCase alignées Twig)**

```jsx
import { ReactFlowProvider } from '@xyflow/react';
import { useEffect } from 'react';
import IshikawaCanvas from './components/IshikawaCanvas.jsx';
import MetaPanel from './components/panels/MetaPanel.jsx';
import useIshikawaStore from './store/useIshikawaStore.js';

export default function IshikawaEditor({ recordId, apiBase, csrfToken }) {
  const initDefaultDiagram = useIshikawaStore((s) => s.initDefaultDiagram);
  const loadDiagram = useIshikawaStore((s) => s.loadDiagram);
  const updateMeta = useIshikawaStore((s) => s.updateMeta);

  useEffect(() => {
    updateMeta({ _apiBase: apiBase ?? '/api/ishikawa', _csrfToken: csrfToken ?? '' });

    if (recordId) {
      loadDiagram(recordId);
    } else {
      initDefaultDiagram();
    }
  }, [recordId, apiBase, csrfToken, initDefaultDiagram, loadDiagram, updateMeta]);

  return (
    <ReactFlowProvider>
      <div className="flex flex-col h-full min-h-[70vh]">
        <MetaPanel />
        <IshikawaCanvas />
      </div>
    </ReactFlowProvider>
  );
}
```

- [ ] **Étape 2 : Commit**

```bash
git add assets/react-src/IshikawaEditor/index.jsx
git commit -m "feat(ishikawa): React entry IshikawaEditor with ReactFlowProvider"
```

---

### Task 12 : Build Babel, commit des JS compilés, cache Symfony

**Fichiers :**

- Créer / modifier : `assets/react/controllers/**` (générés)

- [ ] **Étape 1 : Build**

Run :

```bash
cd /home/neliville/dev/LAB/QSE/oq-ddwinsolutions-prod/assets/react-src/build && npm run build
```

Attendu : `assets/react/controllers/IshikawaEditor.js` présent (et sous-fichiers copiés si `--copy-files`).

- [ ] **Étape 2 : Vérifier le renderer**

Run : `php bin/console debug:container react.component_renderer`

Attendu : service listé sans erreur.

- [ ] **Étape 3 : `cache:clear`**

Run : `php bin/console cache:clear --no-warmup`

- [ ] **Étape 4 : Commit des artefacts**

```bash
git add assets/react/controllers
git commit -m "chore: compile IshikawaEditor React controllers for deploy"
```

---

### Task 13 : Suite de tests + non-régression

- [ ] **Étape 1 : PHPUnit ciblé**

Run :

```bash
cd /home/neliville/dev/LAB/QSE/oq-ddwinsolutions-prod && php bin/phpunit tests/Integration/ApiIshikawaControllerTest.php tests/Integration/IshikawaV2ControllerTest.php tests/Integration/IshikawaApiRouteDuplicationTest.php tests/Functional/IshikawaControllerTest.php
```

Attendu : tous **PASS**.

- [ ] **Étape 2 : Router**

Run : `php bin/console debug:router | grep ishikawa`

Attendu : `app_ishikawa_index`, `app_ishikawa_v2_index`, une seule ligne par endpoint fusionné (`save`, `share`, `list`, `get`, `delete`).

- [ ] **Étape 3 : Checklist manuelle** (navigateur)

Suivre `REACTFLOW_IMPLEMENTATION_GUIDE.md` phases **7.1–7.9** (comportement éditeur, save, load `?record=`, export PNG, quota utilisateur FREE).

- [ ] **Étape 4 : Commit final si ajustements tests**

```bash
git add tests && git commit -m "test: ishikawa v2 and api route coverage" || true
```

---

## Auto-revue du plan (checklist interne)

1. **Couverture spec** : ux-react, Babel, importmap CSS, contrôleur v2 + quota + dashboard, fusion API + `app_api_ishikawa_share`, serializer + enveloppe GET, déploiement O2switch via artefacts versionnés + `deploy.sh` — couverts par les tâches 2–7, 9–10, 12–13.  
2. **Placeholders** : aucun TBD ; les fichiers JSX non reproduits ligne à ligne dans ce plan sont couverts par la copie depuis `Implementation reactflow ishikawa.md` + les blocs complets des panneaux / arêtes.  
3. **Cohérence** : `setSaving(true, null)` exige la signature à deux arguments en tâche 10 ; `edgeTypes` ajouté en tâche 8 ; suppression `spine-start` en tâche 9.

---

**Plan enregistré dans** `docs/superpowers/plans/2026-05-09-ishikawa-v2-reactflow.md`.

**Deux modes d’exécution possibles :**

1. **Subagent-driven (recommandé)** — un agent par tâche, relecture entre les tâches.  
2. **Inline** — enchaîner les tâches dans cette session avec `executing-plans` et points de contrôle.

Laquelle préfères-tu ?


Shell