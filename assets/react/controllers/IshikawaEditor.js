// ../IshikawaEditor/index.jsx
import React from "react";
import { ReactFlowProvider } from "@xyflow/react";
import { Component, useEffect as useEffect2 } from "react";

// ../IshikawaEditor/components/IshikawaCanvas.jsx
import { ReactFlow, Background, Controls, MiniMap, Panel } from "@xyflow/react";
import { useShallow as useShallow2 } from "zustand/react/shallow";

// ../IshikawaEditor/store/useIshikawaStore.js
import { create } from "zustand";
import { devtools } from "zustand/middleware";

// ../IshikawaEditor/utils/ishikawaSerializer.js
function serializeToRecord(storeState) {
  const { nodes, edges, meta } = storeState;
  const contentObject = {
    nodes: nodes.map((node) => ({
      id: node.id,
      type: node.type,
      position: node.position,
      data: {
        label: node.data.label,
        categoryId: node.data.categoryId ?? null,
        color: node.data.color ?? null,
        isTop: node.data.isTop ?? null,
        parentCategoryId: node.data.parentCategoryId ?? null
      },
      width: node.width ?? null,
      height: node.height ?? null
    })),
    edges: edges.map((edge) => ({
      id: edge.id,
      source: edge.source,
      target: edge.target,
      type: edge.type,
      data: edge.data ?? {}
    })),
    meta: {
      author: meta.author,
      date: meta.date
    }
  };
  const payload = {
    title: meta.title,
    content: contentObject,
    problem: meta.problem
  };
  if (meta.recordId) {
    payload.id = meta.recordId;
  }
  return payload;
}
function deserializeFromRecord(apiResponse) {
  const envelope = apiResponse && typeof apiResponse.success === "boolean" && apiResponse.data !== void 0 ? apiResponse.data : apiResponse;
  const inner = envelope.content && typeof envelope.content === "object" ? envelope.content : {};
  return {
    id: envelope.id,
    title: envelope.title ?? "Sans titre",
    problem: envelope.problem ?? "",
    createdAt: envelope.createdAt,
    updatedAt: envelope.updatedAt ?? null,
    content: {
      nodes: inner.nodes ?? [],
      edges: inner.edges ?? [],
      meta: inner.meta ?? {}
    }
  };
}

// ../IshikawaEditor/store/slices/nodesSlice.js
import { applyNodeChanges } from "@xyflow/react";

// ../IshikawaEditor/utils/constants.js
var ISHIKAWA_CATEGORIES_5M = [
  { id: "matiere", label: "Mati\xE8re", color: "#E53E3E", shortLabel: "MAT" },
  { id: "milieu", label: "Milieu", color: "#38A169", shortLabel: "MIL" },
  { id: "methode", label: "M\xE9thode", color: "#3182CE", shortLabel: "MET" },
  { id: "materiel", label: "Mat\xE9riel", color: "#D69E2E", shortLabel: "MAT" },
  { id: "mainoeuvre", label: "Main-d'\u0153uvre", color: "#805AD5", shortLabel: "MO" }
];
var ISHIKAWA_CATEGORIES_6M = [
  ...ISHIKAWA_CATEGORIES_5M,
  { id: "management", label: "Management", color: "#ED8936", shortLabel: "MGT" }
];
var NODE_DIMENSIONS = {
  EFFECT: { width: 180, height: 60 },
  CATEGORY: { width: 140, height: 40 },
  CAUSE: { width: 120, height: 30 }
};
var LAYOUT_CONFIG = {
  SPINE_Y: 300,
  SPINE_START_X: 80,
  SPINE_END_X: 900,
  CATEGORY_OFFSET_Y: 180,
  CAUSE_OFFSET: 80
};
var NODE_TYPES_KEYS = {
  EFFECT: "effectNode",
  CATEGORY: "categoryNode",
  CAUSE: "causeNode"
};
var EDGE_TYPES_KEYS = {
  SPINE: "spineEdge",
  BONE: "boneEdge"
};

// ../IshikawaEditor/utils/ishikawaLayout.js
function generateId() {
  return Math.random().toString(36).substring(2, 9);
}

