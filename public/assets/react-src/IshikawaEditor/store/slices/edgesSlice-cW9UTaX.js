import { applyEdgeChanges, addEdge } from '@xyflow/react';
import { EDGE_TYPES_KEYS, NODE_TYPES_KEYS, TAIL_NODE_ID, EFFECT_NODE_ID } from '../../utils/constants.js';

export const createEdgesSlice = (set, get) => ({
  edges: [],

  onEdgesChange: (changes) => {
    set((state) => ({
      edges: applyEdgeChanges(changes, state.edges),
      meta: { ...state.meta, isDirty: true },
    }));
  },

  onConnect: (connection) => {
    set((state) => ({
      edges: addEdge({ ...connection, type: EDGE_TYPES_KEYS.BONE, animated: false }, state.edges),
      meta: { ...state.meta, isDirty: true },
    }));
  },

  initDefaultEdges: (effectNodeId) => {
    const nodes = get().nodes;
    const catNodes = nodes.filter((n) => n.type === NODE_TYPES_KEYS.CATEGORY);
    const edges = [];

    edges.push({
      id: 'edge-tail-effect-spine',
      source: TAIL_NODE_ID,
      target: effectNodeId,
      type: EDGE_TYPES_KEYS.SPINE,
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
          fishbone: typeof ax === 'number' && typeof ay === 'number',
          spineAttachX: ax,
          spineAttachY: ay,
        },
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
      const color = cat.data?.color ?? '#666';
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
              fishbone: typeof ax === 'number' && typeof ay === 'number',
              spineAttachX: ax,
              spineAttachY: ay,
            },
          },
        ],
        meta: { ...state.meta, isDirty: true },
      };
    });
  },

  addCauseEdge: (categoryId, causeId) => {
    const catNode = get().nodes.find((n) => n.id === categoryId);
    const color = catNode?.data?.color ?? '#666';

    set((state) => ({
      edges: [
        ...state.edges,
        {
          id: `edge-${causeId}-${categoryId}`,
          source: causeId,
          target: categoryId,
          type: EDGE_TYPES_KEYS.BONE,
          zIndex: -2,
          data: { isBone: true, color },
        },
      ],
      meta: { ...state.meta, isDirty: true },
    }));
  },
});
