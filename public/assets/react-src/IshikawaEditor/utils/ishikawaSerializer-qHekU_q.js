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
        spineAttachX: node.data.spineAttachX ?? null,
        spineAttachY: node.data.spineAttachY ?? null,
        isFixed: node.data.isFixed ?? null,
        isHead: node.data.isHead ?? null,
        boneSlotT: node.data.boneSlotT ?? null,
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
 * Contenu tel que stocké en base (objet, ou chaîne JSON parfois double-encodée).
 * @returns {Record<string, unknown>}
 */
export function parseStoredContent(raw) {
  let v = raw;
  if (typeof v === 'string') {
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
  if (typeof v === 'object') {
    return /** @type {Record<string, unknown>} */ (v);
  }
  return {};
}

/**
 * Normalise la réponse GET (enveloppe { success, data }) ou charge utile direct.
 */
export function deserializeFromRecord(apiResponse) {
  const envelope =
    apiResponse && typeof apiResponse.success === 'boolean' && apiResponse.data !== undefined
      ? apiResponse.data
      : apiResponse;

  const inner = parseStoredContent(envelope?.content);

  const innerProblem = typeof inner.problem === 'string' && inner.problem.trim() ? inner.problem : '';

  return {
    id: envelope.id,
    title: envelope.title ?? 'Sans titre',
    problem: (typeof envelope.problem === 'string' && envelope.problem.trim() ? envelope.problem : '') || innerProblem,
    createdAt: envelope.createdAt,
    updatedAt: envelope.updatedAt ?? null,
    content: {
      nodes: inner.nodes ?? [],
      edges: inner.edges ?? [],
      meta: inner.meta ?? {},
      /** Préservé pour l’import legacy (canvas v1) — ne pas retirer. */
      categories: inner.categories,
      problem: typeof inner.problem === 'string' ? inner.problem : undefined,
    },
  };
}
