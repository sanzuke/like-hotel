<?php

namespace App\Models;

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
        return ['expected_price' => 'float'];
    }
}
