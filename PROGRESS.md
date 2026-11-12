# Status & Progres Harian

## 2026-11-12 (Kamis)

**Status: live di https://likehotelapartemen.com.** cPanel + LiteSpeed, PHP 8.3, database `likehote_likehotel` terhubung (25 tabel, 16 migrasi, 1 admin). Data bisnis masih kosong.

### Selesai
- [x] Deploy kode branch `terjemahan-validasi` (termasuk perubahan yang belum di-commit) ke domain utama.
- [x] PHP domain dinaikkan 8.1 → 8.3 lewat CloudLinux PHP Selector (Laravel 13 butuh ^8.3).
- [x] Webroot dirapikan: backup lama di `~/public_html.bak-*`, `.htaccess` root (rewrite ke `public/`, paksa HTTPS, blokir `.env`) kini tersimpan di repo.
- [x] Deploy otomatis `deploy.ps1` + `scripts/deploy-remote.sh`: backup, swap, composer, migrate, cache, health check, rollback otomatis.
- [x] Cron `schedule:run` aktif (auto-expire booking pending).
- [x] Gap alur bisnis dicatat di `../docs/GAP_ALUR_BISNIS.md`.
- [x] **Akun tunggal**: semua peran daftar lewat `/daftar`; profil pemilik diaktifkan dari `/akun/pemilik` (kolom `guests.owner_id`), dashboard owner terbuka tanpa akun kedua.
- [x] **Titip jual wajib akun**: `/titip-jual` login dulu, pengajuan tersimpan dengan `guest_id`, status dipantau di `/akun/titip-jual`.
- [x] **Kategori Hotel/Apartemen**: kolom `apartments.category`, filter di pencarian, pilihan kategori di form owner & admin.
- [x] Tes: 88 lolos (660 assertion); deploy produksi 12 Nov 2026, migrasi kolom baru berhasil, health check 200.

### Belum
- [ ] Owner upload foto unit titip jual sendiri (sekarang masih lewat admin).
- [ ] Import data lama `Reff/likehotel_db.sql` ke database produksi (masih kosong).
- [ ] Midtrans masih sandbox; email masih `MAIL_MAILER=log`.
- [ ] Uji E2E di server: daftar → aktifkan pemilik → tambah unit → titip jual → booking + pembayaran.
- [ ] Hapus/alihtautkan login owner lama setelah data lama diimpor. Detail keputusan di `../docs/GAP_ALUR_BISNIS.md`.

## 2026-11-11 (Rabu)

**Status: ujicoba (belum production).** `main` sudah di-push (`b7f22dc`, 29 Okt). Pekerjaan hari ini ada di branch `terjemahan-validasi`, belum di-commit. Belum dipastikan apakah perubahan 27–29 Okt sudah di-deploy ke server.

### Selesai sejak catatan 24 Okt
- [x] 27 Okt: login/daftar owner via Google; katalog & detail jual beli, halaman Booking Berhasil, dan invoice mengikuti desain Stitch; Cloudflare Turnstile di Titip Jual, login admin/owner, daftar owner; format tanggal `DD MMM YYYY`; skala tipografi seragam; tombol notifikasi WhatsApp di Booking Berhasil dihapus.
- [x] 29 Okt: halaman Ganti Password admin; optimasi SEO (URL berslug, robots/sitemap dinamis, judul & alt, kompresi gambar; panduan di `docs/SEO_CHECKLIST.md`); akun penyewa (daftar/login email + Google, booking wajib login, Booking Saya).
- [x] 29 Okt: push ke remote.
- [x] 11 Nov: terjemahan pesan validasi (`lang/id`: `validation`, `auth`, `pagination`, `passwords`) beserta nama kolom yang ramah dibaca; `.env.example` memakai locale `id`.
- [x] 12 Nov: tombol Hapus di Kelola Apartment (admin) dan Unit Saya (owner); apartment dengan riwayat booking ditolak. Tes: 73 lolos. Belum dicek di browser (login lokal memakai Turnstile).

### Belum
- [ ] Commit, merge, dan deploy branch `terjemahan-validasi` (folder `lang/id` harus ikut disalin ke server).
- [ ] Periksa `redirect()->intended()`: kini dipakai tiga guard (admin, owner, penyewa); isu salah arah dari 24 Okt belum dipastikan beres.
- [ ] Data uji dan E2E alur booking publik, pembayaran Midtrans, dan pencairan owner (POST belum pernah diuji di server).
- [ ] Notifikasi header panel: masih placeholder per 24 Okt, belum dicek ulang.
- [ ] Pastikan server sinkron dengan `main`.

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
