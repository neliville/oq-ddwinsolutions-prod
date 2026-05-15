// ../IshikawaEditor/index.jsx
import React from "react";
import { ReactFlowProvider } from "@xyflow/react";
import { Component, useEffect as useEffect3 } from "react";

// ../IshikawaEditor/components/IshikawaCanvas.jsx
import { ReactFlow, Background, Controls, MiniMap, Panel } from "@xyflow/react";
import { useShallow as useShallow4 } from "zustand/react/shallow";

// ../IshikawaEditor/store/useIshikawaStore.js
import { create } from "zustand";
import { devtools } from "zustand/middleware";

// ../IshikawaEditor/utils/ishikawaSerializer.js
function serializeToRecord(storeState) {
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
        spineAttachX: node.data.spineAttachX ?? null,
        spineAttachY: node.data.spineAttachY ?? null,
        isFixed: node.data.isFixed ?? null,
        isHead: node.data.isHead ?? null,
        boneSlotT: node.data.boneSlotT ?? null
      },
      width: node.width ?? null,
      height: node.height ?? null
    })),
    edges: edges.map((edge) => ({
      id: edge.id,
      source: edge.source,
      target: edge.target,
      type: edge.type,
      data: edge.data ?? {}
    })),
    meta: {
      author: meta.author,
      date: meta.date
    }
  };
  const payload = {
    title: meta.title,
    content: contentObject,
    problem: meta.problem
  };
  if (meta.recordId) {
    payload.id = meta.recordId;
  }
  return payload;
}
function parseStoredContent(raw) {
  let v = raw;
  if (typeof v === "string") {
    const t = v.trim();
    if (!t) return {};
    try {
      v = JSON.parse(t);
    } catch {
      return {};
    }
  }
  if (v == null) return {};
  if (Array.isArray(v)) {
    return { categories: v };
  }
  if (typeof v === "object") {
    return (
      /** @type {Record<string, unknown>} */
      v
    );
  }
  return {};
}
function deserializeFromRecord(apiResponse) {
  const envelope = apiResponse && typeof apiResponse.success === "boolean" && apiResponse.data !== void 0 ? apiResponse.data : apiResponse;
  const inner = parseStoredContent(envelope?.content);
  const innerProblem = typeof inner.problem === "string" && inner.problem.trim() ? inner.problem : "";
  return {
    id: envelope.id,
    title: envelope.title ?? "Sans titre",
    problem: (typeof envelope.problem === "string" && envelope.problem.trim() ? envelope.problem : "") || innerProblem,
    createdAt: envelope.createdAt,
    updatedAt: envelope.updatedAt ?? null,
    content: {
      nodes: inner.nodes ?? [],
      edges: inner.edges ?? [],
      meta: inner.meta ?? {},
      /** Préservé pour l’import legacy (canvas v1) — ne pas retirer. */
      categories: inner.categories,
      problem: typeof inner.problem === "string" ? inner.problem : void 0
    }
  };
}

// ../IshikawaEditor/utils/constants.js
var ISHIKAWA_CATEGORIES_5M = [
  { id: "matiere", label: "Mati\xE8re", color: "#E53E3E", shortLabel: "MAT" },
  { id: "milieu", label: "Milieu", color: "#38A169", shortLabel: "MIL" },
  { id: "methode", label: "M\xE9thode", color: "#3182CE", shortLabel: "MET" },
  { id: "materiel", label: "Mat\xE9riel", color: "#D69E2E", shortLabel: "MAT" },
  { id: "mainoeuvre", label: "Main-d'\u0153uvre", color: "#805AD5", shortLabel: "MO" }
];
var ISHIKAWA_CATEGORIES_6M = [
  ...ISHIKAWA_CATEGORIES_5M,
  { id: "management", label: "Management", color: "#ED8936", shortLabel: "MGT" }
];
var NODE_DIMENSIONS = {
  TAIL: { width: 104, height: 44 },
  EFFECT: { width: 240, height: 68 },
  /** Largeur accrue pour libellés type « Management » sans rognage (cf. canvas v1). */
  CATEGORY: { width: 220, height: 46 },
  CAUSE: { width: 148, height: 34 }
};
var LAYOUT_CONFIG = {
  /** Ordonnée de l’arête (axe du poisson). */
  SPINE_Y: 380,
  /** Centre horizontal de la queue (nœud « début »). */
  TAIL_CENTER_X: 100,
  /** Centre horizontal de la tête (effet / problème) — plus à droite pour aérer (proche du canvas v1). */
  HEAD_CENTER_X: 1140,
  /** Longueur de la branche oblique colonne vertébrale → catégorie. */
  BONE_LENGTH: 136,
  /** Angle (deg) entre l’arête horizontale et la branche vers la catégorie (côté queue). */
  BONE_ANGLE_DEG: 56,
  /** Premier point d’accroche sur l’arête pour la 1re catégorie. */
  CATEGORY_FIRST_ATTACH_X: 300,
  /** Espacement entre accroches sur l’arête (catégories plus espacées). */
  CATEGORY_ATTACH_GAP: 148
};
var NODE_TYPES_KEYS = {
  TAIL: "tailNode",
  EFFECT: "effectNode",
  CATEGORY: "categoryNode",
  CAUSE: "causeNode"
};
var EDGE_TYPES_KEYS = {
  SPINE: "spineEdge",
  BONE: "boneEdge"
};
var TAIL_NODE_ID = "tail-main";
var EFFECT_NODE_ID = "effect-main";
var ISHIKAWA_EXTRA_CATEGORY_COLORS = [
  "#0D9488",
  "#C026D3",
  "#EA580C",
  "#4F46E5",
  "#0891B2",
  "#65A30D"
];
var ISHIKAWA_MIN_CATEGORIES = 1;

