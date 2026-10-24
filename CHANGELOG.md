# Changelog

Format: tanggal, ringkasan per area. Entri terbaru di atas.

## 2026-10-24 — UI panel admin & owner

### Layout panel (`layouts/panel.blade.php`)
- Header atas: logo, ikon notifikasi (popup placeholder, titik merah statis), profil user login dan logout. Notifikasi belum terhubung ke data.
- Sidebar menempel (sticky) di bawah header, bisa di-scroll sendiri bila menu panjang; di layar sempit (<=800px) kembali normal.
- Ikon SVG inline (gaya Lucide) di setiap menu; menu halaman yang sedang dibuka disorot (`aria-current="page"`), termasuk halaman turunan (edit/detail).
- Tombol Logout dan judul "LIKE HOTEL"/"OWNER" dipindah dari sidebar ke header.
- Skrip dialog/popup dipusatkan di layout.

### Komponen baru
- `<x-modal id title>` (`components/modal.blade.php`): popup form berbasis `<dialog>`, terbuka ulang otomatis bila validasi gagal.
- `partials/pagination.blade.php`: ringkasan "Menampilkan x–y dari z data" + tombol halaman bernomor. Dipakai semua tabel, termasuk Audit Log dan Log Midtrans (menggantikan tombol Sebelumnya/Berikutnya).
- `App\Support\TableFilter`: pembaca filter query string; nilai tidak valid (array, enum tak dikenal) diabaikan tanpa error validasi.

### Daftar + tombol tambah (popup) + pagination 10 baris
Kelola Apartment, Promo, Unit Dijual, Komisi Penjualan (admin); Unit Saya, Pencairan Dana "Ajukan Pencairan" (owner).

### Pagination saja
Kelola Owner, Booking & Pembayaran, CRM Titip Jual, Pencairan Owner, Audit Log, Log Midtrans (admin); Booking Unit Saya (owner).

### Filter server-side di baris tabel
| Halaman | Parameter |
|---|---|
| Admin Apartment | `name`, `owner` (id / `none`), `location`, `type`, `status` |
| Admin Owner | `name`, `email`, `status` |
| Admin Booking | `q`, `apartment`, `payment`, `booking` |
| Admin Promo | `code`, `type`, `status` |
| Admin Unit Dijual | `q`, `apartment`, `seller`, `status` |
| Admin Komisi Penjualan | `seller`, `apartment`, `status` |
| Admin CRM Titip Jual | `q`, `unit`, `status` |
| Admin Pencairan Owner (riwayat) | `owner`, `bank`, `status` |
| Owner Unit Saya | `name`, `location`, `type`, `status` |
| Owner Booking | `q`, `apartment`, `payment` |
| Owner Pencairan (riwayat) | `status` |

Filter ikut terbawa saat pindah halaman (`withQueryString()`). Kartu statistik tetap menghitung seluruh data.

### Dashboard admin
- "Aktivitas Terbaru" dirapikan: tiga kartu sama tinggi, ikon, waktu relatif (tanggal lengkap di tooltip), nominal, badge status, link "Lihat semua".

### Lainnya
- Statistik Booking, CRM, dan Komisi Penjualan dihitung di database, bukan dari koleksi yang dimuat (agar benar setelah pagination/filter).
- `Fmt::statusColor`: status "kedaluwarsa" berwarna merah.
- Versi CSS di layout panel: `?v=31`.

### Server (zukelabs, `/opt/likehotel`)
- Data lama diimpor dari `Reff/likehotel_db.sql` (`php artisan likehotel:import-legacy --wipe`): 2 owner, 6 apartment, 2 promo, 21 booking (16 booking lama belum dibayar otomatis dibatalkan/kedaluwarsa), 6 settlement, 2 titip jual, 3 unit dijual, 1 closing. Foto disalin ke `storage/app/public/{apartments,sale_units}`.
- Akun login uji dibuat: admin `developer`, owner `owner@likehotelsewa.com` (password diberikan terpisah, bukan di repo). Admin `admin` dan owner legacy tidak diubah.
- Setelah deploy file view, jalankan `php artisan view:clear` lalu `view:cache`.

## Isu yang diketahui
- Pesan validasi tampil sebagai kunci (`validation.required`); folder `lang/` belum ada.
- `redirect()->intended()` memakai satu session key untuk guard admin dan owner, sehingga login satu peran setelah mengunjungi halaman peran lain bisa mengarah ke halaman login yang salah.
- Server dan repo sempat berbeda untuk form login admin (field `login`); sekarang sinkron dengan commit ini.
- Notifikasi di header masih placeholder.
- Halaman detail apartment dan alur booking publik belum diuji E2E, begitu juga POST pembayaran Midtrans.
