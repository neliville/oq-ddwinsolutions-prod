import useIshikawaStore from '../../store/useIshikawaStore.js';

export default function MetaPanel() {
  const meta = useIshikawaStore((s) => s.meta);
  const updateMeta = useIshikawaStore((s) => s.updateMeta);

  return (
    <div className="border-b border-slate-200 bg-white px-5 py-3 flex flex-wrap gap-4 items-center">
      <label className="text-sm text-slate-600 flex flex-col gap-1">
        Problème
        <textarea
          className="border border-slate-300 rounded px-2 py-1 min-w-[240px] text-sm"
          rows={2}
          value={meta.problem}
          onChange={(e) => updateMeta({ problem: e.target.value })}
        />
      </label>
      <label className="text-sm text-slate-600 flex flex-col gap-1">
        Auteur
        <input
          type="text"
          className="border border-slate-300 rounded px-2 py-1 text-sm"
          value={meta.author}
          onChange={(e) => updateMeta({ author: e.target.value })}
        />
      </label>
    </div>
  );
}
