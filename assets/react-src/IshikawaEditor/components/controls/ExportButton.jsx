import { toPng } from 'html-to-image';
import useIshikawaStore from '../../store/useIshikawaStore.js';

export default function ExportButton() {
  const setExporting = useIshikawaStore((s) => s.setExporting);
  const meta = useIshikawaStore((s) => s.meta);

  const handleExport = async () => {
    setExporting(true);
    try {
      const canvas = document.querySelector('.react-flow__viewport');
      if (!canvas) return;

      const dataUrl = await toPng(canvas, { backgroundColor: '#ffffff', pixelRatio: 2 });

      const link = document.createElement('a');
      const safeTitle = (meta.title || 'ishikawa').replace(/\s+/g, '-').toLowerCase();
      link.download = `ishikawa-${safeTitle}.png`;
      link.href = dataUrl;
      link.click();
    } catch (err) {
      console.error('Erreur export:', err);
    } finally {
      setExporting(false);
    }
  };

  return (
    <button
      type="button"
      onClick={handleExport}
      style={{
        background: '#38A169',
        color: 'white',
        border: 'none',
        borderRadius: 4,
        padding: '4px 12px',
        fontSize: 12,
        cursor: 'pointer',
      }}
    >
      Export PNG
    </button>
  );
}
