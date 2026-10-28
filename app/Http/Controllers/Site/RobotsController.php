<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;

class RobotsController extends Controller
{
    public function __invoke()
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /owner',
            'Disallow: /b/',
            'Disallow: /midtrans/',
            'Disallow: /cek-booking',
            'Disallow: /*/booking$',
            '',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
