import {
  LAYOUT_CONFIG,
  NODE_DIMENSIONS,
  NODE_TYPES_KEYS,
  EDGE_TYPES_KEYS,
  ISHIKAWA_CATEGORIES_5M,
  TAIL_NODE_ID,
  EFFECT_NODE_ID,
} from './constants.js';

/**
 * Génère un ID unique court
 */
export function generateId() {
  return Math.random().toString(36).substring(2, 9);
}

/** Aligne largeur / hauteur des nœuds sur les constantes courantes (ex. après chargement d’anciennes sauvegardes). */
export function normalizeNodeDimensionsToCurrent(nodes) {
  return (nodes ?? []).map((n) => {
    switch (n.type) {
      case NODE_TYPES_KEYS.TAIL:
        return { ...n, width: NODE_DIMENSIONS.TAIL.width, height: NODE_DIMENSIONS.TAIL.height };
      case NODE_TYPES_KEYS.EFFECT:
        return { ...n, width: NODE_DIMENSIONS.EFFECT.width, height: NODE_DIMENSIONS.EFFECT.height };
      case NODE_TYPES_KEYS.CATEGORY:
        return { ...n, width: NODE_DIMENSIONS.CATEGORY.width, height: NODE_DIMENSIONS.CATEGORY.height };
      case NODE_TYPES_KEYS.CAUSE:
        return { ...n, width: NODE_DIMENSIONS.CAUSE.width, height: NODE_DIMENSIONS.CAUSE.height };
      default:
        return n;
    }
  });
}

const degToRad = (deg) => (deg * Math.PI) / 180;

/**
 * À partir du nœud catégorie (position + dimensions), déduit l’accroche sur l’arête et le côté haut/bas.
 * Inverse de getCategoryCenter : centre = (attachX - cos·L, SPINE_Y ± sin·L) ⇒ attachX = cx + cos·L.
 */
export function spineAttachFromCategoryPosition(catNode) {
  const w = catNode.width ?? NODE_DIMENSIONS.CATEGORY.width;
  const h = catNode.height ?? NODE_DIMENSIONS.CATEGORY.height;
  const cx = catNode.position.x + w / 2;
  const cy = catNode.position.y + h / 2;
  const rad = degToRad(LAYOUT_CONFIG.BONE_ANGLE_DEG);
  const cos = Math.cos(rad);
  const attachX = cx + cos * LAYOUT_CONFIG.BONE_LENGTH;
  const attachY = LAYOUT_CONFIG.SPINE_Y;
  const isTop = cy < LAYOUT_CONFIG.SPINE_Y;
  return { attachX, attachY, isTop, centerX: cx, centerY: cy };
}

/** Met à jour data.spineAttach* / isTop selon la position actuelle de la catégorie. */
export function reconcileCategoryFishbone(catNode) {
  if (catNode.type !== NODE_TYPES_KEYS.CATEGORY) return catNode;
  const { attachX, attachY, isTop } = spineAttachFromCategoryPosition(catNode);
  return {
    ...catNode,
    data: {
      ...catNode.data,
      spineAttachX: attachX,
      spineAttachY: attachY,
      isTop,
    },
  };
}

/** Recalcule la géométrie des catégories ; queue fixe, tête / effet déplaçable horizontalement. */
export function finalizeIshikawaNodes(nodes) {
  return nodes.map((n) => {
    const r = reconcileCategoryFishbone(n);
    if (r.id === TAIL_NODE_ID) {
      return { ...r, draggable: false };
    }
    if (r.id === EFFECT_NODE_ID) {
      return { ...r, draggable: true };
    }
    if (r.type === NODE_TYPES_KEYS.CATEGORY || r.type === NODE_TYPES_KEYS.CAUSE) {
      return { ...r, draggable: true };
    }
    return r;
  });
}

/** Synchronise les arêtes catégorie → effet (points sur l’arête, couleur). */
export function syncCategoryFishboneEdges(nodes, edges) {
  return edges.map((e) => {
    if (e.target !== EFFECT_NODE_ID || !e.source?.startsWith('cat-')) return e;
    const cat = nodes.find((n) => n.id === e.source);
    if (!cat || cat.type !== NODE_TYPES_KEYS.CATEGORY) return e;
    const ax = cat.data?.spineAttachX;
    const ay = cat.data?.spineAttachY;
    if (typeof ax !== 'number' || typeof ay !== 'number') return e;
    return {
      ...e,
      data: {
        ...e.data,
        fishbone: true,
        spineAttachX: ax,
        spineAttachY: ay,
        color: cat.data?.color ?? e.data?.color,
      },
    };
  });
}

/**
 * Point de l’os principal identique à l’arête catégorie → effet : handle source bas (catégorie haut)
 * ou haut (catégorie bas), comme dans `CategoryNode` (`to-spine`).
 */
