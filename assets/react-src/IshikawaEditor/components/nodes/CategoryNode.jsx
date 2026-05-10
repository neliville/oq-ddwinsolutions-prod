import { Handle, Position } from '@xyflow/react';
import { useState } from 'react';
import { useShallow } from 'zustand/react/shallow';
import useIshikawaStore from '../../store/useIshikawaStore.js';
import { NODE_TYPES_KEYS, ISHIKAWA_MIN_CATEGORIES, NODE_DIMENSIONS } from '../../utils/constants.js';
import { requestUserConfirmation, notifyIshikawa } from '../../utils/hostUi.js';
import TrashIcon from '../icons/TrashIcon.jsx';

const { width: CAT_W, height: CAT_H } = NODE_DIMENSIONS.CATEGORY;

export default function CategoryNode({ id, data }) {
  const [editValue, setEditValue] = useState(data.label);
  const updateNodeLabel = useIshikawaStore((s) => s.updateNodeLabel);
  const setNodeEditing = useIshikawaStore((s) => s.setNodeEditing);
  const addCause = useIshikawaStore((s) => s.addCause);
  const deleteNode = useIshikawaStore((s) => s.deleteNode);
  const categoryCount = useIshikawaStore(
    useShallow((s) => s.nodes.filter((n) => n.type === NODE_TYPES_KEYS.CATEGORY).length)
  );
  const canRemoveCategory = categoryCount > ISHIKAWA_MIN_CATEGORIES;

  const handleDoubleClick = () => setNodeEditing(id, true);
  const handleBlur = () => updateNodeLabel(id, editValue);
  const handleKeyDown = (e) => {
    if (e.key === 'Enter') updateNodeLabel(id, editValue);
    if (e.key === 'Escape') {
      setEditValue(data.label);
      setNodeEditing(id, false);
    }
  };

  const handleAddCause = (e) => {
    e.stopPropagation();
    addCause(id);
  };

  const handleRemoveCategory = (e) => {
    e.stopPropagation();
    if (!canRemoveCategory) return;
    void (async () => {
      const confirmed = await requestUserConfirmation({
        title: 'Supprimer la catégorie',
        message: `La catégorie « ${data.label} » et toutes ses causes seront supprimées. Cette action est irréversible.`,
        confirmText: 'Supprimer',
        cancelText: 'Annuler',
      });
      if (!confirmed) return;
      deleteNode(id);
      notifyIshikawa('Catégorie supprimée.', 'success');
    })();
  };

  const addBtnStyle = {
    position: 'absolute',
    right: 8,
    top: '50%',
    transform: 'translateY(-50%)',
    flexShrink: 0,
    width: 30,
    height: 30,
    borderRadius: 8,
    display: 'inline-flex',
    alignItems: 'center',
    justifyContent: 'center',
    padding: 0,
    cursor: 'pointer',
    border: '1px solid rgba(0,0,0,0.1)',
    background: 'rgba(255,255,255,0.95)',
    color: data.color ?? '#666',
    fontWeight: 900,
    fontSize: 16,
    lineHeight: 1,
    boxShadow: '0 1px 2px rgba(0,0,0,0.08)',
    zIndex: 2,
  };

  return (
    <div
      className="nopan ishikawa-node ishikawa-node--category"
      style={{
        position: 'relative',
        width: CAT_W,
        minHeight: CAT_H,
        background: data.color ?? '#666',
        color: 'white',
        borderRadius: 10,
        padding: '8px 40px 8px 36px',
        fontWeight: 600,
        fontSize: 13,
        lineHeight: 1.35,
        cursor: data.isEditing ? 'text' : 'grab',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        boxShadow: '0 2px 8px rgba(0,0,0,0.18)',
        boxSizing: 'border-box',
      }}
      onDoubleClick={handleDoubleClick}
      title="Double-clic pour modifier — + pour ajouter une cause"
    >
      {canRemoveCategory ? (
        <button
          type="button"
          className="nodrag nopan ishikawa-node-del ishikawa-node-del--category"
          onClick={handleRemoveCategory}
          style={{
            position: 'absolute',
            left: 8,
            top: '50%',
            transform: 'translateY(-50%)',
            width: 30,
            height: 30,
            borderRadius: 8,
            display: 'inline-flex',
            alignItems: 'center',
            justifyContent: 'center',
            padding: 0,
            cursor: 'pointer',
            border: '1px solid rgba(255,255,255,0.35)',
            background: 'rgba(0,0,0,0.18)',
            color: '#fff',
            zIndex: 2,
          }}
          title="Supprimer cette catégorie et ses causes"
          aria-label="Supprimer cette catégorie"
        >
          <TrashIcon size={15} />
        </button>
      ) : null}

      <div
        style={{
          flex: 1,
          textAlign: 'center',
          minWidth: 0,
          wordBreak: 'break-word',
          hyphens: 'auto',
        }}
      >
        {data.isEditing ? (
          <input
            autoFocus
            className="nodrag"
            value={editValue}
            onChange={(e) => setEditValue(e.target.value)}
            onBlur={handleBlur}
            onKeyDown={handleKeyDown}
            style={{
              background: 'transparent',
              border: 'none',
              color: 'white',
              fontWeight: 600,
              fontSize: 13,
              textAlign: 'center',
              width: '100%',
              outline: 'none',
            }}
          />
        ) : (
          <span>{data.label}</span>
        )}
      </div>

      <button
        type="button"
        className="nodrag nopan"
        onClick={handleAddCause}
        style={addBtnStyle}
        title="Ajouter une cause"
        aria-label="Ajouter une cause"
      >
        +
      </button>

      <Handle type="source" position={data.isTop ? Position.Bottom : Position.Top} id="to-spine" />
      <Handle type="target" position={Position.Left} id="from-causes" />
    </div>
  );
}
