<?php

namespace App\Support;

class Geo
{
    /** Jarak garis lurus (km) antara dua koordinat, rumus Haversine. */
    public static function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /** Teks jarak ramah baca, mis. "850 m" atau "2,3 km". */
    public static function label(float $km): string
    {
        return $km < 1 ? (round($km * 20) * 50).' m' : number_format($km, 1, ',', '.').' km';
    }
}
