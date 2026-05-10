import { BaseEdge, getBezierPath } from '@xyflow/react';
import { useShallow } from 'zustand/react/shallow';
import useIshikawaStore from '../../store/useIshikawaStore.js';
import { NODE_TYPES_KEYS } from '../../utils/constants.js';
import { getCauseRibPath } from '../../utils/ishikawaLayout.js';

/**
 * Branche : polyline catégorie → point sur l’arête → tête (diagramme en arête de poisson),
 * nervure horizontale cause → os oblique → catégorie,
 * ou courbe de Bézier pour les connexions libres (ex. onConnect).
 */
export default function BoneEdge({
  id,
  source,
  target,
  sourceX,
  sourceY,
  targetX,
  targetY,
  sourcePosition,
  targetPosition,
  data,
  style,
}) {
  const stroke = data?.color ?? '#94a3b8';

  const categoryForCause = useIshikawaStore(
    useShallow((s) => {
      if (typeof source !== 'string' || !source.startsWith('cause-')) return null;
      const cat = s.nodes.find((x) => x.id === target);
      return cat?.type === NODE_TYPES_KEYS.CATEGORY ? cat : null;
    })
  );

  const causeNode = useIshikawaStore(
    useShallow((s) => {
      if (typeof source !== 'string' || !source.startsWith('cause-')) return null;
      const c = s.nodes.find((x) => x.id === source);
      return c?.type === NODE_TYPES_KEYS.CAUSE ? c : null;
    })
  );

  if (categoryForCause && typeof categoryForCause.data?.spineAttachX === 'number') {
    const path = getCauseRibPath(
      sourceX,
      sourceY,
      targetX,
      targetY,
      categoryForCause,
      causeNode?.data?.boneSlotT
    );
    return <BaseEdge id={id} path={path} style={{ ...style, strokeWidth: 2, stroke }} />;
  }

  if (data?.fishbone && typeof data.spineAttachX === 'number' && typeof data.spineAttachY === 'number') {
    const jx = data.spineAttachX;
    const jy = data.spineAttachY;
    const path = `M ${sourceX} ${sourceY} L ${jx} ${jy} L ${targetX} ${targetY}`;
    return <BaseEdge id={id} path={path} style={{ ...style, strokeWidth: 2.5, stroke }} />;
  }

  const [edgePath] = getBezierPath({ sourceX, sourceY, sourcePosition, targetX, targetY, targetPosition });
  return <BaseEdge id={id} path={edgePath} style={{ ...style, strokeWidth: 2, stroke }} />;
}
