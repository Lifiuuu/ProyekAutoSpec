/**
 * Navbar Component
 * 
 * Props:
 *  - status?: string - Current workspace/connection status
 *  - onNewSession?: () => void - Callback untuk tombol "New Session"
 *  - onRecentHistory?: () => void - Callback untuk tombol "Recent History"
 */

export default function Navbar({ status, onNewSession, onRecentHistory }) {
  const handleNewSession = () => {
    // Navigate to new session route - ini akan refresh page dengan state baru
    window.location.href = '/new-session';
  };

  return (
    <nav className="sticky top-0 z-40 border-b border-white/10 bg-gradient-to-r from-[#0F1419]/95 via-[#1A1F2E]/95 to-[#0F1419]/95 backdrop-blur-md">
      <div className="mx-auto max-w-7xl px-4 py-4 lg:px-8">
        <div className="flex items-center justify-between">
          {/* Logo & Title */}
          <div className="flex items-center gap-3">
            <img 
              src="/images/autospec-logo.svg" 
              alt="AutoSpec Logo" 
              className="h-14 md:h-16 w-auto"
            />
          </div>

          {/* Actions */}
          <div className="flex items-center gap-3">
            {status && (
              <span className={`inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold ${
                status === 'ready' 
                  ? 'bg-green-500/20 text-green-300 border border-green-500/30' 
                  : 'bg-yellow-500/20 text-yellow-300 border border-yellow-500/30'
              }`}>
                <span className="w-2 h-2 rounded-full bg-current animate-pulse"></span>
                {status}
              </span>
            )}

            <button
              onClick={handleNewSession}
              className="px-4 py-1.5 rounded-lg border border-white/10 bg-white/[0.03] text-sm font-semibold text-gray-200 hover:bg-white/[0.08] hover:border-cyan-400/50 transition-all duration-300"
            >
              + New Session
            </button>

            <button
              onClick={onRecentHistory}
              className="px-4 py-1.5 rounded-lg bg-gradient-to-r from-cyan-400 to-blue-500 text-sm font-semibold text-white hover:shadow-lg hover:shadow-cyan-500/50 transition-all duration-300"
            >
              📋 Recent
            </button>
          </div>
        </div>
      </div>
    </nav>
  );
}