// ../IshikawaEditor/store/slices/nodesSlice.js
var createNodesSlice = (set, get) => ({
  nodes: [],
  onNodesChange: (changes) => {
    set((state) => ({
      nodes: applyNodeChanges(changes, state.nodes),
      meta: { ...state.meta, isDirty: true }
    }));
  },
  initDefaultDiagram: (problemLabel = "Probl\xE8me \xE0 analyser") => {
    const effectNodeId = "effect-main";
    const nodes = [];
    nodes.push({
      id: effectNodeId,
      type: NODE_TYPES_KEYS.EFFECT,
      position: { x: LAYOUT_CONFIG.SPINE_END_X, y: LAYOUT_CONFIG.SPINE_Y - NODE_DIMENSIONS.EFFECT.height / 2 },
      data: {
        label: problemLabel,
        isEditing: false
      },
      width: NODE_DIMENSIONS.EFFECT.width,
      height: NODE_DIMENSIONS.EFFECT.height,
      draggable: false
    });
    ISHIKAWA_CATEGORIES_5M.forEach((cat, index) => {
      const isTop = index % 2 === 0;
      const xPos = LAYOUT_CONFIG.SPINE_START_X + 120 + index * 150;
      const yPos = isTop ? LAYOUT_CONFIG.SPINE_Y - LAYOUT_CONFIG.CATEGORY_OFFSET_Y : LAYOUT_CONFIG.SPINE_Y + LAYOUT_CONFIG.CATEGORY_OFFSET_Y;
      nodes.push({
        id: `cat-${cat.id}`,
        type: NODE_TYPES_KEYS.CATEGORY,
        position: { x: xPos, y: yPos },
        data: {
          label: cat.label,
          categoryId: cat.id,
          color: cat.color,
          isTop,
          isEditing: false
        },
        width: NODE_DIMENSIONS.CATEGORY.width,
        height: NODE_DIMENSIONS.CATEGORY.height
      });
    });
    set({ nodes });
    get().initDefaultEdges(effectNodeId);
    get().markClean();
  },
  addCause: (categoryNodeId, label = "Nouvelle cause") => {
    const state = get();
    const catNode = state.nodes.find((n) => n.id === categoryNodeId);
    if (!catNode) return;
    const existingCauses = state.nodes.filter(
      (n) => n.type === NODE_TYPES_KEYS.CAUSE && n.data.parentCategoryId === categoryNodeId
    );
    const newId = `cause-${generateId()}`;
    const isTop = catNode.data.isTop;
    const xOffset = existingCauses.length * (NODE_DIMENSIONS.CAUSE.width + 10);
    const newNode = {
      id: newId,
      type: NODE_TYPES_KEYS.CAUSE,
      position: {
        x: catNode.position.x - 50 + xOffset,
        y: isTop ? catNode.position.y - LAYOUT_CONFIG.CAUSE_OFFSET : catNode.position.y + NODE_DIMENSIONS.CATEGORY.height + 10
      },
      data: {
        label,
        parentCategoryId: categoryNodeId,
        isEditing: true,
        isNew: true
      },
      width: NODE_DIMENSIONS.CAUSE.width,
      height: NODE_DIMENSIONS.CAUSE.height
    };
    set((s) => ({
      nodes: [...s.nodes, newNode],
      meta: { ...s.meta, isDirty: true }
    }));
    get().addCauseEdge(categoryNodeId, newId);
  },
  updateNodeLabel: (nodeId, label) => {
    set((state) => ({
      nodes: state.nodes.map(
        (n) => n.id === nodeId ? { ...n, data: { ...n.data, label, isEditing: false, isNew: false } } : n
      ),
      meta: { ...state.meta, isDirty: true }
    }));
  },
  setNodeEditing: (nodeId, isEditing) => {
    set({
      nodes: get().nodes.map((n) => n.id === nodeId ? { ...n, data: { ...n.data, isEditing } } : n)
    });
  },
  deleteNode: (nodeId) => {
    const state = get();
    const childIds = state.nodes.filter((n) => n.data?.parentCategoryId === nodeId).map((n) => n.id);
    const idsToRemove = [nodeId, ...childIds];
    set({
      nodes: state.nodes.filter((n) => !idsToRemove.includes(n.id)),
      edges: state.edges.filter((e) => !idsToRemove.includes(e.source) && !idsToRemove.includes(e.target))
    });
  },
  updateCategoryColor: (categoryNodeId, color) => {
    set((state) => ({
      nodes: state.nodes.map((n) => n.id === categoryNodeId ? { ...n, data: { ...n.data, color } } : n),
      meta: { ...state.meta, isDirty: true }
    }));
  }
});

