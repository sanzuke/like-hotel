<?php

namespace App\Models;

use App\Models\Concerns\HasSeoSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Apartment extends Model
{
    use HasSeoSlug;

    protected function seoSlugSource(): string
    {
        return $this->name.' '.$this->type;
    }

    protected $fillable = [
        'owner_id', 'owner_name', 'name', 'location', 'type',
        'price_per_night', 'facilities', 'description', 'image', 'status',
    ];

    protected function casts(): array
    {
        return ['price_per_night' => 'decimal:2'];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ApartmentImage::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'aktif');
    }
}
