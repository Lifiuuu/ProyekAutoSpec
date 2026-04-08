import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { buildErrorPayload, buildSuccessPayload, hasBearerAuthorization } from './shared.js';

const currentDir = path.dirname(fileURLToPath(import.meta.url));
const sampleRequestPath = path.resolve(currentDir, 'sample-request.json');
const sampleRequest = JSON.parse(fs.readFileSync(sampleRequestPath, 'utf8'));

const authorizedRequest = new Request('http://localhost/functions/v1/nlp-to-sql', {
  method: 'POST',
  headers: {
    Authorization: 'Bearer dev-token',
  },
});

const unauthorizedRequest = new Request('http://localhost/functions/v1/nlp-to-sql', {
  method: 'POST',
});

assert.equal(hasBearerAuthorization(authorizedRequest), true, 'authorization header should be accepted');
assert.equal(hasBearerAuthorization(unauthorizedRequest), false, 'missing authorization header should be rejected');

const successPayload = buildSuccessPayload(sampleRequest);
assert.equal(successPayload.success, true);
assert.equal(successPayload.prompt, sampleRequest.prompt);
assert.equal(successPayload.dialect, sampleRequest.dialect);
assert.equal(successPayload.result.rows.length, sampleRequest.limit);
assert.equal(successPayload.result.count, sampleRequest.limit);

const errorPayload = buildErrorPayload('INVALID_PROMPT', 'Prompt is required.');
assert.equal(errorPayload.success, false);
assert.equal(errorPayload.error.code, 'INVALID_PROMPT');

console.log('nlp-to-sql local smoke test passed');