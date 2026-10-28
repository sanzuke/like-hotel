# Panduan SEO Like Hotel

Bagian yang sudah dikerjakan di kode ditandai ✅. Bagian yang butuh tindakan pemilik bisnis ditandai ☐.

## 1. SEO On-Page

| Item | Status |
|---|---|
| Judul unik tiap halaman, ≤ 60 karakter (`Seo::title`) | ✅ |
| Meta description unik, ≤ 155 karakter | ✅ |
| Satu `<h1>` per halaman, hierarki h2/h3 berurutan | ✅ |
| URL berkata kunci, mis. `/apartments/5-easton-park-residence-jatinangor-studio`; URL lama dialihkan 301 | ✅ |
| Canonical + Open Graph + Twitter Card | ✅ |
| Schema JSON-LD: LodgingBusiness, WebSite, FAQPage (beranda); Product/Offer + BreadcrumbList (detail) | ✅ |
| Alt teks gambar berisi nama + jenis + lokasi; `width`/`height` mencegah layout bergeser; `loading="lazy"` | ✅ |
| `sitemap.xml` dan `robots.txt` mengikuti domain aktif; halaman transaksi `noindex` | ✅ |
| Unit nonaktif/terjual tidak diindeks (404) | ✅ |

### Riset kata kunci ☐
Gunakan Google Keyword Planner / Ahrefs / Ubersuggest, lalu pakai di judul unit, deskripsi (100 kata pertama), dan nama unit. Kata kunci awal yang disarankan (cek volume dan persaingannya):

- sewa apartemen jatinangor, apartemen dekat unpad, apartemen dekat itb jatinangor
- sewa apartemen harian bandung, sewa apartemen bulanan jatinangor
- apartemen dijual jatinangor, jual apartemen easton park, apartemen dekat kampus
- sewa apartemen bekasi, sewa apartemen margonda depok, sewa apartemen sleman yogyakarta

Tulis deskripsi unit yang unik (jangan disalin antar unit), sebutkan kampus/kantor terdekat dan jaraknya.

### Foto
- Foto baru otomatis diperkecil (maks. 1600 px) dan dikompres saat diunggah.
- Foto lama: jalankan `php artisan likehotel:optimize-images --dry-run` untuk melihat perkiraan, lalu tanpa `--dry-run` untuk menerapkan.
- ☐ Sediakan gambar bagikan `public/images/og-default.jpg` (1200×630 px) untuk tampilan tautan di WhatsApp/Facebook.

## 2. SEO Teknis
- ✅ Responsif/mobile-friendly, font `display=swap`, gambar lazy-load, CSS satu berkas.
- ✅ Alur checkout singkat: pilih unit → data tamu → bayar (QRIS/VA/kartu via Midtrans) → invoice.
- ✅/☐ Server: gzip untuk CSS/JS/JSON/XML dan cache statis 30 hari (lihat bagian *Nginx* di bawah).
- ☐ Ukur dengan PageSpeed Insights (target LCP < 2,5 dtk) dan perbaiki sesuai laporan.

## 3. Off-Page & Promosi ☐
1. **Google Search Console**: tambahkan properti `https://likehotel.zukelabs.web.id` (atau domain final), isi kode verifikasi ke `.env` (`GOOGLE_SITE_VERIFICATION=...`), lalu kirim `https://<domain>/sitemap.xml`.
2. **Google Business Profile** (Google Bisnisku): daftarkan bisnis, isi alamat/area layanan, jam layanan, foto; masukkan tautannya ke `.env` (`SOCIAL_GOOGLE_BUSINESS=...`).
3. **Media sosial**: isi `SOCIAL_INSTAGRAM`, `SOCIAL_TIKTOK`, `SOCIAL_FACEBOOK` di `.env`; tautan otomatis muncul di footer dan schema `sameAs`. Bagikan link unit dan konten edukatif (tips memilih apartemen dekat kampus).
4. **Backlink berkualitas**: direktori properti, blog/komunitas mahasiswa dan alumni, media lokal Jatinangor/Bandung, kemitraan dengan kampus dan komunitas.
5. **Google Ads** (opsional): untuk hasil cepat di posisi "Bersponsor" sambil SEO organik berjalan; mulai dari kata kunci transaksi (sewa apartemen harian + lokasi).

## Nginx (di VPS)
Tambahkan di blok `server` situs:

```nginx
gzip on;
gzip_vary on;
gzip_comp_level 5;
gzip_min_length 1024;
gzip_types text/css application/javascript application/json application/xml text/xml image/svg+xml text/plain;

location ~* \.(css|js|jpg|jpeg|png|webp|svg|ico|woff2)$ {
    expires 30d;
    add_header Cache-Control "public, max-age=2592000";
    access_log off;
}
```
Lalu `sudo nginx -t && sudo systemctl reload nginx`.
