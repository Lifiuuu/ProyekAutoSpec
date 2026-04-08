export function hasBearerAuthorization(request) {
    const authorization = request.headers.get('authorization') || request.headers.get('Authorization');
    return Boolean(authorization && authorization.toLowerCase().startsWith('bearer '));
}

export function inferRows(prompt, limit) {
    const normalizedPrompt = String(prompt || '').toLowerCase();
    const size = Math.max(1, Math.min(Number(limit) || 1, 10));

    if (normalizedPrompt.includes('order') || normalizedPrompt.includes('pesanan')) {
        return Array.from({ length: size }, (_, index) => ({
            id: index + 1,
            order_number: `ORD-${String(index + 1).padStart(3, '0')}`,
            status: index % 2 === 0 ? 'completed' : 'processing',
        }));
    }

    if (normalizedPrompt.includes('student') || normalizedPrompt.includes('mahasiswa')) {
        return Array.from({ length: size }, (_, index) => ({
            id: index + 1,
            name: `Student ${index + 1}`,
            active: index % 2 === 0,
        }));
    }

    return Array.from({ length: size }, (_, index) => ({
        id: index + 1,
        label: `Row ${index + 1}`,
        summary: `Generated result for: ${prompt}`,
    }));
}

export function buildSuccessPayload({ prompt, dialect, limit }) {
    const safePrompt = String(prompt || '').trim();
    const safeDialect = String(dialect || 'postgresql').trim() || 'postgresql';
    const rows = inferRows(safePrompt, limit);

    return {
        success: true,
        prompt: safePrompt,
        dialect: safeDialect,
        result: {
            rows,
            count: rows.length,
        },
        metadata: {
            executedAt: new Date().toISOString(),
            source: 'supabase-edge-function',
        },
    };
}

export function buildErrorPayload(code, message) {
    return {
        success: false,
        error: {
            code,
            message,
        },
    };
}