// ../IshikawaEditor/store/slices/edgesSlice.js
import { applyEdgeChanges, addEdge } from "@xyflow/react";
var createEdgesSlice = (set, get) => ({
  edges: [],
  onEdgesChange: (changes) => {
    set((state) => ({
      edges: applyEdgeChanges(changes, state.edges),
      meta: { ...state.meta, isDirty: true }
    }));
  },
  onConnect: (connection) => {
    set((state) => ({
      edges: addEdge({ ...connection, type: EDGE_TYPES_KEYS.BONE, animated: false }, state.edges),
      meta: { ...state.meta, isDirty: true }
    }));
  },
  initDefaultEdges: (effectNodeId) => {
    const nodes = get().nodes;
    const catNodes = nodes.filter((n) => n.type === NODE_TYPES_KEYS.CATEGORY);
    const edges = [];
    catNodes.forEach((cat) => {
      edges.push({
        id: `edge-${cat.id}-effect`,
        source: cat.id,
        target: effectNodeId,
        type: EDGE_TYPES_KEYS.BONE,
        data: { isBone: true, color: cat.data.color }
      });
    });
    set({ edges });
  },
  addCauseEdge: (categoryId, causeId) => {
    const catNode = get().nodes.find((n) => n.id === categoryId);
    const color = catNode?.data?.color ?? "#666";
    set((state) => ({
      edges: [
        ...state.edges,
        {
          id: `edge-${causeId}-${categoryId}`,
          source: causeId,
          target: categoryId,
          type: EDGE_TYPES_KEYS.BONE,
          data: { isBone: true, color }
        }
      ],
      meta: { ...state.meta, isDirty: true }
    }));
  }
});

// ../IshikawaEditor/store/slices/metaSlice.js
var createMetaSlice = (set) => ({
  meta: {
    title: "Nouvelle analyse Ishikawa",
    problem: "",
    author: "",
    date: (/* @__PURE__ */ new Date()).toISOString().split("T")[0],
    recordId: null,
    isDirty: false,
    lastSavedAt: null,
    _apiBase: "/api/ishikawa",
    _csrfToken: ""
  },
  updateMeta: (partial) => {
    set((state) => ({
      meta: { ...state.meta, ...partial, isDirty: true }
    }));
  },
  setRecordId: (id) => {
    set((state) => ({
      meta: { ...state.meta, recordId: id, isDirty: false, lastSavedAt: /* @__PURE__ */ new Date() }
    }));
  },
  markClean: () => {
    set((state) => ({
      meta: { ...state.meta, isDirty: false, lastSavedAt: /* @__PURE__ */ new Date() }
    }));
  },
  /** Props hôte (Twig) sans marquer dirty */
  setHostProps: (partial) => {
    set((state) => ({
      meta: { ...state.meta, ...partial }
    }));
  }
});

// ../IshikawaEditor/store/slices/uiSlice.js
var createUiSlice = (set) => ({
  ui: {
    selectedNodeId: null,
    isPropertiesPanelOpen: false,
    isMetaPanelOpen: false,
    isSaving: false,
    saveError: null,
    isExporting: false,
    zoomLevel: 1,
    mode: "view",
    showMinimap: true
  },
  selectNode: (nodeId) => {
    set((state) => ({
      ui: {
        ...state.ui,
        selectedNodeId: nodeId,
        isPropertiesPanelOpen: nodeId !== null
      }
    }));
  },
  setSaving: (isSaving, error = null) => {
    set((state) => ({
      ui: { ...state.ui, isSaving, saveError: error }
    }));
  },
  setExporting: (isExporting) => {
    set((state) => ({ ui: { ...state.ui, isExporting } }));
  },
  toggleMinimap: () => {
    set((state) => ({
      ui: { ...state.ui, showMinimap: !state.ui.showMinimap }
    }));
  },
  setMode: (mode) => {
    set((state) => ({ ui: { ...state.ui, mode } }));
  },
  setZoom: (zoomLevel) => {
    set((state) => ({ ui: { ...state.ui, zoomLevel } }));
  }
});