// ../IshikawaEditor/utils/ishikawaLayout.js
function generateId() {
  return Math.random().toString(36).substring(2, 9);
}
function normalizeNodeDimensionsToCurrent(nodes) {
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
var degToRad = (deg) => deg * Math.PI / 180;
function spineAttachFromCategoryPosition(catNode) {
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
function reconcileCategoryFishbone(catNode) {
  if (catNode.type !== NODE_TYPES_KEYS.CATEGORY) return catNode;
  const { attachX, attachY, isTop } = spineAttachFromCategoryPosition(catNode);
  return {
    ...catNode,
    data: {
      ...catNode.data,
      spineAttachX: attachX,
      spineAttachY: attachY,
      isTop
    }
  };
}
function finalizeIshikawaNodes(nodes) {
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
function syncCategoryFishboneEdges(nodes, edges) {
  return edges.map((e) => {
    if (e.target !== EFFECT_NODE_ID || !e.source?.startsWith("cat-")) return e;
    const cat = nodes.find((n) => n.id === e.source);
    if (!cat || cat.type !== NODE_TYPES_KEYS.CATEGORY) return e;
    const ax = cat.data?.spineAttachX;
    const ay = cat.data?.spineAttachY;
    if (typeof ax !== "number" || typeof ay !== "number") return e;
    return {
      ...e,
      data: {
        ...e.data,
        fishbone: true,
        spineAttachX: ax,
        spineAttachY: ay,
        color: cat.data?.color ?? e.data?.color
      }
    };
  });
}
function getCategoryBoneHandleXY(catNode) {
  const w = catNode.width ?? NODE_DIMENSIONS.CATEGORY.width;
  const h = catNode.height ?? NODE_DIMENSIONS.CATEGORY.height;
  const cy = catNode.position.y + h / 2;
  const isTop = typeof catNode.data?.isTop === "boolean" ? catNode.data.isTop : cy < LAYOUT_CONFIG.SPINE_Y;
  const bx = catNode.position.x + w / 2;
  const by = isTop ? catNode.position.y + h : catNode.position.y;
  return { bx, by };
}
function getPointOnCategoryBone(catNode, t) {
  const ax = catNode.data?.spineAttachX;
  const ay = catNode.data?.spineAttachY ?? LAYOUT_CONFIG.SPINE_Y;
  const { bx, by } = getCategoryBoneHandleXY(catNode);
  const tt = Math.max(0, Math.min(1, t));
  return {
    x: ax + tt * (bx - ax),
    y: ay + tt * (by - ay)
  };
}
var CAUSE_T_HI = 0.92;
var CAUSE_T_LO = 0.1;
var CAUSE_MIN_SEPARATION_ALONG_BONE_PX = 52;
function computeCauseBoneSlotTs(catNode, n) {
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
function repositionCausesAlongBone(catNode, causeNodes, options = {}) {
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
    if (!reassignT && typeof cause.data?.boneSlotT === "number" && Number.isFinite(cause.data.boneSlotT)) {
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
      data: { ...cause.data, boneSlotT: t }
    };
  });
}
function reanchorAllCausesInDiagram(nodes) {
  const catIds = nodes.filter((n) => n.type === NODE_TYPES_KEYS.CATEGORY).map((n) => n.id);
  let out = [...nodes];
  for (const catId of catIds) {
    const cat = out.find((n) => n.id === catId);
    if (!cat) continue;
    const causes = out.filter(
      (n) => n.type === NODE_TYPES_KEYS.CAUSE && n.data?.parentCategoryId === catId
    );
    if (!causes.length) continue;
    const needT = causes.some((c) => typeof c.data?.boneSlotT !== "number");
    const updated = repositionCausesAlongBone(cat, causes, { reassignT: needT });
    const map = new Map(updated.map((u) => [u.id, u]));
    out = out.map((n) => map.get(n.id) ?? n);
  }
  return out;
}
function projectPointToBoneSlotT(catNode, px, py) {
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
function getCauseRibPath(sourceX, sourceY, _targetX, _targetY, catNode, boneSlotT) {
  const ax = catNode.data?.spineAttachX;
  const ay = catNode.data?.spineAttachY ?? LAYOUT_CONFIG.SPINE_Y;
  const { bx, by } = getCategoryBoneHandleXY(catNode);
  const dx = bx - ax;
  const dy = by - ay;
  let t;
  if (typeof boneSlotT === "number" && Number.isFinite(boneSlotT)) {
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
function getNextCategorySpineAttachX(nodes) {
  const cats = nodes.filter((n) => n.type === NODE_TYPES_KEYS.CATEGORY);
  let maxAttach = LAYOUT_CONFIG.CATEGORY_FIRST_ATTACH_X;
  for (const c of cats) {
    const ax = typeof c.data?.spineAttachX === "number" ? c.data.spineAttachX : 0;
    maxAttach = Math.max(maxAttach, ax);
  }
  const next = maxAttach + LAYOUT_CONFIG.CATEGORY_ATTACH_GAP;
  const headLeft = LAYOUT_CONFIG.HEAD_CENTER_X - NODE_DIMENSIONS.EFFECT.width / 2;
  const margin = 100;
  return Math.min(next, headLeft - margin);
}
function getCategorySpineAttachX(index) {
  return LAYOUT_CONFIG.CATEGORY_FIRST_ATTACH_X + index * LAYOUT_CONFIG.CATEGORY_ATTACH_GAP;
}
function getCategoryCenter(attachX, isTop) {
  const rad = degToRad(LAYOUT_CONFIG.BONE_ANGLE_DEG);
  const L = LAYOUT_CONFIG.BONE_LENGTH;
  const cos = Math.cos(rad);
  const sin = Math.sin(rad);
  const dx = -cos * L;
  const dy = isTop ? -sin * L : sin * L;
  return {
    x: attachX + dx,
    y: LAYOUT_CONFIG.SPINE_Y + dy
  };
}
function getCategoryTopLeft(attachX, isTop) {
  const c = getCategoryCenter(attachX, isTop);
  return {
    x: c.x - NODE_DIMENSIONS.CATEGORY.width / 2,
    y: c.y - NODE_DIMENSIONS.CATEGORY.height / 2
  };
}
function getTailTopLeft() {
  return {
    x: LAYOUT_CONFIG.TAIL_CENTER_X - NODE_DIMENSIONS.TAIL.width / 2,
    y: LAYOUT_CONFIG.SPINE_Y - NODE_DIMENSIONS.TAIL.height / 2
  };
}
function getEffectTopLeft() {
  return {
    x: LAYOUT_CONFIG.HEAD_CENTER_X - NODE_DIMENSIONS.EFFECT.width / 2,
    y: LAYOUT_CONFIG.SPINE_Y - NODE_DIMENSIONS.EFFECT.height / 2
  };
}
function patchDiagramFromLegacyRecord(nodes, edges) {
  let outNodes = normalizeNodeDimensionsToCurrent([...nodes ?? []]);
  let outEdges = [...edges ?? []];
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
        spineAttachY: LAYOUT_CONFIG.SPINE_Y
      }
    };
  });
  if (!outNodes.some((n) => n.id === TAIL_NODE_ID) && outNodes.some((n) => n.id === EFFECT_NODE_ID)) {
    const tp = getTailTopLeft();
    outNodes.unshift({
      id: TAIL_NODE_ID,
      type: NODE_TYPES_KEYS.TAIL,
      position: { x: tp.x, y: tp.y },
      data: { label: "Cause racines", isFixed: true },
      width: NODE_DIMENSIONS.TAIL.width,
      height: NODE_DIMENSIONS.TAIL.height,
      draggable: false,
      selectable: true
    });
    if (!outEdges.some((e) => e.id === "edge-tail-effect-spine")) {
      outEdges.unshift({
        id: "edge-tail-effect-spine",
        source: TAIL_NODE_ID,
        target: EFFECT_NODE_ID,
        type: EDGE_TYPES_KEYS.SPINE
      });
    }
  }
  outEdges = outEdges.map((e) => {
    if (e.target !== EFFECT_NODE_ID || !e.source?.startsWith("cat-")) return e;
    const cat = outNodes.find((n) => n.id === e.source);
    const ax = cat?.data?.spineAttachX;
    const ay = cat?.data?.spineAttachY;
    if (typeof ax !== "number" || typeof ay !== "number") return e;
    return {
      ...e,
      data: {
        ...e.data,
        isBone: true,
        color: cat?.data?.color ?? e.data?.color ?? "#94a3b8",
        fishbone: true,
        spineAttachX: ax,
        spineAttachY: ay
      }
    };
  });
  outNodes = finalizeIshikawaNodes(outNodes);
  outEdges = syncCategoryFishboneEdges(outNodes, outEdges);
  outNodes = reanchorAllCausesInDiagram(outNodes);
  return { nodes: outNodes, edges: outEdges };
}

// ../IshikawaEditor/utils/legacyIshikawaCanvasImport.js
function stripDiacritics(s) {
  return String(s || "").normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase().trim();
}
function inferPresetCategoryId(name) {
  const n = stripDiacritics(name);
  if (!n) return null;
  const rules = [
    { id: "matiere", keys: ["matiere", "matiere premiere", "matieres"] },
    { id: "milieu", keys: ["milieu", "environnement", "temperature", "eclairage", "conditions"] },
    {
      id: "methode",
      keys: ["methode", "methodes", "procedure", "procedures", "instruction", "mesure", "metrologie", "calibr"]
    },
    { id: "materiel", keys: ["materiel", "materiels", "machine", "machines", "equipement"] },
    { id: "mainoeuvre", keys: ["main", "main doeuvre", "main-doeuvre", "formation", "competence"] },
    { id: "management", keys: ["management", "leadership", "pilotage"] }
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
  if (typeof entry === "string") return entry;
  if (!entry || typeof entry !== "object") return "";
  if (typeof entry.text === "string") return entry.text;
  if (typeof entry.label === "string") return entry.label;
  if (typeof entry.description === "string") return entry.description;
  if (typeof entry.name === "string") return entry.name;
  if (typeof entry.data === "string") return entry.data;
  return "";
}
function buildFlowFromLegacyCanvasContent(inner, problemFallback = "") {
  const rawCats = Array.isArray(inner?.categories) ? inner.categories : [];
  if (rawCats.length === 0) {
    return { nodes: [], edges: [] };
  }
  const categories = [...rawCats].sort((a, b) => (Number(a?.spineX) || 0) - (Number(b?.spineX) || 0));
  const problemText = (typeof inner?.problem === "string" && inner.problem.trim() ? inner.problem : "") || (typeof problemFallback === "string" ? problemFallback : "") || "Probl\xE8me \xE0 analyser";
  const nodes = [];
  const edges = [];
  const tailPos = getTailTopLeft();
  nodes.push({
    id: TAIL_NODE_ID,
    type: NODE_TYPES_KEYS.TAIL,
    position: { x: tailPos.x, y: tailPos.y },
    data: { label: "Cause racines", isFixed: true },
    width: NODE_DIMENSIONS.TAIL.width,
    height: NODE_DIMENSIONS.TAIL.height,
    draggable: false,
    selectable: true
  });
  const effectPos = getEffectTopLeft();
  nodes.push({
    id: EFFECT_NODE_ID,
    type: NODE_TYPES_KEYS.EFFECT,
    position: { x: effectPos.x, y: effectPos.y },
    data: {
      label: problemText,
      isEditing: false,
      isHead: true
    },
    width: NODE_DIMENSIONS.EFFECT.width,
    height: NODE_DIMENSIONS.EFFECT.height,
    draggable: true,
    selectable: true
  });
  edges.push({
    id: "edge-tail-effect-spine",
    source: TAIL_NODE_ID,
    target: EFFECT_NODE_ID,
    type: EDGE_TYPES_KEYS.SPINE
  });
  categories.forEach((rawCat, idx) => {
    const attachX = getCategorySpineAttachX(idx);
    const isTop = idx % 2 === 0;
    const pos = getCategoryTopLeft(attachX, isTop);
    const name = typeof rawCat?.name === "string" ? rawCat.name : `Cat\xE9gorie ${idx + 1}`;
    const presetId = inferPresetCategoryId(name);
    const color = typeof rawCat?.color === "string" && /^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(rawCat.color.trim()) ? rawCat.color.trim() : ISHIKAWA_EXTRA_CATEGORY_COLORS[idx % ISHIKAWA_EXTRA_CATEGORY_COLORS.length];
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
        spineAttachY: LAYOUT_CONFIG.SPINE_Y
      },
      width: NODE_DIMENSIONS.CATEGORY.width,
      height: NODE_DIMENSIONS.CATEGORY.height,
      draggable: true,
      selectable: true
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
        spineAttachY: LAYOUT_CONFIG.SPINE_Y
      }
    });
    const rawCauses = Array.isArray(rawCat?.causes) ? rawCat.causes : [];
    const texts = rawCauses.map(causeText).filter((t) => t.trim().length > 0);
    if (!texts.length) return;
    const catNode = nodes.find((n) => n.id === catNodeId);
    const stubs = texts.map((label, i) => ({
      id: `cause-${generateId()}`,
      type: NODE_TYPES_KEYS.CAUSE,
      position: { x: 2e3 - i, y: 0 },
      data: {
        label,
        parentCategoryId: catNodeId,
        isEditing: false,
        isNew: false
      },
      width: NODE_DIMENSIONS.CAUSE.width,
      height: NODE_DIMENSIONS.CAUSE.height,
      draggable: true,
      selectable: true
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
        data: { isBone: true, color }
      });
    });
  });
  let finalized = finalizeIshikawaNodes(nodes);
  let syncedEdges = syncCategoryFishboneEdges(finalized, edges);
  finalized = finalizeIshikawaNodes(finalized);
  return { nodes: finalized, edges: syncedEdges };
}
function isLegacyCanvasContent(content) {
  if (!content || typeof content !== "object") return false;
  const cats = content.categories;
  const nodes = content.nodes;
  const hasCats = Array.isArray(cats) && cats.length > 0;
  const hasFlowNodes = Array.isArray(nodes) && nodes.length > 0;
  return hasCats && !hasFlowNodes;
}

