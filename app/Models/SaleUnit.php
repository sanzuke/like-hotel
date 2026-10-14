<?php

namespace App\Models;

use App\Support\ImageUploader;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleUnit extends BaseModel
{
    public const FOLDER = 'sale_units';

    protected function casts(): array
    {
        return ['price' => 'float'];
    }

    public function images(): HasMany
    {
        return $this->hasMany(SaleUnitImage::class)->orderBy('id');
    }

    public function scopeActive($q)
    {
        return $q->where('status', 'aktif');
    }

    public function coverUrl(): ?string
    {
        return ImageUploader::url(self::FOLDER, $this->image);
    }

    public function galleryUrls(): array
    {
        $names = array_filter([$this->image]);

        foreach ($this->images as $img) {
            $names[] = $img->image;
        }

        return array_map(fn ($n) => ImageUploader::url(self::FOLDER, $n), array_values(array_unique($names)));
    }
}
