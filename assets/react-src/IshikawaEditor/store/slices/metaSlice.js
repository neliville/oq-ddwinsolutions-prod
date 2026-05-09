export const createMetaSlice = (set) => ({
  meta: {
    title: 'Nouvelle analyse Ishikawa',
    problem: '',
    author: '',
    date: new Date().toISOString().split('T')[0],
    recordId: null,
    isDirty: false,
    lastSavedAt: null,
    _apiBase: '/api/ishikawa',
    _csrfToken: '',
  },

  updateMeta: (partial) => {
    set((state) => ({
      meta: { ...state.meta, ...partial, isDirty: true },
    }));
  },

  setRecordId: (id) => {
    set((state) => ({
      meta: { ...state.meta, recordId: id, isDirty: false, lastSavedAt: new Date() },
    }));
  },

  markClean: () => {
    set((state) => ({
      meta: { ...state.meta, isDirty: false, lastSavedAt: new Date() },
    }));
  },

  /** Props hôte (Twig) sans marquer dirty */
  setHostProps: (partial) => {
    set((state) => ({
      meta: { ...state.meta, ...partial },
    }));
  },
});
