<?php

namespace App\Support;

use Illuminate\Support\Str;

class Seo
{
    /** Judul tab ideal <= 60 karakter; nama brand ditambahkan hanya bila muat. */
    public static function title(string $main, int $max = 60): string
    {
        $suffix = ' | '.config('likehotel.name');
        $main = trim(preg_replace('/\s+/', ' ', $main));

        return Str::length($main.$suffix) <= $max ? $main.$suffix : Str::limit($main, $max - 1, '…');
    }

    /** Alt teks gambar: deskriptif dan memuat kata kunci (nama + jenis + lokasi). */
    public static function alt(string ...$parts): string
    {
        return trim(implode(' ', array_filter(array_map('trim', $parts))));
    }
}
