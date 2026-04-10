## ADDED Requirements

### Requirement: Runtime database baseline SHALL use local PostgreSQL defaults
The system SHALL define local development baseline env values as follows: `DB_CONNECTION=pgsql`, `DB_HOST=127.0.0.1`, `DB_PORT=54322`, `DB_DATABASE=postgres`, `DB_USERNAME=postgres`, and `DB_PASSWORD=postgres`.

#### Scenario: Local environment is configured without Supabase dependency
- **WHEN** a developer prepares local runtime configuration
- **THEN** the configuration uses the PostgreSQL baseline values and does not require Supabase service endpoints

### Requirement: Generation flow SHALL implement multi-schema isolation
The system SHALL maintain one main schema for user/account records and generation history, and SHALL create a dedicated schema for each successful generate request.

#### Scenario: New schema created per generate request
- **WHEN** an authenticated user submits a generation prompt
- **THEN** the backend creates a new PostgreSQL schema for that request and executes generated SQL within that schema context

### Requirement: Main schema SHALL store ownership and generation history
The system SHALL persist generation metadata in the main schema, including at minimum `user_id`, `schema_name`, `prompt`, generation status, and timestamps.

#### Scenario: History references generated schema
- **WHEN** generation finishes (success or failure)
- **THEN** the history record in main schema references the target schema name and outcome metadata

### Requirement: SQL execution SHALL be schema-scoped
The system SHALL run DDL/DML execution using qualified schema names or `search_path` set to the generated schema to avoid cross-schema contamination.

#### Scenario: Generated objects stay inside target schema
- **WHEN** generated SQL is applied by backend
- **THEN** tables, functions, procedures, and triggers are created only in the generated schema for that request
