## ADDED Requirements

### Requirement: SQL structure API errors SHALL trigger rollback warning toast
The system SHALL detect backend errors related to SQL structure and show a warning toast with exact message: Database telah dikembalikan ke kondisi semula secara aman (Rollback triggered).

#### Scenario: SQL structure error response received
- **WHEN** API response indicates SQL structure failure and rollback is triggered
- **THEN** a warning toast appears with exact rollback-safe message.

### Requirement: Rollback warning toast SHALL be user-visible and non-blocking
The rollback toast SHALL be visible in the interface without requiring modal confirmation.

#### Scenario: User can continue after toast appears
- **WHEN** rollback warning toast is displayed
- **THEN** user can continue interacting with dashboard after reading the message.

### Requirement: UI state SHALL return to safe state after rollback error
When rollback-triggered API error occurs, the system SHALL clear loading state and prevent stale success output from being shown.

#### Scenario: No stale success panel after rollback
- **WHEN** rollback error handling completes
- **THEN** loading indicators are removed and success/result panels are hidden or reset as needed.
