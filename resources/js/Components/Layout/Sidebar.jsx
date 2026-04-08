/**
 * Sidebar Component - History Navigation
 * 
 * Props:
 *  - historyItems: Array<{id, name, status, timestamp, icon_type, description}> - List of generation history
 *  - onItemClick?: (item) => void - Callback saat item diklik
 *  - activeItemId?: string - ID item yang sedang active
 */

export default function Sidebar({ historyItems = [], onItemClick, activeItemId }) {
  const getStatusColor = (status) => {
    switch (status) {
      case 'success': return 'bg-green-900/30 text-green-300';
      case 'pending': return 'bg-yellow-900/30 text-yellow-300';
      case 'error': return 'bg-red-900/30 text-red-300';
      default: return 'bg-gray-900/30 text-gray-300';
    }
  };

  const getStatusLabel = (status) => {
    return status ? status.charAt(0).toUpperCase() + status.slice(1) : 'Unknown';
  };

  const formatTimestamp = (timestamp) => {
    if (!timestamp) return '';
    try {
      const date = new Date(timestamp);
      return date.toLocaleDateString('id-ID', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
    } catch {
      return timestamp;
    }
  };

  return (
    <aside className="sidebar bg-[#141414] border border-[#234C6A]/50 rounded-lg p-5 h-fit sticky top-4">
      <div className="sidebar-header mb-6">
        <h3 className="text-sm font-bold uppercase tracking-wide text-[#456882]">Generation History</h3>
        <p className="text-xs text-gray-500 mt-1">{historyItems.length} items</p>
      </div>

      <ul className="history-list space-y-2">
        {historyItems.length === 0 ? (
          <li className="text-xs text-gray-400 italic">Tidak ada history</li>
        ) : (
          historyItems.map((item) => (
            <li key={item.id}>
              <button
                onClick={() => onItemClick?.(item)}
                className={`history-item w-full text-left px-3 py-2 rounded-lg border border-[#234C6A]/40 transition-all duration-200 hover:bg-[#234C6A]/20 hover:border-[#234C6A]/60 ${
                  activeItemId === item.id ? 'bg-[#234C6A]/30 border-[#234C6A]/80' : 'bg-black/30'
                }`}
              >
                <div className="flex items-start justify-between gap-2">
                  <div className="item-content flex-1 min-w-0">
                    <p className="text-sm font-medium text-white truncate">{item.name}</p>
                    {item.description && (
                      <p className="text-xs text-gray-400 truncate mt-0.5">{item.description}</p>
                    )}
                    <div className="flex items-center gap-2 mt-1">
                      <span className={`text-[10px] font-semibold px-2 py-0.5 rounded ${getStatusColor(item.status)}`}>
                        {getStatusLabel(item.status)}
                      </span>
                      {item.timestamp && (
                        <span className="text-[10px] text-gray-500">{formatTimestamp(item.timestamp)}</span>
                      )}
                    </div>
                  </div>
                  {item.icon_type && <span className="text-lg">{item.icon_type}</span>}
                </div>
              </button>
            </li>
          ))
        )}
      </ul>
    </aside>
  );
}