// ../IshikawaEditor/store/slices/nodesSlice.js
import { applyNodeChanges } from "@xyflow/react";
var createNodesSlice = (set, get) => ({
  nodes: [],
  onNodesChange: (changes) => {
    set((state) => {
      let nodes = applyNodeChanges(changes, state.nodes);
      nodes = finalizeIshikawaNodes(nodes);
      const edges = syncCategoryFishboneEdges(nodes, state.edges);
      return {
        nodes,
        edges,
        meta: { ...state.meta, isDirty: true }
      };
    });
  },
  initDefaultDiagram: (problemLabel = "Probl\xE8me \xE0 analyser") => {
    const tailPos = getTailTopLeft();
    const effectPos = getEffectTopLeft();
    const nodes = [];
    nodes.push({
      id: TAIL_NODE_ID,
      type: NODE_TYPES_KEYS.TAIL,
      position: { x: tailPos.x, y: tailPos.y },
      data: { label: "Cause racines", isFixed: true },
      width: NODE_DIMENSIONS.TAIL.width,
      height: NODE_DIMENSIONS.TAIL.height,
      draggable: false,
      selectable: true
    });
    nodes.push({
      id: EFFECT_NODE_ID,
      type: NODE_TYPES_KEYS.EFFECT,
      position: { x: effectPos.x, y: effectPos.y },
      data: {
        label: problemLabel,
        isEditing: false,
        isHead: true
      },
      width: NODE_DIMENSIONS.EFFECT.width,
      height: NODE_DIMENSIONS.EFFECT.height,
      draggable: true,
      selectable: true
    });
    ISHIKAWA_CATEGORIES_5M.forEach((cat, index) => {
      const isTop = index % 2 === 0;
      const attachX = getCategorySpineAttachX(index);
      const pos = getCategoryTopLeft(attachX, isTop);
      nodes.push({
        id: `cat-${cat.id}`,
        type: NODE_TYPES_KEYS.CATEGORY,
        position: { x: pos.x, y: pos.y },
        data: {
          label: cat.label,
          categoryId: cat.id,
          color: cat.color,
          isTop,
          isEditing: false,
          spineAttachX: attachX,
          spineAttachY: LAYOUT_CONFIG.SPINE_Y
        },
        width: NODE_DIMENSIONS.CATEGORY.width,
        height: NODE_DIMENSIONS.CATEGORY.height,
        draggable: true,
        selectable: true
      });
    });
    set({ nodes });
    get().initDefaultEdges(EFFECT_NODE_ID);
    get().markClean();
  },
  snapEffectToSpineRow: () => {
    set((state) => {
      const eff = state.nodes.find((n) => n.id === EFFECT_NODE_ID);
      if (!eff) return {};
      const h = eff.height ?? NODE_DIMENSIONS.EFFECT.height;
      const y = LAYOUT_CONFIG.SPINE_Y - h / 2;
      const nodes = state.nodes.map(
        (n) => n.id === EFFECT_NODE_ID ? { ...n, position: { ...n.position, y } } : n
      );
      return { nodes, meta: { ...state.meta, isDirty: true } };
    });
  },
  addCategory: (label = "Nouvelle cat\xE9gorie") => {
    const state = get();
    const cats = state.nodes.filter((n) => n.type === NODE_TYPES_KEYS.CATEGORY);
    const attachX = getNextCategorySpineAttachX(state.nodes);
    const isTop = cats.length % 2 === 0;
    const pos = getCategoryTopLeft(attachX, isTop);
    const id = `cat-${generateId()}`;
    const color = ISHIKAWA_EXTRA_CATEGORY_COLORS[cats.length % ISHIKAWA_EXTRA_CATEGORY_COLORS.length];
    const slug = id.replace(/^cat-/, "");
    const newNode = {
      id,
      type: NODE_TYPES_KEYS.CATEGORY,
      position: { x: pos.x, y: pos.y },
      data: {
        label,
        categoryId: slug,
        color,
        isTop,
        isEditing: false,
        spineAttachX: attachX,
        spineAttachY: LAYOUT_CONFIG.SPINE_Y
      },
      width: NODE_DIMENSIONS.CATEGORY.width,
      height: NODE_DIMENSIONS.CATEGORY.height,
      draggable: true,
      selectable: true
    };
    set((s) => ({
      nodes: [...s.nodes, newNode],
      meta: { ...s.meta, isDirty: true }
    }));
    get().addCategoryBoneEdge(id);
  },
  snapCauseToParentBone: (causeId) => {
    set((state) => {
      const cause = state.nodes.find((n) => n.id === causeId);
      if (!cause || cause.type !== NODE_TYPES_KEYS.CAUSE) return {};
      const pid = cause.data?.parentCategoryId;
      if (!pid) return {};
      let cat = state.nodes.find((n) => n.id === pid);
      if (!cat) return {};
      cat = reconcileCategoryFishbone(cat);
      const w = cause.width ?? NODE_DIMENSIONS.CAUSE.width;
      const h = cause.height ?? NODE_DIMENSIONS.CAUSE.height;
      const cx = cause.position.x + w / 2;
      const cy = cause.position.y + h / 2;
      const t = projectPointToBoneSlotT(cat, cx, cy);
      const { y: jy } = getPointOnCategoryBone(cat, t);
      const y = jy - h / 2;
      const nodes = state.nodes.map(
        (n) => n.id === causeId ? { ...n, data: { ...n.data, boneSlotT: t }, position: { ...n.position, y } } : n
      );
      return { nodes, meta: { ...state.meta, isDirty: true } };
    });
  },
  reanchorCausesForCategory: (categoryNodeId, options = {}, draggedNode = null) => {
    const { reassignT = false } = options;
    set((state) => {
      const base = state.nodes.find((n) => n.id === categoryNodeId);
      if (!base || base.type !== NODE_TYPES_KEYS.CATEGORY) return {};
      const merged = draggedNode?.position != null ? { ...base, position: draggedNode.position } : base;
      const cat = reconcileCategoryFishbone(merged);
      const causes = state.nodes.filter(
        (n) => n.type === NODE_TYPES_KEYS.CAUSE && n.data?.parentCategoryId === categoryNodeId
      );
      if (!causes.length) return {};
      const updated = repositionCausesAlongBone(cat, causes, { reassignT });
      const map = new Map(updated.map((u) => [u.id, u]));
      const nodes = state.nodes.map((n) => map.get(n.id) ?? n);
      return { nodes, meta: { ...state.meta, isDirty: true } };
    });
  },
  addCause: (categoryNodeId, label = "Nouvelle cause") => {
    const state = get();
    const catNode = state.nodes.find((n) => n.id === categoryNodeId);
    if (!catNode) return;
    const existingCauses = state.nodes.filter(
      (n) => n.type === NODE_TYPES_KEYS.CAUSE && n.data.parentCategoryId === categoryNodeId
    );
    const newId = `cause-${generateId()}`;
    const stub = {
      id: newId,
      type: NODE_TYPES_KEYS.CAUSE,
      position: { x: 0, y: 0 },
      data: {
        label,
        parentCategoryId: categoryNodeId,
        isEditing: true,
        isNew: true
      },
      width: NODE_DIMENSIONS.CAUSE.width,
      height: NODE_DIMENSIONS.CAUSE.height,
      draggable: true,
      selectable: true
    };
    const laidOut = repositionCausesAlongBone(catNode, [...existingCauses, stub], { reassignT: true });
    set((s) => ({
      nodes: [
        ...s.nodes.filter(
          (n) => !(n.type === NODE_TYPES_KEYS.CAUSE && n.data?.parentCategoryId === categoryNodeId)
        ),
        ...laidOut
      ],
      meta: { ...s.meta, isDirty: true }
    }));
    get().addCauseEdge(categoryNodeId, newId);
  },
  updateNodeLabel: (nodeId, label) => {
    set((state) => ({
      nodes: state.nodes.map(
        (n) => n.id === nodeId ? { ...n, data: { ...n.data, label, isEditing: false, isNew: false } } : n
      ),
      meta: { ...state.meta, isDirty: true }
    }));
  },
  setNodeEditing: (nodeId, isEditing) => {
    set({
      nodes: get().nodes.map((n) => n.id === nodeId ? { ...n, data: { ...n.data, isEditing } } : n)
    });
  },
  deleteNode: (nodeId) => {
    if (nodeId === TAIL_NODE_ID || nodeId === EFFECT_NODE_ID) {
      return;
    }
    const state = get();
    const victim = state.nodes.find((n) => n.id === nodeId);
    if (!victim) return;
    if (victim.type === NODE_TYPES_KEYS.CAUSE) {
      const parentId = victim.data?.parentCategoryId;
      set({
        nodes: state.nodes.filter((n) => n.id !== nodeId),
        edges: state.edges.filter((e) => e.source !== nodeId && e.target !== nodeId),
        meta: { ...state.meta, isDirty: true }
      });
      if (parentId) get().reanchorCausesForCategory(parentId, { reassignT: true });
      return;
    }
    if (victim.type === NODE_TYPES_KEYS.CATEGORY) {
      const nCats = state.nodes.filter((n) => n.type === NODE_TYPES_KEYS.CATEGORY).length;
      if (nCats <= ISHIKAWA_MIN_CATEGORIES) {
        return;
      }
    }
    const childIds = state.nodes.filter((n) => n.data?.parentCategoryId === nodeId).map((n) => n.id);
    const idsToRemove = [nodeId, ...childIds];
    set({
      nodes: state.nodes.filter((n) => !idsToRemove.includes(n.id)),
      edges: state.edges.filter((e) => !idsToRemove.includes(e.source) && !idsToRemove.includes(e.target)),
      meta: { ...state.meta, isDirty: true }
    });
  },
  updateCategoryColor: (categoryNodeId, color) => {
    set((state) => ({
      nodes: state.nodes.map((n) => n.id === categoryNodeId ? { ...n, data: { ...n.data, color } } : n),
      meta: { ...state.meta, isDirty: true }
    }));
  }
});

// ../IshikawaEditor/store/slices/edgesSlice.js
import { applyEdgeChanges, addEdge } from "@xyflow/react";
var createEdgesSlice = (set, get) => ({
  edges: [],
  onEdgesChange: (changes) => {
    set((state) => ({
      edges: applyEdgeChanges(changes, state.edges),
      meta: { ...state.meta, isDirty: true }
    }));
  },
  onConnect: (connection) => {
    set((state) => ({
      edges: addEdge({ ...connection, type: EDGE_TYPES_KEYS.BONE, animated: false }, state.edges),
      meta: { ...state.meta, isDirty: true }
    }));
  },
  initDefaultEdges: (effectNodeId) => {
    const nodes = get().nodes;
    const catNodes = nodes.filter((n) => n.type === NODE_TYPES_KEYS.CATEGORY);
    const edges = [];
    edges.push({
      id: "edge-tail-effect-spine",
      source: TAIL_NODE_ID,
      target: effectNodeId,
      type: EDGE_TYPES_KEYS.SPINE
    });
    catNodes.forEach((cat) => {
      const ax = cat.data?.spineAttachX;
      const ay = cat.data?.spineAttachY;
      edges.push({
        id: `edge-${cat.id}-effect`,
        source: cat.id,
        target: effectNodeId,
        type: EDGE_TYPES_KEYS.BONE,
        zIndex: 1,
        data: {
          isBone: true,
          color: cat.data.color,
          fishbone: typeof ax === "number" && typeof ay === "number",
          spineAttachX: ax,
          spineAttachY: ay
        }
      });
    });
    set({ edges });
  },
  addCategoryBoneEdge: (categoryId) => {
    set((state) => {
      if (state.edges.some((e) => e.id === `edge-${categoryId}-effect`)) {
        return {};
      }
      const cat = state.nodes.find((n) => n.id === categoryId);
      if (!cat || cat.type !== NODE_TYPES_KEYS.CATEGORY) return {};
      const ax = cat.data?.spineAttachX;
      const ay = cat.data?.spineAttachY;
      const color = cat.data?.color ?? "#666";
      return {
        edges: [
          ...state.edges,
          {
            id: `edge-${categoryId}-effect`,
            source: categoryId,
            target: EFFECT_NODE_ID,
            type: EDGE_TYPES_KEYS.BONE,
            zIndex: 1,
            data: {
              isBone: true,
              color,
              fishbone: typeof ax === "number" && typeof ay === "number",
              spineAttachX: ax,
              spineAttachY: ay
            }
          }
        ],
        meta: { ...state.meta, isDirty: true }
      };
    });
  },
  addCauseEdge: (categoryId, causeId) => {
    const catNode = get().nodes.find((n) => n.id === categoryId);
    const color = catNode?.data?.color ?? "#666";
    set((state) => ({
      edges: [
        ...state.edges,
        {
          id: `edge-${causeId}-${categoryId}`,
          source: causeId,
          target: categoryId,
          type: EDGE_TYPES_KEYS.BONE,
          zIndex: -2,
          data: { isBone: true, color }
        }
      ],
      meta: { ...state.meta, isDirty: true }
    }));
  }
});

// ../IshikawaEditor/store/slices/metaSlice.js
var createMetaSlice = (set) => ({
  meta: {
    title: "Nouvelle analyse Ishikawa",
    problem: "",
    author: "",
    date: (/* @__PURE__ */ new Date()).toISOString().split("T")[0],
    recordId: null,
    isDirty: false,
    lastSavedAt: null,
    _apiBase: "/api/ishikawa",
    _csrfToken: "",
    /** Défini par la page hôte (Twig) : afficher « Mes analyses » dans la barre React */
    showSavedList: false
  },
  updateMeta: (partial) => {
    set((state) => ({
      meta: { ...state.meta, ...partial, isDirty: true }
    }));
  },
  setRecordId: (id) => {
    set((state) => ({
      meta: { ...state.meta, recordId: id, isDirty: false, lastSavedAt: /* @__PURE__ */ new Date() }
    }));
  },
  markClean: () => {
    set((state) => ({
      meta: { ...state.meta, isDirty: false, lastSavedAt: /* @__PURE__ */ new Date() }
    }));
  },
  /** Props hôte (Twig) sans marquer dirty */
  setHostProps: (partial) => {
    set((state) => ({
      meta: { ...state.meta, ...partial }
    }));
  }
});

// ../IshikawaEditor/store/slices/uiSlice.js
var createUiSlice = (set) => ({
  ui: {
    selectedNodeId: null,
    isPropertiesPanelOpen: false,
    isMetaPanelOpen: false,
    isSaving: false,
    saveError: null,
    isExporting: false,
    zoomLevel: 1,
    mode: "view",
    showMinimap: true
  },
  selectNode: (nodeId) => {
    set((state) => ({
      ui: {
        ...state.ui,
        selectedNodeId: nodeId,
        isPropertiesPanelOpen: nodeId !== null
      }
    }));
  },
  setSaving: (isSaving, error = null) => {
    set((state) => ({
      ui: { ...state.ui, isSaving, saveError: error }
    }));
  },
  setExporting: (isExporting) => {
    set((state) => ({ ui: { ...state.ui, isExporting } }));
  },
  toggleMinimap: () => {
    set((state) => ({
      ui: { ...state.ui, showMinimap: !state.ui.showMinimap }
    }));
  },
  setMode: (mode) => {
    set((state) => ({ ui: { ...state.ui, mode } }));
  },
  setZoom: (zoomLevel) => {
    set((state) => ({ ui: { ...state.ui, zoomLevel } }));
  }
});

