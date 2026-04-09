import { useEffect, useMemo, useState } from 'react';
import Navbar from './Navbar.jsx';
import Header from './Header.jsx';
import Sidebar from './Sidebar.jsx';
import MainContent from './MainContent.jsx';
import SwaggerDocs from '../SwaggerDocs.jsx';

export default function AppLayout({
  title = 'AI Database Generator',
  subtitle = 'Generate SQL schemas with natural language prompts',
  headerBadge = 'Ready',
  historyItems = [],
  onHistoryItemClick,
  navbarStatus = 'ready',
  children,
}) {
  const [swaggerSpecData, setSwaggerSpecData] = useState(null);
  const [swaggerSchemaTables, setSwaggerSchemaTables] = useState([]);
  const [swaggerDocsOpen, setSwaggerDocsOpen] = useState(false);
  const [generationHistory, setGenerationHistory] = useState(() => {
    if (typeof window === 'undefined') {
      return [];
    }

    try {
      const stored = window.localStorage.getItem('autospec:generation-history');
      const parsed = stored ? JSON.parse(stored) : [];
      return Array.isArray(parsed) ? parsed : [];
    } catch {
      return [];
    }
  });
  const [activeHistoryId, setActiveHistoryId] = useState(null);
  const [restoredGeneration, setRestoredGeneration] = useState(null);

  useEffect(() => {
    try {
      window.localStorage.setItem('autospec:generation-history', JSON.stringify(generationHistory));
    } catch {
      // Ignore storage errors; history still works for the current session.
    }
  }, [generationHistory]);

  const items = useMemo(() => {
    const source = historyItems.length > 0 ? historyItems : generationHistory;
    return [...source].sort((left, right) => new Date(right.timestamp) - new Date(left.timestamp));
  }, [historyItems, generationHistory]);
  const swaggerDocsAvailable = Boolean(swaggerSpecData);

  const cloneGenerationPayload = (payload) => {
    if (!payload || typeof payload !== 'object') {
      return null;
    }

    return {
      ...payload,
      generatedSql: {
        ddl: payload.generatedSql?.ddl || '',
        dml: payload.generatedSql?.dml || '',
        dcl: payload.generatedSql?.dcl || '',
        trigger: payload.generatedSql?.trigger || '',
      },
      schemaJson: payload.schemaJson || payload.schemaOverview || {},
      schemaTables: Array.isArray(payload.schemaTables) ? payload.schemaTables : [],
      credentials: {
        username: payload.credentials?.username || '',
        password: payload.credentials?.password || '',
      },
      downloads: payload.downloads || {},
      files: payload.files || {},
      specData: payload.specData || null,
    };
  };

  const handleSwaggerSpecDataChange = (nextSpecData, nextSchemaTables = []) => {
    setSwaggerSpecData(nextSpecData || null);
    setSwaggerSchemaTables(Array.isArray(nextSchemaTables) ? nextSchemaTables : []);
  };

  const handleGenerationSuccess = (payload) => {
    const snapshot = cloneGenerationPayload(payload);
    if (!snapshot) {
      return;
    }

    const itemId = snapshot.id || `gen_${Date.now()}`;
    const historyEntry = {
      id: itemId,
      name: snapshot.name || 'Generated Database',
      status: snapshot.status || 'success',
      timestamp: snapshot.timestamp || new Date().toISOString(),
      icon_type: snapshot.icon_type || '📊',
      description: snapshot.description || 'Hasil generate database',
      payload: snapshot,
    };

    setGenerationHistory((prev) => {
      const next = [historyEntry, ...prev.filter((item) => item.id !== historyEntry.id)];
      return next.sort((left, right) => new Date(right.timestamp) - new Date(left.timestamp));
    });

    setActiveHistoryId(itemId);
    setRestoredGeneration(snapshot);
    handleSwaggerSpecDataChange(snapshot.specData, snapshot.schemaTables || []);
  };

  const handleHistoryItemClick = (item) => {
    setActiveHistoryId(item.id);
    const payload = cloneGenerationPayload(item.payload);
    setRestoredGeneration(payload);

    if (payload) {
      handleSwaggerSpecDataChange(payload.specData, payload.schemaTables || []);
    }

    onHistoryItemClick?.(item);
  };

  const handleOpenSwaggerDocs = () => {
    if (swaggerSpecData) {
      setSwaggerDocsOpen(true);
    }
  };

  const handleCloseSwaggerDocs = () => {
    setSwaggerDocsOpen(false);
  };

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
            onItemClick={handleHistoryItemClick}
            activeItemId={activeHistoryId}
            onSwaggerDocsClick={handleOpenSwaggerDocs}
            swaggerDocsAvailable={swaggerDocsAvailable}
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
            <MainContent
              dashboardProps={{
                swaggerDocsAvailable,
                onSwaggerSpecDataChange: handleSwaggerSpecDataChange,
                onOpenSwaggerDocs: handleOpenSwaggerDocs,
                onGenerationSuccess: handleGenerationSuccess,
                restoredGeneration,
              }}
            >
              {children}
            </MainContent>
          </div>
        </div>
      </main>

      {swaggerDocsOpen && swaggerSpecData && (
        <SwaggerDocs
          specData={swaggerSpecData}
          schemaTables={swaggerSchemaTables}
          onClose={handleCloseSwaggerDocs}
        />
      )}
    </div>
  );
}
