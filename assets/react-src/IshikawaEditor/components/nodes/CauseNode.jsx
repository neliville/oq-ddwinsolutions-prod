import { Handle, Position } from '@xyflow/react';
import { useState, useEffect, useRef } from 'react';
import useIshikawaStore from '../../store/useIshikawaStore.js';

export default function CauseNode({ id, data }) {
  const [editValue, setEditValue] = useState(data.label);
  const inputRef = useRef(null);
  const updateNodeLabel = useIshikawaStore((s) => s.updateNodeLabel);
  const setNodeEditing = useIshikawaStore((s) => s.setNodeEditing);
  const deleteNode = useIshikawaStore((s) => s.deleteNode);

  useEffect(() => {
    if (data.isNew && data.isEditing && inputRef.current) {
      inputRef.current.focus();
      inputRef.current.select();
    }
  }, [data.isNew, data.isEditing]);

  const handleBlur = () => {
    if (editValue.trim()) updateNodeLabel(id, editValue);
    else deleteNode(id);
  };

  const handleKeyDown = (e) => {
    if (e.key === 'Enter') {
      if (editValue.trim()) updateNodeLabel(id, editValue);
      else deleteNode(id);
    }
    if (e.key === 'Escape') {
      if (data.isNew) deleteNode(id);
      else {
        setEditValue(data.label);
        setNodeEditing(id, false);
      }
    }
    if (e.key === 'Delete' && !data.isEditing) deleteNode(id);
  };

  return (
    <div
      style={{
        background: 'white',
        border: '2px solid #CBD5E0',
        borderRadius: 4,
        padding: '3px 8px',
        minWidth: 100,
        fontSize: 11,
        cursor: 'pointer',
      }}
      onDoubleClick={() => setNodeEditing(id, true)}
      onKeyDown={handleKeyDown}
      tabIndex={0}
    >
      {data.isEditing ? (
        <input
          ref={inputRef}
          value={editValue}
          onChange={(e) => setEditValue(e.target.value)}
          onBlur={handleBlur}
          onKeyDown={handleKeyDown}
          style={{
            border: 'none',
            outline: 'none',
            fontSize: 11,
            width: '100%',
          }}
        />
      ) : (
        <span>{data.label}</span>
      )}

      <Handle type="source" position={Position.Right} />
      <Handle type="target" position={Position.Left} />
    </div>
  );
}
