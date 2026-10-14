<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleRequest extends BaseModel
{
    public const STATUSES = [
        'baru' => '🆕 Baru',
        'follow up' => '📞 Follow Up',
        'survey' => '🏠 Survey',
        'foto unit' => '📷 Foto Unit',
        'listing' => '📢 Listing',
        'negosiasi' => '🤝 Negosiasi',
        'closing' => '💵 Closing',
        'selesai' => '✅ Selesai',
        'batal' => '❌ Batal',
    ];

    public const COLORS = [
        'baru' => '#b45309', 'follow up' => '#1d4ed8', 'survey' => '#7c3aed', 'foto unit' => '#0f766e',
        'listing' => '#087a35', 'negosiasi' => '#be123c', 'closing' => '#15803d', 'selesai' => '#4b5563', 'batal' => '#991b1b',
    ];

    protected function casts(): array
    {
        return ['expected_price' => 'float', 'last_offer' => 'float', 'survey_at' => 'datetime'];
    }

    public function events(): HasMany
    {
        return $this->hasMany(SaleRequestEvent::class)->orderByDesc('id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(SaleRequestPhoto::class)->orderBy('id');
    }

    public function saleUnit(): BelongsTo
    {
        return $this->belongsTo(SaleUnit::class);
    }

    public function saleCommission(): BelongsTo
    {
        return $this->belongsTo(SaleCommission::class);
    }

    public function isFinal(): bool
    {
        return in_array($this->status, ['selesai', 'batal'], true);
    }
}
