import { buildErrorPayload, buildSuccessPayload, hasBearerAuthorization } from './shared.js';

type NlpToSqlRequest = {
    prompt?: string;
    dialect?: string;
    limit?: number;
};

function jsonResponse(body: unknown, status = 200): Response {
    return new Response(JSON.stringify(body, null, 2), {
        status,
        headers: {
            'Content-Type': 'application/json; charset=utf-8',
        },
    });
}

Deno.serve(async (request) => {
    if (request.method !== 'POST') {
        return jsonResponse(buildErrorPayload('METHOD_NOT_ALLOWED', 'Only POST is supported.'), 405);
    }

    if (!hasBearerAuthorization(request)) {
        return jsonResponse(buildErrorPayload('UNAUTHORIZED', 'Missing Authorization header.'), 401);
    }

    let payload: NlpToSqlRequest;

    try {
        payload = await request.json();
    } catch {
        return jsonResponse(buildErrorPayload('INVALID_JSON', 'Request body must be valid JSON.'), 400);
    }

    const prompt = String(payload.prompt || '').trim();
    const dialect = String(payload.dialect || 'postgresql').trim() || 'postgresql';
    const limit = Number.isFinite(payload.limit) ? Number(payload.limit) : 5;

    if (!prompt) {
        return jsonResponse(buildErrorPayload('INVALID_PROMPT', 'Prompt is required.'), 422);
    }

    const response = buildSuccessPayload({ prompt, dialect, limit });

    return jsonResponse(response);
});