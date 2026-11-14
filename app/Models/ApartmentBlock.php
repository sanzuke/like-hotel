<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApartmentBlock extends Model
{
    protected $fillable = ['apartment_id', 'date_from', 'date_to', 'note'];

    protected function casts(): array
    {
        return ['date_from' => 'date', 'date_to' => 'date'];
    }

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    /** Bentrok dengan rentang [checkin, checkout). */
    public function scopeOverlapping($query, $checkin, $checkout)
    {
        return $query->where('date_from', '<', $checkout)->where('date_to', '>', $checkin);
    }
}
