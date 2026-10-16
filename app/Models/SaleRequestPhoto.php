<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleRequestPhoto extends Model
{
    public const FOLDER = 'sale_requests';
    public const UPDATED_AT = null;

    protected $fillable = ['sale_request_id', 'image'];

    public function saleRequest(): BelongsTo
    {
        return $this->belongsTo(SaleRequest::class);
    }

    public function url(): string
    {
        return asset('storage/'.self::FOLDER.'/'.$this->image);
    }
}
