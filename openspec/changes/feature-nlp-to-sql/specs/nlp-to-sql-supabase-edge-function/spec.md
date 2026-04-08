## ADDED Requirements

### Requirement: NLP to SQL request SHALL be handled by Supabase Edge Function
The system SHALL provide an Edge Function endpoint that accepts a natural language prompt from the frontend and processes it into a database query workflow.

#### Scenario: Frontend submits a prompt
- **WHEN** the frontend sends a valid prompt to the Edge Function
- **THEN** the function accepts the request and begins query processing.

### Requirement: Edge Function SHALL use authenticated requests
The system SHALL require the existing Authorization header from `apiClient` for requests to the NLP to SQL endpoint.

#### Scenario: Auth header is present
- **WHEN** the request includes a valid Authorization header
- **THEN** the Edge Function allows the request to proceed.

### Requirement: NLP to SQL endpoint SHALL return JSON output
The system SHALL return structured JSON data to the frontend after the generated SQL is executed.

#### Scenario: Query completes successfully
- **WHEN** the Edge Function finishes executing the generated SQL
- **THEN** the response contains JSON data that can be rendered by the frontend without parsing raw SQL.

### Requirement: NLP to SQL endpoint SHALL not expose raw SQL as primary output
The system SHALL not use raw SQL as the primary response payload for the frontend.

#### Scenario: Result is delivered to UI
- **WHEN** the frontend receives the response payload
- **THEN** the payload is JSON data rather than raw SQL text.

### Requirement: NLP to SQL workflow SHALL support local testing
The system SHALL support local testing of the Edge Function and frontend integration before deployment.

#### Scenario: Developer runs local validation
- **WHEN** the developer executes the local Supabase and frontend test flow
- **THEN** the NLP to SQL request path can be verified end-to-end.