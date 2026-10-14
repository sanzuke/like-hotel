# Like Hotel — Laravel

Porting aplikasi PHP native **Like Hotel (Tahap 5.3 – Settlement)** ke Laravel 13: sewa apartemen, booking + Midtrans Snap, invoice, owner multi-unit, settlement/pencairan, laporan, promo, jual-beli unit, CRM titip jual, dan komisi penjualan.
Perbaikan temuan **AUDIT_LIKE_HOTEL_GO_LIVE.md** (Paket A–C dan sebagian D) sudah dimasukkan.

## Kebutuhan
PHP ≥ 8.3 (ext: mbstring, pdo_mysql, fileinfo, gd/exif opsional), Composer, MySQL/MariaDB.

## Instalasi
```bash
composer install --no-dev --optimize-autoloader
cp .env.example .env && php artisan key:generate
# isi DB_*, MIDTRANS_*, ADMIN_WA, APP_URL di .env
php artisan migrate --force
php artisan db:seed --force          # membuat admin awal (password acak ditampilkan SEKALI, atau isi ADMIN_SEED_PASSWORD)
php artisan storage:link
```
Document root web server = folder `public/`. Cron (untuk kedaluwarsa booking otomatis):
```
* * * * * cd /path/ke/proyek && php artisan schedule:run >> /dev/null 2>&1
```

## Migrasi data dari aplikasi lama
Skema tabel **sama** dengan `likehotel_db` lama (+ kolom/tabel baru), jadi data lama bisa diimpor:
```bash
php artisan migrate
php artisan legacy:import /path/likehotel_db.sql     # hanya perintah INSERT; menolak jika tabel sudah berisi (pakai --force)
cp -r uploads/apartments/* storage/app/public/apartments/
cp -r uploads/sale_units/* storage/app/public/sale_units/
php artisan db:seed --force                           # admin (tabel admins di dump masih kosong)
```
Hash password owner lama (`$2y$`) langsung kompatibel. Booking lama otomatis diberi `access_token` baru untuk URL invoice.
> Catatan: perintah impor sudah diuji pada dump `likehotel_db.sql` (21 booking, 6 apartemen, 2 owner, 3 unit dijual, dst.).

## Midtrans
- Isi `MIDTRANS_SERVER_KEY`, `MIDTRANS_CLIENT_KEY`, `MIDTRANS_IS_PRODUCTION` di `.env` (jangan di-commit).
- **Payment Notification URL** di dashboard Midtrans: `https://DOMAIN/midtrans/notification` (harus HTTPS publik).
- Status "dibayar" hanya berasal dari notifikasi Midtrans yang tervalidasi atau tombol *Lunas* admin. Halaman success/pending hanya tampilan.

## Pemetaan URL lama → baru
| Lama | Baru |
|---|---|
| `public/index.php`, `apartments.php`, `detail.php?id=` | `/`, `/apartments`, `/apartments/{id}` |
| `booking.php?id=` | `/apartments/{id}/booking` |
| `invoice.php?code=` | `/invoice/{token-acak}` (cek via `/cek-booking` dengan kode + email/WA) |
| `payment.php`, `create_snap.php`, `midtrans_notification.php` | `/invoice/{token}/pay`, `/invoice/{token}/snap`, `/midtrans/notification` |
| `sale_units.php`, `sale_detail.php`, `sale_request(s).php` | `/jual-beli`, `/jual-beli/{id}`, `/titip-jual` (dua file duplikat digabung) |
| `owner/*.php` | `/owner/login`, `/owner/dashboard`, `/owner/apartments`, `/owner/bookings`, `/owner/settlement` |
| `admin/*.php` | `/admin/...` (dashboard, apartments, owners, bookings, commission, settlements, reports, promos, sale-units, sale-requests, sale-commissions) |

