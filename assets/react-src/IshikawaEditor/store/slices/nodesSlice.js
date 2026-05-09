import { applyNodeChanges } from '@xyflow/react';
import { NODE_TYPES_KEYS, NODE_DIMENSIONS, LAYOUT_CONFIG, ISHIKAWA_CATEGORIES_5M } from '../../utils/constants.js';
import { generateId } from '../../utils/ishikawaLayout.js';

export const createNodesSlice = (set, get) => ({
  nodes: [],

  onNodesChange: (changes) => {
    set((state) => ({
      nodes: applyNodeChanges(changes, state.nodes),
      meta: { ...state.meta, isDirty: true },
    }));
  },

  initDefaultDiagram: (problemLabel = 'Problème à analyser') => {
    const effectNodeId = 'effect-main';
    const nodes = [];

    nodes.push({
      id: effectNodeId,
      type: NODE_TYPES_KEYS.EFFECT,
      position: { x: LAYOUT_CONFIG.SPINE_END_X, y: LAYOUT_CONFIG.SPINE_Y - NODE_DIMENSIONS.EFFECT.height / 2 },
      data: {
        label: problemLabel,
        isEditing: false,
      },
      width: NODE_DIMENSIONS.EFFECT.width,
      height: NODE_DIMENSIONS.EFFECT.height,
      draggable: false,
    });

    ISHIKAWA_CATEGORIES_5M.forEach((cat, index) => {
      const isTop = index % 2 === 0;
      const xPos = LAYOUT_CONFIG.SPINE_START_X + 120 + index * 150;
      const yPos = isTop
        ? LAYOUT_CONFIG.SPINE_Y - LAYOUT_CONFIG.CATEGORY_OFFSET_Y
        : LAYOUT_CONFIG.SPINE_Y + LAYOUT_CONFIG.CATEGORY_OFFSET_Y;

      nodes.push({
        id: `cat-${cat.id}`,
        type: NODE_TYPES_KEYS.CATEGORY,
        position: { x: xPos, y: yPos },
        data: {
          label: cat.label,
          categoryId: cat.id,
          color: cat.color,
          isTop,
          isEditing: false,
        },
        width: NODE_DIMENSIONS.CATEGORY.width,
        height: NODE_DIMENSIONS.CATEGORY.height,
      });
    });

    set({ nodes });
    get().initDefaultEdges(effectNodeId);
    get().markClean();
  },

  addCause: (categoryNodeId, label = 'Nouvelle cause') => {
    const state = get();
    const catNode = state.nodes.find((n) => n.id === categoryNodeId);
    if (!catNode) return;

    const existingCauses = state.nodes.filter(
      (n) => n.type === NODE_TYPES_KEYS.CAUSE && n.data.parentCategoryId === categoryNodeId
    );

    const newId = `cause-${generateId()}`;
    const isTop = catNode.data.isTop;
    const xOffset = existingCauses.length * (NODE_DIMENSIONS.CAUSE.width + 10);

    const newNode = {
      id: newId,
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
        isEditing: true,
        isNew: true,
      },
      width: NODE_DIMENSIONS.CAUSE.width,
      height: NODE_DIMENSIONS.CAUSE.height,
    };

    set((s) => ({
      nodes: [...s.nodes, newNode],
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
    const state = get();
    const childIds = state.nodes.filter((n) => n.data?.parentCategoryId === nodeId).map((n) => n.id);
    const idsToRemove = [nodeId, ...childIds];

    set({
      nodes: state.nodes.filter((n) => !idsToRemove.includes(n.id)),
      edges: state.edges.filter((e) => !idsToRemove.includes(e.source) && !idsToRemove.includes(e.target)),
    });
  },

  updateCategoryColor: (categoryNodeId, color) => {
    set((state) => ({
      nodes: state.nodes.map((n) => (n.id === categoryNodeId ? { ...n, data: { ...n.data, color } } : n)),
      meta: { ...state.meta, isDirty: true },
    }));
  },
});
