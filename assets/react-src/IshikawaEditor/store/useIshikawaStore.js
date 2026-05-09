import { create } from 'zustand';
import { devtools } from 'zustand/middleware';
import { serializeToRecord, deserializeFromRecord } from '../utils/ishikawaSerializer.js';
import { createNodesSlice } from './slices/nodesSlice.js';
import { createEdgesSlice } from './slices/edgesSlice.js';
import { createMetaSlice } from './slices/metaSlice.js';
import { createUiSlice } from './slices/uiSlice.js';

const useIshikawaStore = create(
  devtools(
    (set, get) => ({
      ...createNodesSlice(set, get),
      ...createEdgesSlice(set, get),
      ...createMetaSlice(set),
      ...createUiSlice(set),

      resetDiagram: () => {
        const apiBase = get().meta?._apiBase ?? '/api/ishikawa';
        const csrf = get().meta?._csrfToken ?? '';
        set({
          nodes: [],
          edges: [],
          meta: {
            title: 'Nouvelle analyse Ishikawa',
            problem: '',
            author: '',
            date: new Date().toISOString().split('T')[0],
            recordId: null,
            isDirty: false,
            lastSavedAt: null,
            _apiBase: apiBase,
            _csrfToken: csrf,
          },
          ui: {
            selectedNodeId: null,
            isPropertiesPanelOpen: false,
            isMetaPanelOpen: false,
            isSaving: false,
            saveError: null,
            isExporting: false,
            zoomLevel: 1,
            mode: 'edit',
            showMinimap: true,
          },
        });
      },

      loadFromRecord: (record) => {
        const { nodes, edges, meta: contentMeta } = record.content ?? {};
        set({
          nodes: nodes ?? [],
          edges: edges ?? [],
          meta: {
            title: record.title ?? 'Sans titre',
            problem: record.problem ?? '',
            author: contentMeta?.author ?? '',
            date: record.createdAt ? String(record.createdAt).slice(0, 10) : new Date().toISOString().split('T')[0],
            recordId: record.id,
            isDirty: false,
            lastSavedAt: record.updatedAt ?? null,
            _apiBase: get().meta?._apiBase ?? '/api/ishikawa',
            _csrfToken: get().meta?._csrfToken ?? '',
          },
        });
      },

      saveDiagram: async () => {
        const state = get();
        get().setSaving(true, null);

        try {
          const payload = serializeToRecord(state);
          const response = await fetch('/api/ishikawa/save', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-Requested-With': 'XMLHttpRequest',
              'X-CSRF-Token': state.meta._csrfToken ?? '',
            },
            body: JSON.stringify(payload),
          });

          if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
          }

          const json = await response.json();
          if (!json.success) {
            throw new Error(json.message ?? 'Erreur inconnue');
          }

          get().setRecordId(json.data.id);
          get().setSaving(false, null);
        } catch (err) {
          get().setSaving(false, err.message);
          console.error('Erreur sauvegarde:', err);
        }
      },

      loadDiagram: async (recordId) => {
        try {
          const response = await fetch(`/api/ishikawa/${recordId}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
          });
          if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
          }
          const apiResponse = await response.json();
          const record = deserializeFromRecord(apiResponse);
          get().loadFromRecord(record);
        } catch (err) {
          console.error('Erreur chargement:', err);
        }
      },
    }),
    { name: 'IshikawaStore' }
  )
);

export default useIshikawaStore;
