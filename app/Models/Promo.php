<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promo extends Model
{
    protected $fillable = ['code', 'type', 'value', 'status', 'expired_at'];

    protected function casts(): array
    {
        return ['value' => 'decimal:2', 'expired_at' => 'date'];
    }

    /** Promo yang aktif dan belum kedaluwarsa untuk kode tertentu. */
    public static function findValid(string $code): ?self
    {
        return static::where('code', strtoupper(trim($code)))
            ->where('status', 'aktif')
            ->where(fn ($q) => $q->whereNull('expired_at')->orWhere('expired_at', '>=', today()))
            ->first();
    }

    public function discountFor(float $subtotal): float
    {
        $discount = $this->type === 'percent'
            ? round($subtotal * ((float) $this->value / 100))
            : (float) $this->value;

        return min($discount, $subtotal);
    }
}
