<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/** Simpan / hapus gambar di disk "public" (storage/app/public/<folder>). */
class ImageStore
{
    public const RULE = 'image|mimes:jpg,jpeg,png,webp|max:4096';

    /** Sisi terpanjang maksimum (px) dan kualitas hasil kompresi. */
    public const MAX_SIDE = 1600;

    public static function store(UploadedFile $file, string $folder, string $prefix): string
    {
        // Nama acak + ekstensi dari MIME sebenarnya (bukan dari nama file yang dikirim).
        $name = $prefix.'_'.now()->timestamp.'_'.Str::lower(Str::random(8)).'.'.$file->guessExtension();
        $file->storeAs($folder, $name, 'public');
        self::optimize(Storage::disk('public')->path($folder.'/'.$name));

        return $name;
    }

    /**
     * Perkecil (maks. 1600px) dan kompres ulang di tempat agar halaman cepat dimuat.
     * Aman: bila GD tidak ada, gagal, atau hasilnya tidak lebih kecil, berkas asli dibiarkan.
     */
    public static function optimize(string $path): bool
    {
        if (! extension_loaded('gd') || ! is_file($path)) {
            return false;
        }

        try {
            $info = @getimagesize($path);
            if (! $info) {
                return false;
            }
            [$w, $h, $type] = $info;
            $before = filesize($path);

            $src = match ($type) {
                IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
                IMAGETYPE_PNG => @imagecreatefrompng($path),
                IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
                default => false,
            };
            if (! $src) {
                return false;
            }

            // Putar sesuai EXIF agar foto dari ponsel tidak miring setelah metadata dibuang.
            if ($type === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
                $orientation = @exif_read_data($path)['Orientation'] ?? 1;
                $angle = [3 => 180, 6 => -90, 8 => 90][$orientation] ?? 0;
                if ($angle && ($rot = imagerotate($src, $angle, 0))) {
                    $src = $rot;
                    [$w, $h] = [imagesx($src), imagesy($src)];
                }
            }

            $scale = min(1, self::MAX_SIDE / max($w, $h));
            if ($scale < 1) {
                $src = imagescale($src, (int) round($w * $scale), (int) round($h * $scale), IMG_BICUBIC);
            }

            $tmp = $path.'.tmp';
            $ok = match ($type) {
                IMAGETYPE_JPEG => imagejpeg($src, $tmp, 82),
                IMAGETYPE_PNG => (function () use ($src, $tmp) {
                    imagealphablending($src, false);
                    imagesavealpha($src, true);

                    return imagepng($src, $tmp, 9);
                })(),
                IMAGETYPE_WEBP => imagewebp($src, $tmp, 80),
                default => false,
            };
            unset($src);

            if ($ok && is_file($tmp) && filesize($tmp) < $before) {
                rename($tmp, $path);

                return true;
            }
            @unlink($tmp);
        } catch (Throwable) {
            @unlink($path.'.tmp');
        }

        return false;
    }

    public static function delete(?string $name, string $folder): void
    {
        if ($name) {
            Storage::disk('public')->delete($folder.'/'.$name);
        }
    }
}
