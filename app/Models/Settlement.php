<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Settlement extends BaseModel
{
    protected function casts(): array
    {
        return ['amount' => 'float', 'paid_at' => 'datetime'];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    public function statusColor(): string
    {
        return ['paid' => '#087a35', 'approved' => '#1d4ed8', 'pending' => '#b45309', 'rejected' => '#991b1b'][$this->status] ?? '#6b7280';
    }
}
