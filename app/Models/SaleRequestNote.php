<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleRequestNote extends Model
{
    /** Jenis entri yang boleh ditulis admin lewat form catatan. */
    public const TYPES = [
        'catatan' => '📝 Catatan',
        'follow up' => '📞 Follow Up',
        'survey' => '🏠 Survey',
        'foto unit' => '📷 Foto Unit',
        'negosiasi' => '🤝 Negosiasi',
    ];

    /** Seluruh label timeline, termasuk entri otomatis dari sistem. */
    public const LABELS = self::TYPES + [
        'penawaran' => '🤝 Penawaran Pembeli',
        'foto' => '📷 Foto',
        'publish' => '📢 Publikasi',
        'closing' => '💵 Closing',
    ];

    protected $fillable = ['sale_request_id', 'type', 'body', 'amount', 'author'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function saleRequest(): BelongsTo
    {
        return $this->belongsTo(SaleRequest::class);
    }
}
