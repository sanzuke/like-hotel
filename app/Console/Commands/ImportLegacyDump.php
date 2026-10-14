<?php

namespace App\Console\Commands;

use App\Models\Booking;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ImportLegacyDump extends Command
{
    protected $signature = 'legacy:import {file : Path ke dump SQL lama (likehotel_db.sql)} {--force : Impor walau tabel tujuan sudah berisi data}';

    protected $description = 'Impor data (hanya perintah INSERT) dari dump SQL aplikasi PHP lama ke skema Laravel';

    public function handle(): int
    {
        $file = $this->argument('file');

        if (! is_file($file)) {
            $this->error("File tidak ditemukan: {$file}");

            return self::FAILURE;
        }

        $inserts = array_values(array_filter(
            $this->splitStatements(file_get_contents($file)),
            fn ($s) => preg_match('/^INSERT\s+INTO\s+`?(\w+)`?/i', $s)
        ));

        $tables = collect($inserts)->map(fn ($s) => preg_match('/^INSERT\s+INTO\s+`?(\w+)`?/i', $s, $m) ? $m[1] : null)->unique();

        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                $this->error("Tabel '{$table}' tidak ada. Jalankan `php artisan migrate` dulu.");

                return self::FAILURE;
            }

            if (! $this->option('force') && DB::table($table)->exists()) {
                $this->error("Tabel '{$table}' sudah berisi data. Gunakan --force bila yakin.");

                return self::FAILURE;
            }
        }

        Schema::disableForeignKeyConstraints();

        try {
            DB::transaction(function () use ($inserts) {
                foreach ($inserts as $sql) {
                    DB::unprepared($sql);
                }
            });
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $this->info(count($inserts).' perintah INSERT diimpor ('.$tables->implode(', ').').');

        // Booking lama belum punya token rahasia untuk URL invoice publik.
        $n = 0;
        Booking::whereNull('access_token')->each(function (Booking $b) use (&$n) {
            $b->forceFill(['access_token' => Str::random(40)])->save();
            $n++;
        });
        $this->info("{$n} booking lama diberi access_token baru.");

        // Owner dari dump lama tetap berstatus asli (aktif); akun baru default 'pending'.
        $this->line('Selesai. Salin folder uploads lama: uploads/apartments/* -> storage/app/public/apartments/, uploads/sale_units/* -> storage/app/public/sale_units/');

        return self::SUCCESS;
    }

    /** Pecah SQL menjadi statement per ';' di luar string kutip. */
    private function splitStatements(string $sql): array
    {
        // buang komentar baris "-- ..." dan blok /*! ... */
        $sql = preg_replace('/^--.*$/m', '', $sql);
        $sql = preg_replace('#/\*.*?\*/;?#s', '', $sql);

        $out = [];
        $buf = '';
        $quote = null;
        $len = strlen($sql);

        for ($i = 0; $i < $len; $i++) {
            $c = $sql[$i];

            if ($quote) {
                $buf .= $c;

                if ($c === '\\' && $i + 1 < $len) {
                    $buf .= $sql[++$i];
                } elseif ($c === $quote) {
                    if (($sql[$i + 1] ?? '') === $quote) {
                        $buf .= $sql[++$i];
                    } else {
                        $quote = null;
                    }
                }

                continue;
            }

            if ($c === "'" || $c === '"') {
                $quote = $c;
                $buf .= $c;
            } elseif ($c === ';') {
                $out[] = trim($buf);
                $buf = '';
            } else {
                $buf .= $c;
            }
        }

        if (trim($buf) !== '') {
            $out[] = trim($buf);
        }

        return array_values(array_filter($out));
    }
}
