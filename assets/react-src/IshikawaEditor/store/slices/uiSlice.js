export const createUiSlice = (set) => ({
  ui: {
    selectedNodeId: null,
    isPropertiesPanelOpen: false,
    isMetaPanelOpen: false,
    isSaving: false,
    saveError: null,
    isExporting: false,
    zoomLevel: 1,
    mode: 'view',
    showMinimap: true,
  },

  selectNode: (nodeId) => {
    set((state) => ({
      ui: {
        ...state.ui,
        selectedNodeId: nodeId,
        isPropertiesPanelOpen: nodeId !== null,
      },
    }));
  },

  setSaving: (isSaving, error = null) => {
    set((state) => ({
      ui: { ...state.ui, isSaving, saveError: error },
    }));
  },

  setExporting: (isExporting) => {
    set((state) => ({ ui: { ...state.ui, isExporting } }));
  },

  toggleMinimap: () => {
    set((state) => ({
      ui: { ...state.ui, showMinimap: !state.ui.showMinimap },
    }));
  },

  setMode: (mode) => {
    set((state) => ({ ui: { ...state.ui, mode } }));
  },

  setZoom: (zoomLevel) => {
    set((state) => ({ ui: { ...state.ui, zoomLevel } }));
  },
});
