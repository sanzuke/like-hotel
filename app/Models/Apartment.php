<?php

namespace App\Models;

use App\Support\ImageUploader;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Apartment extends BaseModel
{
    public const FOLDER = 'apartments';

    protected function casts(): array
    {
        return ['price_per_night' => 'float'];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ApartmentImage::class)->orderBy('id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function scopeActive($q)
    {
        return $q->where('status', 'aktif');
    }

    public function coverUrl(): ?string
    {
        return ImageUploader::url(self::FOLDER, $this->image);
    }

    /** Cover + semua foto galeri, tanpa duplikat. */
    public function galleryUrls(): array
    {
        $names = array_filter([$this->image]);

        foreach ($this->images as $img) {
            $names[] = $img->image;
        }

        return array_map(fn ($n) => ImageUploader::url(self::FOLDER, $n), array_values(array_unique($names)));
    }
}
