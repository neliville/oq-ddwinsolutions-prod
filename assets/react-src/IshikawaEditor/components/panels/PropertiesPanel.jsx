import useIshikawaStore from '../../store/useIshikawaStore.js';

export default function PropertiesPanel() {
  const selectedId = useIshikawaStore((s) => s.ui.selectedNodeId);
  const nodes = useIshikawaStore((s) => s.nodes);
  const updateNodeLabel = useIshikawaStore((s) => s.updateNodeLabel);

  if (!selectedId) {
    return null;
  }

  const node = nodes.find((n) => n.id === selectedId);
  if (!node) {
    return null;
  }

  return (
    <div className="bg-white border border-slate-200 rounded-lg shadow p-4 w-64 text-sm">
      <h3 className="font-semibold mb-2">Nœud sélectionné</h3>
      <label className="block text-slate-600 mb-1">Libellé</label>
      <input
        type="text"
        className="w-full border border-slate-300 rounded px-2 py-1"
        value={node.data.label ?? ''}
        onChange={(e) => updateNodeLabel(selectedId, e.target.value)}
      />
    </div>
  );
}
