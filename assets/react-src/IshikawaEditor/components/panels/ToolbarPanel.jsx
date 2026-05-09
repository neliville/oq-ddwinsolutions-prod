import { useShallow } from 'zustand/react/shallow';
import useIshikawaStore from '../../store/useIshikawaStore.js';
import ExportButton from '../controls/ExportButton.jsx';

export default function ToolbarPanel() {
  const { meta, ui, updateMeta, saveDiagram, toggleMinimap } = useIshikawaStore(
    useShallow((state) => ({
      meta: state.meta,
      ui: state.ui,
      updateMeta: state.updateMeta,
      saveDiagram: state.saveDiagram,
      toggleMinimap: state.toggleMinimap,
    }))
  );

  return (
    <div
      style={{
        background: 'white',
        borderRadius: 8,
        padding: '8px 16px',
        boxShadow: '0 2px 8px rgba(0,0,0,0.1)',
        display: 'flex',
        alignItems: 'center',
        gap: 12,
        minWidth: 400,
      }}
    >
      <input
        value={meta.title}
        onChange={(e) => updateMeta({ title: e.target.value })}
        placeholder="Titre de l'analyse..."
        style={{
          border: '1px solid #E2E8F0',
          borderRadius: 4,
          padding: '4px 8px',
          fontSize: 13,
          flex: 1,
        }}
      />

      <span style={{ fontSize: 11, color: meta.isDirty ? '#E53E3E' : '#38A169', whiteSpace: 'nowrap' }}>
        {ui.isSaving ? '⏳ Sauvegarde…' : meta.isDirty ? '● Non sauvegardé' : '✓ Sauvegardé'}
      </span>

      <button
        type="button"
        onClick={saveDiagram}
        disabled={ui.isSaving || !meta.isDirty}
        style={{
          background: '#3182CE',
          color: 'white',
          border: 'none',
          borderRadius: 4,
          padding: '4px 12px',
          fontSize: 12,
          cursor: 'pointer',
          opacity: !meta.isDirty || ui.isSaving ? 0.5 : 1,
        }}
      >
        Sauvegarder
      </button>

      <ExportButton />

      <button
        type="button"
        onClick={toggleMinimap}
        style={{
          background: 'transparent',
          border: '1px solid #CBD5E0',
          borderRadius: 4,
          padding: '4px 8px',
          fontSize: 11,
          cursor: 'pointer',
        }}
        title="Afficher/masquer la minimap"
      >
        🗺
      </button>
    </div>
  );
}
