<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleRequestEvent extends BaseModel
{
    public const ICONS = [
        'masuk' => '🆕', 'status' => '🔄', 'catatan' => '📝', 'survey' => '🏠',
        'negosiasi' => '🤝', 'foto' => '📷', 'publish' => '📢', 'closing' => '💵',
    ];

    protected function casts(): array
    {
        return ['amount' => 'float'];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function icon(): string
    {
        return self::ICONS[$this->type] ?? '•';
    }
}
