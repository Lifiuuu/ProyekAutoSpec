## ADDED Requirements

### Requirement: Props Structure Ready for API Integration
Komponen AppLayout dan Sidebar SHALL dirancang dengan props structure yang memungkinkan pemanggilan `/api/history` endpoint tanpa perubahan rendering logic.

#### Scenario: History data passed as props
- **WHEN** AppLayout.jsx menerima prop `historyItems` dari parent atau state
- **THEN** Sidebar dapat render data tersebut dengan benar, indifferent terhadap source (mock vs API)

#### Scenario: Status enum matches API response
- **WHEN** history item memiliki field `status` dengan nilai dari enum (success, pending, error)
- **THEN** Sidebar menampilkan icon dan text label yang corresponding tanpa additional data transformation

### Requirement: Mock Data Structure Defined
Sistem SHALL mendefinisikan mock data structure untuk history items yang sesuai dengan yang diharapkan dari `/api/history` endpoint Prism.

#### Scenario: Mock history item structure
- **WHEN** developer melihat mock data di AppLayout atau Sidebar component
- **THEN** struktur mencakup minimal: `{ id, name, status, timestamp, icon_type, description }` sesuai API contract

### Requirement: Easy swap from mock to API
Sistem SHALL membuat sangat mudah untuk mengganti mock data dengan API call tanpa mengubah komponen rendering logic.

#### Scenario: API hook integration point identified
- **WHEN** developer ingin mengintegrasikan API nantinya
- **THEN** hanya perlu membuat hook atau state di AppLayout parent, pass data via props ke Sidebar/komponen child
