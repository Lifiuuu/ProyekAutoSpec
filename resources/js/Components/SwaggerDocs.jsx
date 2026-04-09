import SwaggerUI from 'swagger-ui-react';
import 'swagger-ui-react/swagger-ui.css';

function toSchemaBadge(type) {
  const raw = String(type || '').toLowerCase();

  if (raw === 'id' || raw.includes('int') || raw.includes('number')) return 'Integer';
  if (raw.includes('bool')) return 'Boolean';
  if (raw.includes('date') && raw.includes('time')) return 'DateTime';
  if (raw.includes('date')) return 'Date';
  if (raw.includes('decimal') || raw.includes('float') || raw.includes('double')) return 'Decimal';

  return 'String';
}

function normalizeSchemaTables(specData, schemaTables) {
  if (Array.isArray(schemaTables) && schemaTables.length > 0) {
    return schemaTables;
  }

  const schemas = specData?.components?.schemas || {};

  return Object.entries(schemas).map(([name, schema]) => ({
    name,
    columns: Object.entries(schema?.properties || {}).map(([columnName, columnSchema]) => ({
      name: columnName,
      type: columnSchema?.type === 'integer'
        ? 'Integer'
        : columnSchema?.type === 'boolean'
          ? 'Boolean'
          : columnSchema?.format === 'date-time'
            ? 'DateTime'
            : columnSchema?.format === 'date'
              ? 'Date'
              : columnSchema?.type === 'number'
                ? 'Decimal'
                : 'String',
    })),
  }));
}

export default function SwaggerDocs({ specData, schemaTables = [], onClose }) {
  if (!specData) {
    return null;
  }

  const tables = normalizeSchemaTables(specData, schemaTables);

  return (
    <div className="swagger-docs-overlay fixed inset-0 z-50 flex items-center justify-center bg-black/70 px-4 py-6 backdrop-blur-sm">
      <div className="swagger-docs-shell flex h-[92vh] w-full max-w-7xl flex-col overflow-hidden rounded-2xl border border-[#234C6A]/60 bg-[#1E1E1E] text-[#F7F8F0] shadow-2xl">
        <div className="flex items-center justify-between gap-4 border-b border-white/10 px-5 py-4">
          <div>
            <p className="text-xs uppercase tracking-[0.3em] text-[#456882]">Swagger Documentation</p>
            <h3 className="mt-1 text-lg font-bold text-[#F7F8F0]">Lihat Dokumentasi API</h3>
          </div>
          <button
            type="button"
            onClick={onClose}
            className="rounded-lg border border-[#456882]/60 px-4 py-2 text-sm font-semibold text-[#F7F8F0] transition-all duration-200 hover:border-[#456882] hover:bg-[#234C6A]/20"
          >
            Tutup
          </button>
        </div>

        <div className="flex-1 overflow-y-auto px-4 py-4 md:px-6">
          <div className="rounded-2xl border border-white/10 bg-white/[0.03] p-4 md:p-6">
            <SwaggerUI
              spec={specData}
              deepLinking
              docExpansion="list"
              defaultModelsExpandDepth={1}
              defaultModelExpandDepth={1}
              filter={false}
              persistAuthorization={false}
              supportedSubmitMethods={['get', 'post', 'put', 'patch', 'delete']}
              tryItOutEnabled
              displayOperationId={false}
              displayRequestDuration
              showExtensions
              showCommonExtensions
              syntaxHighlight={{ activated: true, theme: 'monokai' }}
              topbar={false}
              layout="BaseLayout"
            />
          </div>

          <div className="mt-6 rounded-2xl border border-white/10 bg-white/[0.03] p-4 md:p-6">
            <div className="mb-4 flex items-center justify-between gap-3">
              <div>
                <h4 className="text-sm font-bold uppercase tracking-wide text-[#456882]">Database Models</h4>
                <p className="text-xs text-gray-400">Validasi tipe data schema dari hasil generate</p>
              </div>
              <span className="rounded-full border border-[#456882]/50 px-3 py-1 text-[11px] font-semibold text-[#F7F8F0]">
                {tables.length} model
              </span>
            </div>

            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
              {tables.map((table) => (
                <div key={table.name} className="rounded-xl border border-[#234C6A]/50 bg-[#141414] p-4">
                  <h5 className="mb-4 text-sm font-bold uppercase tracking-wide text-[#456882]">{table.name}</h5>
                  <div className="space-y-2">
                    {table.columns?.length > 0 ? (
                      table.columns.map((column) => (
                        <div key={`${table.name}-${column.name}`} className="flex items-center justify-between gap-3 text-sm">
                          <span className="truncate text-gray-200">{column.name}</span>
                          <span className="rounded-md border border-[#456882]/60 px-2 py-0.5 text-[11px] font-semibold text-[#F7F8F0]">
                            {toSchemaBadge(column.type)}
                          </span>
                        </div>
                      ))
                    ) : (
                      <p className="text-xs text-gray-400">Tidak ada kolom yang tersedia.</p>
                    )}
                  </div>
                </div>
              ))}

              {tables.length === 0 && (
                <div className="rounded-xl border border-dashed border-[#456882]/50 p-4 text-sm text-gray-400 md:col-span-2 xl:col-span-3">
                  Schema model belum tersedia.
                </div>
              )}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}