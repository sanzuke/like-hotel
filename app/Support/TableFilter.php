<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Pembaca filter tabel dari query string.
 * Nilai yang tidak valid (kosong, array, di luar daftar) diabaikan dan dikembalikan sebagai '' —
 * tidak pernah melempar error validasi, supaya URL filter yang rusak tidak memicu 500 atau popup form.
 */
final class TableFilter
{
    /** Teks bebas, dipangkas dan dibatasi panjangnya. */
    public static function text(Request $request, string $key, int $max = 100): string
    {
        $v = $request->query($key);

        return is_string($v) ? mb_substr(trim($v), 0, $max) : '';
    }

    /** Salah satu dari $allowed (perbandingan ketat), selain itu ''. */
    public static function enum(Request $request, string $key, array $allowed): string
    {
        $v = self::text($request, $key, 50);

        return in_array($v, $allowed, true) ? $v : '';
    }

    /** ID numerik (string digit), selain itu ''. */
    public static function id(Request $request, string $key): string
    {
        $v = self::text($request, $key, 20);

        return ctype_digit($v) ? $v : '';
    }

    /** Membuang filter kosong; hasilnya dipakai view untuk mengisi ulang input dan menampilkan tombol Reset. */
    public static function active(array $filters): array
    {
        return array_filter($filters, fn ($v) => $v !== '');
    }
}
