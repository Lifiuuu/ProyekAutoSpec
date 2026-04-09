## 1. Setup

- [x] 1.1 Add `swagger-ui-react` to the frontend dependencies and confirm the project still builds.
- [x] 1.2 Add scoped Swagger UI styling overrides for the AutoSpec dark palette and minimal chrome.

## 2. Swagger Viewer

- [x] 2.1 Create `SwaggerDocs.jsx` that accepts `specData` and renders Swagger UI inside a modal or full-width card.
- [x] 2.2 Configure the viewer so `Try it out` stays enabled and uses the `servers` definition from the OpenAPI payload.
- [x] 2.3 Render a schema summary section below Swagger UI using `specData` model/schema information with fallback messaging.
- [x] 2.4 Hide non-essential Swagger UI elements such as the search bar and keep the layout responsive.

## 3. Dashboard Integration

- [x] 3.1 Add shared open/close state in the dashboard parent so both sidebar and result-area actions open the same viewer.
- [x] 3.2 Wire the `Lihat Dokumentasi API` action in the sidebar or result area to the shared handler.
- [x] 3.3 Pass the latest `openapi.json` data as `specData` to `SwaggerDocs.jsx` after generation completes.

## 4. Validation

- [x] 4.1 Verify `database.sql`, `openapi.json`, and `postman_collection.json` download buttons still render in the artifacts section.
- [x] 4.2 Confirm credentials, DDL, DML, and schema overview still appear correctly after a successful generation.
- [x] 4.3 Test the documentation panel on desktop and mobile widths to ensure the layout remains usable.
