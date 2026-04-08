<section class="min-h-screen bg-background text-text">
    <div class="mx-auto max-w-7xl px-4 py-4 lg:px-8 lg:py-8">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-[300px,1fr]">
            <aside class="rounded-xl border border-primary/40 bg-secondary/20 p-5">
                <div class="mb-6 rounded-lg border border-accent/40 bg-background/70 p-4">
                    <div class="mb-2 inline-flex h-10 w-10 items-center justify-center rounded-md bg-primary text-text font-bold">AS</div>
                    <h1 class="text-2xl font-semibold tracking-wide text-text">AUTOSPEC</h1>
                    <p class="mt-1 text-sm text-accent">Database Generator</p>
                </div>

                <div>
                    <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-accent">Histori Generasi</h2>
                    <ul class="space-y-2 text-sm">
                        <li class="rounded-md border border-primary/30 bg-background/60 px-3 py-2">Schema toko online</li>
                        <li class="rounded-md border border-primary/30 bg-background/60 px-3 py-2">Sistem absensi kampus</li>
                        <li class="rounded-md border border-primary/30 bg-background/60 px-3 py-2">Inventory gudang</li>
                    </ul>
                </div>
            </aside>

            <main class="rounded-xl border border-primary/40 bg-secondary/10 p-5 lg:p-6">
                <div class="space-y-5">
                    <div>
                        <label for="nlpPrompt" class="mb-2 block text-sm font-medium text-accent">NLP Prompt Box</label>
                        <textarea
                            id="nlpPrompt"
                            class="h-64 w-full resize-y rounded-lg border border-[#234C6A] bg-background/70 px-4 py-3 text-text placeholder:text-accent/80 focus:outline-none focus:ring-2 focus:ring-primary"
                            placeholder="Bikinin database perpustakaan..."
                        ></textarea>
                    </div>

                    <div>
                        <label for="dialect" class="mb-2 block text-sm font-medium text-accent">Target Database Dialect</label>
                        <select
                            id="dialect"
                            class="w-full rounded-lg border border-primary/50 bg-background/70 px-3 py-2 text-text focus:outline-none focus:ring-2 focus:ring-primary"
                        >
                            <option value="postgresql" selected>PostgreSQL</option>
                            <option value="mysql" disabled>MySQL (Coming Soon)</option>
                            <option value="mariadb" disabled>MariaDB (Coming Soon)</option>
                            <option value="sqlite" disabled>SQLite (Coming Soon)</option>
                        </select>
                    </div>

                    <div class="flex justify-end">
                        <button
                            id="generateButton"
                            type="button"
                            onclick="onGenerate()"
                            class="rounded-lg bg-[#456882] px-5 py-2.5 text-sm font-semibold text-text transition hover:brightness-110 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-background focus:ring-accent"
                        >
                            <span id="generateButtonText">Generate</span>
                        </button>
                    </div>

                    <div id="loadingPanel" class="hidden rounded-xl border border-primary/40 bg-background/70 p-5">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-5 w-5 animate-spin rounded-full border-2 border-accent border-t-transparent"></span>
                            <p class="text-sm font-medium text-accent">AI sedang memproses prompt dan menyiapkan SQL...</p>
                        </div>
                        <div class="mt-4 space-y-2">
                            <div class="h-2 w-full animate-pulse rounded bg-primary/40"></div>
                            <div class="h-2 w-5/6 animate-pulse rounded bg-primary/30"></div>
                            <div class="h-2 w-2/3 animate-pulse rounded bg-primary/20"></div>
                        </div>
                    </div>

                    <div id="rollbackToast" role="status" aria-live="polite" class="pointer-events-none fixed right-4 top-4 z-50 hidden max-w-sm rounded-lg border border-amber-300/60 bg-amber-100/95 px-4 py-3 text-sm text-amber-900 shadow-lg">
                        Database telah dikembalikan ke kondisi semula secara aman (Rollback triggered).
                    </div>

                    <section id="sqlReviewPanel" class="hidden rounded-xl border border-primary/40 bg-background/70 p-5">
                        <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <h3 class="text-lg font-semibold text-text">SQL Review Panel</h3>
                            <p class="text-xs text-accent">Tinjau skrip sebelum eksekusi final ke Kubernetes</p>
                        </div>

                        <div class="mb-4 grid grid-cols-2 gap-2 sm:grid-cols-4">
                            <button type="button" data-sql-tab="ddl" class="sql-tab rounded-md border border-primary/40 bg-secondary/30 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-text">DDL</button>
                            <button type="button" data-sql-tab="dml" class="sql-tab rounded-md border border-primary/40 bg-secondary/10 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-text">DML</button>
                            <button type="button" data-sql-tab="dcl" class="sql-tab rounded-md border border-primary/40 bg-secondary/10 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-text">DCL</button>
                            <button type="button" data-sql-tab="trigger" class="sql-tab rounded-md border border-primary/40 bg-secondary/10 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-text">Trigger</button>
                        </div>

                        <div class="space-y-3">
                            <div data-sql-pane="ddl" class="sql-pane">
                                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-accent">DDL Script</label>
                                <textarea id="sql-editor-ddl" readonly class="h-44 w-full resize-y rounded-lg border border-primary/40 bg-[#10161D] px-3 py-2 font-mono text-xs leading-relaxed text-[#F7F8F0] focus:outline-none"></textarea>
                            </div>
                            <div data-sql-pane="dml" class="sql-pane hidden">
                                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-accent">DML Script</label>
                                <textarea id="sql-editor-dml" readonly class="h-44 w-full resize-y rounded-lg border border-primary/40 bg-[#10161D] px-3 py-2 font-mono text-xs leading-relaxed text-[#F7F8F0] focus:outline-none"></textarea>
                            </div>
                            <div data-sql-pane="dcl" class="sql-pane hidden">
                                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-accent">DCL Script</label>
                                <textarea id="sql-editor-dcl" readonly class="h-44 w-full resize-y rounded-lg border border-primary/40 bg-[#10161D] px-3 py-2 font-mono text-xs leading-relaxed text-[#F7F8F0] focus:outline-none"></textarea>
                            </div>
                            <div data-sql-pane="trigger" class="sql-pane hidden">
                                <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-accent">Trigger Script</label>
                                <textarea id="sql-editor-trigger" readonly class="h-44 w-full resize-y rounded-lg border border-primary/40 bg-[#10161D] px-3 py-2 font-mono text-xs leading-relaxed text-[#F7F8F0] focus:outline-none"></textarea>
                            </div>
                        </div>
                    </section>

                    <section id="schemaOverviewPanel" class="hidden rounded-xl border border-primary/40 bg-background/70 p-5">
                        <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <h3 class="text-lg font-semibold text-text">Visual Schema Overview</h3>
                            <p class="text-xs text-accent">Pemetaan struktur tabel dari hasil JSON API</p>
                        </div>

                        <div id="tableCardsContainer" class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3"></div>

                        <div class="mt-5 rounded-lg border border-accent/40 bg-secondary/20 p-4">
                            <h4 class="mb-3 text-sm font-semibold uppercase tracking-wide text-accent">Database Credentials</h4>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div class="rounded-md border border-primary/40 bg-background/70 px-3 py-2">
                                    <p class="text-[11px] uppercase tracking-wide text-accent">Username</p>
                                    <p id="dbUsername" class="mt-1 font-mono text-sm text-text">-</p>
                                </div>
                                <div class="rounded-md border border-primary/40 bg-background/70 px-3 py-2">
                                    <p class="text-[11px] uppercase tracking-wide text-accent">Password</p>
                                    <p id="dbPassword" class="mt-1 font-mono text-sm text-text">-</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5">
                            <h4 class="mb-3 text-sm font-semibold uppercase tracking-wide text-accent">Download Artifacts</h4>
                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                                <button type="button" data-download="database.sql" class="download-btn rounded-md border border-primary/40 bg-primary/20 px-3 py-2 text-sm font-medium text-text transition hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-50">database.sql</button>
                                <button type="button" data-download="openapi.json" class="download-btn rounded-md border border-primary/40 bg-primary/20 px-3 py-2 text-sm font-medium text-text transition hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-50">openapi.json</button>
                                <button type="button" data-download="postman_collection.json" class="download-btn rounded-md border border-primary/40 bg-primary/20 px-3 py-2 text-sm font-medium text-text transition hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-50">postman_collection.json</button>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-xl border border-primary/40 bg-background/70 p-5">
                        <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <h3 class="text-lg font-semibold text-text">NLP to SQL Demo</h3>
                            <p class="text-xs text-accent">Frontend call to Supabase Edge Function via apiClient</p>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label for="nlpToSqlPrompt" class="mb-2 block text-sm font-medium text-accent">Natural Language Prompt</label>
                                <textarea id="nlpToSqlPrompt" class="h-36 w-full resize-y rounded-lg border border-[#234C6A] bg-background/70 px-4 py-3 text-text placeholder:text-accent/80 focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Tampilkan daftar pesanan aktif dengan total transaksi tertinggi..."></textarea>
                            </div>

                            <div class="grid grid-cols-1 gap-3 md:grid-cols-[1fr,auto] md:items-end">
                                <div>
                                    <label for="nlpToSqlDialect" class="mb-2 block text-sm font-medium text-accent">Dialect</label>
                                    <select id="nlpToSqlDialect" class="w-full rounded-lg border border-primary/50 bg-background/70 px-3 py-2 text-text focus:outline-none focus:ring-2 focus:ring-primary">
                                        <option value="postgresql" selected>PostgreSQL</option>
                                        <option value="mysql">MySQL</option>
                                    </select>
                                </div>

                                <button id="nlpToSqlGenerateButton" type="button" class="rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-text transition hover:brightness-110 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-background focus:ring-accent">
                                    <span id="nlpToSqlGenerateButtonText">Run NLP to SQL</span>
                                </button>
                            </div>

                            <div class="rounded-lg border border-primary/30 bg-secondary/20 p-3 text-sm text-accent" id="nlpToSqlStatus" data-tone="neutral">Siap mengirim prompt ke Edge Function.</div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-accent">JSON Response</label>
                                <pre id="nlpToSqlResult" class="max-h-72 overflow-auto rounded-lg border border-primary/40 bg-[#10161D] p-4 text-xs leading-relaxed text-[#F7F8F0]">{}</pre>
                            </div>
                        </div>
                    </section>
                </div>
            </main>
        </div>
    </div>
