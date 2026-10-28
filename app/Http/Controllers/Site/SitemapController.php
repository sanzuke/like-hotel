<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\SaleUnit;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public function __invoke()
    {
        $xml = Cache::remember('sitemap.xml', 3600, function () {
            $urls = [
                [route('home'), null, '1.0'],
                [route('apartments.index'), null, '0.9'],
                [route('sale-units.index'), null, '0.8'],
                [route('sale-request.create'), null, '0.6'],
                [route('booking.check'), null, '0.3'],
            ];

            foreach (Apartment::active()->get(['id', 'name', 'type', 'updated_at']) as $a) {
                $urls[] = [route('apartments.show', $a), $a->updated_at, '0.8'];
            }
            foreach (SaleUnit::active()->get(['id', 'title', 'apartment_name', 'location', 'updated_at']) as $s) {
                $urls[] = [route('sale-units.show', $s), $s->updated_at, '0.7'];
            }

            return view('site.sitemap', ['urls' => $urls])->render();
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
