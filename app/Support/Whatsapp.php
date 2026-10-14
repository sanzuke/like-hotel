<?php

namespace App\Support;

class Whatsapp
{
    public static function normalize(?string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone ?? '');

        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }

        return $digits;
    }

    public static function link(?string $phone, string $message): string
    {
        $phone = self::normalize($phone) ?: config('likehotel.admin_wa');

        return 'https://wa.me/'.$phone.'?text='.rawurlencode($message);
    }

    public static function admin(string $message): string
    {
        return self::link(config('likehotel.admin_wa'), $message);
    }
}
