import { Handle, Position } from '@xyflow/react';
import { useState } from 'react';
import useIshikawaStore from '../../store/useIshikawaStore.js';
import { NODE_DIMENSIONS } from '../../utils/constants.js';

const { width: EFF_W, height: EFF_H } = NODE_DIMENSIONS.EFFECT;

export default function EffectNode({ id, data }) {
  const [editValue, setEditValue] = useState(data.label);
  const updateNodeLabel = useIshikawaStore((s) => s.updateNodeLabel);
  const setNodeEditing = useIshikawaStore((s) => s.setNodeEditing);

  const handleDoubleClick = () => setNodeEditing(id, true);

  const handleBlur = () => {
    updateNodeLabel(id, editValue);
  };

  const handleKeyDown = (e) => {
    if (e.key === 'Enter') updateNodeLabel(id, editValue);
    if (e.key === 'Escape') {
      setEditValue(data.label);
      setNodeEditing(id, false);
    }
  };

  return (
    <div
      className="nopan"
      style={{
        background: '#E53E3E',
        color: 'white',
        borderRadius: 10,
        padding: '10px 18px',
        width: EFF_W,
        minHeight: EFF_H,
        boxSizing: 'border-box',
        textAlign: 'center',
        fontWeight: 700,
        fontSize: 13,
        lineHeight: 1.35,
        wordBreak: 'break-word',
        boxShadow: '0 2px 8px rgba(0,0,0,0.2)',
        cursor: data.isEditing ? 'text' : 'grab',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
      }}
      onDoubleClick={handleDoubleClick}
      title="Double-clic pour modifier"
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
            fontWeight: 700,
            fontSize: 13,
            textAlign: 'center',
            width: '100%',
            outline: 'none',
          }}
        />
      ) : (
        <span>{data.label || 'Problème principal'}</span>
      )}
      <Handle type="target" position={Position.Left} style={{ background: 'white' }} />
    </div>
  );
}
