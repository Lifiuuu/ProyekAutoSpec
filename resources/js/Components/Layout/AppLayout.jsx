import Navbar from './Navbar.jsx';
import Header from './Header.jsx';
import Sidebar from './Sidebar.jsx';
import MainContent from './MainContent.jsx';

export default function AppLayout({
  title = 'AI Database Generator',
  subtitle = 'Generate SQL schemas with natural language prompts',
  headerBadge = 'Ready',
  historyItems = [],
  onHistoryItemClick,
  navbarStatus = 'ready',
  children,
}) {
  const mockHistoryItems = [
    {
      id: '1',
      name: 'Schema Toko Online',
      status: 'success',
      timestamp: new Date(Date.now() - 3600000).toISOString(),
      icon_type: '📊',
      description: 'Database toko e-commerce',
    },
    {
      id: '2',
      name: 'Sistem Absensi Kampus',
      status: 'success',
      timestamp: new Date(Date.now() - 7200000).toISOString(),
      icon_type: '📋',
      description: 'Sistem manajemen kehadiran',
    },
    {
      id: '3',
      name: 'Inventory Gudang',
      status: 'pending',
      timestamp: new Date(Date.now() - 10800000).toISOString(),
      icon_type: '📦',
      description: 'Sistem manajemen stok',
    },
  ];

  const items = historyItems.length > 0 ? historyItems : mockHistoryItems;

  return (
    <div className="min-h-screen bg-gradient-to-br from-[#0F1419] via-[#1A1F2E] to-[#0F1419]">
      {/* Navbar */}
      <Navbar status={navbarStatus} />

      {/* Main Content Area */}
      <main className="flex flex-1">
        {/* Sidebar - Desktop Only */}
        <div className="hidden lg:block w-80 border-r border-white/10 bg-white/[0.02] overflow-y-auto">
          <Sidebar 
            historyItems={items}
            onItemClick={onHistoryItemClick}
          />
        </div>

        {/* Content Area */}
        <div className="flex-1 overflow-y-auto">
          <div className="mx-auto max-w-7xl px-4 py-6 lg:px-8">
            {/* Header */}
            <Header 
              title={title}
              subtitle={subtitle}
              badge={headerBadge}
            />

            {/* Main Content */}
            <MainContent>
              {children}
            </MainContent>
          </div>
        </div>
      </main>
    </div>
  );
}
