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
  TAIL: { width: 104, height: 44 },
  EFFECT: { width: 240, height: 68 },
  /** Largeur accrue pour libellés type « Management » sans rognage (cf. canvas v1). */
  CATEGORY: { width: 220, height: 46 },
  CAUSE: { width: 148, height: 34 },
};

/** Arête de poisson : queue (gauche) — arête horizontale — tête / effet (droite). */
export const LAYOUT_CONFIG = {
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
  CATEGORY_ATTACH_GAP: 148,
};

export const NODE_TYPES_KEYS = {
  TAIL: 'tailNode',
  EFFECT: 'effectNode',
  CATEGORY: 'categoryNode',
  CAUSE: 'causeNode',
};

export const EDGE_TYPES_KEYS = {
  SPINE: 'spineEdge',
  BONE: 'boneEdge',
};

export const TAIL_NODE_ID = 'tail-main';
export const EFFECT_NODE_ID = 'effect-main';

/** Couleurs proposées pour les catégories ajoutées au-delà des 5 M. */
export const ISHIKAWA_EXTRA_CATEGORY_COLORS = [
  '#0D9488',
  '#C026D3',
  '#EA580C',
  '#4F46E5',
  '#0891B2',
  '#65A30D',
];

/** Nombre minimal de catégories (diagramme valide). */
export const ISHIKAWA_MIN_CATEGORIES = 1;
