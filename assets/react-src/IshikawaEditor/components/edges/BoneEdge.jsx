import { BaseEdge, getBezierPath } from '@xyflow/react';

export default function BoneEdge({ id, sourceX, sourceY, targetX, targetY, sourcePosition, targetPosition, data, style }) {
  const [edgePath] = getBezierPath({ sourceX, sourceY, sourcePosition, targetX, targetY, targetPosition });
  const stroke = data?.color ?? '#94a3b8';
  return <BaseEdge id={id} path={edgePath} style={{ ...style, strokeWidth: 2, stroke }} />;
}