export function getCategoryBoneHandleXY(catNode) {
  const w = catNode.width ?? NODE_DIMENSIONS.CATEGORY.width;
  const h = catNode.height ?? NODE_DIMENSIONS.CATEGORY.height;
  const cy = catNode.position.y + h / 2;
  const isTop =
    typeof catNode.data?.isTop === 'boolean' ? catNode.data.isTop : cy < LAYOUT_CONFIG.SPINE_Y;
  const bx = catNode.position.x + w / 2;
  const by = isTop ? catNode.position.y + h : catNode.position.y;
  return { bx, by };
}

/** Point sur l’os (segment accroche arête → handle os), t ∈ [0,1], 0 = accroche, 1 = catégorie. */
export function getPointOnCategoryBone(catNode, t) {
  const ax = catNode.data?.spineAttachX;
  const ay = catNode.data?.spineAttachY ?? LAYOUT_CONFIG.SPINE_Y;
  const { bx, by } = getCategoryBoneHandleXY(catNode);
  const tt = Math.max(0, Math.min(1, t));
  return {
    x: ax + tt * (bx - ax),
    y: ay + tt * (by - ay),
  };
}

/** Marge sur t (extrémités de l’os). */
const CAUSE_T_HI = 0.92;
const CAUSE_T_LO = 0.1;
/** Écart minimal entre deux jonctions sur l’os (évite causes « collées » qui se gênent visuellement). */
const CAUSE_MIN_SEPARATION_ALONG_BONE_PX = 52;

/**
 * Liste des t sur l’os pour n causes (tri droite→gauche : index 0 = près de la catégorie, n−1 = vers la queue).
 * Respecte un écart minimal le long du segment os, avec repli sur un espacement uniforme si trop de causes.
 */
export function computeCauseBoneSlotTs(catNode, n) {
  if (n <= 1) return [(CAUSE_T_HI + CAUSE_T_LO) / 2];
  const ax = catNode.data?.spineAttachX;
  const ay = catNode.data?.spineAttachY ?? LAYOUT_CONFIG.SPINE_Y;
  const { bx, by } = getCategoryBoneHandleXY(catNode);
  const len = Math.hypot(bx - ax, by - ay) || 1;
  const maxSpread = CAUSE_T_HI - CAUSE_T_LO;
  const uniformStep = maxSpread / (n - 1);
  const minStepByPx = Math.max(0.055, Math.min(0.2, CAUSE_MIN_SEPARATION_ALONG_BONE_PX / len));
  let step = Math.max(uniformStep, minStepByPx);
  if ((n - 1) * step > maxSpread + 1e-9) {
    step = uniformStep;
    return Array.from({ length: n }, (_, i) => {
      const t = CAUSE_T_HI - i * step;
      return Math.max(CAUSE_T_LO, Math.min(CAUSE_T_HI, t));
    });
  }
  const used = (n - 1) * step;
  const slack = maxSpread - used;
  const tStart = CAUSE_T_HI - slack / 2;
  return Array.from({ length: n }, (_, i) => {
    const t = tStart - i * step;
    return Math.max(CAUSE_T_LO, Math.min(CAUSE_T_HI, t));
  });
}

/**
 * Paramètre t sur l’os pour la i‑ème cause (fallback sans géométrie os — préférer computeCauseBoneSlotTs).
 */
export function computeCauseBoneSlotT(indexRightToLeft, n) {
  if (n <= 1) return (CAUSE_T_HI + CAUSE_T_LO) / 2;
  const step = (CAUSE_T_HI - CAUSE_T_LO) / (n - 1);
  return CAUSE_T_HI - indexRightToLeft * step;
}

/**
 * Recalcule positions + `boneSlotT` pour toutes les causes d’une catégorie.
 * `reassignT` : redistribue les t (ajout / migration) ; sinon conserve `boneSlotT` et ne met à jour que x,y.
 */
export function repositionCausesAlongBone(catNode, causeNodes, options = {}) {
  const { reassignT = false } = options;
  const sorted = [...causeNodes].sort((a, b) => b.position.x - a.position.x);
  const n = sorted.length;
  const gap = 22;
  const w = NODE_DIMENSIONS.CAUSE.width;
  const h = NODE_DIMENSIONS.CAUSE.height;
  const rightAnchor = catNode.position.x - 12;
  const tsDefault = computeCauseBoneSlotTs(catNode, n);

  return sorted.map((cause, i) => {
    let t;
    if (!reassignT && typeof cause.data?.boneSlotT === 'number' && Number.isFinite(cause.data.boneSlotT)) {
      t = Math.max(0, Math.min(1, cause.data.boneSlotT));
    } else {
      t = tsDefault[i];
    }
    const { y: jy } = getPointOnCategoryBone(catNode, t);
    const x = rightAnchor - (i + 1) * (w + gap);
    const y = jy - h / 2;
    return {
      ...cause,
      position: { x, y },
      data: { ...cause.data, boneSlotT: t },
    };
  });
}

