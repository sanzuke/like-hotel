<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Settlement extends Model
{
    protected $fillable = [
        'owner_id', 'amount', 'bank_name', 'bank_account_name', 'bank_account_number',
        'notes', 'admin_notes', 'status', 'paid_at',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'paid_at' => 'datetime'];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }
}
