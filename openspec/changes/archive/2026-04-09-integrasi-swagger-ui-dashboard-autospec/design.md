## Context

AutoSpec sudah menghasilkan `openapi.json`, schema database, dan artefak unduhan, tetapi belum memiliki viewer dokumentasi interaktif yang mudah diakses dari dashboard. Change ini menambah satu komponen React `SwaggerDocs.jsx` yang dirender di dalam shell dashboard Laravel/Vite dan dibuka dari sidebar maupun area hasil generasi.

Konteks teknis utama:
- Frontend: React di `resources/js`.
- Backend: Laravel API yang mengembalikan `openapi.json` hasil generasi.
- Viewer: `swagger-ui-react`.
- UI target: dark mode AutoSpec dengan palet `#1E1E1E`, `#F7F8F0`, dan aksen `#456882`.

## Goals / Non-Goals

**Goals:**
- Menampilkan dokumentasi API interaktif langsung dari dashboard.
- Menjaga `Try it out` tetap aktif untuk validasi endpoint CRUD.
- Menampilkan ringkasan model/schema database di bagian bawah Swagger UI.
- Menyediakan satu viewer yang dipakai oleh sidebar dan area hasil agar UX konsisten.
- Menjaga UI tetap minimalis dengan menghilangkan elemen Swagger yang tidak diperlukan.

**Non-Goals:**
- Tidak mengubah generator OpenAPI backend secara fundamental.
- Tidak membuat editor OpenAPI manual.
- Tidak mengubah alur authentication backend selain mengikuti konfigurasi yang sudah ada.
- Tidak mengganti layout dashboard utama secara besar-besaran.

## Decisions

1. **Gunakan `swagger-ui-react` sebagai renderer utama**
   - Rationale: library ini sudah menyediakan navigasi endpoint, contoh request, response, dan `Try it out` tanpa perlu membangun UI dokumentasi dari nol.
   - Alternative considered: custom docs viewer. Ditolak karena terlalu mahal untuk fitur yang sudah tersedia di Swagger UI.

2. **Render viewer di full-width card atau modal dalam dashboard**
   - Rationale: user tetap berada di konteks hasil generasi sehingga bisa membandingkan schema, SQL, dan dokumentasi API tanpa pindah halaman.
   - Alternative considered: membuka halaman baru. Ditolak karena memutus konteks kerja dan demo.

3. **Gunakan `specData` sebagai source of truth**
   - Rationale: komponen menerima objek JSON hasil `openapi.json` langsung, sehingga mudah diuji dan tidak bergantung pada fetch di dalam komponen.
   - Alternative considered: komponen membaca file sendiri. Ditolak karena lebih sulit dikontrol dan diuji.

4. **Satu state open/close untuk semua entry point**
   - Rationale: tombol sidebar dan tombol area hasil harus membuka viewer yang sama agar state dokumentasi tidak terpecah.
   - Alternative considered: viewer terpisah untuk sidebar dan result area. Ditolak karena menambah kompleksitas dan risiko inkonsistensi.

5. **Ekstrak schema summary dari `components.schemas` / schema object yang tersedia**
   - Rationale: informasi model sudah ada di OpenAPI output, jadi ringkasan tabel dan tipe data bisa ditampilkan tanpa sumber data tambahan.
   - Alternative considered: menyimpan model summary di state terpisah. Ditolak karena rentan sinkronisasi.

6. **Override styling via wrapper CSS, bukan fork Swagger UI**
   - Rationale: kebutuhan visual hanya butuh dark mode dan penyembunyian elemen yang tidak relevan.
   - Alternative considered: fork library atau tema kompleks. Ditolak karena terlalu berat.

## Risks / Trade-offs

- `Try it out` bisa gagal jika `servers` OpenAPI tidak mengarah ke endpoint yang benar → Mitigation: pastikan `openapi.json` dan environment backend memakai base URL yang valid, lalu tampilkan error response apa adanya.
- Swagger UI punya markup internal yang kompleks → Mitigation: scoped CSS pada wrapper dan selector spesifik.
- Jika `openapi.json` belum lengkap, ringkasan model bisa kosong → Mitigation: tampilkan fallback messaging daripada memaksakan render.
- Menambah dependency frontend akan menaikkan ukuran bundle → Mitigation: lazy-load viewer saat tombol dokumentasi dibuka.
- Data unduhan dan credentials bisa tidak tersedia dari response lama → Mitigation: sediakan fallback default di frontend dan backend.

## Migration Plan

1. Tambahkan dependency `swagger-ui-react` dan styling pendukung frontend.
2. Implementasikan `SwaggerDocs.jsx` dengan prop `specData` dan ringkasan model di bawah viewer.
3. Tambahkan state open/close di dashboard parent dan sambungkan ke tombol `Lihat Dokumentasi API`.
4. Pastikan sidebar dan result area memanggil handler yang sama untuk membuka viewer.
5. Validasi response `openapi.json` dari backend dan pastikan `Try it out` menargetkan endpoint yang benar.
6. Rollback strategy: nonaktifkan tombol entry point dan fallback ke dashboard semula tanpa mengubah generator database atau API output.

## Open Questions

- Apakah viewer akan default dibuka sebagai modal atau full-width card di semua ukuran layar?
- Apakah ringkasan model cukup dari OpenAPI schema saja, atau perlu fallback tambahan dari payload schema database?
- Apakah environment Kubernetes akan menyediakan base URL yang sama untuk demo dan testing lokal?