import React from 'react';
import { ReactFlowProvider } from '@xyflow/react';
import { Component, useEffect } from 'react';
import IshikawaCanvas from './components/IshikawaCanvas.jsx';
import MetaPanel from './components/panels/MetaPanel.jsx';
import useIshikawaStore from './store/useIshikawaStore.js';

/** Affiche l’erreur à l’écran (Edge masque parfois la console : catégorie « other », messages cachés). */
class IshikawaErrorBoundary extends Component {
  constructor(props) {
    super(props);
    this.state = { error: null };
  }

  static getDerivedStateFromError(error) {
    return { error };
  }

  componentDidCatch(error, info) {
    console.error('[IshikawaEditor]', error, info?.componentStack);
  }

  render() {
    const { error } = this.state;
    if (error) {
      const text = error?.stack || error?.message || String(error);
      return (
        <div
          role="alert"
          style={{
            padding: '1rem',
            margin: '1rem',
            background: '#fff5f5',
            border: '1px solid #feb2b2',
            color: '#742a2a',
            fontFamily: 'system-ui, sans-serif',
            maxWidth: '56rem',
          }}
        >
          <strong>Erreur dans l’éditeur Ishikawa (v2)</strong>
          <pre style={{ whiteSpace: 'pre-wrap', fontSize: '0.8rem', marginTop: '0.75rem' }}>{text}</pre>
        </div>
      );
    }
    return this.props.children;
  }
}

export default function IshikawaEditor({ recordId, apiBase, csrfToken, savedListEnabled }) {
  const initDefaultDiagram = useIshikawaStore((s) => s.initDefaultDiagram);
  const loadDiagram = useIshikawaStore((s) => s.loadDiagram);
  const setHostProps = useIshikawaStore((s) => s.setHostProps);

  useEffect(() => {
    setHostProps({
      _apiBase: apiBase ?? '/api/ishikawa',
      _csrfToken: csrfToken ?? '',
      showSavedList: Boolean(savedListEnabled),
    });

    const rid =
      recordId != null && String(recordId).trim() !== '' ? Number.parseInt(String(recordId), 10) : Number.NaN;
    if (Number.isInteger(rid) && rid > 0) {
      loadDiagram(rid);
    } else {
      initDefaultDiagram();
    }
  }, [recordId, apiBase, csrfToken, savedListEnabled, initDefaultDiagram, loadDiagram, setHostProps]);

  return (
    <IshikawaErrorBoundary>
      <ReactFlowProvider>
        <div className="flex flex-col h-full min-h-[70vh]">
          <MetaPanel />
          <IshikawaCanvas />
        </div>
      </ReactFlowProvider>
    </IshikawaErrorBoundary>
  );
}
