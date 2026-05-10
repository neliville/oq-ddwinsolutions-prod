/**
 * Convertit le JSON historique du canvas (`getDiagramData()` dans public/js/ishikawa.js)
 * vers le modèle nœuds / arêtes React Flow.
 */
import {
  EDGE_TYPES_KEYS,
  EFFECT_NODE_ID,
  ISHIKAWA_CATEGORIES_6M,
  ISHIKAWA_EXTRA_CATEGORY_COLORS,
  LAYOUT_CONFIG,
  NODE_DIMENSIONS,
  NODE_TYPES_KEYS,
  TAIL_NODE_ID,
} from './constants.js';
import {
  finalizeIshikawaNodes,
  generateId,
  getCategorySpineAttachX,
  getCategoryTopLeft,
  getEffectTopLeft,
  getTailTopLeft,
  repositionCausesAlongBone,
  syncCategoryFishboneEdges,
} from './ishikawaLayout.js';

function stripDiacritics(s) {
  return String(s || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .trim();
}

/**
 * Associe un libellé libre (v1) à un id de catégorie connu pour la palette / cohérence.
 */
function inferPresetCategoryId(name) {
  const n = stripDiacritics(name);
  if (!n) return null;
  const rules = [
    { id: 'matiere', keys: ['matiere', 'matiere premiere', 'matieres'] },
    { id: 'milieu', keys: ['milieu', 'environnement', 'temperature', 'eclairage', 'conditions'] },
    {
      id: 'methode',
      keys: ['methode', 'methodes', 'procedure', 'procedures', 'instruction', 'mesure', 'metrologie', 'calibr'],
    },
    { id: 'materiel', keys: ['materiel', 'materiels', 'machine', 'machines', 'equipement'] },
    { id: 'mainoeuvre', keys: ['main', 'main doeuvre', 'main-doeuvre', 'formation', 'competence'] },
    { id: 'management', keys: ['management', 'leadership', 'pilotage'] },
  ];
  for (const { id, keys } of rules) {
    if (keys.some((k) => n.includes(k) || k.includes(n))) return id;
  }
  for (const c of ISHIKAWA_CATEGORIES_6M) {
    const label = stripDiacritics(c.label);
    if (label && (n === label || n.includes(label) || label.includes(n))) return c.id;
  }
  return null;
}

function causeText(entry) {
  if (typeof entry === 'string') return entry;
  if (!entry || typeof entry !== 'object') return '';
  if (typeof entry.text === 'string') return entry.text;
  if (typeof entry.label === 'string') return entry.label;
  if (typeof entry.description === 'string') return entry.description;
  if (typeof entry.name === 'string') return entry.name;
  if (typeof entry.data === 'string') return entry.data;
  return '';
}

/**
 * @param {{ categories?: unknown[], problem?: string }} inner — typiquement `record.content` legacy
 * @param {string} problemFallback — `record.problem` API
 * @returns {{ nodes: object[], edges: object[] }}
 */
export function buildFlowFromLegacyCanvasContent(inner, problemFallback = '') {
  const rawCats = Array.isArray(inner?.categories) ? inner.categories : [];
  if (rawCats.length === 0) {
    return { nodes: [], edges: [] };
  }

  const categories = [...rawCats].sort((a, b) => (Number(a?.spineX) || 0) - (Number(b?.spineX) || 0));
  const problemText =
    (typeof inner?.problem === 'string' && inner.problem.trim() ? inner.problem : '') ||
    (typeof problemFallback === 'string' ? problemFallback : '') ||
    'Problème à analyser';

  const nodes = [];
  const edges = [];

  const tailPos = getTailTopLeft();
  nodes.push({
    id: TAIL_NODE_ID,
    type: NODE_TYPES_KEYS.TAIL,
    position: { x: tailPos.x, y: tailPos.y },
    data: { label: 'Cause racines', isFixed: true },
    width: NODE_DIMENSIONS.TAIL.width,
    height: NODE_DIMENSIONS.TAIL.height,
    draggable: false,
    selectable: true,
  });

  const effectPos = getEffectTopLeft();
  nodes.push({
    id: EFFECT_NODE_ID,
    type: NODE_TYPES_KEYS.EFFECT,
    position: { x: effectPos.x, y: effectPos.y },
    data: {
      label: problemText,
      isEditing: false,
      isHead: true,
    },
    width: NODE_DIMENSIONS.EFFECT.width,
    height: NODE_DIMENSIONS.EFFECT.height,
    draggable: true,
    selectable: true,
  });

  edges.push({
    id: 'edge-tail-effect-spine',
    source: TAIL_NODE_ID,
    target: EFFECT_NODE_ID,
    type: EDGE_TYPES_KEYS.SPINE,
  });

  categories.forEach((rawCat, idx) => {
    const attachX = getCategorySpineAttachX(idx);
    const isTop = idx % 2 === 0;
    const pos = getCategoryTopLeft(attachX, isTop);
    const name = typeof rawCat?.name === 'string' ? rawCat.name : `Catégorie ${idx + 1}`;
    const presetId = inferPresetCategoryId(name);
    const color =
      typeof rawCat?.color === 'string' && /^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(rawCat.color.trim())
        ? rawCat.color.trim()
        : ISHIKAWA_EXTRA_CATEGORY_COLORS[idx % ISHIKAWA_EXTRA_CATEGORY_COLORS.length];

    const catNodeId = `cat-import-${idx}`;
    nodes.push({
      id: catNodeId,
      type: NODE_TYPES_KEYS.CATEGORY,
      position: { x: pos.x, y: pos.y },
      data: {
        label: name,
        categoryId: presetId ?? `legacy-${idx}`,
        color,
        isTop,
        isEditing: false,
        spineAttachX: attachX,
        spineAttachY: LAYOUT_CONFIG.SPINE_Y,
      },
      width: NODE_DIMENSIONS.CATEGORY.width,
      height: NODE_DIMENSIONS.CATEGORY.height,
      draggable: true,
      selectable: true,
    });

    edges.push({
      id: `edge-${catNodeId}-effect`,
      source: catNodeId,
      target: EFFECT_NODE_ID,
      type: EDGE_TYPES_KEYS.BONE,
      zIndex: 1,
      data: {
        isBone: true,
        color,
        fishbone: true,
        spineAttachX: attachX,
        spineAttachY: LAYOUT_CONFIG.SPINE_Y,
      },
    });

    const rawCauses = Array.isArray(rawCat?.causes) ? rawCat.causes : [];
    const texts = rawCauses.map(causeText).filter((t) => t.trim().length > 0);
    if (!texts.length) return;

    const catNode = nodes.find((n) => n.id === catNodeId);
    /** Ordre stable pour `repositionCausesAlongBone` (tri par `position.x`). */
    const stubs = texts.map((label, i) => ({
      id: `cause-${generateId()}`,
      type: NODE_TYPES_KEYS.CAUSE,
      position: { x: 2000 - i, y: 0 },
      data: {
        label,
        parentCategoryId: catNodeId,
        isEditing: false,
        isNew: false,
      },
      width: NODE_DIMENSIONS.CAUSE.width,
      height: NODE_DIMENSIONS.CAUSE.height,
      draggable: true,
      selectable: true,
    }));

    const laidOut = repositionCausesAlongBone(catNode, stubs, { reassignT: true });
    laidOut.forEach((c) => {
      nodes.push(c);
      edges.push({
        id: `edge-${c.id}-${catNodeId}`,
        source: c.id,
        target: catNodeId,
        type: EDGE_TYPES_KEYS.BONE,
        zIndex: -2,
        data: { isBone: true, color },
      });
    });
  });

  let finalized = finalizeIshikawaNodes(nodes);
  let syncedEdges = syncCategoryFishboneEdges(finalized, edges);
  finalized = finalizeIshikawaNodes(finalized);

  return { nodes: finalized, edges: syncedEdges };
}

/**
 * @param {object} content — `record.content` tel que renvoyé par l’API
 * @returns {boolean}
 */
export function isLegacyCanvasContent(content) {
  if (!content || typeof content !== 'object') return false;
  const cats = content.categories;
  const nodes = content.nodes;
  const hasCats = Array.isArray(cats) && cats.length > 0;
  const hasFlowNodes = Array.isArray(nodes) && nodes.length > 0;
  return hasCats && !hasFlowNodes;
}