// ../IshikawaEditor/store/useIshikawaStore.js
var useIshikawaStore = create(
  devtools(
    (set, get) => ({
      ...createNodesSlice(set, get),
      ...createEdgesSlice(set, get),
      ...createMetaSlice(set),
      ...createUiSlice(set),
      resetDiagram: () => {
        const apiBase = get().meta?._apiBase ?? "/api/ishikawa";
        const csrf = get().meta?._csrfToken ?? "";
        set({
          nodes: [],
          edges: [],
          meta: {
            title: "Nouvelle analyse Ishikawa",
            problem: "",
            author: "",
            date: (/* @__PURE__ */ new Date()).toISOString().split("T")[0],
            recordId: null,
            isDirty: false,
            lastSavedAt: null,
            _apiBase: apiBase,
            _csrfToken: csrf
          },
          ui: {
            selectedNodeId: null,
            isPropertiesPanelOpen: false,
            isMetaPanelOpen: false,
            isSaving: false,
            saveError: null,
            isExporting: false,
            zoomLevel: 1,
            mode: "edit",
            showMinimap: true
          }
        });
      },
      loadFromRecord: (record) => {
        const { nodes, edges, meta: contentMeta } = record.content ?? {};
        set({
          nodes: nodes ?? [],
          edges: edges ?? [],
          meta: {
            title: record.title ?? "Sans titre",
            problem: record.problem ?? "",
            author: contentMeta?.author ?? "",
            date: record.createdAt ? String(record.createdAt).slice(0, 10) : (/* @__PURE__ */ new Date()).toISOString().split("T")[0],
            recordId: record.id,
            isDirty: false,
            lastSavedAt: record.updatedAt ?? null,
            _apiBase: get().meta?._apiBase ?? "/api/ishikawa",
            _csrfToken: get().meta?._csrfToken ?? ""
          }
        });
      },
      saveDiagram: async () => {
        const state = get();
        get().setSaving(true, null);
        try {
          const payload = serializeToRecord(state);
          const response = await fetch("/api/ishikawa/save", {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
              "X-Requested-With": "XMLHttpRequest",
              "X-CSRF-Token": state.meta._csrfToken ?? ""
            },
            body: JSON.stringify(payload)
          });
          if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
          }
          const json = await response.json();
          if (!json.success) {
            throw new Error(json.message ?? "Erreur inconnue");
          }
          get().setRecordId(json.data.id);
          get().setSaving(false, null);
        } catch (err) {
          get().setSaving(false, err.message);
          console.error("Erreur sauvegarde:", err);
        }
      },
      loadDiagram: async (recordId) => {
        try {
          const response = await fetch(`/api/ishikawa/${recordId}`, {
            headers: { "X-Requested-With": "XMLHttpRequest" }
          });
          if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
          }
          const apiResponse = await response.json();
          const record = deserializeFromRecord(apiResponse);
          get().loadFromRecord(record);
        } catch (err) {
          console.error("Erreur chargement:", err);
        }
      }
    }),
    { name: "IshikawaStore" }
  )
);
var useIshikawaStore_default = useIshikawaStore;

// ../IshikawaEditor/components/nodes/EffectNode.jsx
import { Handle, Position } from "@xyflow/react";
import { useState } from "react";
import { jsx, jsxs } from "react/jsx-runtime";
function EffectNode({ id, data }) {
  const [editValue, setEditValue] = useState(data.label);
  const updateNodeLabel = useIshikawaStore_default((s) => s.updateNodeLabel);
  const setNodeEditing = useIshikawaStore_default((s) => s.setNodeEditing);
  const handleDoubleClick = () => setNodeEditing(id, true);
  const handleBlur = () => {
    updateNodeLabel(id, editValue);
  };
  const handleKeyDown = (e) => {
    if (e.key === "Enter") updateNodeLabel(id, editValue);
    if (e.key === "Escape") {
      setEditValue(data.label);
      setNodeEditing(id, false);
    }
  };
  return /* @__PURE__ */ jsxs(
    "div",
    {
      style: {
        background: "#E53E3E",
        color: "white",
        borderRadius: 8,
        padding: "8px 16px",
        minWidth: 160,
        textAlign: "center",
        fontWeight: 700,
        fontSize: 13,
        boxShadow: "0 2px 8px rgba(0,0,0,0.2)",
        cursor: "pointer"
      },
      onDoubleClick: handleDoubleClick,
      title: "Double-clic pour modifier",
      children: [
        data.isEditing ? /* @__PURE__ */ jsx(
          "input",
          {
            autoFocus: true,
            value: editValue,
            onChange: (e) => setEditValue(e.target.value),
            onBlur: handleBlur,
            onKeyDown: handleKeyDown,
            style: {
              background: "transparent",
              border: "none",
              color: "white",
              fontWeight: 700,
              fontSize: 13,
              textAlign: "center",
              width: "100%",
              outline: "none"
            }
          }
        ) : /* @__PURE__ */ jsx("span", { children: data.label || "Probl\xE8me principal" }),
        /* @__PURE__ */ jsx(Handle, { type: "target", position: Position.Left, style: { background: "white" } })
      ]
    }
  );
}

