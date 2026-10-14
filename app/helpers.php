<?php

use App\Support\Whatsapp;

if (! function_exists('rupiah')) {
    function rupiah(float|int|string|null $amount): string
    {
        return 'Rp'.number_format((float) $amount, 0, ',', '.');
    }
}

if (! function_exists('wa_link')) {
    function wa_link(?string $phone, string $message): string
    {
        return Whatsapp::link($phone, $message);
    }
}
