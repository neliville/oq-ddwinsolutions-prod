import { ReactFlow, Background, Controls, MiniMap, Panel } from '@xyflow/react';
import { useShallow } from 'zustand/react/shallow';
import useIshikawaStore from '../store/useIshikawaStore.js';
import EffectNode from './nodes/EffectNode.jsx';
import CategoryNode from './nodes/CategoryNode.jsx';
import CauseNode from './nodes/CauseNode.jsx';
import ToolbarPanel from './panels/ToolbarPanel.jsx';
import PropertiesPanel from './panels/PropertiesPanel.jsx';
import SpineEdge from './edges/SpineEdge.jsx';
import BoneEdge from './edges/BoneEdge.jsx';
import { NODE_TYPES_KEYS, EDGE_TYPES_KEYS } from '../utils/constants.js';

const nodeTypes = {
  [NODE_TYPES_KEYS.EFFECT]: EffectNode,
  [NODE_TYPES_KEYS.CATEGORY]: CategoryNode,
  [NODE_TYPES_KEYS.CAUSE]: CauseNode,
};

const edgeTypes = {
  [EDGE_TYPES_KEYS.SPINE]: SpineEdge,
  [EDGE_TYPES_KEYS.BONE]: BoneEdge,
};

export default function IshikawaCanvas() {
  const { nodes, edges, onNodesChange, onEdgesChange, onConnect, selectNode, ui } = useIshikawaStore(
    useShallow((state) => ({
      nodes: state.nodes,
      edges: state.edges,
      onNodesChange: state.onNodesChange,
      onEdgesChange: state.onEdgesChange,
      onConnect: state.onConnect,
      selectNode: state.selectNode,
      ui: state.ui,
    }))
  );

  return (
    <div style={{ width: '100%', height: '75vh', background: '#F7FAFC' }}>
      <ReactFlow
        nodes={nodes}
        edges={edges}
        nodeTypes={nodeTypes}
        edgeTypes={edgeTypes}
        onNodesChange={onNodesChange}
        onEdgesChange={onEdgesChange}
        onConnect={onConnect}
        onNodeClick={(_, node) => selectNode(node.id)}
        onPaneClick={() => selectNode(null)}
        fitView
        attributionPosition="bottom-left"
        proOptions={{ hideAttribution: false }}
      >
        <Background variant="dots" gap={20} size={1} color="#CBD5E0" />
        <Controls position="bottom-right" />
        {ui.showMinimap ? <MiniMap nodeStrokeWidth={3} pannable zoomable /> : null}

        <Panel position="top-left">
          <ToolbarPanel />
        </Panel>

        {ui.isPropertiesPanelOpen ? (
          <Panel position="top-right">
            <PropertiesPanel />
          </Panel>
        ) : null}
      </ReactFlow>
    </div>
  );
}
