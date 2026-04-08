## ADDED Requirements

### Requirement: Generate action SHALL expose loading state
The system SHALL set `isLoading` to true immediately after the Generate button is clicked and keep it true until AI processing completes or fails.

#### Scenario: Loading state starts on generate
- **WHEN** user clicks Generate
- **THEN** the UI enters loading state and prevents duplicate generate action until processing ends.

### Requirement: UI SHALL show modern loading animation during processing
The system SHALL display a modern loading animation while `isLoading` is true.

#### Scenario: Loading animation visibility follows processing state
- **WHEN** AI processing is in progress
- **THEN** loading animation is visible and hidden when processing ends.

### Requirement: SQL Review Panel SHALL appear after processing
The system SHALL show SQL Review Panel only after AI processing completes and SQL output is available.

#### Scenario: Review panel appears after successful generation
- **WHEN** AI finishes processing successfully
- **THEN** SQL Review Panel is rendered for user review before final execution to Kubernetes.

### Requirement: SQL Review Panel SHALL use code editor view
The system SHALL render generated SQL using a code-editor-style component in read-only review mode.

#### Scenario: SQL content is shown in editor format
- **WHEN** SQL Review Panel is shown
- **THEN** user can inspect SQL in monospaced editor-style presentation.

### Requirement: SQL output SHALL be grouped by category
The system SHALL display generated SQL grouped into DDL, DML, DCL, and Trigger sections.

#### Scenario: All SQL categories are present in review
- **WHEN** SQL Review Panel is opened
- **THEN** the UI shows DDL, DML, DCL, and Trigger categories with their corresponding scripts or clear fallback text when empty.
