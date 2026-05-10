import { Handle, Position } from '@xyflow/react';
import { NODE_DIMENSIONS } from '../../utils/constants.js';

const { width: TAIL_W, height: TAIL_H } = NODE_DIMENSIONS.TAIL;

/**
 * Queue du diagramme (début de l’arête) — libellé et position fixes.
 */
export default function TailNode() {
  return (
    <div
      className="nodrag nopan"
      style={{
        background: '#64748b',
        color: 'white',
        borderRadius: 8,
        padding: '8px 12px',
        width: TAIL_W,
        minHeight: TAIL_H,
        boxSizing: 'border-box',
        textAlign: 'center',
        fontWeight: 600,
        fontSize: 12,
        letterSpacing: '0.02em',
        border: '2px solid #475569',
        cursor: 'default',
        userSelect: 'none',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
      }}
      title="Début de l’arête de poisson (fixe)"
    >
      Cause racines
      <Handle type="source" position={Position.Right} style={{ background: '#e2e8f0' }} />
    </div>
  );
}
