<?php

namespace App\Console\Commands;

use App\Support\ImageStore;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class OptimizeImages extends Command
{
    protected $signature = 'likehotel:optimize-images {--dry-run : Hanya hitung perkiraan, jangan ubah berkas}';

    protected $description = 'Perkecil dan kompres foto apartemen/unit dijual yang sudah ada (maks. 1600px) agar halaman lebih cepat';

    public function handle(): int
    {
        $disk = Storage::disk('public');
        $saved = 0;
        $changed = 0;
        $total = 0;

        foreach (['apartments', 'sale_units'] as $dir) {
            foreach ($disk->files($dir) as $file) {
                if (! preg_match('/\.(jpe?g|png|webp)$/i', $file)) {
                    continue;
                }
                $total++;
                $path = $disk->path($file);
                $before = filesize($path);

                if ($this->option('dry-run')) {
                    [$w, $h] = @getimagesize($path) ?: [0, 0];
                    if ($before > 300_000 || max($w, $h) > ImageStore::MAX_SIDE) {
                        $changed++;
                    }

                    continue;
                }

                if (ImageStore::optimize($path)) {
                    clearstatcache(true, $path);
                    $saved += $before - filesize($path);
                    $changed++;
                }
            }
        }

        $this->info($this->option('dry-run')
            ? "{$total} foto diperiksa, {$changed} kandidat dikompres (jalankan tanpa --dry-run untuk menerapkan)."
            : "{$total} foto diperiksa, {$changed} dikompres, hemat ".number_format($saved / 1024, 0, ',', '.').' KB.');

        return self::SUCCESS;
    }
}