// ../IshikawaEditor/utils/hostUi.js
function notifyIshikawa(message, type = "info") {
  if (typeof document === "undefined" || !message) return;
  document.dispatchEvent(
    new CustomEvent("app:notification", {
      bubbles: true,
      detail: { message: String(message), type }
    })
  );
}
async function requestUserConfirmation(options = {}, fallbackMessage = "\xCAtes-vous s\xFBr de vouloir continuer ?") {
  const modalElement = document.getElementById("globalConfirmationModal");
  if (modalElement && window.Stimulus) {
    try {
      const title = options.title || "Confirmation";
      const message = options.message || fallbackMessage;
      const confirmText = options.confirmText || "Confirmer";
      const cancelText = options.cancelText || "Annuler";
      const messageElement = modalElement.querySelector('[data-confirmation-modal-target="message"]');
      const titleElement = modalElement.querySelector('[id$="-title"], h5.font-semibold');
      const confirmButton = modalElement.querySelector('button[data-action*="onConfirmed"]');
      const cancelButton = modalElement.querySelector('button[data-action*="onCancelled"]');
      if (messageElement) messageElement.textContent = message;
      if (titleElement) {
        const icon = titleElement.querySelector("i");
        titleElement.innerHTML = "";
        if (icon) titleElement.appendChild(icon);
        titleElement.appendChild(document.createTextNode(` ${title}`));
      }
      if (confirmButton) confirmButton.textContent = confirmText;
      if (cancelButton) cancelButton.textContent = cancelText;
      return new Promise((resolve) => {
        const resolveId = `confirmResolve_${Date.now()}`;
        let settled = false;
        const cleanup = () => {
          if (confirmButton) confirmButton.removeEventListener("click", onConfirmClick);
          if (cancelButton) cancelButton.removeEventListener("click", onCancelClick);
          if (closeButton) closeButton.removeEventListener("click", onCancelClick);
          modalElement.removeEventListener("app-modal:hidden", onHiddenModal);
          delete modalElement.dataset.confirmPromiseResolve;
          delete window[resolveId];
        };
        const settle = (value) => {
          if (settled) return;
          settled = true;
          cleanup();
          resolve(value);
        };
        const hideModal = () => {
          const modalController2 = window.Stimulus.getControllerForElementAndIdentifier(
            modalElement,
            "app-modal"
          );
          if (modalController2 && typeof modalController2.hide === "function") {
            modalController2.hide();
            return;
          }
          modalElement.style.display = "none";
        };
        const onConfirmClick = (event) => {
          event.preventDefault();
          settle(true);
          hideModal();
        };
        const onCancelClick = (event) => {
          event.preventDefault();
          settle(false);
          hideModal();
        };
        const onHiddenModal = () => {
          settle(false);
        };
        const closeButton = modalElement.querySelector('button[aria-label="Fermer le dialogue"]');
        window[resolveId] = settle;
        modalElement.dataset.confirmPromiseResolve = resolveId;
        if (confirmButton) confirmButton.addEventListener("click", onConfirmClick);
        if (cancelButton) cancelButton.addEventListener("click", onCancelClick);
        if (closeButton) closeButton.addEventListener("click", onCancelClick);
        modalElement.addEventListener("app-modal:hidden", onHiddenModal);
        if (typeof window.lucide !== "undefined") {
          window.lucide.createIcons?.();
        }
        const modalController = window.Stimulus.getControllerForElementAndIdentifier(
          modalElement,
          "app-modal"
        );
        if (modalController && typeof modalController.show === "function") {
          modalController.show();
        } else {
          settle(window.confirm(message));
        }
      });
    } catch (error) {
      console.error("IshikawaEditor: erreur ouverture modal de confirmation", error);
    }
  }
  if (typeof window.showConfirmationModal === "function") {
    try {
      return await window.showConfirmationModal(options);
    } catch (error) {
      console.error("IshikawaEditor: showConfirmationModal", error);
    }
  }
  return window.confirm(options.message || fallbackMessage);
}

// ../IshikawaEditor/store/useIshikawaStore.js
function parseIshikawaApiBody(rawBody) {
  const trimmed = String(rawBody ?? "").trim();
  try {
    return JSON.parse(rawBody ?? "{}");
  } catch {
    if (trimmed.startsWith("<!") || trimmed.toLowerCase().startsWith("<html")) {
      throw new Error(
        "Session expir\xE9e ou connexion requise. Reconnectez-vous pour charger cette analyse."
      );
    }
    throw new Error("R\xE9ponse serveur illisible.");
  }
}
var useIshikawaStore = create(
  devtools(
    (set, get) => ({
      ...createNodesSlice(set, get),
      ...createEdgesSlice(set, get),
      ...createMetaSlice(set),
      ...createUiSlice(set),
      resetDiagram: () => {
        const apiBase = get().meta?._apiBase ?? "/api/ishikawa";
        const csrf = get().meta?._csrfToken ?? "";
        const showSavedList = get().meta?.showSavedList ?? false;
        set({
          nodes: [],
          edges: [],
          meta: {
            title: "Nouvelle analyse Ishikawa",
            problem: "",
            author: "",
            date: (/* @__PURE__ */ new Date()).toISOString().split("T")[0],
            recordId: null,
            isDirty: false,
            lastSavedAt: null,
            _apiBase: apiBase,
            _csrfToken: csrf,
            showSavedList
          },
          ui: {
            selectedNodeId: null,
            isPropertiesPanelOpen: false,
            isMetaPanelOpen: false,
            isSaving: false,
            saveError: null,
            isExporting: false,
            zoomLevel: 1,
            mode: "edit",
            showMinimap: true
          }
        });
      },
      loadFromRecord: (record) => {
        const content = record.content ?? {};
        let nodes = content.nodes ?? [];
        let edges = content.edges ?? [];
        if (isLegacyCanvasContent(content)) {
          const built = buildFlowFromLegacyCanvasContent(content, record.problem ?? "");
          nodes = built.nodes;
          edges = built.edges;
        }
        const { meta: contentMeta } = content;
        const patched = patchDiagramFromLegacyRecord(nodes ?? [], edges ?? []);
        if (!patched.nodes.length && record.id) {
          get().initDefaultDiagram(record.problem || "Probl\xE8me \xE0 analyser");
          set({
            meta: {
              ...get().meta,
              title: record.title ?? "Sans titre",
              problem: record.problem ?? "",
              author: contentMeta?.author ?? "",
              date: record.createdAt ? String(record.createdAt).slice(0, 10) : (/* @__PURE__ */ new Date()).toISOString().split("T")[0],
              recordId: record.id,
              isDirty: false,
              lastSavedAt: record.updatedAt ?? null
            }
          });
          notifyIshikawa(
            "Ce diagramme ne contient pas de donn\xE9es exploitables (format vide ou inconnu). Un mod\xE8le vierge a \xE9t\xE9 charg\xE9.",
            "warning"
          );
          return;
        }
        set({
          nodes: patched.nodes,
          edges: patched.edges,
          meta: {
            title: record.title ?? "Sans titre",
            problem: record.problem ?? "",
            author: contentMeta?.author ?? "",
            date: record.createdAt ? String(record.createdAt).slice(0, 10) : (/* @__PURE__ */ new Date()).toISOString().split("T")[0],
            recordId: record.id,
            isDirty: false,
            lastSavedAt: record.updatedAt ?? null,
            _apiBase: get().meta?._apiBase ?? "/api/ishikawa",
            _csrfToken: get().meta?._csrfToken ?? "",
            showSavedList: get().meta?.showSavedList ?? false
          }
        });
      },
      saveDiagram: async () => {
        const state = get();
        get().setSaving(true, null);
        try {
          const payload = serializeToRecord(state);
          const base = String(state.meta._apiBase ?? "/api/ishikawa").replace(/\/$/, "");
          const response = await fetch(`${base}/save`, {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
              "X-Requested-With": "XMLHttpRequest",
              "X-CSRF-Token": state.meta._csrfToken ?? ""
            },
            credentials: "same-origin",
            body: JSON.stringify(payload)
          });
          const raw = await response.text();
          const json = parseIshikawaApiBody(raw);
          if (!response.ok) {
            throw new Error(
              typeof json.message === "string" && json.message ? json.message : `HTTP ${response.status}`
            );
          }
          if (!json.success) {
            throw new Error(json.message ?? "Erreur inconnue");
          }
          get().setRecordId(json.data.id);
          get().setSaving(false, null);
          notifyIshikawa("Diagramme enregistr\xE9.", "success");
        } catch (err) {
          get().setSaving(false, err.message);
          console.error("Erreur sauvegarde:", err);
          notifyIshikawa(
            err?.message ? `Sauvegarde impossible : ${err.message}` : "Sauvegarde impossible.",
            "error"
          );
        }
      },
      loadDiagram: async (recordId) => {
        try {
          const base = String(get().meta?._apiBase ?? "/api/ishikawa").replace(/\/$/, "");
          const response = await fetch(`${base}/${recordId}`, {
            headers: { "X-Requested-With": "XMLHttpRequest" },
            credentials: "same-origin"
          });
          const raw = await response.text();
          const apiResponse = parseIshikawaApiBody(raw);
          if (!response.ok || apiResponse.success === false) {
            const msg = typeof apiResponse.message === "string" && apiResponse.message ? apiResponse.message : `Chargement impossible (erreur ${response.status}).`;
            throw new Error(msg);
          }
          const record = deserializeFromRecord(apiResponse);
          get().loadFromRecord(record);
        } catch (err) {
          console.error("Erreur chargement:", err);
          notifyIshikawa(
            err?.message ? `Chargement impossible : ${err.message}` : "Chargement impossible.",
            "error"
          );
        }
      }
    }),
    { name: "IshikawaStore" }
  )
);
var useIshikawaStore_default = useIshikawaStore;

// ../IshikawaEditor/components/nodes/TailNode.jsx
import { Handle, Position } from "@xyflow/react";
import { jsx, jsxs } from "react/jsx-runtime";
var { width: TAIL_W, height: TAIL_H } = NODE_DIMENSIONS.TAIL;
function TailNode() {
  return /* @__PURE__ */ jsxs(
    "div",
    {
      className: "nodrag nopan",
      style: {
        background: "#64748b",
        color: "white",
        borderRadius: 8,
        padding: "8px 12px",
        width: TAIL_W,
        minHeight: TAIL_H,
        boxSizing: "border-box",
        textAlign: "center",
        fontWeight: 600,
        fontSize: 12,
        letterSpacing: "0.02em",
        border: "2px solid #475569",
        cursor: "default",
        userSelect: "none",
        display: "flex",
        alignItems: "center",
        justifyContent: "center"
      },
      title: "D\xE9but de l\u2019ar\xEAte de poisson (fixe)",
      children: [
        "Cause racines",
        /* @__PURE__ */ jsx(Handle, { type: "source", position: Position.Right, style: { background: "#e2e8f0" } })
      ]
    }
  );
}

