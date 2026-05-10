import useIshikawaStore from '../../store/useIshikawaStore.js';
import { NODE_TYPES_KEYS } from '../../utils/constants.js';

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

  if (node.type === NODE_TYPES_KEYS.TAIL) {
    return (
      <div className="bg-white border border-slate-200 rounded-lg shadow p-4 w-64 text-sm">
        <h3 className="font-semibold mb-2">Queue du diagramme</h3>
        <p className="text-slate-600 text-xs leading-relaxed">
          Repère fixe du côté des causes racines (début de l’arête horizontale). Libellé et position ne sont pas modifiables.
        </p>
      </div>
    );
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
      {node.type === NODE_TYPES_KEYS.EFFECT ? (
        <p className="text-slate-500 text-xs mt-2">
          Déplacez la tête horizontalement sur le canvas ; au relâchement, elle se recale verticalement sur l’arête du poisson. Le libellé du problème est éditable ci-dessus.
        </p>
      ) : null}
      {node.type === NODE_TYPES_KEYS.CATEGORY || node.type === NODE_TYPES_KEYS.CAUSE ? (
        <p className="text-slate-500 text-xs mt-2">
          Déplacez ce nœud en le faisant glisser. Le lien d’une cause est un segment droit jusqu’à la branche oblique ; après déplacement d’une cause, l’accroche se recale sur l’os.
        </p>
      ) : null}
    </div>
  );
}