// ../IshikawaEditor/components/nodes/CategoryNode.jsx
import { Handle as Handle2, Position as Position2 } from "@xyflow/react";
import { useState as useState2 } from "react";
import { jsx as jsx2, jsxs as jsxs2 } from "react/jsx-runtime";
function CategoryNode({ id, data }) {
  const [editValue, setEditValue] = useState2(data.label);
  const updateNodeLabel = useIshikawaStore_default((s) => s.updateNodeLabel);
  const setNodeEditing = useIshikawaStore_default((s) => s.setNodeEditing);
  const addCause = useIshikawaStore_default((s) => s.addCause);
  const handleDoubleClick = () => setNodeEditing(id, true);
  const handleBlur = () => updateNodeLabel(id, editValue);
  const handleKeyDown = (e) => {
    if (e.key === "Enter") updateNodeLabel(id, editValue);
    if (e.key === "Escape") {
      setEditValue(data.label);
      setNodeEditing(id, false);
    }
  };
  const handleAddCause = (e) => {
    e.stopPropagation();
    addCause(id);
  };
  return /* @__PURE__ */ jsxs2(
    "div",
    {
      style: {
        background: data.color ?? "#666",
        color: "white",
        borderRadius: 6,
        padding: "4px 12px",
        minWidth: 120,
        textAlign: "center",
        fontWeight: 600,
        fontSize: 12,
        position: "relative",
        cursor: "pointer"
      },
      onDoubleClick: handleDoubleClick,
      title: "Double-clic pour modifier \u2014 Clic + sur le bouton pour ajouter une cause",
      children: [
        data.isEditing ? /* @__PURE__ */ jsx2(
          "input",
          {
            autoFocus: true,
            value: editValue,
            onChange: (e) => setEditValue(e.target.value),
            onBlur: handleBlur,
            onKeyDown: handleKeyDown,
            style: {
              background: "transparent",
              border: "none",
              color: "white",
              fontWeight: 600,
              fontSize: 12,
              textAlign: "center",
              width: "100%",
              outline: "none"
            }
          }
        ) : /* @__PURE__ */ jsx2("span", { children: data.label }),
        /* @__PURE__ */ jsx2(
          "button",
          {
            type: "button",
            onClick: handleAddCause,
            style: {
              position: "absolute",
              right: -10,
              top: "50%",
              transform: "translateY(-50%)",
              width: 20,
              height: 20,
              borderRadius: "50%",
              background: "white",
              color: data.color ?? "#666",
              border: "none",
              fontWeight: 900,
              fontSize: 14,
              cursor: "pointer",
              lineHeight: "20px",
              textAlign: "center",
              padding: 0
            },
            title: "Ajouter une cause",
            children: "+"
          }
        ),
        /* @__PURE__ */ jsx2(Handle2, { type: "source", position: data.isTop ? Position2.Bottom : Position2.Top }),
        /* @__PURE__ */ jsx2(Handle2, { type: "target", position: Position2.Right })
      ]
    }
  );
}

// ../IshikawaEditor/components/nodes/CauseNode.jsx
import { Handle as Handle3, Position as Position3 } from "@xyflow/react";
import { useState as useState3, useEffect, useRef } from "react";
import { jsx as jsx3, jsxs as jsxs3 } from "react/jsx-runtime";
function CauseNode({ id, data }) {
  const [editValue, setEditValue] = useState3(data.label);
  const inputRef = useRef(null);
  const updateNodeLabel = useIshikawaStore_default((s) => s.updateNodeLabel);
  const setNodeEditing = useIshikawaStore_default((s) => s.setNodeEditing);
  const deleteNode = useIshikawaStore_default((s) => s.deleteNode);
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
    if (e.key === "Enter") {
      if (editValue.trim()) updateNodeLabel(id, editValue);
      else deleteNode(id);
    }
    if (e.key === "Escape") {
      if (data.isNew) deleteNode(id);
      else {
        setEditValue(data.label);
        setNodeEditing(id, false);
      }
    }
    if (e.key === "Delete" && !data.isEditing) deleteNode(id);
  };
  return /* @__PURE__ */ jsxs3(
    "div",
    {
      style: {
        background: "white",
        border: "2px solid #CBD5E0",
        borderRadius: 4,
        padding: "3px 8px",
        minWidth: 100,
        fontSize: 11,
        cursor: "pointer"
      },
      onDoubleClick: () => setNodeEditing(id, true),
      onKeyDown: handleKeyDown,
      tabIndex: 0,
      children: [
        data.isEditing ? /* @__PURE__ */ jsx3(
          "input",
          {
            ref: inputRef,
            value: editValue,
            onChange: (e) => setEditValue(e.target.value),
            onBlur: handleBlur,
            onKeyDown: handleKeyDown,
            style: {
              border: "none",
              outline: "none",
              fontSize: 11,
              width: "100%"
            }
          }
        ) : /* @__PURE__ */ jsx3("span", { children: data.label }),
        /* @__PURE__ */ jsx3(Handle3, { type: "source", position: Position3.Right }),
        /* @__PURE__ */ jsx3(Handle3, { type: "target", position: Position3.Left })
      ]
    }
  );
}