// ../IshikawaEditor/components/nodes/EffectNode.jsx
import { Handle as Handle2, Position as Position2 } from "@xyflow/react";
import { useState } from "react";
import { jsx as jsx2, jsxs as jsxs2 } from "react/jsx-runtime";
var { width: EFF_W, height: EFF_H } = NODE_DIMENSIONS.EFFECT;
function EffectNode({ id, data }) {
  const [editValue, setEditValue] = useState(data.label);
  const updateNodeLabel = useIshikawaStore_default((s) => s.updateNodeLabel);
  const setNodeEditing = useIshikawaStore_default((s) => s.setNodeEditing);
  const handleDoubleClick = () => setNodeEditing(id, true);
  const handleBlur = () => {
    updateNodeLabel(id, editValue);
  };
  const handleKeyDown = (e) => {
    if (e.key === "Enter") updateNodeLabel(id, editValue);
    if (e.key === "Escape") {
      setEditValue(data.label);
      setNodeEditing(id, false);
    }
  };
  return /* @__PURE__ */ jsxs2(
    "div",
    {
      className: "nopan",
      style: {
        background: "#E53E3E",
        color: "white",
        borderRadius: 10,
        padding: "10px 18px",
        width: EFF_W,
        minHeight: EFF_H,
        boxSizing: "border-box",
        textAlign: "center",
        fontWeight: 700,
        fontSize: 13,
        lineHeight: 1.35,
        wordBreak: "break-word",
        boxShadow: "0 2px 8px rgba(0,0,0,0.2)",
        cursor: data.isEditing ? "text" : "grab",
        display: "flex",
        alignItems: "center",
        justifyContent: "center"
      },
      onDoubleClick: handleDoubleClick,
      title: "Double-clic pour modifier",
      children: [
        data.isEditing ? /* @__PURE__ */ jsx2(
          "input",
          {
            autoFocus: true,
            value: editValue,
            onChange: (e) => setEditValue(e.target.value),
            onBlur: handleBlur,
            onKeyDown: handleKeyDown,
            style: {
              background: "transparent",
              border: "none",
              color: "white",
              fontWeight: 700,
              fontSize: 13,
              textAlign: "center",
              width: "100%",
              outline: "none"
            }
          }
        ) : /* @__PURE__ */ jsx2("span", { children: data.label || "Probl\xE8me principal" }),
        /* @__PURE__ */ jsx2(Handle2, { type: "target", position: Position2.Left, style: { background: "white" } })
      ]
    }
  );
}

// ../IshikawaEditor/components/nodes/CategoryNode.jsx
import { Handle as Handle3, Position as Position3 } from "@xyflow/react";
import { useState as useState2 } from "react";
import { useShallow } from "zustand/react/shallow";

// ../IshikawaEditor/components/icons/TrashIcon.jsx
import { jsx as jsx3, jsxs as jsxs3 } from "react/jsx-runtime";
function TrashIcon({ size = 14 }) {
  return /* @__PURE__ */ jsxs3(
    "svg",
    {
      width: size,
      height: size,
      viewBox: "0 0 24 24",
      fill: "none",
      stroke: "currentColor",
      strokeWidth: "2",
      strokeLinecap: "round",
      strokeLinejoin: "round",
      "aria-hidden": "true",
      children: [
        /* @__PURE__ */ jsx3("path", { d: "M3 6h18" }),
        /* @__PURE__ */ jsx3("path", { d: "M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" }),
        /* @__PURE__ */ jsx3("line", { x1: "10", y1: "11", x2: "10", y2: "17" }),
        /* @__PURE__ */ jsx3("line", { x1: "14", y1: "11", x2: "14", y2: "17" })
      ]
    }
  );
}

// ../IshikawaEditor/components/nodes/CategoryNode.jsx
import { jsx as jsx4, jsxs as jsxs4 } from "react/jsx-runtime";
var { width: CAT_W, height: CAT_H } = NODE_DIMENSIONS.CATEGORY;
function CategoryNode({ id, data }) {
  const [editValue, setEditValue] = useState2(data.label);
  const updateNodeLabel = useIshikawaStore_default((s) => s.updateNodeLabel);
  const setNodeEditing = useIshikawaStore_default((s) => s.setNodeEditing);
  const addCause = useIshikawaStore_default((s) => s.addCause);
  const deleteNode = useIshikawaStore_default((s) => s.deleteNode);
  const categoryCount = useIshikawaStore_default(
    useShallow((s) => s.nodes.filter((n) => n.type === NODE_TYPES_KEYS.CATEGORY).length)
  );
  const canRemoveCategory = categoryCount > ISHIKAWA_MIN_CATEGORIES;
  const handleDoubleClick = () => setNodeEditing(id, true);
  const handleBlur = () => updateNodeLabel(id, editValue);
  const handleKeyDown = (e) => {
    if (e.key === "Enter") updateNodeLabel(id, editValue);
    if (e.key === "Escape") {
      setEditValue(data.label);
      setNodeEditing(id, false);
    }
  };
  const handleAddCause = (e) => {
    e.stopPropagation();
    addCause(id);
  };
  const handleRemoveCategory = (e) => {
    e.stopPropagation();
    if (!canRemoveCategory) return;
    void (async () => {
      const confirmed = await requestUserConfirmation({
        title: "Supprimer la cat\xE9gorie",
        message: `La cat\xE9gorie \xAB ${data.label} \xBB et toutes ses causes seront supprim\xE9es. Cette action est irr\xE9versible.`,
        confirmText: "Supprimer",
        cancelText: "Annuler"
      });
      if (!confirmed) return;
      deleteNode(id);
      notifyIshikawa("Cat\xE9gorie supprim\xE9e.", "success");
    })();
  };
  const addBtnStyle = {
    position: "absolute",
    right: 8,
    top: "50%",
    transform: "translateY(-50%)",
    flexShrink: 0,
    width: 30,
    height: 30,
    borderRadius: 8,
    display: "inline-flex",
    alignItems: "center",
    justifyContent: "center",
    padding: 0,
    cursor: "pointer",
    border: "1px solid rgba(0,0,0,0.1)",
    background: "rgba(255,255,255,0.95)",
    color: data.color ?? "#666",
    fontWeight: 900,
    fontSize: 16,
    lineHeight: 1,
    boxShadow: "0 1px 2px rgba(0,0,0,0.08)",
    zIndex: 2
  };
  return /* @__PURE__ */ jsxs4(
    "div",
    {
      className: "nopan ishikawa-node ishikawa-node--category",
      style: {
        position: "relative",
        width: CAT_W,
        minHeight: CAT_H,
        background: data.color ?? "#666",
        color: "white",
        borderRadius: 10,
        padding: "8px 40px 8px 36px",
        fontWeight: 600,
        fontSize: 13,
        lineHeight: 1.35,
        cursor: data.isEditing ? "text" : "grab",
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        boxShadow: "0 2px 8px rgba(0,0,0,0.18)",
        boxSizing: "border-box"
      },
      onDoubleClick: handleDoubleClick,
      title: "Double-clic pour modifier \u2014 + pour ajouter une cause",
      children: [
        canRemoveCategory ? /* @__PURE__ */ jsx4(
          "button",
          {
            type: "button",
            className: "nodrag nopan ishikawa-node-del ishikawa-node-del--category",
            onClick: handleRemoveCategory,
            style: {
              position: "absolute",
              left: 8,
              top: "50%",
              transform: "translateY(-50%)",
              width: 30,
              height: 30,
              borderRadius: 8,
              display: "inline-flex",
              alignItems: "center",
              justifyContent: "center",
              padding: 0,
              cursor: "pointer",
              border: "1px solid rgba(255,255,255,0.35)",
              background: "rgba(0,0,0,0.18)",
              color: "#fff",
              zIndex: 2
            },
            title: "Supprimer cette cat\xE9gorie et ses causes",
            "aria-label": "Supprimer cette cat\xE9gorie",
            children: /* @__PURE__ */ jsx4(TrashIcon, { size: 15 })
          }
        ) : null,
        /* @__PURE__ */ jsx4(
          "div",
          {
            style: {
              flex: 1,
              textAlign: "center",
              minWidth: 0,
              wordBreak: "break-word",
              hyphens: "auto"
            },
            children: data.isEditing ? /* @__PURE__ */ jsx4(
              "input",
              {
                autoFocus: true,
                className: "nodrag",
                value: editValue,
                onChange: (e) => setEditValue(e.target.value),
                onBlur: handleBlur,
                onKeyDown: handleKeyDown,
                style: {
                  background: "transparent",
                  border: "none",
                  color: "white",
                  fontWeight: 600,
                  fontSize: 13,
                  textAlign: "center",
                  width: "100%",
                  outline: "none"
                }
              }
            ) : /* @__PURE__ */ jsx4("span", { children: data.label })
          }
        ),
        /* @__PURE__ */ jsx4(
          "button",
          {
            type: "button",
            className: "nodrag nopan",
            onClick: handleAddCause,
            style: addBtnStyle,
            title: "Ajouter une cause",
            "aria-label": "Ajouter une cause",
            children: "+"
          }
        ),
        /* @__PURE__ */ jsx4(Handle3, { type: "source", position: data.isTop ? Position3.Bottom : Position3.Top, id: "to-spine" }),
        /* @__PURE__ */ jsx4(Handle3, { type: "target", position: Position3.Left, id: "from-causes" })
      ]
    }
  );
}

// ../IshikawaEditor/components/nodes/CauseNode.jsx
import { Handle as Handle4, Position as Position4 } from "@xyflow/react";
import { useState as useState3, useEffect, useRef } from "react";
import { jsx as jsx5, jsxs as jsxs5 } from "react/jsx-runtime";
var { width: CAUSE_W, height: CAUSE_H } = NODE_DIMENSIONS.CAUSE;
function CauseNode({ id, data }) {
  const [editValue, setEditValue] = useState3(data.label);
  const inputRef = useRef(null);
  const updateNodeLabel = useIshikawaStore_default((s) => s.updateNodeLabel);
  const setNodeEditing = useIshikawaStore_default((s) => s.setNodeEditing);
  const deleteNode = useIshikawaStore_default((s) => s.deleteNode);
  useEffect(() => {
    if (data.isNew && data.isEditing && inputRef.current) {
      inputRef.current.focus();
      inputRef.current.select();
    }
  }, [data.isNew, data.isEditing]);
  const handleBlur = () => {
    if (editValue.trim()) updateNodeLabel(id, editValue);
    else deleteNode(id);
  };
  const confirmAndDeleteCause = async () => {
    const labelPreview = (data.label || "").trim() || "cette cause";
    const confirmed = await requestUserConfirmation({
      title: "Supprimer la cause",
      message: `Supprimer \xAB ${labelPreview} \xBB ? Cette action est irr\xE9versible.`,
      confirmText: "Supprimer",
      cancelText: "Annuler"
    });
    if (!confirmed) return;
    deleteNode(id);
    notifyIshikawa("Cause supprim\xE9e.", "success");
  };
  const handleRemoveCauseClick = (e) => {
    e.stopPropagation();
    void confirmAndDeleteCause();
  };
  const handleKeyDown = (e) => {
    if (e.key === "Enter") {
      if (editValue.trim()) updateNodeLabel(id, editValue);
      else deleteNode(id);
    }
    if (e.key === "Escape") {
      if (data.isNew) deleteNode(id);
      else {
        setEditValue(data.label);
        setNodeEditing(id, false);
      }
    }
    if (e.key === "Delete" && !data.isEditing) {
      e.preventDefault();
      void confirmAndDeleteCause();
    }
  };
  return /* @__PURE__ */ jsxs5(
    "div",
    {
      className: "nopan ishikawa-node ishikawa-node--cause",
      style: {
        position: "relative",
        width: CAUSE_W,
        minHeight: CAUSE_H,
        background: "white",
        border: "2px solid #CBD5E0",
        borderRadius: 8,
        padding: "6px 10px 6px 32px",
        fontSize: 12,
        lineHeight: 1.35,
        cursor: data.isEditing ? "text" : "grab",
        display: "flex",
        alignItems: "center",
        boxShadow: "0 1px 4px rgba(0,0,0,0.07)",
        boxSizing: "border-box"
      },
      onDoubleClick: () => setNodeEditing(id, true),
      onKeyDown: handleKeyDown,
      tabIndex: 0,
      children: [
        !data.isEditing ? /* @__PURE__ */ jsx5(
          "button",
          {
            type: "button",
            className: "nodrag nopan ishikawa-node-del ishikawa-node-del--cause",
            onClick: handleRemoveCauseClick,
            style: {
              position: "absolute",
              left: 6,
              top: "50%",
              transform: "translateY(-50%)",
              width: 26,
              height: 26,
              borderRadius: 6,
              display: "inline-flex",
              alignItems: "center",
              justifyContent: "center",
              padding: 0,
              cursor: "pointer",
              border: "1px solid rgba(148,163,184,0.45)",
              background: "rgba(248,250,252,0.92)",
              color: "#64748b",
              zIndex: 1
            },
            title: "Supprimer cette cause",
            "aria-label": "Supprimer cette cause",
            children: /* @__PURE__ */ jsx5(TrashIcon, { size: 13 })
          }
        ) : null,
        /* @__PURE__ */ jsx5("div", { style: { flex: 1, minWidth: 0, wordBreak: "break-word" }, children: data.isEditing ? /* @__PURE__ */ jsx5(
          "input",
          {
            ref: inputRef,
            className: "nodrag",
            value: editValue,
            onChange: (e) => setEditValue(e.target.value),
            onBlur: handleBlur,
            onKeyDown: handleKeyDown,
            style: {
              border: "none",
              outline: "none",
              fontSize: 12,
              width: "100%"
            }
          }
        ) : /* @__PURE__ */ jsx5("span", { children: data.label }) }),
        /* @__PURE__ */ jsx5(Handle4, { type: "source", position: Position4.Right }),
        /* @__PURE__ */ jsx5(Handle4, { type: "target", position: Position4.Left })
      ]
    }
  );
}

