<?php

namespace App\Models;

class Promo extends BaseModel
{
    protected function casts(): array
    {
        return ['value' => 'float', 'expired_at' => 'date:Y-m-d'];
    }

    public function isUsable(): bool
    {
        return $this->status === 'aktif'
            && (! $this->expired_at || $this->expired_at->endOfDay()->isFuture());
    }

    public function discountFor(float $subtotal): float
    {
        $discount = $this->type === 'percent'
            ? round($subtotal * ($this->value / 100))
            : $this->value;

        return min($discount, $subtotal);
    }
}
