## ADDED Requirements

### Requirement: Stage-1 system prompt SHALL be strict English JSON-only contract
The system SHALL use an English system prompt that instructs the LLM to output one pure JSON object only, with no prose, markdown, or code fences.

#### Scenario: Prompt enforces pure JSON output
- **WHEN** the backend requests schema generation from LLM
- **THEN** the system prompt explicitly requires valid JSON and rejects explanatory output formats

### Requirement: JSON output SHALL include exact root keys
The system SHALL require the root JSON object to include `tables`, `stored_procedures`, `functions`, and `triggers` keys, each as arrays.

#### Scenario: Missing key is treated invalid
- **WHEN** the LLM response omits one required root key
- **THEN** the response is considered invalid against contract and handled by generation error flow

### Requirement: Allowed column types SHALL be strictly limited
The system SHALL allow only these abstract types: `id`, `string`, `integer`, `text`, `boolean`, `date`, `datetime`, `decimal`.

#### Scenario: Raw SQL types are disallowed
- **WHEN** the LLM outputs raw SQL types such as `VARCHAR` or `INT`
- **THEN** the output is treated as contract violation and must not proceed as valid schema JSON

### Requirement: Primary and foreign key typing SHALL follow strict rules
The system SHALL allow `type: "id"` only for the primary key column named exactly `id`; foreign key columns (e.g., `user_id`) SHALL use `type: "integer"`.

#### Scenario: Foreign key incorrectly typed as id
- **WHEN** a foreign key column uses `type: "id"`
- **THEN** the output is rejected as invalid because FK must be integer

### Requirement: PostgreSQL trigger definitions SHALL target function invocation only
The system SHALL require trigger definitions to invoke functions and SHALL not accept trigger definitions that invoke procedures.

#### Scenario: Trigger calls procedure
- **WHEN** trigger definition uses procedure invocation pattern
- **THEN** the output is rejected or corrected before SQL translation because PostgreSQL triggers must call functions