// ../IshikawaEditor/components/panels/ToolbarPanel.jsx
import { useShallow } from "zustand/react/shallow";

// ../IshikawaEditor/components/controls/ExportButton.jsx
import { toPng } from "html-to-image";
import { jsx as jsx4 } from "react/jsx-runtime";
function ExportButton() {
  const setExporting = useIshikawaStore_default((s) => s.setExporting);
  const meta = useIshikawaStore_default((s) => s.meta);
  const handleExport = async () => {
    setExporting(true);
    try {
      const canvas = document.querySelector(".react-flow__viewport");
      if (!canvas) return;
      const dataUrl = await toPng(canvas, { backgroundColor: "#ffffff", pixelRatio: 2 });
      const link = document.createElement("a");
      const safeTitle = (meta.title || "ishikawa").replace(/\s+/g, "-").toLowerCase();
      link.download = `ishikawa-${safeTitle}.png`;
      link.href = dataUrl;
      link.click();
    } catch (err) {
      console.error("Erreur export:", err);
    } finally {
      setExporting(false);
    }
  };
  return /* @__PURE__ */ jsx4(
    "button",
    {
      type: "button",
      onClick: handleExport,
      style: {
        background: "#38A169",
        color: "white",
        border: "none",
        borderRadius: 4,
        padding: "4px 12px",
        fontSize: 12,
        cursor: "pointer"
      },
      children: "Export PNG"
    }
  );
}

// ../IshikawaEditor/components/panels/ToolbarPanel.jsx
import { jsx as jsx5, jsxs as jsxs4 } from "react/jsx-runtime";
function ToolbarPanel() {
  const { meta, ui, updateMeta, saveDiagram, toggleMinimap } = useIshikawaStore_default(
    useShallow((state) => ({
      meta: state.meta,
      ui: state.ui,
      updateMeta: state.updateMeta,
      saveDiagram: state.saveDiagram,
      toggleMinimap: state.toggleMinimap
    }))
  );
  return /* @__PURE__ */ jsxs4(
    "div",
    {
      style: {
        background: "white",
        borderRadius: 8,
        padding: "8px 16px",
        boxShadow: "0 2px 8px rgba(0,0,0,0.1)",
        display: "flex",
        alignItems: "center",
        gap: 12,
        minWidth: 400
      },
      children: [
        /* @__PURE__ */ jsx5(
          "input",
          {
            value: meta.title,
            onChange: (e) => updateMeta({ title: e.target.value }),
            placeholder: "Titre de l'analyse...",
            style: {
              border: "1px solid #E2E8F0",
              borderRadius: 4,
              padding: "4px 8px",
              fontSize: 13,
              flex: 1
            }
          }
        ),
        /* @__PURE__ */ jsx5("span", { style: { fontSize: 11, color: meta.isDirty ? "#E53E3E" : "#38A169", whiteSpace: "nowrap" }, children: ui.isSaving ? "\u23F3 Sauvegarde\u2026" : meta.isDirty ? "\u25CF Non sauvegard\xE9" : "\u2713 Sauvegard\xE9" }),
        /* @__PURE__ */ jsx5(
          "button",
          {
            type: "button",
            onClick: saveDiagram,
            disabled: ui.isSaving || !meta.isDirty,
            style: {
              background: "#3182CE",
              color: "white",
              border: "none",
              borderRadius: 4,
              padding: "4px 12px",
              fontSize: 12,
              cursor: "pointer",
              opacity: !meta.isDirty || ui.isSaving ? 0.5 : 1
            },
            children: "Sauvegarder"
          }
        ),
        /* @__PURE__ */ jsx5(ExportButton, {}),
        /* @__PURE__ */ jsx5(
          "button",
          {
            type: "button",
            onClick: toggleMinimap,
            style: {
              background: "transparent",
              border: "1px solid #CBD5E0",
              borderRadius: 4,
              padding: "4px 8px",
              fontSize: 11,
              cursor: "pointer"
            },
            title: "Afficher/masquer la minimap",
            children: "\u{1F5FA}"
          }
        )
      ]
    }
  );
}

