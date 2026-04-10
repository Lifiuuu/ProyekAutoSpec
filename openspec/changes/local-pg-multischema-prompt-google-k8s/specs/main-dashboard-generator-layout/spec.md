## MODIFIED Requirements

### Requirement: SQL output SHALL be grouped by review categories aligned to prompt artifacts
The system SHALL display generated SQL in review categories `DDL`, `DML`, `DCL`, `Functions`, `Stored Procedures`, and `Triggers`.

#### Scenario: Expanded SQL categories are available
- **WHEN** SQL Review Panel is opened after generation
- **THEN** the UI shows tabs or sections for `DDL`, `DML`, `DCL`, `Functions`, `Stored Procedures`, and `Triggers` with corresponding script content or clear empty-state text

### Requirement: DCL category SHALL provide credentials/security context
The system SHALL present DCL output as security and access credentials context in the review panel so users can inspect grants/revokes and related access settings.

#### Scenario: DCL reviewed as credentials context
- **WHEN** user inspects the DCL review section
- **THEN** the section clearly communicates security/credentials statements associated with generated schema access

### Requirement: Frontend visual design SHALL remain unchanged while extending review categories
The system SHALL preserve existing dashboard visual design language and layout while only extending SQL review content categories.

#### Scenario: Functional expansion without redesign
- **WHEN** review panel categories are expanded
- **THEN** existing color tokens, spacing language, and core dashboard composition remain consistent with current UI design
