<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ImportLegacyData extends Command
{
    protected $signature = 'likehotel:import-legacy
        {file=../Reff/likehotel_db.sql : Path dump SQL aplikasi lama (relatif terhadap folder proyek)}
        {--wipe : Kosongkan tabel bisnis di database baru sebelum impor}
        {--keep-stale : Jangan batalkan booking lama yang belum dibayar dan sudah kedaluwarsa}';

    protected $description = 'Impor data dari dump SQL aplikasi PHP lama ke database Laravel';

    /** Urutan penting karena foreign key. Admin tidak diimpor: aplikasi lama tidak menyimpannya. */
    private const TABLES = [
        'owners', 'apartments', 'apartment_images', 'promos', 'bookings', 'settlements',
        'sale_requests', 'sale_units', 'sale_unit_images', 'sale_commissions',
    ];

    private const TEMP_DB = 'likehotel_legacy_import';

    public function handle(): int
    {
        $path = realpath($this->argument('file')) ?: realpath(base_path($this->argument('file')));

        if (! $path || ! is_file($path)) {
            $this->error('File dump tidak ditemukan: '.$this->argument('file'));

            return self::FAILURE;
        }

        $filled = collect(self::TABLES)->filter(fn ($t) => DB::table($t)->exists());

        if ($filled->isNotEmpty() && ! $this->option('wipe')) {
            $this->error('Tabel berikut sudah berisi data: '.$filled->implode(', '));
            $this->line('Jalankan ulang dengan --wipe untuk mengosongkannya terlebih dahulu.');

            return self::FAILURE;
        }

        if ($filled->isNotEmpty() && ! $this->confirm('Semua data bisnis di database baru akan DIHAPUS lalu diganti data lama. Lanjutkan?')) {
            return self::FAILURE;
        }

        try {
            $this->loadDumpIntoTempDatabase($path);
            $this->wipeTargetTables();
            DB::transaction(fn () => $this->copyData());
        } catch (Throwable $e) {
            $this->error('Impor gagal: '.$e->getMessage());

            return self::FAILURE;
        } finally {
            DB::connection('legacy_import')->disconnect();
            DB::statement('DROP DATABASE IF EXISTS `'.self::TEMP_DB.'`');
        }

        $this->checkImages();
        $this->newLine();
        $this->info('Impor selesai.');

        return self::SUCCESS;
    }

    private function loadDumpIntoTempDatabase(string $path): void
    {
        $this->info('Memuat dump ke database sementara...');

        DB::statement('DROP DATABASE IF EXISTS `'.self::TEMP_DB.'`');
        DB::statement('CREATE DATABASE `'.self::TEMP_DB.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

        config(['database.connections.legacy_import' => array_merge(
            config('database.connections.mysql'),
            ['database' => self::TEMP_DB],
        )]);

        // PDO mysql menjalankan banyak statement sekaligus, sehingga seluruh dump bisa dieksekusi apa adanya.
        DB::connection('legacy_import')->getPdo()->exec(file_get_contents($path));
    }

    /** TRUNCATE menyebabkan commit implisit, jadi dilakukan sebelum transaksi impor. */
    private function wipeTargetTables(): void
    {
        if (! $this->option('wipe')) {
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach (array_reverse(self::TABLES) as $t) {
            DB::table($t)->truncate();
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    private function legacy(string $table)
    {
        return DB::connection('legacy_import')->table($table);
    }

    private function copyData(): void
    {
        $expiryHours = (int) config('likehotel.pending_expiry_hours');
        $stale = 0;

        foreach (self::TABLES as $table) {
            $count = 0;

            $this->legacy($table)->orderBy('id')->each(function ($row) use ($table, $expiryHours, &$count, &$stale) {
                $data = (array) $row;
                $created = $data['created_at'] ?? now();
                $data['updated_at'] = $created;

                if ($table === 'bookings') {
                    $data['access_token'] = Str::random(40);
                    $data['paid_at'] = $data['booking_status'] === 'dibayar' ? $created : null;
                    $data['expires_at'] = null;

                    // Booking lama yang tak pernah dibayar akan memblokir kalender selamanya; batalkan.
                    if (! $this->option('keep-stale')
                        && $data['booking_status'] === 'baru'
                        && now()->diffInHours($created, true) > $expiryHours) {
                        $data['booking_status'] = 'batal';
                        $data['payment_status'] = 'kedaluwarsa';
                        $stale++;
                    }
                }

                DB::table($table)->insert($data);
                $count++;
            });

            $this->line(sprintf('  %-18s %d baris', $table, $count));
        }

        if ($stale > 0) {
            $this->warn("{$stale} booking lama yang belum dibayar ditandai batal (kedaluwarsa). Gunakan --keep-stale untuk mempertahankannya.");
        }
    }

    /** Peringatkan jika file foto yang dirujuk tidak ada di storage/app/public. */
    private function checkImages(): void
    {
        $missing = collect();
        $refs = [
            'apartments' => DB::table('apartments')->whereNotNull('image')->pluck('image')
                ->merge(DB::table('apartment_images')->pluck('image')),
            'sale_units' => DB::table('sale_units')->whereNotNull('image')->pluck('image')
                ->merge(DB::table('sale_unit_images')->pluck('image')),
        ];

        foreach ($refs as $dir => $files) {
            foreach ($files->unique() as $file) {
                if (! Storage::disk('public')->exists("{$dir}/{$file}")) {
                    $missing->push("{$dir}/{$file}");
                }
            }
        }

        if ($missing->isNotEmpty()) {
            $this->warn('File foto belum ada di storage/app/public:');
            $missing->each(fn ($f) => $this->line("  - {$f}"));
            $this->line('Salin folder uploads lama ke storage/app/public (apartments/, sale_units/).');
        } else {
            $this->info('Semua file foto yang dirujuk tersedia.');
        }
    }
}