// ../IshikawaEditor/components/panels/PropertiesPanel.jsx
import { jsx as jsx6, jsxs as jsxs5 } from "react/jsx-runtime";
function PropertiesPanel() {
  const selectedId = useIshikawaStore_default((s) => s.ui.selectedNodeId);
  const nodes = useIshikawaStore_default((s) => s.nodes);
  const updateNodeLabel = useIshikawaStore_default((s) => s.updateNodeLabel);
  if (!selectedId) {
    return null;
  }
  const node = nodes.find((n) => n.id === selectedId);
  if (!node) {
    return null;
  }
  return /* @__PURE__ */ jsxs5("div", { className: "bg-white border border-slate-200 rounded-lg shadow p-4 w-64 text-sm", children: [
    /* @__PURE__ */ jsx6("h3", { className: "font-semibold mb-2", children: "N\u0153ud s\xE9lectionn\xE9" }),
    /* @__PURE__ */ jsx6("label", { className: "block text-slate-600 mb-1", children: "Libell\xE9" }),
    /* @__PURE__ */ jsx6(
      "input",
      {
        type: "text",
        className: "w-full border border-slate-300 rounded px-2 py-1",
        value: node.data.label ?? "",
        onChange: (e) => updateNodeLabel(selectedId, e.target.value)
      }
    )
  ] });
}

// ../IshikawaEditor/components/edges/SpineEdge.jsx
import { BaseEdge, getBezierPath } from "@xyflow/react";
import { jsx as jsx7 } from "react/jsx-runtime";
function SpineEdge({ id, sourceX, sourceY, targetX, targetY, sourcePosition, targetPosition, style }) {
  const [edgePath] = getBezierPath({ sourceX, sourceY, sourcePosition, targetX, targetY, targetPosition });
  return /* @__PURE__ */ jsx7(BaseEdge, { id, path: edgePath, style: { ...style, strokeWidth: 3, stroke: "#64748b" } });
}

// ../IshikawaEditor/components/edges/BoneEdge.jsx
import { BaseEdge as BaseEdge2, getBezierPath as getBezierPath2 } from "@xyflow/react";
import { jsx as jsx8 } from "react/jsx-runtime";
function BoneEdge({ id, sourceX, sourceY, targetX, targetY, sourcePosition, targetPosition, data, style }) {
  const [edgePath] = getBezierPath2({ sourceX, sourceY, sourcePosition, targetX, targetY, targetPosition });
  const stroke = data?.color ?? "#94a3b8";
  return /* @__PURE__ */ jsx8(BaseEdge2, { id, path: edgePath, style: { ...style, strokeWidth: 2, stroke } });
}

// ../IshikawaEditor/components/IshikawaCanvas.jsx
import { jsx as jsx9, jsxs as jsxs6 } from "react/jsx-runtime";
var nodeTypes = {
  [NODE_TYPES_KEYS.EFFECT]: EffectNode,
  [NODE_TYPES_KEYS.CATEGORY]: CategoryNode,
  [NODE_TYPES_KEYS.CAUSE]: CauseNode
};
var edgeTypes = {
  [EDGE_TYPES_KEYS.SPINE]: SpineEdge,
  [EDGE_TYPES_KEYS.BONE]: BoneEdge
};
function IshikawaCanvas() {
  const { nodes, edges, onNodesChange, onEdgesChange, onConnect, selectNode, ui } = useIshikawaStore_default(
    useShallow2((state) => ({
      nodes: state.nodes,
      edges: state.edges,
      onNodesChange: state.onNodesChange,
      onEdgesChange: state.onEdgesChange,
      onConnect: state.onConnect,
      selectNode: state.selectNode,
      ui: state.ui
    }))
  );
  return /* @__PURE__ */ jsx9("div", { style: { width: "100%", height: "75vh", background: "#F7FAFC" }, children: /* @__PURE__ */ jsxs6(
    ReactFlow,
    {
      nodes,
      edges,
      nodeTypes,
      edgeTypes,
      onNodesChange,
      onEdgesChange,
      onConnect,
      onNodeClick: (_, node) => selectNode(node.id),
      onPaneClick: () => selectNode(null),
      fitView: true,
      attributionPosition: "bottom-left",
      proOptions: { hideAttribution: false },
      children: [
        /* @__PURE__ */ jsx9(Background, { variant: "dots", gap: 20, size: 1, color: "#CBD5E0" }),
        /* @__PURE__ */ jsx9(Controls, { position: "bottom-right" }),
        ui.showMinimap ? /* @__PURE__ */ jsx9(MiniMap, { nodeStrokeWidth: 3, pannable: true, zoomable: true }) : null,
        /* @__PURE__ */ jsx9(Panel, { position: "top-left", children: /* @__PURE__ */ jsx9(ToolbarPanel, {}) }),
        ui.isPropertiesPanelOpen ? /* @__PURE__ */ jsx9(Panel, { position: "top-right", children: /* @__PURE__ */ jsx9(PropertiesPanel, {}) }) : null
      ]
    }
  ) });
}

