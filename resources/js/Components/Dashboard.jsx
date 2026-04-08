import React, { useState } from 'react';

/**
 * Modern Dashboard Component
 * Professional UI with glassmorphism, smooth animations, and aesthetic design
 */
const Dashboard = () => {
    const [state, setState] = useState({
        isLoading: false,
        nlpPrompt: '',
        dialect: 'postgresql',
        showReviewPanel: false,
        showSchemaOverview: false,
        generatedSql: {
            ddl: '',
            dml: '',
            dcl: '',
            trigger: '',
        },
        activeSqlTab: 'ddl',
        schemaOverview: {
            tables: [],
            credentials: {
                username: '',
                password: '',
            },
            downloads: {
                'database.sql': false,
                'openapi.json': false,
                'postman_collection.json': false,
            },
            files: {
                'database.sql': '',
                'openapi.json': '',
                'postman_collection.json': '',
            },
        },
        showRollbackToast: false,
    });

    const mapSchemaJsonToTables = (payload) => {
        if (!payload || typeof payload !== 'object') return [];
        const list = Array.isArray(payload.tables) ? payload.tables : [];
        return list.map((table, idx) => {
            const columns = Array.isArray(table.columns) ? table.columns : [];
            return {
                name: table.name || `table_${idx + 1}`,
                columns: columns.map((column, cIdx) => ({
                    name: column.name || `column_${cIdx + 1}`,
                    type: normalizeDataType(column.type),
                })),
            };
        });
    };

    const normalizeDataType = (type) => {
        const raw = String(type || '').toLowerCase();
        if (raw.includes('id')) return 'ID';
        if (raw.includes('int') || raw.includes('number')) return 'Integer';
        return 'String';
    };

    const fakeAiGenerate = async (prompt, dialect) => {
        await new Promise((resolve) => setTimeout(resolve, 1700));

        if (prompt.toLowerCase().includes('simulate rollback')) {
            throw new Error('Invalid SQL structure. Rollback triggered.');
        }

        const safePrompt = prompt.replace(/'/g, "''");
        const schemaJson = {
            tables: [
                {
                    name: 'books',
                    columns: [
                        { name: 'id', type: 'id' },
                        { name: 'title', type: 'string' },
                        { name: 'published_year', type: 'integer' },
                    ],
                },
                {
                    name: 'members',
                    columns: [
                        { name: 'id', type: 'id' },
                        { name: 'full_name', type: 'string' },
                        { name: 'member_number', type: 'integer' },
                    ],
                },
            ],
        };

        const credentials = {
            username: `autospec_${Math.random().toString(36).slice(2, 8)}`,
            password: `PW-${Math.random().toString(36).slice(2, 10)}`,
        };

        const files = {
            'database.sql': '-- database.sql\nCREATE TABLE books (...);\nCREATE TABLE members (...);\n',
            'openapi.json': JSON.stringify({ openapi: '3.0.0', info: { title: 'Library API', version: '1.0.0' } }, null, 2),
            'postman_collection.json': JSON.stringify({ info: { name: 'Library Collection' }, item: [] }, null, 2),
        };

        return {
            ddl: `-- DDL for ${safePrompt}\nCREATE TABLE books (\n  id SERIAL PRIMARY KEY,\n  title VARCHAR(255) NOT NULL\n);`,
            dml: `-- DML\nINSERT INTO books (title) VALUES ('Sample');`,
            dcl: `-- DCL\nGRANT SELECT ON TABLE books TO app_user;`,
            trigger: `-- Trigger\nCREATE OR REPLACE FUNCTION set_updated_at() RETURNS TRIGGER AS $$ BEGIN NEW.updated_at = NOW(); RETURN NEW; END; $$ LANGUAGE plpgsql;`,
            schemaJson,
            credentials,
            downloads: {
                'database.sql': true,
                'openapi.json': true,
                'postman_collection.json': true,
            },
            files,
        };
    };

    const handleGenerate = async () => {
        if (state.isLoading || !state.nlpPrompt.trim()) {
            alert('Silakan masukkan prompt terlebih dahulu.');
            return;
        }

        setState((prev) => ({ ...prev, isLoading: true }));

        try {
            const result = await fakeAiGenerate(state.nlpPrompt, state.dialect);
            setState((prev) => ({
                ...prev,
                generatedSql: result,
                showReviewPanel: true,
                schemaOverview: {
                    tables: mapSchemaJsonToTables(result.schemaJson),
                    credentials: result.credentials || { username: '', password: '' },
                    downloads: result.downloads || {},
                    files: result.files || {},
                },
                showSchemaOverview: true,
                isLoading: false,
            }));
        } catch (error) {
            setState((prev) => ({
                ...prev,
                isLoading: false,
                showRollbackToast: true,
            }));
            setTimeout(() => {
                setState((prev) => ({ ...prev, showRollbackToast: false }));
            }, 3600);
        }
    };

    const handleDownload = (filename) => {
        const content = state.schemaOverview.files[filename] || '';
        const blob = new Blob([content], { type: 'text/plain;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        link.click();
        URL.revokeObjectURL(url);
    };

    return (
        <div className="min-h-screen bg-gradient-to-br from-[#0F1419] via-[#1A1F2E] to-[#0F1419]">
            {/* Background decoration */}
            <div className="fixed inset-0 overflow-hidden pointer-events-none">
                <div className="absolute w-96 h-96 bg-blue-500/10 rounded-full blur-3xl -top-32 -left-32"></div>
                <div className="absolute w-96 h-96 bg-cyan-500/10 rounded-full blur-3xl -bottom-32 -right-32"></div>
            </div>

            {/* Main content */}
            <div className="relative">
                <div className="space-y-6 p-8">
                        {/* Top Section */}
                        <div className="rounded-2xl border border-white/10 bg-gradient-to-br from-white/5 to-white/[0.02] backdrop-blur-xl p-8 shadow-xl">
                            <div className="mb-6">
                                <h2 className="text-2xl font-bold text-white mb-2">Generate Database Schema</h2>
                                <p className="text-gray-400 text-sm">Describe your database requirements in natural language</p>
                            </div>

                            {/* Prompt Textarea */}
                            <div className="mb-6">
                                <label className="block text-sm font-semibold text-gray-200 mb-3">Your Prompt</label>
                                <textarea
                                    value={state.nlpPrompt}
                                    onChange={(e) => setState((prev) => ({ ...prev, nlpPrompt: e.target.value }))}
                                    className="w-full h-32 rounded-xl border border-white/10 bg-white/[0.03] backdrop-blur px-5 py-4 text-gray-100 placeholder:text-gray-500 focus:outline-none focus:border-cyan-400/50 focus:bg-white/[0.08] transition-all duration-300 resize-none font-mono text-sm"
                                    placeholder="e.g., Create a database for an online bookstore with books, members, and transactions..."
                                />
                            </div>

                            {/* Dialect & Button */}
                            <div className="flex flex-col sm:flex-row gap-4 items-end">
                                <div className="flex-1">
                                    <label className="block text-sm font-semibold text-gray-200 mb-3">Database Type</label>
                                    <select
                                        value={state.dialect}
                                        onChange={(e) => setState((prev) => ({ ...prev, dialect: e.target.value }))}
                                        className="w-full rounded-xl border border-white/10 bg-white/[0.03] backdrop-blur px-5 py-3 text-gray-100 focus:outline-none focus:border-cyan-400/50 focus:bg-white/[0.08] transition-all duration-300"
                                    >
                                        <option value="postgresql">PostgreSQL</option>
                                        <option value="mysql" disabled>MySQL (Coming Soon)</option>
                                    </select>
                                </div>
                                <button
                                    onClick={handleGenerate}
                                    disabled={state.isLoading}
                                    className={`px-8 py-3 rounded-xl font-semibold transition-all duration-300 transform hover:scale-105 ${
                                        state.isLoading
                                            ? 'bg-gray-600 text-gray-400 cursor-not-allowed'
                                            : 'bg-gradient-to-r from-cyan-400 to-blue-500 text-white hover:shadow-lg hover:shadow-cyan-500/50 active:scale-95'
                                    }`}
                                >
                                    {state.isLoading ? (
                                        <span className="flex items-center gap-2">
                                            <svg className="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Generating...
                                        </span>
                                    ) : (
                                        'Generate Schema'
                                    )}
                                </button>
                            </div>
                        </div>

                        {/* Loading State */}
                        {state.isLoading && (
                            <div className="rounded-2xl border border-white/10 bg-gradient-to-br from-white/5 to-white/[0.02] backdrop-blur-xl p-8 shadow-xl">
                                <div className="flex items-center gap-4 mb-6">
                                    <div className="h-6 w-6 rounded-full border-2 border-cyan-400/30 border-t-cyan-400 animate-spin"></div>
                                    <p className="text-gray-200 font-medium">AI is processing your request...</p>
                                </div>
                                <div className="space-y-3">
                                    <div className="h-2 bg-gradient-to-r from-cyan-500/30 to-transparent rounded-full overflow-hidden">
                                        <div className="h-full bg-gradient-to-r from-cyan-400 to-blue-500 animate-pulse"></div>
                                    </div>
                                    <div className="h-2 bg-gradient-to-r from-cyan-500/20 to-transparent rounded-full overflow-hidden w-5/6">
                                        <div className="h-full bg-gradient-to-r from-cyan-400 to-blue-500 animate-pulse" style={{ animationDelay: '0.2s' }}></div>
                                    </div>
                                    <div className="h-2 bg-gradient-to-r from-cyan-500/10 to-transparent rounded-full overflow-hidden w-2/3">
                                        <div className="h-full bg-gradient-to-r from-cyan-400 to-blue-500 animate-pulse" style={{ animationDelay: '0.4s' }}></div>
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* Rollback Toast */}
                        {state.showRollbackToast && (
                            <div className="fixed top-6 right-6 z-50 max-w-sm rounded-xl border border-amber-400/30 bg-amber-500/20 backdrop-blur-xl px-6 py-4 text-sm text-amber-100 shadow-xl animate-in fade-in slide-in-from-top-4">
                                ⚠️ Database rolled back safely
                            </div>
                        )}

                        {/* SQL Review Panel */}
                        {state.showReviewPanel && (
                            <div className="rounded-2xl border border-white/10 bg-gradient-to-br from-white/5 to-white/[0.02] backdrop-blur-xl p-8 shadow-xl">
                                <h3 className="text-xl font-bold text-white mb-2">SQL Review Panel</h3>
                                <p className="text-gray-400 text-sm mb-6">Review generated SQL before execution</p>

                                <div className="flex gap-2 mb-6 overflow-x-auto pb-2">
                                    {['ddl', 'dml', 'dcl', 'trigger'].map((tab) => (
                                        <button
                                            key={tab}
                                            onClick={() => setState((prev) => ({ ...prev, activeSqlTab: tab }))}
                                            className={`px-4 py-2 rounded-lg font-semibold text-sm transition-all duration-300 whitespace-nowrap ${
                                                state.activeSqlTab === tab
                                                    ? 'bg-gradient-to-r from-cyan-400 to-blue-500 text-white shadow-lg shadow-cyan-500/50'
                                                    : 'border border-white/10 bg-white/[0.03] text-gray-300 hover:border-white/20'
                                            }`}
                                        >
                                            {tab.toUpperCase()}
                                        </button>
                                    ))}
                                </div>

                                <div className="rounded-xl border border-white/10 bg-black/30 p-4 overflow-hidden">
                                    <pre className="text-xs text-gray-300 font-mono overflow-x-auto max-h-64 overflow-y-auto">
                                        <code>{state.generatedSql[state.activeSqlTab] || '-- No output'}</code>
                                    </pre>
                                </div>
                            </div>
                        )}

                        {/* Schema Overview */}
                        {state.showSchemaOverview && (
                            <div className="rounded-2xl border border-white/10 bg-gradient-to-br from-white/5 to-white/[0.02] backdrop-blur-xl p-8 shadow-xl">
                                <h3 className="text-xl font-bold text-white mb-2">Database Schema</h3>
                                <p className="text-gray-400 text-sm mb-6">Table structure and fields</p>

                                <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 mb-8">
                                    {state.schemaOverview.tables.map((table, idx) => (
                                        <div key={idx} className="rounded-xl border border-white/10 bg-white/[0.03] p-4 hover:bg-white/[0.08] transition-all duration-300">
                                            <h4 className="text-sm font-bold text-cyan-300 mb-4 uppercase tracking-wide">{table.name}</h4>
                                            <div className="space-y-2">
                                                {table.columns.map((col, cIdx) => (
                                                    <div key={cIdx} className="flex items-center justify-between gap-2 text-xs">
                                                        <span className="text-gray-300">{col.name}</span>
                                                        <span className="px-2.5 py-1 rounded-lg bg-cyan-500/20 text-cyan-300 font-semibold border border-cyan-500/30">
                                                            {col.type}
                                                        </span>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    ))}
                                </div>

                                {/* Credentials */}
                                <div className="rounded-xl border border-white/10 bg-white/[0.03] p-6 mb-8">
                                    <h4 className="text-sm font-bold text-cyan-300 mb-4 uppercase tracking-wide">Database Credentials</h4>
                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <p className="text-xs text-gray-500 mb-2">Username</p>
                                            <p className="font-mono text-sm text-gray-200 break-all">{state.schemaOverview.credentials.username || '-'}</p>
                                        </div>
                                        <div>
                                            <p className="text-xs text-gray-500 mb-2">Password</p>
                                            <p className="font-mono text-sm text-gray-200 break-all">{state.schemaOverview.credentials.password || '-'}</p>
                                        </div>
                                    </div>
                                </div>

                                {/* Downloads */}
                                <div>
                                    <h4 className="text-sm font-bold text-cyan-300 mb-4 uppercase tracking-wide">Download Artifacts</h4>
                                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                        {Object.entries(state.schemaOverview.downloads).map(([filename, isAvailable]) => (
                                            <button
                                                key={filename}
                                                onClick={() => handleDownload(filename)}
                                                disabled={!isAvailable}
                                                className={`px-4 py-3 rounded-xl font-semibold text-sm transition-all duration-300 ${
                                                    isAvailable
                                                        ? 'border border-white/10 bg-white/[0.03] text-gray-200 hover:bg-white/[0.08] hover:border-cyan-400/50'
                                                        : 'opacity-50 cursor-not-allowed border border-white/5 bg-white/[0.02] text-gray-500'
                                                }`}
                                            >
                                                📥 {filename.split('.')[0]}
                                            </button>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        )}
                </div>
            </div>
        </div>
    );
};

export default Dashboard;
