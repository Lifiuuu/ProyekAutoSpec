## ADDED Requirements

### Requirement: Swagger documentation panel SHALL render OpenAPI data interactively
The system SHALL provide a `SwaggerDocs.jsx` component that renders the supplied `specData` object from `openapi.json` using `swagger-ui-react` inside a modal or full-width card.

#### Scenario: OpenAPI spec is rendered in dashboard context
- **WHEN** the parent component passes valid `specData` to `SwaggerDocs.jsx`
- **THEN** the component renders interactive Swagger documentation without requiring manual file upload or page navigation

### Requirement: Swagger documentation panel SHALL preserve Try it out capability
The system SHALL keep Swagger UI's `Try it out` interactions enabled so users can execute CRUD requests directly against the configured backend endpoint.

#### Scenario: User executes an API request from Swagger UI
- **WHEN** the user clicks `Try it out` on a documented endpoint and submits a request
- **THEN** Swagger UI sends the request to the live backend endpoint defined in the OpenAPI document

### Requirement: Swagger documentation panel SHALL display model validation context
The system SHALL render a database model summary below the Swagger UI content so users can validate field types such as String, Integer, and other schema-relevant types.

#### Scenario: Model summary is visible below API documentation
- **WHEN** the documentation panel is opened
- **THEN** the lower section shows the related database models or schema summary with readable type labels for validation

### Requirement: Swagger documentation panel SHALL stay visually minimal and focused
The system SHALL hide non-essential Swagger UI elements such as the default search bar and apply the AutoSpec dark theme palette for a focused dashboard experience.

#### Scenario: Minimal UI is rendered
- **WHEN** the documentation panel is shown
- **THEN** the search bar is not visible and the panel uses the dark palette with background `#1E1E1E`, text `#F7F8F0`, and accent `#456882`
