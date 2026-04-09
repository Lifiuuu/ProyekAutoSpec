import React, { useEffect, useState } from 'react';
import axios from 'axios';

/**
 * Modern Dashboard Component
 * Professional UI with glassmorphism, smooth animations, and aesthetic design
 */
const Dashboard = ({
    swaggerDocsAvailable = false,
    onSwaggerSpecDataChange,
    onOpenSwaggerDocs,
    onGenerationSuccess,
    restoredGeneration,
}) => {

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

    const applyGenerationSnapshot = (snapshot) => {
        if (!snapshot || typeof snapshot !== 'object') {
            return;
        }

        const generatedSql = {
            ddl: snapshot.generatedSql?.ddl || '',
            dml: snapshot.generatedSql?.dml || '',
            dcl: snapshot.generatedSql?.dcl || '',
            trigger: snapshot.generatedSql?.trigger || '',
        };

        const schemaTables = Array.isArray(snapshot.schemaTables) ? snapshot.schemaTables : mapSchemaJsonToTables(snapshot.schemaJson || snapshot.schemaOverview || {});
        const schemaJson = snapshot.schemaJson || snapshot.schemaOverview || {};
        const credentials = snapshot.credentials || { username: '', password: '' };
        const defaultDownloads = {
            'database.sql': false,
            'openapi.json': false,
            'postman_collection.json': false,
        };
        const downloads = { ...defaultDownloads, ...(snapshot.downloads || {}) };
        const files = {
            'database.sql': '',
            'openapi.json': '',
            'postman_collection.json': '',
            ...(snapshot.files || {}),
        };

        setState((prev) => ({
            ...prev,
            isLoading: false,
            nlpPrompt: snapshot.prompt || prev.nlpPrompt,
            dialect: snapshot.dialect || prev.dialect,
            showReviewPanel: true,
            generatedSql,
            activeSqlTab: 'ddl',
            showSchemaOverview: true,
            schemaOverview: {
                tables: schemaTables,
                credentials,
                downloads,
                files,
            },
            showRollbackToast: false,
        }));

        onSwaggerSpecDataChange?.(snapshot.specData || extractSwaggerSpec({ ...snapshot, files }, schemaJson), schemaTables);
    };

    useEffect(() => {
        if (restoredGeneration) {
            applyGenerationSnapshot(restoredGeneration);
        }
    }, [restoredGeneration]);

    const buildSwaggerSchemaProperty = (type) => {
        const raw = String(type || '').toLowerCase();

        if (raw === 'id' || raw.includes('int') || raw.includes('number')) {
            return { type: 'integer', format: 'int64' };
        }

        if (raw.includes('bool')) {
            return { type: 'boolean' };
        }

        if (raw.includes('date') && raw.includes('time')) {
            return { type: 'string', format: 'date-time' };
        }

        if (raw.includes('date')) {
            return { type: 'string', format: 'date' };
        }

        if (raw.includes('decimal') || raw.includes('float') || raw.includes('double')) {
            return { type: 'number', format: 'double' };
        }

        return { type: 'string' };
    };

    const sanitizeSchemaName = (value) => {
        const base = String(value || 'Table').trim();
        const cleaned = base.replace(/[^A-Za-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
        return cleaned ? cleaned.charAt(0).toUpperCase() + cleaned.slice(1) : 'Table';
    };

    const sanitizePathSegment = (value) => {
        const cleaned = String(value || 'resource').trim().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
        return cleaned || 'resource';
    };

    const buildFallbackOpenApiSpec = (tables = []) => {
        const components = { schemas: {} };
        const paths = {};
        const serverUrl = window.location.origin;

        tables.forEach((table) => {
            const rawName = table?.name || 'table';
            const schemaName = sanitizeSchemaName(rawName);
            const resourceName = sanitizePathSegment(rawName);
            const columns = Array.isArray(table?.columns) ? table.columns : [];

            const properties = {};
            const required = [];

            columns.forEach((column) => {
                const columnName = String(column?.name || '').trim().replace(/[^A-Za-z0-9_]/g, '_');
                if (!columnName) {
                    return;
                }

                properties[columnName] = buildSwaggerSchemaProperty(column?.type);
                if (column?.type === 'id') {
                    required.push(columnName);
                }
            });

            components.schemas[schemaName] = {
                type: 'object',
                properties,
                ...(required.length > 0 ? { required } : {}),
            };

            const listPath = `/api/${resourceName}`;
            const detailPath = `/api/${resourceName}/{id}`;

            paths[listPath] = {
                get: {
                    summary: `List ${rawName}`,
                    responses: {
                        200: {
                            description: 'OK',
                            content: {
                                'application/json': {
                                    schema: {
                                        type: 'array',
                                        items: { $ref: `#/components/schemas/${schemaName}` },
                                    },
                                },
                            },
                        },
                    },
                },
                post: {
                    summary: `Create ${rawName}`,
                    requestBody: {
                        required: true,
                        content: {
                            'application/json': {
                                schema: { $ref: `#/components/schemas/${schemaName}` },
                            },
                        },
                    },
                    responses: {
                        201: {
                            description: 'Created',
                            content: {
                                'application/json': {
                                    schema: { $ref: `#/components/schemas/${schemaName}` },
                                },
                            },
                        },
                    },
                },
            };

            paths[detailPath] = {
                get: {
                    summary: `Get ${rawName} by ID`,
                    parameters: [
                        {
                            name: 'id',
                            in: 'path',
                            required: true,
                            schema: { type: 'integer' },
                        },
                    ],
                    responses: {
                        200: {
                            description: 'OK',
                            content: {
                                'application/json': {
                                    schema: { $ref: `#/components/schemas/${schemaName}` },
                                },
                            },
                        },
                    },
                },
                put: {
                    summary: `Update ${rawName}`,
                    parameters: [
                        {
                            name: 'id',
                            in: 'path',
                            required: true,
                            schema: { type: 'integer' },
                        },
                    ],
                    requestBody: {
                        required: true,
                        content: {
                            'application/json': {
                                schema: { $ref: `#/components/schemas/${schemaName}` },
                            },
                        },
                    },
                    responses: {
                        200: {
                            description: 'Updated',
                            content: {
                                'application/json': {
                                    schema: { $ref: `#/components/schemas/${schemaName}` },
                                },
                            },
                        },
                    },
                },
                delete: {
                    summary: `Delete ${rawName}`,
                    parameters: [
                        {
                            name: 'id',
                            in: 'path',
                            required: true,
                            schema: { type: 'integer' },
                        },
                    ],
                    responses: {
                        204: {
                            description: 'Deleted',
                        },
                    },
                },
            };
        });

        return {
            openapi: '3.0.3',
            info: {
                title: 'AutoSpec Generated API',
                version: '1.0.0',
                description: 'Fallback documentation generated from the schema overview.',
            },
            servers: [{ url: serverUrl }],
            paths,
            components,
        };
    };

    const extractSwaggerSpec = (data, schemaJson) => {
        const rawOpenApi =
            data?.specData ||
            data?.openapi ||
            data?.openapiSpec ||
            data?.openapiJson ||
            data?.files?.['openapi.json'] ||
            data?.schemaOverview?.files?.['openapi.json'] ||
            data?.schema_overview?.files?.['openapi.json'] ||
            null;

        if (rawOpenApi && typeof rawOpenApi === 'object' && (rawOpenApi.openapi || rawOpenApi.swagger)) {
            return rawOpenApi;
        }

        if (typeof rawOpenApi === 'string' && rawOpenApi.trim()) {
            try {
                const parsed = JSON.parse(rawOpenApi);
                if (parsed && typeof parsed === 'object' && (parsed.openapi || parsed.swagger)) {
                    return parsed;
                }
            } catch {
                // Fallback to schema-driven spec below.
            }
        }

        const tables = Array.isArray(schemaJson?.tables) ? schemaJson.tables : [];
        return buildFallbackOpenApiSpec(tables);
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
            const payload = { prompt: state.nlpPrompt, dialect: state.dialect };
            const response = await axios.post('/api/generate', payload);
            const data = response?.data || {};

            let generatedSql = { ddl: '', dml: '', dcl: '', trigger: '' };

            // If backend already returns categorized SQL, use it
            if (data.generatedSql && typeof data.generatedSql === 'object') {
                generatedSql = {
                    ddl: data.generatedSql.ddl || '',
                    dml: data.generatedSql.dml || '',
                    dcl: data.generatedSql.dcl || '',
                    trigger: data.generatedSql.trigger || '',
                };
            } else if (typeof data.sql === 'string' && data.sql.trim()) {
                // Best-effort parsing of a single SQL string into categories
                const sqlText = data.sql;
                const funcRegex = /CREATE\s+(?:OR\s+REPLACE\s+)?FUNCTION[\s\S]*?\$\$[\s\S]*?\$\$\s*;?/ig;
                const procRegex = /CREATE\s+(?:OR\s+REPLACE\s+)?PROCEDURE[\s\S]*?\$\$[\s\S]*?\$\$\s*;?/ig;
                const triggerRegex = /CREATE\s+(?:OR\s+REPLACE\s+)?TRIGGER[\s\S]*?;/ig;
                const insertRegex = /INSERT\s+INTO[\s\S]*?;/ig;
                const createTableRegex = /CREATE\s+TABLE[\s\S]*?;/ig;

                const funcs = sqlText.match(funcRegex) || [];
                const procs = sqlText.match(procRegex) || [];
                const triggers = sqlText.match(triggerRegex) || [];

                const triggerPart = [...funcs, ...procs, ...triggers].join('\n\n').trim();

                // Extract DCL statements (GRANT/REVOKE) before removing other parts
                const dclRegex = /\b(?:GRANT|REVOKE)\b[\s\S]*?;/ig;
                const dclMatches = sqlText.match(dclRegex) || [];
                const dcl = dclMatches.join('\n\n').trim();

                // Remove function/proc/trigger and DCLs to find remaining DDL/DML
                let remaining = sqlText.replace(funcRegex, '').replace(procRegex, '').replace(triggerRegex, '').replace(dclRegex, '');

                const inserts = remaining.match(insertRegex) || [];
                const dml = inserts.join('\n\n').trim();

                const tables = remaining.match(createTableRegex) || [];
                const ddl = tables.join('\n\n').trim();

                generatedSql = {
                    ddl: ddl || (!dml && !triggerPart && !dcl ? sqlText : ddl),
                    dml: dml || '',
                    dcl: dcl || data.sql_dcl || data.dcl || '',
                    trigger: triggerPart || '',
                };
            } else {
                // Fallback to older keys
                generatedSql = {
                    ddl: data.sql_ddl || data.ddl || '',
                    dml: data.sql_dml || data.dml || '',
                    dcl: data.sql_dcl || data.dcl || '',
                    trigger: data.sql_trigger || data.trigger || '',
                };
            }

            const schemaJson = data.schemaOverview || data.schema_overview || data.schemaJson || data.schema || {};
            const credentials = data.credentials || data.schemaOverview?.credentials || data.schema_overview?.credentials || { username: '', password: '' };
            const defaultDownloads = {
                'database.sql': false,
                'openapi.json': false,
                'postman_collection.json': false,
            };
            const downloads = { ...defaultDownloads, ...(data.downloads || {}), ...(data.schemaOverview?.downloads || {}), ...(data.schema_overview?.downloads || {}) };
            const files = {
                'database.sql': '',
                'openapi.json': '',
                'postman_collection.json': '',
                ...(data.files || {}),
                ...(data.schemaOverview?.files || {}),
                ...(data.schema_overview?.files || {}),
            };
            const schemaTables = mapSchemaJsonToTables(schemaJson);
            const specData = extractSwaggerSpec(data, schemaJson);
            const timestamp = data.timestamp || new Date().toISOString();
            const historySnapshot = {
                id: data.runId || `gen_${Date.now()}`,
                name: data.name || data.title || state.nlpPrompt.trim().slice(0, 42) || 'Generated Database',
                description: data.description || data.summary || state.nlpPrompt.trim().slice(0, 64) || 'Hasil generate database',
                status: data.status || 'success',
                icon_type: data.icon_type || '📊',
                timestamp,
                prompt: state.nlpPrompt,
                dialect: state.dialect,
                generatedSql,
                schemaJson,
                schemaTables,
                credentials,
                downloads,
                files,
                specData,
            };

            setState((prev) => ({
                ...prev,
                generatedSql,
                showReviewPanel: true,
                schemaOverview: {
                    tables: schemaTables,
                    credentials,
                    downloads,
                    files,
                },
                showSchemaOverview: true,
            }));

            onSwaggerSpecDataChange?.(specData, schemaTables);
            onGenerationSuccess?.(historySnapshot);
        } catch (error) {
            console.error('Generate request failed', error);
            const message = error?.response?.data?.message || error.message || 'Unknown error';
            setState((prev) => ({ ...prev, showRollbackToast: true }));
            // Inform the user
            alert(`Generate gagal: ${message}`);
            setTimeout(() => {
                setState((prev) => ({ ...prev, showRollbackToast: false }));
            }, 3600);
        } finally {
            setState((prev) => ({ ...prev, isLoading: false }));
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

                            <div className="mt-4 flex justify-end">
                                <button
                                    type="button"
                                    onClick={onOpenSwaggerDocs}
                                    disabled={!swaggerDocsAvailable}
                                    className="rounded-xl border border-[#234C6A]/60 bg-[#1E1E1E] px-4 py-2 text-sm font-semibold text-[#F7F8F0] transition-all duration-300 hover:border-[#456882] hover:bg-[#234C6A]/20 disabled:cursor-not-allowed disabled:opacity-40"
                                >
                                    Lihat Dokumentasi API
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
                                <div className="mb-6 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <h3 className="text-xl font-bold text-white mb-2">SQL Review Panel</h3>
                                        <p className="text-gray-400 text-sm">Review generated SQL before execution</p>
                                    </div>
                                    <button
                                        type="button"
                                        onClick={onOpenSwaggerDocs}
                                        disabled={!swaggerDocsAvailable}
                                        className="rounded-xl border border-[#234C6A]/60 bg-[#1E1E1E] px-4 py-2 text-sm font-semibold text-[#F7F8F0] transition-all duration-300 hover:border-[#456882] hover:bg-[#234C6A]/20 disabled:cursor-not-allowed disabled:opacity-40"
                                    >
                                        Lihat Dokumentasi API
                                    </button>
                                </div>

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
                                <div className="mb-6 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <h3 className="text-xl font-bold text-white mb-2">Database Schema</h3>
                                        <p className="text-gray-400 text-sm">Table structure and fields</p>
                                    </div>
                                    <button
                                        type="button"
                                        onClick={onOpenSwaggerDocs}
                                        disabled={!swaggerDocsAvailable}
                                        className="rounded-xl border border-[#234C6A]/60 bg-[#1E1E1E] px-4 py-2 text-sm font-semibold text-[#F7F8F0] transition-all duration-300 hover:border-[#456882] hover:bg-[#234C6A]/20 disabled:cursor-not-allowed disabled:opacity-40"
                                    >
                                        Lihat Dokumentasi API
                                    </button>
                                </div>

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
