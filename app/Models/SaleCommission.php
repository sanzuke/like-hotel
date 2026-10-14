<?php

namespace App\Models;

class SaleCommission extends BaseModel
{
    protected function casts(): array
    {
        return [
            'deal_price' => 'float', 'commission_value' => 'float', 'commission_amount' => 'float',
            'other_cost' => 'float', 'seller_net_amount' => 'float',
        ];
    }

    /** Hitung komisi & hak penjual dari input form. */
    public static function compute(float $deal, string $type, float $value, float $otherCost): array
    {
        $commission = $type === 'percent' ? round($deal * ($value / 100)) : $value;

        return [
            'commission_amount' => $commission,
            'seller_net_amount' => $deal - $commission - $otherCost,
        ];
    }

    public function number(): string
    {
        return 'CLS'.str_pad($this->id, 5, '0', STR_PAD_LEFT);
    }
}
