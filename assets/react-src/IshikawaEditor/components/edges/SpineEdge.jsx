import { BaseEdge, getBezierPath } from '@xyflow/react';

export default function SpineEdge({ id, sourceX, sourceY, targetX, targetY, sourcePosition, targetPosition, style }) {
  const [edgePath] = getBezierPath({ sourceX, sourceY, sourcePosition, targetX, targetY, targetPosition });
  return <BaseEdge id={id} path={edgePath} style={{ ...style, strokeWidth: 3, stroke: '#64748b' }} />;
}
