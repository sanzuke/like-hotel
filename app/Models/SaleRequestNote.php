<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleRequestNote extends Model
{
    /** Jenis entri timeline yang boleh ditulis admin. */
    public const TYPES = [
        'catatan' => '📝 Catatan',
        'follow up' => '📞 Follow Up',
        'survey' => '🏠 Survey',
        'foto unit' => '📷 Foto Unit',
        'negosiasi' => '🤝 Negosiasi',
    ];

    protected $fillable = ['sale_request_id', 'type', 'body', 'author'];

    public function saleRequest(): BelongsTo
    {
        return $this->belongsTo(SaleRequest::class);
    }
}
