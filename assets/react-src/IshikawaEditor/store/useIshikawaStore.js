import { create } from 'zustand';
import { devtools } from 'zustand/middleware';
import { serializeToRecord, deserializeFromRecord } from '../utils/ishikawaSerializer.js';
import { patchDiagramFromLegacyRecord } from '../utils/ishikawaLayout.js';
import { buildFlowFromLegacyCanvasContent, isLegacyCanvasContent } from '../utils/legacyIshikawaCanvasImport.js';
import { createNodesSlice } from './slices/nodesSlice.js';
import { createEdgesSlice } from './slices/edgesSlice.js';
import { createMetaSlice } from './slices/metaSlice.js';
import { createUiSlice } from './slices/uiSlice.js';
import { notifyIshikawa } from '../utils/hostUi.js';

/**
 * Lit le corps d’une réponse API (évite « Unexpected token '<' » si le pare-feu renvoie une page HTML).
 * @param {string} rawBody
 * @returns {Record<string, unknown>}
 */
function parseIshikawaApiBody(rawBody) {
  const trimmed = String(rawBody ?? '').trim();
  try {
    return JSON.parse(rawBody ?? '{}');
  } catch {
    if (trimmed.startsWith('<!') || trimmed.toLowerCase().startsWith('<html')) {
      throw new Error(
        'Session expirée ou connexion requise. Reconnectez-vous pour charger cette analyse.'
      );
    }
    throw new Error('Réponse serveur illisible.');
  }
}

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
        const showSavedList = get().meta?.showSavedList ?? false;
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
            showSavedList,
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
        const content = record.content ?? {};
        let nodes = content.nodes ?? [];
        let edges = content.edges ?? [];

        if (isLegacyCanvasContent(content)) {
          const built = buildFlowFromLegacyCanvasContent(content, record.problem ?? '');
          nodes = built.nodes;
          edges = built.edges;
        }

        const { meta: contentMeta } = content;
        const patched = patchDiagramFromLegacyRecord(nodes ?? [], edges ?? []);

        if (!patched.nodes.length && record.id) {
          get().initDefaultDiagram(record.problem || 'Problème à analyser');
          set({
            meta: {
              ...get().meta,
              title: record.title ?? 'Sans titre',
              problem: record.problem ?? '',
              author: contentMeta?.author ?? '',
              date: record.createdAt ? String(record.createdAt).slice(0, 10) : new Date().toISOString().split('T')[0],
              recordId: record.id,
              isDirty: false,
              lastSavedAt: record.updatedAt ?? null,
            },
          });
          notifyIshikawa(
            'Ce diagramme ne contient pas de données exploitables (format vide ou inconnu). Un modèle vierge a été chargé.',
            'warning'
          );
          return;
        }

        set({
          nodes: patched.nodes,
          edges: patched.edges,
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
            showSavedList: get().meta?.showSavedList ?? false,
          },
        });
      },

      saveDiagram: async () => {
        const state = get();
        get().setSaving(true, null);

        try {
          const payload = serializeToRecord(state);
          const base = String(state.meta._apiBase ?? '/api/ishikawa').replace(/\/$/, '');
          const response = await fetch(`${base}/save`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-Requested-With': 'XMLHttpRequest',
              'X-CSRF-Token': state.meta._csrfToken ?? '',
            },
            credentials: 'same-origin',
            body: JSON.stringify(payload),
          });

          const raw = await response.text();
          const json = parseIshikawaApiBody(raw);
          if (!response.ok) {
            throw new Error(
              typeof json.message === 'string' && json.message
                ? json.message
                : `HTTP ${response.status}`
            );
          }
          if (!json.success) {
            throw new Error(json.message ?? 'Erreur inconnue');
          }

          get().setRecordId(json.data.id);
          get().setSaving(false, null);
          notifyIshikawa('Diagramme enregistré.', 'success');
        } catch (err) {
          get().setSaving(false, err.message);
          console.error('Erreur sauvegarde:', err);
          notifyIshikawa(
            err?.message ? `Sauvegarde impossible : ${err.message}` : 'Sauvegarde impossible.',
            'error'
          );
        }
      },

      loadDiagram: async (recordId) => {
        try {
          const base = String(get().meta?._apiBase ?? '/api/ishikawa').replace(/\/$/, '');
          const response = await fetch(`${base}/${recordId}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
          });
          const raw = await response.text();
          const apiResponse = parseIshikawaApiBody(raw);
          if (!response.ok || apiResponse.success === false) {
            const msg =
              typeof apiResponse.message === 'string' && apiResponse.message
                ? apiResponse.message
                : `Chargement impossible (erreur ${response.status}).`;
            throw new Error(msg);
          }
          const record = deserializeFromRecord(apiResponse);
          get().loadFromRecord(record);
        } catch (err) {
          console.error('Erreur chargement:', err);
          notifyIshikawa(
            err?.message ? `Chargement impossible : ${err.message}` : 'Chargement impossible.',
            'error'
          );
        }
      },
    }),
    { name: 'IshikawaStore' }
  )
);

export default useIshikawaStore;
