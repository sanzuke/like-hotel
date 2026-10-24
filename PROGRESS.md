# Status & Progres Harian

## 2026-10-24 (Sabtu)

**Status: ujicoba (belum production).** Aplikasi berjalan di https://likehotel.zukelabs.web.id (VPS `zukelabs`, `/opt/likehotel`, `APP_ENV=production`, data uji). Kode repo dan server sama dengan commit `7b17371`; belum di-push ke remote.

### Selesai hari ini
- [x] Akun login uji admin dan owner dibuat di server (password tidak dicatat di repo).
- [x] Data lama dari `Reff/likehotel_db.sql` diimpor ke server beserta foto (6 apartment, 21 booking, 3 unit dijual, dst.).
- [x] E2E smoke test (read-only) halaman publik, admin, owner, dan proteksi akses: lolos.
- [x] Redesain panel: sidebar sticky + ikon + menu aktif, header (notifikasi, profil, logout).
- [x] Pola daftar + tombol tambah (popup) + pagination di semua tabel utama.
- [x] Filter server-side di baris tabel untuk 10 tabel (admin dan owner).
- [x] Dashboard: bagian "Aktivitas Terbaru" dirapikan.
- [x] `CHANGELOG.md` dan `PROGRESS.md` dicatat.

### Belum / rencana besok
- [ ] Terjemahan pesan validasi (`lang/id`): sekarang tampil `validation.required`.
- [ ] Perbaiki `redirect()->intended()` yang dipakai bersama guard admin dan owner (salah arah bila satu browser dipakai kedua peran).
- [ ] Data uji untuk E2E alur booking publik, pembayaran Midtrans, dan pencairan owner (POST belum pernah diuji).
- [ ] Notifikasi header: tentukan sumber data (booking baru, pengajuan pencairan, lead CRM), ganti placeholder.
- [ ] Push ke remote (`git push`).
- [ ] Putuskan setelan model gratis untuk subagen (`task.agentModelOverrides`, saat ini sesi-saja, belum terbukti jalan).
- [ ] Opsional: filter di Audit Log/Log Midtrans sudah ada; tabel lain tanpa filter kosong.

### Catatan operasional (cara melanjutkan)
- **Deploy:** tidak ada pipeline. Salin file yang berubah dengan `scp` ke `/opt/likehotel/<path>`, lalu di server: `php artisan view:clear && php artisan view:cache`. Lint: `php -l` pada controller dan view terkompilasi. Jalankan sebagai user `ubuntu` (log `storage/logs/laravel.log` milik `www-data`).
- **Cek setelah deploy:** login admin dan owner di browser, buka halaman yang diubah. Parse error di layout membuat semua halaman panel 500 (pernah terjadi hari ini).
- **Versi CSS:** naikkan `?v=` di `layouts/panel.blade.php` setiap `style.css` berubah (sekarang 31).
- **Akun uji:** admin `developer`, owner `owner@likehotelsewa.com`. Admin `admin` dan owner legacy tidak diubah; `likehotel:admin admin` akan mereset password admin tersebut.
- **Impor ulang data lama:** `php artisan likehotel:import-legacy <file> --wipe` (menghapus data bisnis; owner uji perlu dibuat ulang). User DB `likehotel` tidak punya hak `CREATE DATABASE`; impor butuh grant sementara untuk `likehotel_legacy_import`.
- **Server:** `.env` memakai `APP_LOCALE=id`; config dan route di-cache (`php artisan config:clear` bila `.env` diubah).

### Risiko / catatan
- Data booking hasil impor: 16 booking belum dibayar berstatus kedaluwarsa/batal.
- Filter `LIKE '%…%'` tidak memakai index; aman untuk data kecil, perlu index/full-text bila data membesar.
- Perubahan subagen (bantuan model) selalu diverifikasi sendiri di browser sebelum dianggap selesai.
