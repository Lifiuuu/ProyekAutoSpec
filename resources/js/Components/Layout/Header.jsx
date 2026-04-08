/**
 * Header Component
 * 
 * Props:
 *  - title: string - Judul halaman
 *  - subtitle?: string - Subtitle/description halaman
 *  - badge?: string - Status badge di kanan
 */

export default function Header({ title, subtitle, badge }) {
  return (
    <header className="rounded-xl border border-white/10 bg-gradient-to-r from-white/5 via-white/[0.02] to-white/5 backdrop-blur-xl px-6 py-4 mb-6">
      <div className="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div>
          <h2 className="text-2xl font-bold text-white">{title}</h2>
          {subtitle && <p className="text-sm text-gray-400 mt-1">{subtitle}</p>}
        </div>
        
        {badge && (
          <div className="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-cyan-500/20 border border-cyan-500/30 text-cyan-300 text-sm font-semibold whitespace-nowrap">
            <span className="w-2 h-2 rounded-full bg-cyan-400"></span>
            {badge}
          </div>
        )}
      </div>
    </header>
  );
}
