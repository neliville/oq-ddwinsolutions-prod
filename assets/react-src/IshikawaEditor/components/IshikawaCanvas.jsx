import { ReactFlow, Background, Controls, MiniMap, Panel } from '@xyflow/react';
import { useShallow } from 'zustand/react/shallow';
import useIshikawaStore from '../store/useIshikawaStore.js';
import TailNode from './nodes/TailNode.jsx';
import EffectNode from './nodes/EffectNode.jsx';
import CategoryNode from './nodes/CategoryNode.jsx';
import CauseNode from './nodes/CauseNode.jsx';
import ToolbarPanel from './panels/ToolbarPanel.jsx';
import PropertiesPanel from './panels/PropertiesPanel.jsx';
import SpineEdge from './edges/SpineEdge.jsx';
import BoneEdge from './edges/BoneEdge.jsx';
import {
  NODE_TYPES_KEYS,
  EDGE_TYPES_KEYS,
  EFFECT_NODE_ID,
} from '../utils/constants.js';

const nodeTypes = {
  [NODE_TYPES_KEYS.TAIL]: TailNode,
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
    <div style={{ width: '100%', height: '78vh', background: '#F7FAFC' }}>
      <ReactFlow
        nodes={nodes}
        edges={edges}
        nodeTypes={nodeTypes}
        edgeTypes={edgeTypes}
        nodesDraggable
        nodesConnectable={false}
        onNodesChange={onNodesChange}
        onEdgesChange={onEdgesChange}
        onConnect={onConnect}
        onNodeClick={(_, node) => selectNode(node.id)}
        onPaneClick={() => selectNode(null)}
        onNodeDragStop={(_, node) => {
          const api = useIshikawaStore.getState();
          if (node.type === NODE_TYPES_KEYS.CATEGORY) {
            api.reanchorCausesForCategory(node.id, {}, node);
          }
          if (node.type === NODE_TYPES_KEYS.CAUSE) {
            api.snapCauseToParentBone(node.id);
          }
          if (node.id === EFFECT_NODE_ID) {
            api.snapEffectToSpineRow();
          }
        }}
        fitView
        fitViewOptions={{ padding: 0.24 }}
        attributionPosition="bottom-left"
        proOptions={{ hideAttribution: false }}
      >
        <Background variant="dots" gap={24} size={1} color="#CBD5E0" />

        <Panel
          position="bottom-right"
          className="ishikawa-flow-rail"
          style={{
            display: 'flex',
            flexDirection: 'column',
            alignItems: 'flex-end',
            gap: 10,
            margin: '10px 14px 14px 10px',
            zIndex: 20,
          }}
        >
          <Controls
            className="ishikawa-flow-controls"
            orientation="vertical"
            showZoom
            showFitView
            showInteractive={false}
          />
          {ui.showMinimap ? (
            <div className="ishikawa-flow-minimap-chrome">
              <MiniMap
                pannable
                zoomable
                nodeStrokeWidth={2}
                style={{ width: 200, height: 140 }}
                maskColor="rgba(15, 23, 42, 0.18)"
                nodeColor={(node) => {
                  switch (node.type) {
                    case NODE_TYPES_KEYS.EFFECT:
                      return '#e53e3e';
                    case NODE_TYPES_KEYS.TAIL:
                      return '#475569';
                    case NODE_TYPES_KEYS.CATEGORY:
                      return '#94a3b8';
                    case NODE_TYPES_KEYS.CAUSE:
                      return '#cbd5e1';
                    default:
                      return '#a8b4c4';
                  }
                }}
              />
            </div>
          ) : null}
        </Panel>

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
