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

    /** Kategori unit. Tipe kamar (Studio, 1 Bedroom, dst.) tetap di kolom type. */
    public const CATEGORIES = [
        'apartment' => 'Apartemen',
        'hotel' => 'Hotel',
    ];

    protected $fillable = [
        'owner_id', 'owner_name', 'name', 'location', 'type', 'category',
        'price_per_night', 'price_per_month', 'price_per_year', 'facilities', 'description', 'image', 'status',
        'ad_priority', 'ad_until', 'lat', 'lng',
    ];

    protected function casts(): array
    {
        return ['price_per_night' => 'decimal:2', 'price_per_month' => 'decimal:2', 'price_per_year' => 'decimal:2', 'ad_until' => 'date', 'lat' => 'float', 'lng' => 'float'];
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

    public function blocks(): HasMany
    {
        return $this->hasMany(ApartmentBlock::class);
    }

    /** Iklan berbayar masih berlaku. */
    public function isSponsored(): bool
    {
        return $this->ad_priority > 0 && (! $this->ad_until || $this->ad_until->gte(today()));
    }

    /** Urutan tampil: unit beriklan aktif (prioritas tertinggi) lebih dulu, lalu yang terbaru. */
    public function scopeRanked($query)
    {
        return $query
            ->orderByRaw('CASE WHEN ad_priority > 0 AND (ad_until IS NULL OR ad_until >= ?) THEN ad_priority ELSE 0 END DESC', [today()->toDateString()])
            ->latest('id');
    }

    /** Unit yang kosong di seluruh rentang [checkin, checkout): tanpa booking aktif dan tanpa tanggal ditutup owner. */
    public function scopeFreeBetween($query, $checkin, $checkout)
    {
        return $query
            ->whereNotIn('id', Booking::blocking()->overlapping($checkin, $checkout)->select('apartment_id'))
            ->whereNotIn('id', ApartmentBlock::overlapping($checkin, $checkout)->select('apartment_id'));
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'aktif');
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? self::CATEGORIES['apartment'];
    }
}
