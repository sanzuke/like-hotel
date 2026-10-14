<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Menyimpan foto ke disk "public" (storage/app/public/{folder}).
 * Ekstensi ditentukan dari MIME asli file, bukan nama file dari klien.
 */
class ImageUploader
{
    /** @param  array<UploadedFile>|UploadedFile|null  $files */
    public static function store(array|UploadedFile|null $files, string $folder, string $prefix): array
    {
        $files = is_array($files) ? $files : array_filter([$files]);
        $names = [];

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }

            $ext = $file->guessExtension() ?: 'jpg';
            $ext = $ext === 'jpeg' ? 'jpeg' : $ext;
            $name = $prefix.'_'.time().'_'.Str::lower(Str::random(8)).'.'.$ext;

            Storage::disk('public')->putFileAs($folder, $file, $name);
            $names[] = $name;
        }

        return $names;
    }

    public static function delete(string $folder, ?string $name): void
    {
        if ($name) {
            Storage::disk('public')->delete($folder.'/'.basename($name));
        }
    }

    public static function url(string $folder, ?string $name): ?string
    {
        // asset() mengikuti host/subfolder request saat ini, tidak terkunci ke APP_URL.
        return $name ? asset('storage/'.$folder.'/'.basename($name)) : null;
    }

    /** Aturan validasi standar untuk input file foto. */
    public static function rules(): array
    {
        return ['image', 'mimes:'.implode(',', config('likehotel.upload.mimes')), 'max:'.config('likehotel.upload.max_kb')];
    }
}
