<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleUnit extends Model
{
    protected $fillable = [
        'owner_id', 'title', 'apartment_name', 'location', 'unit_type', 'floor', 'size',
        'certificate', 'furnishing', 'price', 'description', 'image',
        'seller_name', 'seller_phone', 'status',
    ];

    protected function casts(): array
    {
        return ['price' => 'decimal:2'];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(SaleUnitImage::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'aktif');
    }
}
