import { applyEdgeChanges, addEdge } from '@xyflow/react';
import { EDGE_TYPES_KEYS, NODE_TYPES_KEYS } from '../../utils/constants.js';

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
          data: { isBone: true, color },
        },
      ],
      meta: { ...state.meta, isDirty: true },
    }));
  },
});