/** Réancrage de toutes les causes du diagramme (chargement / migration). */
export function reanchorAllCausesInDiagram(nodes) {
  const catIds = nodes.filter((n) => n.type === NODE_TYPES_KEYS.CATEGORY).map((n) => n.id);
  let out = [...nodes];
  for (const catId of catIds) {
    const cat = out.find((n) => n.id === catId);
    if (!cat) continue;
    const causes = out.filter(
      (n) => n.type === NODE_TYPES_KEYS.CAUSE && n.data?.parentCategoryId === catId
    );
    if (!causes.length) continue;
    const needT = causes.some((c) => typeof c.data?.boneSlotT !== 'number');
    const updated = repositionCausesAlongBone(cat, causes, { reassignT: needT });
    const map = new Map(updated.map((u) => [u.id, u]));
    out = out.map((n) => map.get(n.id) ?? n);
  }
  return out;
}

/**
 * Paramètre t sur l’os le plus proche du point (px, py) — utile après glisser une cause.
 */
export function projectPointToBoneSlotT(catNode, px, py) {
  const ax = catNode.data?.spineAttachX;
  const ay = catNode.data?.spineAttachY ?? LAYOUT_CONFIG.SPINE_Y;
  const { bx, by } = getCategoryBoneHandleXY(catNode);
  const dx = bx - ax;
  const dy = by - ay;
  const len2 = dx * dx + dy * dy;
  if (len2 < 1e-12) return 0.5;
  let u = ((px - ax) * dx + (py - ay) * dy) / len2;
  u = Math.max(0.04, Math.min(0.96, u));
  return u;
}

/**
 * Nervure cause → point sur l’os uniquement : **segment droit** (fluide au déplacement, sans coude en L).
 */
export function getCauseRibPath(sourceX, sourceY, _targetX, _targetY, catNode, boneSlotT) {
  const ax = catNode.data?.spineAttachX;
  const ay = catNode.data?.spineAttachY ?? LAYOUT_CONFIG.SPINE_Y;
  const { bx, by } = getCategoryBoneHandleXY(catNode);
  const dx = bx - ax;
  const dy = by - ay;
  let t;
  if (typeof boneSlotT === 'number' && Number.isFinite(boneSlotT)) {
    t = Math.max(0, Math.min(1, boneSlotT));
  } else if (Math.abs(dy) < 1e-6) {
    t = 0.5;
  } else {
    t = (sourceY - ay) / dy;
    t = Math.max(0, Math.min(1, t));
  }
  const jx = ax + t * dx;
  const jy = ay + t * dy;
  return `M ${sourceX} ${sourceY} L ${jx} ${jy}`;
}

/** Prochaine abscisse d’accroche sur l’arête pour une nouvelle catégorie (évite la zone de la tête). */
export function getNextCategorySpineAttachX(nodes) {
  const cats = nodes.filter((n) => n.type === NODE_TYPES_KEYS.CATEGORY);
  let maxAttach = LAYOUT_CONFIG.CATEGORY_FIRST_ATTACH_X;
  for (const c of cats) {
    const ax = typeof c.data?.spineAttachX === 'number' ? c.data.spineAttachX : 0;
    maxAttach = Math.max(maxAttach, ax);
  }
  const next = maxAttach + LAYOUT_CONFIG.CATEGORY_ATTACH_GAP;
  const headLeft = LAYOUT_CONFIG.HEAD_CENTER_X - NODE_DIMENSIONS.EFFECT.width / 2;
  const margin = 100;
  return Math.min(next, headLeft - margin);
}

/**
 * Abscisse sur l’arête horizontale pour la catégorie d’index `index` (0..n-1).
 */
export function getCategorySpineAttachX(index) {
  return LAYOUT_CONFIG.CATEGORY_FIRST_ATTACH_X + index * LAYOUT_CONFIG.CATEGORY_ATTACH_GAP;
}

/**
 * Centre (x, y) du nœud catégorie : branche oblique depuis (attachX, SPINE_Y) vers l’extérieur du poisson.
 * Haut : au-dessus de l’arête ; bas : en dessous.
 */
export function getCategoryCenter(attachX, isTop) {
  const rad = degToRad(LAYOUT_CONFIG.BONE_ANGLE_DEG);
  const L = LAYOUT_CONFIG.BONE_LENGTH;
  const cos = Math.cos(rad);
  const sin = Math.sin(rad);
  const dx = -cos * L;
  const dy = isTop ? -sin * L : sin * L;
  return {
    x: attachX + dx,
    y: LAYOUT_CONFIG.SPINE_Y + dy,
  };
}

