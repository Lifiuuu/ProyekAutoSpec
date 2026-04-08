## ADDED Requirements

### Requirement: Download actions SHALL provide three artifact buttons
The system SHALL provide three distinct download buttons for `database.sql`, `openapi.json`, and `postman_collection.json`.

#### Scenario: All required download buttons are present
- **WHEN** user opens the download action area
- **THEN** buttons for `database.sql`, `openapi.json`, and `postman_collection.json` are visible.

### Requirement: Download actions SHALL map each button to its target file
Each download button SHALL trigger download for its corresponding artifact only.

#### Scenario: User downloads selected artifact
- **WHEN** user clicks one download button
- **THEN** the system starts download for the matching file and not for other files.

### Requirement: Download buttons SHALL expose unavailable state
The system SHALL indicate disabled state when target artifact is not available.

#### Scenario: Artifact unavailable disables button
- **WHEN** a target file has not been generated
- **THEN** its download button is disabled with clear unavailable indication.
