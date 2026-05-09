/**
 * Génère un ID unique court
 */
export function generateId() {
  return Math.random().toString(36).substring(2, 9);
}

/**
 * Recalcule les positions (extension possible).
 */
export function recalculateLayout(nodes, config) {
  return nodes;
}
