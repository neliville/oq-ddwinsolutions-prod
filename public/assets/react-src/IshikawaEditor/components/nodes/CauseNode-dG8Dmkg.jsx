import { Handle, Position } from '@xyflow/react';
import { useState, useEffect, useRef } from 'react';
import useIshikawaStore from '../../store/useIshikawaStore.js';
import { NODE_DIMENSIONS } from '../../utils/constants.js';
import { requestUserConfirmation, notifyIshikawa } from '../../utils/hostUi.js';
import TrashIcon from '../icons/TrashIcon.jsx';

const { width: CAUSE_W, height: CAUSE_H } = NODE_DIMENSIONS.CAUSE;

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

  const confirmAndDeleteCause = async () => {
    const labelPreview = (data.label || '').trim() || 'cette cause';
    const confirmed = await requestUserConfirmation({
      title: 'Supprimer la cause',
      message: `Supprimer « ${labelPreview} » ? Cette action est irréversible.`,
      confirmText: 'Supprimer',
      cancelText: 'Annuler',
    });
    if (!confirmed) return;
    deleteNode(id);
    notifyIshikawa('Cause supprimée.', 'success');
  };

  const handleRemoveCauseClick = (e) => {
    e.stopPropagation();
    void confirmAndDeleteCause();
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
    if (e.key === 'Delete' && !data.isEditing) {
      e.preventDefault();
      void confirmAndDeleteCause();
    }
  };

  return (
    <div
      className="nopan ishikawa-node ishikawa-node--cause"
      style={{
        position: 'relative',
        width: CAUSE_W,
        minHeight: CAUSE_H,
        background: 'white',
        border: '2px solid #CBD5E0',
        borderRadius: 8,
        padding: '6px 10px 6px 32px',
        fontSize: 12,
        lineHeight: 1.35,
        cursor: data.isEditing ? 'text' : 'grab',
        display: 'flex',
        alignItems: 'center',
        boxShadow: '0 1px 4px rgba(0,0,0,0.07)',
        boxSizing: 'border-box',
      }}
      onDoubleClick={() => setNodeEditing(id, true)}
      onKeyDown={handleKeyDown}
      tabIndex={0}
    >
      {!data.isEditing ? (
        <button
          type="button"
          className="nodrag nopan ishikawa-node-del ishikawa-node-del--cause"
          onClick={handleRemoveCauseClick}
          style={{
            position: 'absolute',
            left: 6,
            top: '50%',
            transform: 'translateY(-50%)',
            width: 26,
            height: 26,
            borderRadius: 6,
            display: 'inline-flex',
            alignItems: 'center',
            justifyContent: 'center',
            padding: 0,
            cursor: 'pointer',
            border: '1px solid rgba(148,163,184,0.45)',
            background: 'rgba(248,250,252,0.92)',
            color: '#64748b',
            zIndex: 1,
          }}
          title="Supprimer cette cause"
          aria-label="Supprimer cette cause"
        >
          <TrashIcon size={13} />
        </button>
      ) : null}

      <div style={{ flex: 1, minWidth: 0, wordBreak: 'break-word' }}>
        {data.isEditing ? (
          <input
            ref={inputRef}
            className="nodrag"
            value={editValue}
            onChange={(e) => setEditValue(e.target.value)}
            onBlur={handleBlur}
            onKeyDown={handleKeyDown}
            style={{
              border: 'none',
              outline: 'none',
              fontSize: 12,
              width: '100%',
            }}
          />
        ) : (
          <span>{data.label}</span>
        )}
      </div>

      <Handle type="source" position={Position.Right} />
      <Handle type="target" position={Position.Left} />
    </div>
  );
}
