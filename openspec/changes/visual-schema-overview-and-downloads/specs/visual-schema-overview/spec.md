## ADDED Requirements

### Requirement: API schema JSON SHALL be mapped into table cards
The system SHALL transform API JSON schema output into a collection of visual table cards.

#### Scenario: Table cards are generated from API JSON
- **WHEN** schema JSON response is available
- **THEN** the UI renders one card per table in the response.

### Requirement: Each table card SHALL display core metadata
Each rendered table card SHALL display table name, column list, and data types including ID, String, and Integer where applicable.

#### Scenario: Table card shows required fields
- **WHEN** a table card is displayed
- **THEN** it includes table name, listed columns, and recognized data type labels such as ID, String, and Integer.

### Requirement: Visual overview SHALL support empty or partial data safely
The visual schema overview SHALL show fallback messaging when table metadata is empty or partially missing.

#### Scenario: Fallback shown on incomplete schema
- **WHEN** table metadata is incomplete in API response
- **THEN** the card shows safe fallback text instead of breaking layout.
