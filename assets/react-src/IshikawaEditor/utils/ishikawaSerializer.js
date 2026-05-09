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
