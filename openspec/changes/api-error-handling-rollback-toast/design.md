## Context

Alur generate SQL saat ini sudah memiliki loading, review panel, dan visual schema overview, tetapi jalur kegagalan API untuk error struktur SQL belum memberikan feedback eksplisit tentang rollback. Saat backend mengembalikan error yang memicu rollback, pengguna perlu mendapat notifikasi yang tegas bahwa sistem aman dan data kembali ke kondisi semula.

## Goals / Non-Goals

**Goals:**
- Menambahkan error handling pada fungsi API call untuk kasus error struktur SQL.
- Menampilkan Toast warning dengan pesan persis: Database telah dikembalikan ke kondisi semula secara aman (Rollback triggered).
- Menjaga state UI kembali aman setelah kegagalan API.
- Mencegah kebingungan pengguna tentang status transaksi saat rollback terjadi.

**Non-Goals:**
- Mengubah mekanisme rollback di backend.
- Menambahkan sistem logging server-side baru.
- Mendesain ulang seluruh komponen notifikasi aplikasi.

## Decisions

1. Gunakan deteksi error berbasis payload/kode error dari response API.
- Rationale: Dapat membedakan error struktur SQL dari error umum.
- Alternative considered: Satu pesan generic untuk semua error.
- Why not: Tidak memberi konteks rollback yang dibutuhkan user.

2. Tampilkan toast warning non-blocking di layer UI utama.
- Rationale: Pesan terlihat jelas tanpa memutus alur interaksi sepenuhnya.
- Alternative considered: Modal blocking.
- Why not: Terlalu intrusif untuk error yang sudah ditangani rollback otomatis.

3. Reset state penting UI di blok finalisasi error path.
- Rationale: Mencegah state loading tersangkut dan panel hasil stale.
- Alternative considered: Reset parsial per titik error.
- Why not: Berisiko inkonsistensi state antar jalur async.

## Risks / Trade-offs

- [Payload error backend tidak konsisten] -> Mitigasi: Sediakan fallback matcher dan default error handling.
- [Toast tidak terlihat karena timing/stacking] -> Mitigasi: Gunakan posisi tetap dengan durasi tampil yang cukup.
- [State lama tetap tertinggal setelah rollback] -> Mitigasi: Bersihkan state hasil generate saat error rollback terdeteksi.