// ../IshikawaEditor/components/panels/ToolbarPanel.jsx
import { useShallow as useShallow2 } from "zustand/react/shallow";

// ../IshikawaEditor/components/controls/ExportButton.jsx
import { useCallback, useEffect as useEffect2, useRef as useRef2, useState as useState4 } from "react";
import { toPng } from "html-to-image";

// ../../lib/exportBranding.js
var SYSTEM_DEFAULTS = {
  brandName: "OUTILS-QUALIT\xC9",
  website: "www.outils-qualite.com",
  copyright: "\xA9 OUTILS-QUALIT\xC9 - www.outils-qualite.com",
  watermark: "OUTILS-QUALIT\xC9"
};
var cache = null;
var loadPromise = null;
function normalizeBranding(raw) {
  const system = { ...SYSTEM_DEFAULTS, ...raw?.system || {} };
  const userRaw = raw?.user || {};
  const user = {};
  if (raw?.exportDisplayName || userRaw.displayName) {
    user.displayName = (raw.exportDisplayName || userRaw.displayName || "").trim() || null;
  }
  if (raw?.exportJobTitle || userRaw.jobTitle) {
    user.jobTitle = (raw.exportJobTitle || userRaw.jobTitle || "").trim() || null;
  }
  if (raw?.exportCompanyName || userRaw.companyName) {
    user.companyName = (raw.exportCompanyName || userRaw.companyName || "").trim() || null;
  }
  const pdfFooter = (raw?.exportPdfFooter || userRaw.pdfFooter || "").trim();
  if (pdfFooter) {
    user.pdfFooter = pdfFooter;
  }
  return { system, user, raw };
}
function getUserLines(branding) {
  const u = branding?.user || {};
  return [u.displayName, u.jobTitle, u.companyName].filter((s) => s && String(s).trim() !== "");
}
function applyJsPdfHeader(pdf, branding, pageWidth, options) {
  const { titleText, exportLocale, titleY = 12, dateY = 18 } = options;
  pdf.setFont("helvetica", "bold");
  pdf.setFontSize(16);
  pdf.setTextColor(31, 41, 55);
  pdf.text(titleText, pageWidth / 2, titleY, { align: "center" });
  let cursorY = titleY + 8;
  const lines = getUserLines(branding);
  if (lines.length) {
    pdf.setFont("helvetica", "normal");
    pdf.setFontSize(10);
    pdf.setTextColor(51, 65, 85);
    for (const line of lines) {
      cursorY += 5;
      pdf.text(line, pageWidth / 2, cursorY, { align: "center" });
    }
  }
  pdf.setFont("helvetica", "normal");
  pdf.setFontSize(10);
  pdf.setTextColor(71, 85, 105);
  const finalDateY = Math.max(dateY, cursorY + 6);
  pdf.text(`Export\xE9 le ${exportLocale}`, pageWidth / 2, finalDateY, { align: "center" });
  return finalDateY;
}
function applyJsPdfFooter(pdf, branding, pageHeight, pageWidth) {
  const customFooter = branding?.user?.pdfFooter;
  const copyright = branding?.system?.copyright || SYSTEM_DEFAULTS.copyright;
  const x = pageWidth ? pageWidth / 2 : 10;
  const textOpts = pageWidth ? { align: "center" } : void 0;
  pdf.setFontSize(8);
  pdf.setTextColor(150, 150, 150);
  if (customFooter) {
    pdf.setFontSize(7);
    pdf.text(customFooter, x, pageHeight - 10, textOpts);
    pdf.setFontSize(8);
  }
  pdf.text(copyright, x, pageHeight - (pageWidth ? 6 : 5), textOpts);
}
function enrichMetadata(metadata, branding) {
  const normalized = branding?.system ? branding : normalizeBranding(branding || {});
  const userLines = getUserLines(normalized);
  const userBlock = {};
  if (normalized.user?.displayName) userBlock.displayName = normalized.user.displayName;
  if (normalized.user?.jobTitle) userBlock.jobTitle = normalized.user.jobTitle;
  if (normalized.user?.companyName) userBlock.companyName = normalized.user.companyName;
  if (normalized.user?.pdfFooter) userBlock.pdfFooter = normalized.user.pdfFooter;
  const result = {
    ...metadata,
    source: metadata?.source || normalized.system.brandName,
    branding: {
      system: { ...normalized.system }
    }
  };
  if (Object.keys(userBlock).length > 0) {
    result.branding.user = userBlock;
  }
  if (userLines.length > 0) {
    result.branding.headerLines = userLines;
  }
  return result;
}
async function loadExportBranding() {
  if (typeof window !== "undefined" && window.OqExportBranding?.load) {
    return window.OqExportBranding.load();
  }
  if (cache !== null) {
    return cache;
  }
  if (loadPromise === null) {
    loadPromise = fetch("/api/user/export-branding", {
      method: "GET",
      headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
      credentials: "same-origin"
    }).then(async (response) => {
      const ct = response.headers.get("content-type") || "";
      if (!response.ok || !ct.includes("application/json")) {
        return normalizeBranding({});
      }
      try {
        return normalizeBranding(await response.json());
      } catch {
        return normalizeBranding({});
      }
    }).catch(() => normalizeBranding({}));
  }
  cache = await loadPromise;
  return cache;
}

