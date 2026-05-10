import { BaseEdge } from '@xyflow/react';

/** Arête principale horizontale (queue → tête). */
export default function SpineEdge({ id, sourceX, sourceY, targetX, targetY, style }) {
  const path = `M ${sourceX} ${sourceY} L ${targetX} ${targetY}`;
  return <BaseEdge id={id} path={path} style={{ ...style, strokeWidth: 4, stroke: '#475569' }} />;
}
