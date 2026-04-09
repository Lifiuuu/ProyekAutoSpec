**What**

Integrasi logika backend Autospec (`GenerationService`) ke Frontend Dashboard: perbarui controller yang menerima input dari dashboard sehingga memanggil service untuk membuat skema, dan kembalikan `runId` kepada frontend agar UI dapat menampilkan link download untuk file `generations/{runId}.[sql|openapi.json|postman.json]`.

**Why**

- Frontend Dashboard baru membutuhkan cara untuk memicu generation dan menautkan hasil artefak ke UI. Saat ini `GenerationService` membuat artefak di `storage/app/generations/` tetapi tidak mengembalikan `runId` yang diperlukan frontend.
- Menyelaraskan response controller memudahkan integrasi AJAX atau redirect dengan flash message.

**Scope**

- Audit `app/Http/Controllers/GeneratorController.php` (atau controller terkait) and update `store` (or equivalent) method.
- Ensure controller calls `$generationService->generate($request->prompt)` (or updated signature) and obtains `runId`.
- Return JSON when request expects AJAX, otherwise redirect back with success message and `runId`.
- Keep artifact path as `generations/` inside `storage/app` and expose links accordingly.

Location: `openspec/changes/integrasi-backend-autospec-ke-frontend-dashboard/`