## Perbaikan audit yang sudah diterapkan
| Temuan audit | Penanganan |
|---|---|
| SQL tidak lengkap | Migrasi lengkap seluruh tabel/kolom/index/FK |
| `success.php` bisa menandai lunas | Dihapus perilakunya; hanya redirect ke invoice |
| Callback Midtrans menerima signature kosong | Signature wajib & `hash_equals`; cek order, nominal; idempoten; booking lunas tak bisa diturunkan; semua notifikasi dicatat di `midtrans_logs` |
| Login admin hard-coded | Tabel `admins`, hash bcrypt, seeder dari `.env`, tanpa password di HTML |
| Aksi lewat GET tanpa CSRF | Semua aksi ubah data `POST/PUT/DELETE` + CSRF (callback Midtrans dikecualikan) |
| Upload hanya cek ekstensi | Validasi MIME `jpg/jpeg/png/webp`, maks 4 MB, nama acak, ekstensi dari MIME asli, disimpan di `storage` |
| URL WhatsApp/localhost, nomor admin tersebar | `config/likehotel.php` + `.env`; link invoice memakai `route()` |
| Booking pending memblokir kalender selamanya | Kedaluwarsa `BOOKING_PENDING_EXPIRY_MINUTES` (default 120): tidak memblokir + command `bookings:expire` (terjadwal tiap 5 menit) |
| Kode booking rawan bentrok | `LH`+waktu+4 karakter acak, unik, retry; cek bentrok + insert dalam satu transaksi dengan row lock |
| Invoice publik bisa ditebak | Token acak 40 karakter; `/cek-booking` butuh kode **dan** email/WA |
| Owner langsung aktif / email ganda | Status awal `pending` (disetujui admin di *Kelola Owner*), validasi email unik & password ≥ 8 |
| Laporan memakai `created_at` untuk pencairan | Pencairan dilaporkan per `paid_at`; opsi basis tanggal booking dibuat / check-in; ekspor **CSV** asli (bukan HTML `.xls`) |
| Target dashboard hard-coded | `TARGET_*` di `.env` |
| Rate limiting | `throttle` pada login, registrasi, booking, titip jual |
| Audit log | Tabel `activity_logs` (lunas/batal, settlement, status owner, notifikasi Midtrans) |
| `crm.css.css`, sidebar tidak konsisten | `public/css/crm.css`; satu layout panel untuk semua halaman |

Perilaku lain: pencarian lokasi/tanggal di beranda & daftar apartemen kini berfungsi (sebelumnya hanya tampilan); transisi settlement divalidasi di service (status `paid` tidak bisa ditolak lagi) dan memakai row lock agar saldo tidak bisa dicairkan ganda.

## Alur CRM titip jual
Halaman **CRM Titip Jual** → klik *Detail* pada lead (`/admin/sale-requests/{id}`):
- Stepper status `baru → follow up → survey → foto unit → listing → negosiasi → closing → selesai` (+ `batal`), perubahan status dengan catatan.
- **Timeline** semua aktivitas (pengajuan masuk, status, catatan, survey, penawaran, foto, publish, closing) lengkap dengan admin pelaku.
- **Jadwal survey** (status otomatis maju ke *survey*), **penawaran pembeli** (maju ke *negosiasi*), **foto unit** (maju ke *foto unit*).
- **Publikasikan** lead menjadi listing di `/jual-beli` (data & foto disalin; hanya sekali).
- **Closing**: membuat data *Komisi Penjualan* (terhubung ke lead dan unit, invoice `CLS#####`) dan menandai unit `terjual` (hilang dari halaman publik); hanya sekali per lead.
- Daftar CRM: filter per status (klik kartu statistik) dan pencarian pemilik/apartemen/WA.
Perubahan DB: migrasi `2026_01_02_000001_crm_workflow` (tabel `sale_request_events`, `sale_request_photos`, + 4 kolom di `sale_requests`). Foto lead disimpan di `storage/app/public/sale_requests/`.

## Laporan Penjualan
Menu **Laporan Penjualan** (`/admin/sales-report`): rekap closing dari Komisi Penjualan per periode (default 1 Januari s/d hari ini) — jumlah closing, total harga deal, komisi (+ rata-rata %), biaya lain, hak penjual; rekap per bulan, per apartemen (urut harga deal), dan detail closing dengan link invoice. Closing berstatus `batal` dikecualikan kecuali dicentang. Ekspor CSV tersedia (ringkasan + rekap + detail).

## Belum dikerjakan
UAT end-to-end dengan Midtrans Sandbox di staging HTTPS.

## Tes
```bash
php artisan test      # 42 tes: booking/bentrok/promo/kedaluwarsa, Midtrans, settlement, akses & CSRF, alur CRM, render semua halaman
```