// ../IshikawaEditor/components/controls/ExportButton.jsx
import { jsx as jsx6, jsxs as jsxs6 } from "react/jsx-runtime";
var MENU_ITEM_BASE = {
  display: "flex",
  alignItems: "center",
  gap: 10,
  width: "100%",
  boxSizing: "border-box",
  margin: 0,
  border: "none",
  background: "transparent",
  cursor: "pointer",
  textAlign: "left",
  fontSize: 13,
  lineHeight: 1.35,
  fontWeight: 500,
  color: "#0f172a",
  padding: "10px 14px",
  borderRadius: 0
};
function MenuGlyph({ children }) {
  return /* @__PURE__ */ jsx6(
    "span",
    {
      "aria-hidden": true,
      style: {
        display: "inline-flex",
        width: 18,
        flexShrink: 0,
        color: "#64748b",
        alignItems: "center",
        justifyContent: "center"
      },
      children
    }
  );
}
function ExportButton() {
  const [open, setOpen] = useState4(false);
  const rootRef = useRef2(null);
  const setExporting = useIshikawaStore_default((s) => s.setExporting);
  const meta = useIshikawaStore_default((s) => s.meta);
  const ui = useIshikawaStore_default((s) => s.ui);
  const close = useCallback(() => setOpen(false), []);
  useEffect2(() => {
    if (!open) return void 0;
    const onDocMouseDown = (e) => {
      if (rootRef.current && !rootRef.current.contains(e.target)) {
        close();
      }
    };
    const onKeyDown = (e) => {
      if (e.key === "Escape") close();
    };
    document.addEventListener("mousedown", onDocMouseDown, true);
    document.addEventListener("keydown", onKeyDown);
    return () => {
      document.removeEventListener("mousedown", onDocMouseDown, true);
      document.removeEventListener("keydown", onKeyDown);
    };
  }, [open, close]);
  const safeFileBase = () => (meta.title || "ishikawa").replace(/\s+/g, "-").toLowerCase().replace(/[^a-z0-9-_]/gi, "") || "ishikawa";
  const handleExportPng = async () => {
    close();
    setExporting(true);
    try {
      const canvas = document.querySelector(".react-flow__viewport");
      if (!canvas) {
        notifyIshikawa("Zone du diagramme introuvable pour l\u2019export.", "warning");
        return;
      }
      const dataUrl = await toPng(canvas, { backgroundColor: "#ffffff", pixelRatio: 2 });
      const link = document.createElement("a");
      link.download = `${safeFileBase()}.png`;
      link.href = dataUrl;
      link.click();
      notifyIshikawa("Image PNG export\xE9e.", "success");
    } catch (err) {
      console.error("Erreur export PNG:", err);
      notifyIshikawa(err?.message ? `Export PNG impossible : ${err.message}` : "Export PNG impossible.", "error");
    } finally {
      setExporting(false);
    }
  };
  const handleExportPdf = async () => {
    close();
    const html2canvas = window.html2canvas;
    const jspdfNs = window.jspdf;
    const JsPDF = jspdfNs?.jsPDF;
    if (typeof html2canvas !== "function" || typeof JsPDF !== "function") {
      notifyIshikawa(
        "Export PDF : biblioth\xE8ques indisponibles. Rechargez la page ou v\xE9rifiez votre bloqueur de contenu.",
        "warning"
      );
      return;
    }
    setExporting(true);
    try {
      const branding = await loadExportBranding();
      const el = document.querySelector(".react-flow__viewport");
      if (!el) {
        notifyIshikawa("Zone du diagramme introuvable pour l\u2019export.", "warning");
        return;
      }
      const snapshot = await html2canvas(el, { backgroundColor: "#ffffff", scale: 2, useCORS: true });
      const imgData = snapshot.toDataURL("image/png", 1);
      const w = snapshot.width;
      const h = snapshot.height;
      const headerBand = 80 + (branding?.user ? 18 * [branding.user.displayName, branding.user.jobTitle, branding.user.companyName].filter(Boolean).length : 0);
      const footerBand = 40;
      const pdf = new JsPDF({
        orientation: w > h ? "l" : "p",
        unit: "px",
        format: [w, h + headerBand + footerBand]
      });
      const pageWidth = pdf.internal.pageSize.getWidth();
      const pageHeight = pdf.internal.pageSize.getHeight();
      const titleText = (meta.title || "Diagramme Ishikawa").substring(0, 80);
      const exportLocale = (/* @__PURE__ */ new Date()).toLocaleString("fr-FR");
      applyJsPdfHeader(pdf, branding, pageWidth, { titleText, exportLocale, titleY: 14, dateY: 22 });
      pdf.addImage(imgData, "PNG", 0, headerBand, w, h, void 0, "FAST");
      applyJsPdfFooter(pdf, branding, pageHeight, pageWidth);
      pdf.save(`${safeFileBase()}.pdf`);
      notifyIshikawa("Document PDF export\xE9.", "success");
    } catch (err) {
      console.error("Erreur export PDF:", err);
      notifyIshikawa(err?.message ? `Export PDF impossible : ${err.message}` : "Export PDF impossible.", "error");
    } finally {
      setExporting(false);
    }
  };
  const handleExportJson = async () => {
    close();
    try {
      const branding = await loadExportBranding();
      const state = useIshikawaStore_default.getState();
      const payload = serializeToRecord(state);
      const envelope = {
        version: 2,
        tool: "ishikawa-reactflow",
        exportedAt: (/* @__PURE__ */ new Date()).toISOString(),
        metadata: enrichMetadata(
          {
            tool: "ishikawa-reactflow",
            source: "OUTILS-QUALIT\xC9",
            title: meta.title || "Diagramme Ishikawa"
          },
          branding
        ),
        ...payload
      };
      const blob = new Blob([JSON.stringify(envelope, null, 2)], { type: "application/json;charset=utf-8" });
      const url = URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      link.download = `${safeFileBase()}.json`;
      link.click();
      URL.revokeObjectURL(url);
      notifyIshikawa("Fichier JSON export\xE9.", "success");
    } catch (err) {
      console.error("Erreur export JSON:", err);
      notifyIshikawa("Export JSON impossible.", "error");
    }
  };
  const busy = ui.isExporting;
  return /* @__PURE__ */ jsxs6(
    "div",
    {
      ref: rootRef,
      className: "ishikawa-export-wrap",
      style: {
        position: "relative",
        zIndex: 40,
        display: "inline-flex",
        flexDirection: "column",
        alignItems: "stretch"
      },
      children: [
        /* @__PURE__ */ jsxs6(
          "button",
          {
            type: "button",
            className: "ishikawa-export-trigger",
            "aria-haspopup": "menu",
            "aria-expanded": open,
            disabled: busy,
            onClick: () => setOpen((v) => !v),
            style: {
              display: "inline-flex",
              alignItems: "center",
              gap: 6,
              background: "#2563eb",
              color: "white",
              border: "1px solid #1d4ed8",
              borderRadius: 8,
              padding: "6px 14px",
              fontSize: 12,
              fontWeight: 600,
              cursor: busy ? "wait" : "pointer",
              boxShadow: "0 1px 2px rgba(15,23,42,0.12)",
              opacity: busy ? 0.75 : 1
            },
            title: "Exporter le diagramme",
            children: [
              /* @__PURE__ */ jsx6("span", { "aria-hidden": "true", style: { fontSize: 14, lineHeight: 1 }, children: "\u2B07" }),
              "Exporter",
              /* @__PURE__ */ jsx6("span", { "aria-hidden": "true", style: { fontSize: 10, opacity: 0.9, marginLeft: 2 }, children: "\u25BE" })
            ]
          }
        ),
        open ? /* @__PURE__ */ jsxs6(
          "div",
          {
            className: "ishikawa-export-dropdown",
            role: "menu",
            "aria-label": "Options d\u2019export",
            style: {
              position: "absolute",
              top: "calc(100% + 6px)",
              left: 0,
              right: "auto",
              minWidth: "100%",
              width: "max-content",
              maxWidth: "min(20rem, calc(100vw - 24px))",
              background: "#ffffff",
              border: "1px solid #cbd5e1",
              borderRadius: 10,
              boxShadow: "0 10px 40px rgba(15, 23, 42, 0.18)",
              padding: "4px 0",
              overflow: "hidden"
            },
            children: [
              /* @__PURE__ */ jsx6(
                MenuRow,
                {
                  label: "Exporter en PNG",
                  onClick: handleExportPng,
                  disabled: busy,
                  glyph: /* @__PURE__ */ jsxs6("svg", { width: "16", height: "16", viewBox: "0 0 24 24", fill: "none", stroke: "currentColor", strokeWidth: "2", children: [
                    /* @__PURE__ */ jsx6("rect", { x: "3", y: "3", width: "18", height: "18", rx: "2" }),
                    /* @__PURE__ */ jsx6("circle", { cx: "8.5", cy: "8.5", r: "1.5" }),
                    /* @__PURE__ */ jsx6("path", { d: "M21 15l-5-5L5 21" })
                  ] })
                }
              ),
              /* @__PURE__ */ jsx6(
                MenuRow,
                {
                  label: "Exporter en PDF",
                  onClick: handleExportPdf,
                  disabled: busy,
                  glyph: /* @__PURE__ */ jsxs6("svg", { width: "16", height: "16", viewBox: "0 0 24 24", fill: "none", stroke: "currentColor", strokeWidth: "2", children: [
                    /* @__PURE__ */ jsx6("path", { d: "M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" }),
                    /* @__PURE__ */ jsx6("polyline", { points: "14 2 14 8 20 8" }),
                    /* @__PURE__ */ jsx6("path", { d: "M10 12h4M10 16h4" })
                  ] })
                }
              ),
              /* @__PURE__ */ jsx6(
                MenuRow,
                {
                  label: "Exporter en JSON",
                  onClick: handleExportJson,
                  disabled: busy,
                  glyph: /* @__PURE__ */ jsxs6("svg", { width: "16", height: "16", viewBox: "0 0 24 24", fill: "none", stroke: "currentColor", strokeWidth: "2", children: [
                    /* @__PURE__ */ jsx6("polyline", { points: "16 18 22 12 16 6" }),
                    /* @__PURE__ */ jsx6("polyline", { points: "8 6 2 12 8 18" })
                  ] })
                }
              )
            ]
          }
        ) : null
      ]
    }
  );
}
function MenuRow({ label, onClick, disabled, glyph }) {
  const [hover, setHover] = useState4(false);
  return /* @__PURE__ */ jsxs6(
    "button",
    {
      type: "button",
      role: "menuitem",
      disabled,
      onMouseEnter: () => setHover(true),
      onMouseLeave: () => setHover(false),
      onClick,
      style: {
        ...MENU_ITEM_BASE,
        background: hover && !disabled ? "#f1f5f9" : "transparent",
        color: disabled ? "#94a3b8" : "#0f172a",
        cursor: disabled ? "not-allowed" : "pointer"
      },
      children: [
        glyph ? /* @__PURE__ */ jsx6(MenuGlyph, { children: glyph }) : null,
        /* @__PURE__ */ jsx6("span", { style: { minWidth: 0 }, children: label })
      ]
    }
  );
}

// ../IshikawaEditor/components/panels/ToolbarPanel.jsx
import { jsx as jsx7, jsxs as jsxs7 } from "react/jsx-runtime";
function ToolbarPanel() {
  const { meta, ui, updateMeta, saveDiagram, toggleMinimap, addCategory } = useIshikawaStore_default(
    useShallow2((state) => ({
      meta: state.meta,
      ui: state.ui,
      updateMeta: state.updateMeta,
      saveDiagram: state.saveDiagram,
      toggleMinimap: state.toggleMinimap,
      addCategory: state.addCategory
    }))
  );
  return /* @__PURE__ */ jsxs7(
    "div",
    {
      style: {
        background: "white",
        borderRadius: 8,
        padding: "8px 16px",
        boxShadow: "0 2px 8px rgba(0,0,0,0.1)",
        display: "flex",
        alignItems: "center",
        gap: 12,
        minWidth: 400
      },
      children: [
        /* @__PURE__ */ jsx7(
          "input",
          {
            value: meta.title,
            onChange: (e) => updateMeta({ title: e.target.value }),
            placeholder: "Titre de l'analyse...",
            style: {
              border: "1px solid #E2E8F0",
              borderRadius: 4,
              padding: "4px 8px",
              fontSize: 13,
              flex: 1
            }
          }
        ),
        /* @__PURE__ */ jsx7("span", { style: { fontSize: 11, color: meta.isDirty ? "#E53E3E" : "#38A169", whiteSpace: "nowrap" }, children: ui.isSaving ? "\u23F3 Sauvegarde\u2026" : meta.isDirty ? "\u25CF Non sauvegard\xE9" : "\u2713 Sauvegard\xE9" }),
        /* @__PURE__ */ jsx7(
          "button",
          {
            type: "button",
            onClick: saveDiagram,
            disabled: ui.isSaving || !meta.isDirty,
            style: {
              background: "#3182CE",
              color: "white",
              border: "none",
              borderRadius: 4,
              padding: "4px 12px",
              fontSize: 12,
              cursor: "pointer",
              opacity: !meta.isDirty || ui.isSaving ? 0.5 : 1
            },
            children: "Sauvegarder"
          }
        ),
        meta.showSavedList ? /* @__PURE__ */ jsx7(
          "button",
          {
            type: "button",
            onClick: () => {
              if (typeof window.openIshikawaSaved === "function") {
                window.openIshikawaSaved();
              } else {
                notifyIshikawa("Liste des analyses indisponible sur cette page.", "error");
              }
            },
            style: {
              background: "#EDF2F7",
              color: "#2D3748",
              border: "1px solid #CBD5E0",
              borderRadius: 4,
              padding: "4px 10px",
              fontSize: 12,
              cursor: "pointer",
              whiteSpace: "nowrap"
            },
            title: "Ouvrir vos diagrammes enregistr\xE9s",
            children: "Mes analyses"
          }
        ) : null,
        /* @__PURE__ */ jsx7(ExportButton, {}),
        /* @__PURE__ */ jsx7(
          "button",
          {
            type: "button",
            onClick: () => {
              addCategory();
              notifyIshikawa("Cat\xE9gorie ajout\xE9e. Double-cliquez sur l\u2019\xE9tiquette pour la renommer.", "success");
            },
            style: {
              background: "#EDF2F7",
              color: "#2D3748",
              border: "1px solid #CBD5E0",
              borderRadius: 4,
              padding: "4px 10px",
              fontSize: 12,
              cursor: "pointer",
              whiteSpace: "nowrap"
            },
            title: "Ajouter une branche cat\xE9gorie (en plus des 5 M)",
            children: "\uFF0B Cat\xE9gorie"
          }
        ),
        /* @__PURE__ */ jsx7(
          "button",
          {
            type: "button",
            onClick: toggleMinimap,
            style: {
              background: "transparent",
              border: "1px solid #CBD5E0",
              borderRadius: 4,
              padding: "4px 8px",
              fontSize: 11,
              cursor: "pointer"
            },
            title: "Afficher/masquer la minimap",
            children: "\u{1F5FA}"
          }
        )
      ]
    }
  );
}

// ../IshikawaEditor/components/panels/PropertiesPanel.jsx
import { jsx as jsx8, jsxs as jsxs8 } from "react/jsx-runtime";
function PropertiesPanel() {
  const selectedId = useIshikawaStore_default((s) => s.ui.selectedNodeId);
  const nodes = useIshikawaStore_default((s) => s.nodes);
  const updateNodeLabel = useIshikawaStore_default((s) => s.updateNodeLabel);
  if (!selectedId) {
    return null;
  }
  const node = nodes.find((n) => n.id === selectedId);
  if (!node) {
    return null;
  }
  if (node.type === NODE_TYPES_KEYS.TAIL) {
    return /* @__PURE__ */ jsxs8("div", { className: "bg-white border border-slate-200 rounded-lg shadow p-4 w-64 text-sm", children: [
      /* @__PURE__ */ jsx8("h3", { className: "font-semibold mb-2", children: "Queue du diagramme" }),
      /* @__PURE__ */ jsx8("p", { className: "text-slate-600 text-xs leading-relaxed", children: "Rep\xE8re fixe du c\xF4t\xE9 des causes racines (d\xE9but de l\u2019ar\xEAte horizontale). Libell\xE9 et position ne sont pas modifiables." })
    ] });
  }
  return /* @__PURE__ */ jsxs8("div", { className: "bg-white border border-slate-200 rounded-lg shadow p-4 w-64 text-sm", children: [
    /* @__PURE__ */ jsx8("h3", { className: "font-semibold mb-2", children: "N\u0153ud s\xE9lectionn\xE9" }),
    /* @__PURE__ */ jsx8("label", { className: "block text-slate-600 mb-1", children: "Libell\xE9" }),
    /* @__PURE__ */ jsx8(
      "input",
      {
        type: "text",
        className: "w-full border border-slate-300 rounded px-2 py-1",
        value: node.data.label ?? "",
        onChange: (e) => updateNodeLabel(selectedId, e.target.value)
      }
    ),
    node.type === NODE_TYPES_KEYS.EFFECT ? /* @__PURE__ */ jsx8("p", { className: "text-slate-500 text-xs mt-2", children: "D\xE9placez la t\xEAte horizontalement sur le canvas ; au rel\xE2chement, elle se recale verticalement sur l\u2019ar\xEAte du poisson. Le libell\xE9 du probl\xE8me est \xE9ditable ci-dessus." }) : null,
    node.type === NODE_TYPES_KEYS.CATEGORY || node.type === NODE_TYPES_KEYS.CAUSE ? /* @__PURE__ */ jsx8("p", { className: "text-slate-500 text-xs mt-2", children: "D\xE9placez ce n\u0153ud en le faisant glisser. Le lien d\u2019une cause est un segment droit jusqu\u2019\xE0 la branche oblique ; apr\xE8s d\xE9placement d\u2019une cause, l\u2019accroche se recale sur l\u2019os." }) : null
  ] });
}

