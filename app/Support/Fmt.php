<?php

namespace App\Support;

class Fmt
{
    public static function rupiah(float|int|string|null $n): string
    {
        return 'Rp'.number_format((float) $n, 0, ',', '.');
    }

    /** Warna teks status pembayaran / pencairan. */
    public static function statusColor(?string $status): string
    {
        $s = strtolower((string) $status);

        return match (true) {
            str_contains($s, 'lunas'), $s === 'paid', $s === 'closing', $s === 'aktif' => '#087a35',
            $s === 'approved' => '#1d4ed8',
            str_contains($s, 'pending'), str_contains($s, 'menunggu'), str_contains($s, 'snap') => '#b45309',
            str_contains($s, 'batal'), str_contains($s, 'gagal'), $s === 'rejected' => '#991b1b',
            default => '#6b7280',
        };
    }

    public static function digits(?string $phone): string
    {
        return preg_replace('/\D/', '', (string) $phone);
    }

    /** Normalisasi nomor ke format internasional Indonesia (62...). */
    public static function normalizeWa(?string $phone): string
    {
        $p = self::digits($phone);

        if (str_starts_with($p, '0')) {
            return '62'.substr($p, 1);
        }

        return $p;
    }

    public static function waLink(?string $phone, string $message): string
    {
        return 'https://wa.me/'.self::normalizeWa($phone).'?text='.rawurlencode($message);
    }

    public static function adminWa(): string
    {
        return config('likehotel.admin_wa');
    }

    /** Nomor admin untuk ditampilkan, mis. 0821 2924 8953. */
    public static function adminWaDisplay(): string
    {
        $local = '0'.substr(self::normalizeWa(self::adminWa()), 2);

        return trim(chunk_split($local, 4, ' '));
    }
}