</section>

<script>
    const dashboardState = {
        isLoading: false,
        rollbackToastTimer: null,
        generatedSql: {
            ddl: '',
            dml: '',
            dcl: '',
            trigger: '',
        },
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
        showReviewPanel: false,
        showSchemaOverview: false,
    };

    const ROLLBACK_TOAST_MESSAGE = 'Database telah dikembalikan ke kondisi semula secara aman (Rollback triggered).';

    function setLoadingState(next) {
        dashboardState.isLoading = next;

        const loadingPanel = document.getElementById('loadingPanel');
        const button = document.getElementById('generateButton');
        const buttonText = document.getElementById('generateButtonText');

        if (loadingPanel) {
            loadingPanel.classList.toggle('hidden', !next);
        }

        if (button) {
            button.disabled = next;
            button.classList.toggle('opacity-60', next);
            button.classList.toggle('cursor-not-allowed', next);
        }

        if (buttonText) {
            buttonText.textContent = next ? 'Generating...' : 'Generate';
        }
    }

    function renderSqlReviewPanel() {
        const panel = document.getElementById('sqlReviewPanel');
        if (!panel) {
            return;
        }

        panel.classList.toggle('hidden', !dashboardState.showReviewPanel);

        const fallback = '-- Tidak ada output untuk kategori ini.';
        const ddl = document.getElementById('sql-editor-ddl');
        const dml = document.getElementById('sql-editor-dml');
        const dcl = document.getElementById('sql-editor-dcl');
        const trigger = document.getElementById('sql-editor-trigger');

        if (ddl) ddl.value = dashboardState.generatedSql.ddl || fallback;
        if (dml) dml.value = dashboardState.generatedSql.dml || fallback;
        if (dcl) dcl.value = dashboardState.generatedSql.dcl || fallback;
        if (trigger) trigger.value = dashboardState.generatedSql.trigger || fallback;
    }

    function resetGeneratedOutputState() {
        dashboardState.generatedSql = {
            ddl: '',
            dml: '',
            dcl: '',
            trigger: '',
        };
        dashboardState.showReviewPanel = false;
        renderSqlReviewPanel();

        dashboardState.schemaOverview.tables = [];
        dashboardState.schemaOverview.credentials = {
            username: '',
            password: '',
        };
        dashboardState.schemaOverview.downloads = {
            'database.sql': false,
            'openapi.json': false,
            'postman_collection.json': false,
        };
        dashboardState.schemaOverview.files = {
            'database.sql': '',
            'openapi.json': '',
            'postman_collection.json': '',
        };
        dashboardState.showSchemaOverview = false;
        renderSchemaOverview();
    }

    function showRollbackToast() {
        const toast = document.getElementById('rollbackToast');
        if (!toast) {
            return;
        }

        toast.textContent = ROLLBACK_TOAST_MESSAGE;
        toast.classList.remove('hidden');

        if (dashboardState.rollbackToastTimer) {
            clearTimeout(dashboardState.rollbackToastTimer);
        }

        dashboardState.rollbackToastTimer = setTimeout(() => {
            toast.classList.add('hidden');
        }, 3600);
    }

    function isRollbackStructureError(error) {
        if (!error) {
            return false;
        }

        const response = error.response || {};
        const data = response.data || {};
        const signals = [
            error.code,
            error.name,
            error.message,
            data.code,
            data.error,
            data.type,
            data.message,
            data.detail,
            response.status,
        ]
            .filter((value) => value !== undefined && value !== null)
            .map((value) => String(value).toLowerCase());

        const hasStructureSqlSignal = signals.some((value) =>
            value.includes('sql_structure') ||
            value.includes('structure sql') ||
            value.includes('struktur sql') ||
            value.includes('invalid sql structure') ||
            value.includes('unprocessable entity')
        );

        const hasRollbackSignal =
            data.rollbackTriggered === true ||
            data.rollback === true ||
            signals.some((value) => value.includes('rollback'));

        return hasStructureSqlSignal && hasRollbackSignal;
    }

    function normalizeDataType(type) {
        const raw = String(type || '').toLowerCase();
        if (raw.includes('id')) return 'ID';
        if (raw.includes('int') || raw.includes('number')) return 'Integer';
        return 'String';
    }

    function mapSchemaJsonToTables(payload) {
        if (!payload || typeof payload !== 'object') {
            return [];
        }

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
    }

    function renderSchemaOverview() {
        const panel = document.getElementById('schemaOverviewPanel');
        const cardsContainer = document.getElementById('tableCardsContainer');
        const dbUsername = document.getElementById('dbUsername');
        const dbPassword = document.getElementById('dbPassword');

        if (!panel || !cardsContainer || !dbUsername || !dbPassword) {
            return;
        }

        panel.classList.toggle('hidden', !dashboardState.showSchemaOverview);

        cardsContainer.innerHTML = '';
        if (!dashboardState.schemaOverview.tables.length) {
            cardsContainer.innerHTML = '<p class="rounded-md border border-primary/30 bg-background/60 px-3 py-2 text-sm text-accent">Belum ada data schema untuk ditampilkan.</p>';
        } else {
            dashboardState.schemaOverview.tables.forEach((table) => {
                const card = document.createElement('article');
                card.className = 'rounded-lg border border-primary/40 bg-background/80 p-4';

                const title = document.createElement('h5');
                title.className = 'mb-3 text-sm font-semibold uppercase tracking-wide text-text';
                title.textContent = table.name;
                card.appendChild(title);

                if (!table.columns.length) {
                    const empty = document.createElement('p');
                    empty.className = 'text-xs text-accent';
                    empty.textContent = 'Kolom tidak tersedia.';
                    card.appendChild(empty);
                } else {
                    const list = document.createElement('ul');
                    list.className = 'space-y-2';

                    table.columns.forEach((column) => {
                        const item = document.createElement('li');
                        item.className = 'flex items-center justify-between gap-2 rounded-md border border-primary/30 bg-secondary/10 px-2 py-1.5';

                        const name = document.createElement('span');
                        name.className = 'text-xs text-text';
                        name.textContent = column.name;

                        const badge = document.createElement('span');
                        badge.className = 'rounded-full border border-accent/50 bg-accent/20 px-2 py-0.5 text-[11px] font-semibold text-accent';
                        badge.textContent = column.type;

                        item.appendChild(name);
                        item.appendChild(badge);
                        list.appendChild(item);
                    });

                    card.appendChild(list);
                }

                cardsContainer.appendChild(card);
            });
        }

        dbUsername.textContent = dashboardState.schemaOverview.credentials.username || '-';
        dbPassword.textContent = dashboardState.schemaOverview.credentials.password || '-';

        const buttons = document.querySelectorAll('.download-btn');
        buttons.forEach((button) => {
            const file = button.getAttribute('data-download');
            button.disabled = !dashboardState.schemaOverview.downloads[file];
        });
    }

    function handleArtifactDownload(event) {
        const button = event.currentTarget;
        const file = button.getAttribute('data-download');

        if (!dashboardState.schemaOverview.downloads[file]) {
            return;
        }

        const content = dashboardState.schemaOverview.files[file] || '';
        const blob = new Blob([content], { type: 'text/plain;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = file;
        link.click();
        URL.revokeObjectURL(url);
    }

    function bindDownloadActions() {
        document.querySelectorAll('.download-btn').forEach((button) => {
            button.addEventListener('click', handleArtifactDownload);
        });
    }

    function bindSqlTabSwitch() {
        const tabs = document.querySelectorAll('.sql-tab');
        const panes = document.querySelectorAll('.sql-pane');

        tabs.forEach((tab) => {
            tab.addEventListener('click', () => {
                const target = tab.getAttribute('data-sql-tab');

                tabs.forEach((t) => {
                    const active = t === tab;
                    t.classList.toggle('bg-secondary/30', active);
                    t.classList.toggle('bg-secondary/10', !active);
                });

                panes.forEach((pane) => {
                    pane.classList.toggle('hidden', pane.getAttribute('data-sql-pane') !== target);
                });
            });
        });
    }

    async function fakeAiGenerate(prompt, dialect) {
        await new Promise((resolve) => setTimeout(resolve, 1700));

        if (prompt.toLowerCase().includes('simulate rollback')) {
            const simulatedError = new Error('Invalid SQL structure. Rollback triggered by backend.');
            simulatedError.code = 'SQL_STRUCTURE_INVALID';
            simulatedError.response = {
                status: 422,
                data: {
                    rollbackTriggered: true,
                    type: 'sql_structure_error',
                    message: 'Rollback triggered due to SQL structure failure.',
                },
            };
            throw simulatedError;
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
            ddl: `-- DDL\nCREATE TABLE books (\n  id SERIAL PRIMARY KEY,\n  title VARCHAR(255) NOT NULL,\n  author VARCHAR(255) NOT NULL,\n  published_year INT\n);`,
            dml: `-- DML\nINSERT INTO books (title, author, published_year) VALUES\n('Database Perpustakaan Dasar', 'AI Generator', 2026);`,
            dcl: `-- DCL\nGRANT SELECT, INSERT, UPDATE ON TABLE books TO app_writer;`,
            trigger: `-- Trigger\nCREATE OR REPLACE FUNCTION set_updated_at() RETURNS TRIGGER AS $$\nBEGIN\n  NEW.updated_at = NOW();\n  RETURN NEW;\nEND;\n$$ LANGUAGE plpgsql;\n\n-- Prompt source: ${safePrompt}\n-- Dialect: ${dialect}`,
            schemaJson,
            credentials,
            downloads: {
                'database.sql': true,
                'openapi.json': true,
                'postman_collection.json': true,
            },
            files,
        };
    }

    function onGenerate() {
        const promptEl = document.getElementById('nlpPrompt');
        const dialectEl = document.getElementById('dialect');

        if (!promptEl || !dialectEl) {
            return;
        }

        const selected = dialectEl.options[dialectEl.selectedIndex];
        const allowedDialects = ['postgresql'];

        if (dashboardState.isLoading) {
            return;
        }

        if (!selected || selected.disabled || !allowedDialects.includes(dialectEl.value)) {
            alert('Dialect ini belum tersedia. Silakan gunakan PostgreSQL.');
            return;
        }

        const prompt = promptEl.value.trim();
        if (!prompt) {
            alert('Prompt tidak boleh kosong.');
            return;
        }

        resetGeneratedOutputState();
        setLoadingState(true);

        (async () => {
            try {
                const result = await fakeAiGenerate(prompt, dialectEl.value);
                dashboardState.generatedSql = result;
                dashboardState.showReviewPanel = true;
                renderSqlReviewPanel();

                dashboardState.schemaOverview.tables = mapSchemaJsonToTables(result.schemaJson);
                dashboardState.schemaOverview.credentials = result.credentials || { username: '', password: '' };
                dashboardState.schemaOverview.downloads = result.downloads || {};
                dashboardState.schemaOverview.files = result.files || {};
                dashboardState.showSchemaOverview = true;
                renderSchemaOverview();
            } catch (error) {
                if (isRollbackStructureError(error)) {
                    resetGeneratedOutputState();
                    showRollbackToast();
                    return;
                }

                alert('Terjadi kesalahan saat memproses generate SQL.');
            } finally {
                setLoadingState(false);
            }
        })();
    }

    bindSqlTabSwitch();
    bindDownloadActions();
</script>
