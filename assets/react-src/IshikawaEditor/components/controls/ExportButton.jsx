import { useCallback, useEffect, useRef, useState } from 'react';
import { toPng } from 'html-to-image';
import useIshikawaStore from '../../store/useIshikawaStore.js';
import { serializeToRecord } from '../../utils/ishikawaSerializer.js';
import { notifyIshikawa } from '../../utils/hostUi.js';

const MENU_ITEM_BASE = {
  display: 'flex',
  alignItems: 'center',
  gap: 10,
  width: '100%',
  boxSizing: 'border-box',
  margin: 0,
  border: 'none',
  background: 'transparent',
  cursor: 'pointer',
  textAlign: 'left',
  fontSize: 13,
  lineHeight: 1.35,
  fontWeight: 500,
  color: '#0f172a',
  padding: '10px 14px',
  borderRadius: 0,
};

function MenuGlyph({ children }) {
  return (
    <span
      aria-hidden
      style={{
        display: 'inline-flex',
        width: 18,
        flexShrink: 0,
        color: '#64748b',
        alignItems: 'center',
        justifyContent: 'center',
      }}
    >
      {children}
    </span>
  );
}

export default function ExportButton() {
  const [open, setOpen] = useState(false);
  const rootRef = useRef(null);
  const setExporting = useIshikawaStore((s) => s.setExporting);
  const meta = useIshikawaStore((s) => s.meta);
  const ui = useIshikawaStore((s) => s.ui);

  const close = useCallback(() => setOpen(false), []);

  useEffect(() => {
    if (!open) return undefined;
    const onDocMouseDown = (e) => {
      if (rootRef.current && !rootRef.current.contains(e.target)) {
        close();
      }
    };
    const onKeyDown = (e) => {
      if (e.key === 'Escape') close();
    };
    document.addEventListener('mousedown', onDocMouseDown, true);
    document.addEventListener('keydown', onKeyDown);
    return () => {
      document.removeEventListener('mousedown', onDocMouseDown, true);
      document.removeEventListener('keydown', onKeyDown);
    };
  }, [open, close]);

  const safeFileBase = () => (meta.title || 'ishikawa').replace(/\s+/g, '-').toLowerCase().replace(/[^a-z0-9-_]/gi, '') || 'ishikawa';

  const handleExportPng = async () => {
    close();
    setExporting(true);
    try {
      const canvas = document.querySelector('.react-flow__viewport');
      if (!canvas) {
        notifyIshikawa('Zone du diagramme introuvable pour l’export.', 'warning');
        return;
      }
      const dataUrl = await toPng(canvas, { backgroundColor: '#ffffff', pixelRatio: 2 });
      const link = document.createElement('a');
      link.download = `${safeFileBase()}.png`;
      link.href = dataUrl;
      link.click();
      notifyIshikawa('Image PNG exportée.', 'success');
    } catch (err) {
      console.error('Erreur export PNG:', err);
      notifyIshikawa(err?.message ? `Export PNG impossible : ${err.message}` : 'Export PNG impossible.', 'error');
    } finally {
      setExporting(false);
    }
  };

  const handleExportPdf = async () => {
    close();
    const html2canvas = window.html2canvas;
    const jspdfNs = window.jspdf;
    const JsPDF = jspdfNs?.jsPDF;
    if (typeof html2canvas !== 'function' || typeof JsPDF !== 'function') {
      notifyIshikawa(
        'Export PDF : bibliothèques indisponibles. Rechargez la page ou vérifiez votre bloqueur de contenu.',
        'warning'
      );
      return;
    }

    setExporting(true);
    try {
      const el = document.querySelector('.react-flow__viewport');
      if (!el) {
        notifyIshikawa('Zone du diagramme introuvable pour l’export.', 'warning');
        return;
      }
      const snapshot = await html2canvas(el, { backgroundColor: '#ffffff', scale: 2, useCORS: true });
      const imgData = snapshot.toDataURL('image/png', 1.0);
      const w = snapshot.width;
      const h = snapshot.height;
      const pdf = new JsPDF({ orientation: w > h ? 'l' : 'p', unit: 'px', format: [w, h] });
      pdf.addImage(imgData, 'PNG', 0, 0, w, h, undefined, 'FAST');
      pdf.save(`${safeFileBase()}.pdf`);
      notifyIshikawa('Document PDF exporté.', 'success');
    } catch (err) {
      console.error('Erreur export PDF:', err);
      notifyIshikawa(err?.message ? `Export PDF impossible : ${err.message}` : 'Export PDF impossible.', 'error');
    } finally {
      setExporting(false);
    }
  };

  const handleExportJson = () => {
    close();
    try {
      const state = useIshikawaStore.getState();
      const payload = serializeToRecord(state);
      const envelope = {
        version: 2,
        tool: 'ishikawa-reactflow',
        exportedAt: new Date().toISOString(),
        ...payload,
      };
      const blob = new Blob([JSON.stringify(envelope, null, 2)], { type: 'application/json;charset=utf-8' });
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = `${safeFileBase()}.json`;
      link.click();
      URL.revokeObjectURL(url);
      notifyIshikawa('Fichier JSON exporté.', 'success');
    } catch (err) {
      console.error('Erreur export JSON:', err);
      notifyIshikawa('Export JSON impossible.', 'error');
    }
  };

  const busy = ui.isExporting;

  return (
    <div
      ref={rootRef}
      className="ishikawa-export-wrap"
      style={{
        position: 'relative',
        zIndex: 40,
        display: 'inline-flex',
        flexDirection: 'column',
        alignItems: 'stretch',
      }}
    >
      <button
        type="button"
        className="ishikawa-export-trigger"
        aria-haspopup="menu"
        aria-expanded={open}
        disabled={busy}
        onClick={() => setOpen((v) => !v)}
        style={{
          display: 'inline-flex',
          alignItems: 'center',
          gap: 6,
          background: '#2563eb',
          color: 'white',
          border: '1px solid #1d4ed8',
          borderRadius: 8,
          padding: '6px 14px',
          fontSize: 12,
          fontWeight: 600,
          cursor: busy ? 'wait' : 'pointer',
          boxShadow: '0 1px 2px rgba(15,23,42,0.12)',
          opacity: busy ? 0.75 : 1,
        }}
        title="Exporter le diagramme"
      >
        <span aria-hidden="true" style={{ fontSize: 14, lineHeight: 1 }}>
          ⬇
        </span>
        Exporter
        <span aria-hidden="true" style={{ fontSize: 10, opacity: 0.9, marginLeft: 2 }}>
          ▾
        </span>
      </button>

      {open ? (
        <div
          className="ishikawa-export-dropdown"
          role="menu"
          aria-label="Options d’export"
          style={{
            position: 'absolute',
            top: 'calc(100% + 6px)',
            left: 0,
            right: 'auto',
            minWidth: '100%',
            width: 'max-content',
            maxWidth: 'min(20rem, calc(100vw - 24px))',
            background: '#ffffff',
            border: '1px solid #cbd5e1',
            borderRadius: 10,
            boxShadow: '0 10px 40px rgba(15, 23, 42, 0.18)',
            padding: '4px 0',
            overflow: 'hidden',
          }}
        >
          <MenuRow
            label="Exporter en PNG"
            onClick={handleExportPng}
            disabled={busy}
            glyph={
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <rect x="3" y="3" width="18" height="18" rx="2" />
                <circle cx="8.5" cy="8.5" r="1.5" />
                <path d="M21 15l-5-5L5 21" />
              </svg>
            }
          />
          <MenuRow
            label="Exporter en PDF"
            onClick={handleExportPdf}
            disabled={busy}
            glyph={
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                <polyline points="14 2 14 8 20 8" />
                <path d="M10 12h4M10 16h4" />
              </svg>
            }
          />
          <MenuRow
            label="Exporter en JSON"
            onClick={handleExportJson}
            disabled={busy}
            glyph={
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <polyline points="16 18 22 12 16 6" />
                <polyline points="8 6 2 12 8 18" />
              </svg>
            }
          />
        </div>
      ) : null}
    </div>
  );
}

function MenuRow({ label, onClick, disabled, glyph }) {
  const [hover, setHover] = useState(false);
  return (
    <button
      type="button"
      role="menuitem"
      disabled={disabled}
      onMouseEnter={() => setHover(true)}
      onMouseLeave={() => setHover(false)}
      onClick={onClick}
      style={{
        ...MENU_ITEM_BASE,
        background: hover && !disabled ? '#f1f5f9' : 'transparent',
        color: disabled ? '#94a3b8' : '#0f172a',
        cursor: disabled ? 'not-allowed' : 'pointer',
      }}
    >
      {glyph ? <MenuGlyph>{glyph}</MenuGlyph> : null}
      <span style={{ minWidth: 0 }}>{label}</span>
    </button>
  );
}
