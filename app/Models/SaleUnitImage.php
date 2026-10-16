<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleUnitImage extends Model
{
    protected $fillable = ['sale_unit_id', 'image'];

    public function saleUnit(): BelongsTo
    {
        return $this->belongsTo(SaleUnit::class);
    }
}
