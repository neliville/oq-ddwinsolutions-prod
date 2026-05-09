// Catégories standards méthode 5M / 6M Ishikawa
export const ISHIKAWA_CATEGORIES_5M = [
  { id: 'matiere', label: 'Matière', color: '#E53E3E', shortLabel: 'MAT' },
  { id: 'milieu', label: 'Milieu', color: '#38A169', shortLabel: 'MIL' },
  { id: 'methode', label: 'Méthode', color: '#3182CE', shortLabel: 'MET' },
  { id: 'materiel', label: 'Matériel', color: '#D69E2E', shortLabel: 'MAT' },
  { id: 'mainoeuvre', label: "Main-d'œuvre", color: '#805AD5', shortLabel: 'MO' },
];

export const ISHIKAWA_CATEGORIES_6M = [
  ...ISHIKAWA_CATEGORIES_5M,
  { id: 'management', label: 'Management', color: '#ED8936', shortLabel: 'MGT' },
];

export const NODE_DIMENSIONS = {
  EFFECT: { width: 180, height: 60 },
  CATEGORY: { width: 140, height: 40 },
  CAUSE: { width: 120, height: 30 },
};

export const LAYOUT_CONFIG = {
  SPINE_Y: 300,
  SPINE_START_X: 80,
  SPINE_END_X: 900,
  CATEGORY_OFFSET_Y: 180,
  CAUSE_OFFSET: 80,
};

export const NODE_TYPES_KEYS = {
  EFFECT: 'effectNode',
  CATEGORY: 'categoryNode',
  CAUSE: 'causeNode',
};

export const EDGE_TYPES_KEYS = {
  SPINE: 'spineEdge',
  BONE: 'boneEdge',
};
