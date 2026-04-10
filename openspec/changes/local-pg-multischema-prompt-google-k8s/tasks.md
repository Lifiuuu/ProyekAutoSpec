## 1. Requirement Baseline Shift (Supabase -> Local PostgreSQL)

- [x] 1.1 Update affected OpenSpec capabilities to remove Supabase assumptions from runtime/API headers/docs.
- [x] 1.2 Define required local env baseline: `DB_CONNECTION=pgsql`, `DB_HOST=127.0.0.1`, `DB_PORT=5432`, `DB_DATABASE=dbautospec`, `DB_USERNAME=postgres`, `DB_PASSWORD=`.
- [x] 1.3 Ensure deployment docs distinguish local baseline vs cluster runtime DB endpoints.

## 2. Multi-Schema Backend Generation

- [x] 2.1 Define requirement for one main schema used for user identity and schema generation history.
- [x] 2.2 Define requirement that each generate prompt creates a dedicated PostgreSQL schema.
- [x] 2.3 Define requirement to persist schema ownership/history metadata (user_id, schema_name, prompt, timestamps).
- [x] 2.4 Define requirement for safe execution context (`search_path`/qualified names) per generated schema.

## 3. Strict English System Prompt Contract

- [x] 3.1 Replace Indonesian prompt text in requirement with strict English prompt.
- [x] 3.2 Enforce exact JSON root keys: `tables`, `stored_procedures`, `functions`, `triggers`.
- [x] 3.3 Enforce allowed type list and PK/FK typing rules (`id` only for PK id column, FK -> `integer`).
- [x] 3.4 Enforce PostgreSQL trigger rule: trigger invokes function only.

## 4. Frontend Review Panel Requirement Expansion (No Visual Redesign)

- [x] 4.1 Keep existing design language/layout intact (no theme or major style changes).
- [x] 4.2 Update requirement so review panel shows: `DDL`, `DML`, `DCL`, `Functions`, `Stored Procedures`, `Triggers`.
- [x] 4.3 Define requirement that DCL section represents credentials/security statements clearly.

## 5. Google Login Requirement Update

- [x] 5.1 Set Google OAuth credential values for deployment environment via secret/env configuration.
- [x] 5.2 Update requirement that login flow continues to support Google sign-in with backend endpoint integration.
- [x] 5.3 Add security note that credentials must not be committed in plaintext manifest examples.

## 6. Docker and Kubernetes Deployment Alignment

- [x] 6.1 Update Docker/K8s requirements to align with namespace `semute`.
- [x] 6.2 Update ingress/domain requirement to `https://semute.hackathon.sev-2.com`.
- [x] 6.3 Update DB runtime requirement for cluster credentials (`103.185.52.138:1185`, db `semute`).
- [x] 6.4 Add requirement that K8s secret examples remain sanitized and production values supplied at deploy time.