/** Coin supérieur gauche du nœud catégorie (React Flow). */
export function getCategoryTopLeft(attachX, isTop) {
  const c = getCategoryCenter(attachX, isTop);
  return {
    x: c.x - NODE_DIMENSIONS.CATEGORY.width / 2,
    y: c.y - NODE_DIMENSIONS.CATEGORY.height / 2,
  };
}

/** Coin supérieur gauche de la queue (fixe). */
export function getTailTopLeft() {
  return {
    x: LAYOUT_CONFIG.TAIL_CENTER_X - NODE_DIMENSIONS.TAIL.width / 2,
    y: LAYOUT_CONFIG.SPINE_Y - NODE_DIMENSIONS.TAIL.height / 2,
  };
}

/** Coin supérieur gauche de la tête / effet (fixe). */
export function getEffectTopLeft() {
  return {
    x: LAYOUT_CONFIG.HEAD_CENTER_X - NODE_DIMENSIONS.EFFECT.width / 2,
    y: LAYOUT_CONFIG.SPINE_Y - NODE_DIMENSIONS.EFFECT.height / 2,
  };
}

/**
 * @deprecated Utiliser `repositionCausesAlongBone` depuis le slice (plusieurs causes d’un coup).
 * Conservé pour appels externes éventuels : place une seule cause « brouillon » avant reposition globale.
 */
export function getNextCauseTopLeft(catNode, existingCauseCount) {
  const n = existingCauseCount + 1;
  const ts = computeCauseBoneSlotTs(catNode, n);
  const t = ts[n - 1];
  const { y: jy } = getPointOnCategoryBone(catNode, t);
  const h = NODE_DIMENSIONS.CAUSE.height;
  const gap = 16;
  const w = NODE_DIMENSIONS.CAUSE.width;
  const x = catNode.position.x - 12 - (existingCauseCount + 1) * (w + gap);
  return { x, y: jy - h / 2 };
}

/**
 * Recalcule les positions (extension possible).
 */
export function recalculateLayout(nodes, _config) {
  return finalizeIshikawaNodes(nodes);
}

/**
 * Complète queue / accroches arête pour les enregistrements créés avant le modèle arête de poisson.
 */
export function patchDiagramFromLegacyRecord(nodes, edges) {
  let outNodes = normalizeNodeDimensionsToCurrent([...(nodes ?? [])]);
  let outEdges = [...(edges ?? [])];

  outNodes = outNodes.map((n) => {
    if (n.type !== NODE_TYPES_KEYS.CATEGORY) return n;
    if (n.data?.spineAttachX != null && n.data?.spineAttachY != null) return n;
    const idx = ISHIKAWA_CATEGORIES_5M.findIndex((c) => `cat-${c.id}` === n.id);
    if (idx < 0) return n;
    const attachX = getCategorySpineAttachX(idx);
    return {
      ...n,
      data: {
        ...n.data,
        spineAttachX: attachX,
        spineAttachY: LAYOUT_CONFIG.SPINE_Y,
      },
    };
  });

  if (!outNodes.some((n) => n.id === TAIL_NODE_ID) && outNodes.some((n) => n.id === EFFECT_NODE_ID)) {
    const tp = getTailTopLeft();
    outNodes.unshift({
      id: TAIL_NODE_ID,
      type: NODE_TYPES_KEYS.TAIL,
      position: { x: tp.x, y: tp.y },
      data: { label: 'Cause racines', isFixed: true },
      width: NODE_DIMENSIONS.TAIL.width,
      height: NODE_DIMENSIONS.TAIL.height,
      draggable: false,
      selectable: true,
    });
    if (!outEdges.some((e) => e.id === 'edge-tail-effect-spine')) {
      outEdges.unshift({
        id: 'edge-tail-effect-spine',
        source: TAIL_NODE_ID,
        target: EFFECT_NODE_ID,
        type: EDGE_TYPES_KEYS.SPINE,
      });
    }
  }

  outEdges = outEdges.map((e) => {
    if (e.target !== EFFECT_NODE_ID || !e.source?.startsWith('cat-')) return e;
    const cat = outNodes.find((n) => n.id === e.source);
    const ax = cat?.data?.spineAttachX;
    const ay = cat?.data?.spineAttachY;
    if (typeof ax !== 'number' || typeof ay !== 'number') return e;
    return {
      ...e,
      data: {
        ...e.data,
        isBone: true,
        color: cat?.data?.color ?? e.data?.color ?? '#94a3b8',
        fishbone: true,
        spineAttachX: ax,
        spineAttachY: ay,
      },
    };
  });

  outNodes = finalizeIshikawaNodes(outNodes);
  outEdges = syncCategoryFishboneEdges(outNodes, outEdges);
  outNodes = reanchorAllCausesInDiagram(outNodes);

  return { nodes: outNodes, edges: outEdges };
}

