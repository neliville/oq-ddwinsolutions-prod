import { applyNodeChanges } from '@xyflow/react';
import {
  NODE_TYPES_KEYS,
  NODE_DIMENSIONS,
  LAYOUT_CONFIG,
  ISHIKAWA_CATEGORIES_5M,
  TAIL_NODE_ID,
  EFFECT_NODE_ID,
  ISHIKAWA_EXTRA_CATEGORY_COLORS,
  ISHIKAWA_MIN_CATEGORIES,
} from '../../utils/constants.js';
import {
  generateId,
  getCategorySpineAttachX,
  getCategoryTopLeft,
  getTailTopLeft,
  getEffectTopLeft,
  finalizeIshikawaNodes,
  syncCategoryFishboneEdges,
  repositionCausesAlongBone,
  reconcileCategoryFishbone,
  getPointOnCategoryBone,
  projectPointToBoneSlotT,
  getNextCategorySpineAttachX,
} from '../../utils/ishikawaLayout.js';

export const createNodesSlice = (set, get) => ({
  nodes: [],

  onNodesChange: (changes) => {
    set((state) => {
      let nodes = applyNodeChanges(changes, state.nodes);
      nodes = finalizeIshikawaNodes(nodes);
      const edges = syncCategoryFishboneEdges(nodes, state.edges);
      return {
        nodes,
        edges,
        meta: { ...state.meta, isDirty: true },
      };
    });
  },

  initDefaultDiagram: (problemLabel = 'Problème à analyser') => {
    const tailPos = getTailTopLeft();
    const effectPos = getEffectTopLeft();
    const nodes = [];

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

    nodes.push({
      id: EFFECT_NODE_ID,
      type: NODE_TYPES_KEYS.EFFECT,
      position: { x: effectPos.x, y: effectPos.y },
      data: {
        label: problemLabel,
        isEditing: false,
        isHead: true,
      },
      width: NODE_DIMENSIONS.EFFECT.width,
      height: NODE_DIMENSIONS.EFFECT.height,
      draggable: true,
      selectable: true,
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
          spineAttachY: LAYOUT_CONFIG.SPINE_Y,
        },
        width: NODE_DIMENSIONS.CATEGORY.width,
        height: NODE_DIMENSIONS.CATEGORY.height,
        draggable: true,
        selectable: true,
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
      const nodes = state.nodes.map((n) =>
        n.id === EFFECT_NODE_ID ? { ...n, position: { ...n.position, y } } : n
      );
      return { nodes, meta: { ...state.meta, isDirty: true } };
    });
  },

  addCategory: (label = 'Nouvelle catégorie') => {
    const state = get();
    const cats = state.nodes.filter((n) => n.type === NODE_TYPES_KEYS.CATEGORY);
    const attachX = getNextCategorySpineAttachX(state.nodes);
    const isTop = cats.length % 2 === 0;
    const pos = getCategoryTopLeft(attachX, isTop);
    const id = `cat-${generateId()}`;
    const color = ISHIKAWA_EXTRA_CATEGORY_COLORS[cats.length % ISHIKAWA_EXTRA_CATEGORY_COLORS.length];
    const slug = id.replace(/^cat-/, '');
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
        spineAttachY: LAYOUT_CONFIG.SPINE_Y,
      },
      width: NODE_DIMENSIONS.CATEGORY.width,
      height: NODE_DIMENSIONS.CATEGORY.height,
      draggable: true,
      selectable: true,
    };
    set((s) => ({
      nodes: [...s.nodes, newNode],
      meta: { ...s.meta, isDirty: true },
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
      const nodes = state.nodes.map((n) =>
        n.id === causeId ? { ...n, data: { ...n.data, boneSlotT: t }, position: { ...n.position, y } } : n
      );
      return { nodes, meta: { ...state.meta, isDirty: true } };
    });
  },

  reanchorCausesForCategory: (categoryNodeId, options = {}, draggedNode = null) => {
    const { reassignT = false } = options;
    set((state) => {
      const base = state.nodes.find((n) => n.id === categoryNodeId);
      if (!base || base.type !== NODE_TYPES_KEYS.CATEGORY) return {};
      const merged =
        draggedNode?.position != null ? { ...base, position: draggedNode.position } : base;
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

  addCause: (categoryNodeId, label = 'Nouvelle cause') => {
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
        isNew: true,
      },
      width: NODE_DIMENSIONS.CAUSE.width,
      height: NODE_DIMENSIONS.CAUSE.height,
      draggable: true,
      selectable: true,
    };

    const laidOut = repositionCausesAlongBone(catNode, [...existingCauses, stub], { reassignT: true });

    set((s) => ({
      nodes: [
        ...s.nodes.filter(
          (n) => !(n.type === NODE_TYPES_KEYS.CAUSE && n.data?.parentCategoryId === categoryNodeId)
        ),
        ...laidOut,
      ],
      meta: { ...s.meta, isDirty: true },
    }));
    get().addCauseEdge(categoryNodeId, newId);
  },

  updateNodeLabel: (nodeId, label) => {
    set((state) => ({
      nodes: state.nodes.map((n) =>
        n.id === nodeId ? { ...n, data: { ...n.data, label, isEditing: false, isNew: false } } : n
      ),
      meta: { ...state.meta, isDirty: true },
    }));
  },

  setNodeEditing: (nodeId, isEditing) => {
    set({
      nodes: get().nodes.map((n) => (n.id === nodeId ? { ...n, data: { ...n.data, isEditing } } : n)),
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
        meta: { ...state.meta, isDirty: true },
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
      meta: { ...state.meta, isDirty: true },
    });
  },

  updateCategoryColor: (categoryNodeId, color) => {
    set((state) => ({
      nodes: state.nodes.map((n) => (n.id === categoryNodeId ? { ...n, data: { ...n.data, color } } : n)),
      meta: { ...state.meta, isDirty: true },
    }));
  },
});
