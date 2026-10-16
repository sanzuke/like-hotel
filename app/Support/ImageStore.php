<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Simpan / hapus gambar di disk "public" (storage/app/public/<folder>). */
class ImageStore
{
    public const RULE = 'image|mimes:jpg,jpeg,png,webp|max:4096';

    public static function store(UploadedFile $file, string $folder, string $prefix): string
    {
        // Nama acak + ekstensi dari MIME sebenarnya (bukan dari nama file yang dikirim).
        $name = $prefix.'_'.now()->timestamp.'_'.Str::lower(Str::random(8)).'.'.$file->guessExtension();
        $file->storeAs($folder, $name, 'public');

        return $name;
    }

    public static function delete(?string $name, string $folder): void
    {
        if ($name) {
            Storage::disk('public')->delete($folder.'/'.$name);
        }
    }
}
