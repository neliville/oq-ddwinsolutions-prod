import { Handle, Position } from '@xyflow/react';
import { useState } from 'react';
import useIshikawaStore from '../../store/useIshikawaStore.js';

export default function CategoryNode({ id, data }) {
  const [editValue, setEditValue] = useState(data.label);
  const updateNodeLabel = useIshikawaStore((s) => s.updateNodeLabel);
  const setNodeEditing = useIshikawaStore((s) => s.setNodeEditing);
  const addCause = useIshikawaStore((s) => s.addCause);

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

  return (
    <div
      style={{
        background: data.color ?? '#666',
        color: 'white',
        borderRadius: 6,
        padding: '4px 12px',
        minWidth: 120,
        textAlign: 'center',
        fontWeight: 600,
        fontSize: 12,
        position: 'relative',
        cursor: 'pointer',
      }}
      onDoubleClick={handleDoubleClick}
      title="Double-clic pour modifier — Clic + sur le bouton pour ajouter une cause"
    >
      {data.isEditing ? (
        <input
          autoFocus
          value={editValue}
          onChange={(e) => setEditValue(e.target.value)}
          onBlur={handleBlur}
          onKeyDown={handleKeyDown}
          style={{
            background: 'transparent',
            border: 'none',
            color: 'white',
            fontWeight: 600,
            fontSize: 12,
            textAlign: 'center',
            width: '100%',
            outline: 'none',
          }}
        />
      ) : (
        <span>{data.label}</span>
      )}

      <button
        type="button"
        onClick={handleAddCause}
        style={{
          position: 'absolute',
          right: -10,
          top: '50%',
          transform: 'translateY(-50%)',
          width: 20,
          height: 20,
          borderRadius: '50%',
          background: 'white',
          color: data.color ?? '#666',
          border: 'none',
          fontWeight: 900,
          fontSize: 14,
          cursor: 'pointer',
          lineHeight: '20px',
          textAlign: 'center',
          padding: 0,
        }}
        title="Ajouter une cause"
      >
        +
      </button>

      <Handle type="source" position={data.isTop ? Position.Bottom : Position.Top} />
      <Handle type="target" position={Position.Right} />
    </div>
  );
}