// ../IshikawaEditor/components/edges/SpineEdge.jsx
import { BaseEdge } from "@xyflow/react";
import { jsx as jsx9 } from "react/jsx-runtime";
function SpineEdge({ id, sourceX, sourceY, targetX, targetY, style }) {
  const path = `M ${sourceX} ${sourceY} L ${targetX} ${targetY}`;
  return /* @__PURE__ */ jsx9(BaseEdge, { id, path, style: { ...style, strokeWidth: 4, stroke: "#475569" } });
}

// ../IshikawaEditor/components/edges/BoneEdge.jsx
import { BaseEdge as BaseEdge2, getBezierPath } from "@xyflow/react";
import { useShallow as useShallow3 } from "zustand/react/shallow";
import { jsx as jsx10 } from "react/jsx-runtime";
function BoneEdge({
  id,
  source,
  target,
  sourceX,
  sourceY,
  targetX,
  targetY,
  sourcePosition,
  targetPosition,
  data,
  style
}) {
  const stroke = data?.color ?? "#94a3b8";
  const categoryForCause = useIshikawaStore_default(
    useShallow3((s) => {
      if (typeof source !== "string" || !source.startsWith("cause-")) return null;
      const cat = s.nodes.find((x) => x.id === target);
      return cat?.type === NODE_TYPES_KEYS.CATEGORY ? cat : null;
    })
  );
  const causeNode = useIshikawaStore_default(
    useShallow3((s) => {
      if (typeof source !== "string" || !source.startsWith("cause-")) return null;
      const c = s.nodes.find((x) => x.id === source);
      return c?.type === NODE_TYPES_KEYS.CAUSE ? c : null;
    })
  );
  if (categoryForCause && typeof categoryForCause.data?.spineAttachX === "number") {
    const path = getCauseRibPath(
      sourceX,
      sourceY,
      targetX,
      targetY,
      categoryForCause,
      causeNode?.data?.boneSlotT
    );
    return /* @__PURE__ */ jsx10(BaseEdge2, { id, path, style: { ...style, strokeWidth: 2, stroke } });
  }
  if (data?.fishbone && typeof data.spineAttachX === "number" && typeof data.spineAttachY === "number") {
    const jx = data.spineAttachX;
    const jy = data.spineAttachY;
    const path = `M ${sourceX} ${sourceY} L ${jx} ${jy} L ${targetX} ${targetY}`;
    return /* @__PURE__ */ jsx10(BaseEdge2, { id, path, style: { ...style, strokeWidth: 2.5, stroke } });
  }
  const [edgePath] = getBezierPath({ sourceX, sourceY, sourcePosition, targetX, targetY, targetPosition });
  return /* @__PURE__ */ jsx10(BaseEdge2, { id, path: edgePath, style: { ...style, strokeWidth: 2, stroke } });
}

// ../IshikawaEditor/components/IshikawaCanvas.jsx
import { jsx as jsx11, jsxs as jsxs9 } from "react/jsx-runtime";
var nodeTypes = {
  [NODE_TYPES_KEYS.TAIL]: TailNode,
  [NODE_TYPES_KEYS.EFFECT]: EffectNode,
  [NODE_TYPES_KEYS.CATEGORY]: CategoryNode,
  [NODE_TYPES_KEYS.CAUSE]: CauseNode
};
var edgeTypes = {
  [EDGE_TYPES_KEYS.SPINE]: SpineEdge,
  [EDGE_TYPES_KEYS.BONE]: BoneEdge
};
function IshikawaCanvas() {
  const { nodes, edges, onNodesChange, onEdgesChange, onConnect, selectNode, ui } = useIshikawaStore_default(
    useShallow4((state) => ({
      nodes: state.nodes,
      edges: state.edges,
      onNodesChange: state.onNodesChange,
      onEdgesChange: state.onEdgesChange,
      onConnect: state.onConnect,
      selectNode: state.selectNode,
      ui: state.ui
    }))
  );
  return /* @__PURE__ */ jsx11("div", { style: { width: "100%", height: "78vh", background: "#F7FAFC" }, children: /* @__PURE__ */ jsxs9(
    ReactFlow,
    {
      nodes,
      edges,
      nodeTypes,
      edgeTypes,
      nodesDraggable: true,
      nodesConnectable: false,
      onNodesChange,
      onEdgesChange,
      onConnect,
      onNodeClick: (_, node) => selectNode(node.id),
      onPaneClick: () => selectNode(null),
      onNodeDragStop: (_, node) => {
        const api = useIshikawaStore_default.getState();
        if (node.type === NODE_TYPES_KEYS.CATEGORY) {
          api.reanchorCausesForCategory(node.id, {}, node);
        }
        if (node.type === NODE_TYPES_KEYS.CAUSE) {
          api.snapCauseToParentBone(node.id);
        }
        if (node.id === EFFECT_NODE_ID) {
          api.snapEffectToSpineRow();
        }
      },
      fitView: true,
      fitViewOptions: { padding: 0.24 },
      attributionPosition: "bottom-left",
      proOptions: { hideAttribution: false },
      children: [
        /* @__PURE__ */ jsx11(Background, { variant: "dots", gap: 24, size: 1, color: "#CBD5E0" }),
        /* @__PURE__ */ jsxs9(
          Panel,
          {
            position: "bottom-right",
            className: "ishikawa-flow-rail",
            style: {
              display: "flex",
              flexDirection: "column",
              alignItems: "flex-end",
              gap: 10,
              margin: "10px 14px 14px 10px",
              zIndex: 20
            },
            children: [
              /* @__PURE__ */ jsx11(
                Controls,
                {
                  className: "ishikawa-flow-controls",
                  orientation: "vertical",
                  showZoom: true,
                  showFitView: true,
                  showInteractive: false
                }
              ),
              ui.showMinimap ? /* @__PURE__ */ jsx11("div", { className: "ishikawa-flow-minimap-chrome", children: /* @__PURE__ */ jsx11(
                MiniMap,
                {
                  pannable: true,
                  zoomable: true,
                  nodeStrokeWidth: 2,
                  style: { width: 200, height: 140 },
                  maskColor: "rgba(15, 23, 42, 0.18)",
                  nodeColor: (node) => {
                    switch (node.type) {
                      case NODE_TYPES_KEYS.EFFECT:
                        return "#e53e3e";
                      case NODE_TYPES_KEYS.TAIL:
                        return "#475569";
                      case NODE_TYPES_KEYS.CATEGORY:
                        return "#94a3b8";
                      case NODE_TYPES_KEYS.CAUSE:
                        return "#cbd5e1";
                      default:
                        return "#a8b4c4";
                    }
                  }
                }
              ) }) : null
            ]
          }
        ),
        /* @__PURE__ */ jsx11(Panel, { position: "top-left", children: /* @__PURE__ */ jsx11(ToolbarPanel, {}) }),
        ui.isPropertiesPanelOpen ? /* @__PURE__ */ jsx11(Panel, { position: "top-right", children: /* @__PURE__ */ jsx11(PropertiesPanel, {}) }) : null
      ]
    }
  ) });
}

// ../IshikawaEditor/components/panels/MetaPanel.jsx
import { jsx as jsx12, jsxs as jsxs10 } from "react/jsx-runtime";
function MetaPanel() {
  const meta = useIshikawaStore_default((s) => s.meta);
  const updateMeta = useIshikawaStore_default((s) => s.updateMeta);
  return /* @__PURE__ */ jsxs10("div", { className: "border-b border-slate-200 bg-white px-5 py-3 flex flex-wrap gap-4 items-center", children: [
    /* @__PURE__ */ jsxs10("label", { className: "text-sm text-slate-600 flex flex-col gap-1", children: [
      "Probl\xE8me",
      /* @__PURE__ */ jsx12(
        "textarea",
        {
          className: "border border-slate-300 rounded px-2 py-1 min-w-[240px] text-sm",
          rows: 2,
          value: meta.problem,
          onChange: (e) => updateMeta({ problem: e.target.value })
        }
      )
    ] }),
    /* @__PURE__ */ jsxs10("label", { className: "text-sm text-slate-600 flex flex-col gap-1", children: [
      "Auteur",
      /* @__PURE__ */ jsx12(
        "input",
        {
          type: "text",
          className: "border border-slate-300 rounded px-2 py-1 text-sm",
          value: meta.author,
          onChange: (e) => updateMeta({ author: e.target.value })
        }
      )
    ] })
  ] });
}

// ../IshikawaEditor/index.jsx
import { jsx as jsx13, jsxs as jsxs11 } from "react/jsx-runtime";
var IshikawaErrorBoundary = class extends Component {
  constructor(props) {
    super(props);
    this.state = { error: null };
  }
  static getDerivedStateFromError(error) {
    return { error };
  }
  componentDidCatch(error, info) {
    console.error("[IshikawaEditor]", error, info?.componentStack);
  }
  render() {
    const { error } = this.state;
    if (error) {
      const text = error?.stack || error?.message || String(error);
      return /* @__PURE__ */ jsxs11(
        "div",
        {
          role: "alert",
          style: {
            padding: "1rem",
            margin: "1rem",
            background: "#fff5f5",
            border: "1px solid #feb2b2",
            color: "#742a2a",
            fontFamily: "system-ui, sans-serif",
            maxWidth: "56rem"
          },
          children: [
            /* @__PURE__ */ jsx13("strong", { children: "Erreur dans l\u2019\xE9diteur Ishikawa (v2)" }),
            /* @__PURE__ */ jsx13("pre", { style: { whiteSpace: "pre-wrap", fontSize: "0.8rem", marginTop: "0.75rem" }, children: text })
          ]
        }
      );
    }
    return this.props.children;
  }
};
function IshikawaEditor({ recordId, apiBase, csrfToken, savedListEnabled }) {
  const initDefaultDiagram = useIshikawaStore_default((s) => s.initDefaultDiagram);
  const loadDiagram = useIshikawaStore_default((s) => s.loadDiagram);
  const setHostProps = useIshikawaStore_default((s) => s.setHostProps);
  useEffect3(() => {
    setHostProps({
      _apiBase: apiBase ?? "/api/ishikawa",
      _csrfToken: csrfToken ?? "",
      showSavedList: Boolean(savedListEnabled)
    });
    const rid = recordId != null && String(recordId).trim() !== "" ? Number.parseInt(String(recordId), 10) : Number.NaN;
    if (Number.isInteger(rid) && rid > 0) {
      loadDiagram(rid);
    } else {
      initDefaultDiagram();
    }
  }, [recordId, apiBase, csrfToken, savedListEnabled, initDefaultDiagram, loadDiagram, setHostProps]);
  return /* @__PURE__ */ jsx13(IshikawaErrorBoundary, { children: /* @__PURE__ */ jsx13(ReactFlowProvider, { children: /* @__PURE__ */ jsxs11("div", { className: "flex flex-col h-full min-h-[70vh]", children: [
    /* @__PURE__ */ jsx13(MetaPanel, {}),
    /* @__PURE__ */ jsx13(IshikawaCanvas, {})
  ] }) }) });
}
export {
  IshikawaEditor as default
};
