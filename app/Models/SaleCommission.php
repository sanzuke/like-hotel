<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleCommission extends Model
{
    protected $fillable = [
        'sale_request_id', 'sale_unit_id', 'seller_name', 'apartment_name', 'deal_price',
        'commission_type', 'commission_value', 'commission_amount', 'other_cost',
        'seller_net_amount', 'notes', 'status',
    ];

    protected function casts(): array
    {
        return [
            'deal_price' => 'decimal:2',
            'commission_value' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'other_cost' => 'decimal:2',
            'seller_net_amount' => 'decimal:2',
        ];
    }

    public function saleRequest(): BelongsTo
    {
        return $this->belongsTo(SaleRequest::class);
    }

    public function saleUnit(): BelongsTo
    {
        return $this->belongsTo(SaleUnit::class);
    }

    /** Hitung komisi & bersih penjual dari harga deal. */
    public static function compute(float $deal, string $type, float $value, float $other): array
    {
        $commission = $type === 'percent' ? round($deal * $value / 100) : $value;

        return [
            'commission_amount' => $commission,
            'seller_net_amount' => $deal - $commission - $other,
        ];
    }
}
