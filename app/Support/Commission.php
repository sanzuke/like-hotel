<?php

namespace App\Support;

use App\Models\Setting;

/** Persentase komisi Like Hotel. Nilai dari pengaturan admin; bila belum diatur, dari config/.env. */
class Commission
{
    public const RENT_KEY = 'commission.rent_percent';

    public const SALE_KEY = 'commission.sale_percent';

    /** Komisi sewa dalam persen, mis. 15. */
    public static function rentPercent(): float
    {
        $v = Setting::read(self::RENT_KEY);

        return $v !== null ? (float) $v : round(config('likehotel.commission_rate') * 100, 2);
    }

    /** Komisi sewa untuk owner tertentu (persen): tarif khusus owner bila ada, selain itu tarif umum. */
    public static function rentPercentFor(?\App\Models\Owner $owner): float
    {
        return $owner?->commission_percent ?? self::rentPercent();
    }

    /** Komisi sewa sebagai pecahan, mis. 0.15. */
    public static function rentRate(): float
    {
        return self::rentPercent() / 100;
    }

    /** Komisi jual/beli default (persen) untuk form closing. */
    public static function salePercent(): float
    {
        $v = Setting::read(self::SALE_KEY);

        return $v !== null ? (float) $v : (float) config('likehotel.sale_commission_percent');
    }
}
