## MODIFIED Requirements

### Requirement: Generate action SHALL expose loading state
The system SHALL set `isLoading` to true immediately after the Generate button is clicked and keep it true until AI processing completes or fails, including rollback error paths.

#### Scenario: Loading state starts on generate
- **WHEN** user clicks Generate
- **THEN** the UI enters loading state and prevents duplicate generate action until processing ends.

#### Scenario: Loading state resets on rollback error
- **WHEN** backend returns SQL structure error and rollback is triggered
- **THEN** `isLoading` is reset to false in the final error handling flow.

### Requirement: SQL Review Panel SHALL appear after processing
The system SHALL show SQL Review Panel only after AI processing completes successfully and SQL output is available.

#### Scenario: Review panel appears after successful generation
- **WHEN** AI finishes processing successfully
- **THEN** SQL Review Panel is rendered for user review before final execution to Kubernetes.

#### Scenario: Review panel hidden on rollback error
- **WHEN** backend returns rollback-triggered SQL structure error
- **THEN** SQL Review Panel is not shown with stale or invalid output.