// ../IshikawaEditor/components/panels/MetaPanel.jsx
import { jsx as jsx10, jsxs as jsxs7 } from "react/jsx-runtime";
function MetaPanel() {
  const meta = useIshikawaStore_default((s) => s.meta);
  const updateMeta = useIshikawaStore_default((s) => s.updateMeta);
  return /* @__PURE__ */ jsxs7("div", { className: "border-b border-slate-200 bg-white px-5 py-3 flex flex-wrap gap-4 items-center", children: [
    /* @__PURE__ */ jsxs7("label", { className: "text-sm text-slate-600 flex flex-col gap-1", children: [
      "Probl\xE8me",
      /* @__PURE__ */ jsx10(
        "textarea",
        {
          className: "border border-slate-300 rounded px-2 py-1 min-w-[240px] text-sm",
          rows: 2,
          value: meta.problem,
          onChange: (e) => updateMeta({ problem: e.target.value })
        }
      )
    ] }),
    /* @__PURE__ */ jsxs7("label", { className: "text-sm text-slate-600 flex flex-col gap-1", children: [
      "Auteur",
      /* @__PURE__ */ jsx10(
        "input",
        {
          type: "text",
          className: "border border-slate-300 rounded px-2 py-1 text-sm",
          value: meta.author,
          onChange: (e) => updateMeta({ author: e.target.value })
        }
      )
    ] })
  ] });
}

// ../IshikawaEditor/index.jsx
import { jsx as jsx11, jsxs as jsxs8 } from "react/jsx-runtime";
var IshikawaErrorBoundary = class extends Component {
  constructor(props) {
    super(props);
    this.state = { error: null };
  }
  static getDerivedStateFromError(error) {
    return { error };
  }
  componentDidCatch(error, info) {
    console.error("[IshikawaEditor]", error, info?.componentStack);
  }
  render() {
    const { error } = this.state;
    if (error) {
      const text = error?.stack || error?.message || String(error);
      return /* @__PURE__ */ jsxs8(
        "div",
        {
          role: "alert",
          style: {
            padding: "1rem",
            margin: "1rem",
            background: "#fff5f5",
            border: "1px solid #feb2b2",
            color: "#742a2a",
            fontFamily: "system-ui, sans-serif",
            maxWidth: "56rem"
          },
          children: [
            /* @__PURE__ */ jsx11("strong", { children: "Erreur dans l\u2019\xE9diteur Ishikawa (v2)" }),
            /* @__PURE__ */ jsx11("pre", { style: { whiteSpace: "pre-wrap", fontSize: "0.8rem", marginTop: "0.75rem" }, children: text })
          ]
        }
      );
    }
    return this.props.children;
  }
};
function IshikawaEditor({ recordId, apiBase, csrfToken }) {
  const initDefaultDiagram = useIshikawaStore_default((s) => s.initDefaultDiagram);
  const loadDiagram = useIshikawaStore_default((s) => s.loadDiagram);
  const setHostProps = useIshikawaStore_default((s) => s.setHostProps);
  useEffect2(() => {
    setHostProps({
      _apiBase: apiBase ?? "/api/ishikawa",
      _csrfToken: csrfToken ?? ""
    });
    if (recordId) {
      loadDiagram(recordId);
    } else {
      initDefaultDiagram();
    }
  }, [recordId, apiBase, csrfToken, initDefaultDiagram, loadDiagram, setHostProps]);
  return /* @__PURE__ */ jsx11(IshikawaErrorBoundary, { children: /* @__PURE__ */ jsx11(ReactFlowProvider, { children: /* @__PURE__ */ jsxs8("div", { className: "flex flex-col h-full min-h-[70vh]", children: [
    /* @__PURE__ */ jsx11(MetaPanel, {}),
    /* @__PURE__ */ jsx11(IshikawaCanvas, {})
  ] }) }) });
}
export {
  IshikawaEditor as default
};
