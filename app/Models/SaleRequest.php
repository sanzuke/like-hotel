<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleRequest extends Model
{
    /** Tahapan CRM titip jual, berurutan. */
    public const STAGES = [
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

    protected $fillable = [
        'owner_name', 'owner_phone', 'apartment_name', 'location', 'unit_type',
        'size', 'expected_price', 'agreed_price', 'last_offer', 'notes', 'status', 'survey_at', 'sale_unit_id',
    ];

    protected function casts(): array
    {
        return [
            'expected_price' => 'decimal:2',
            'agreed_price' => 'decimal:2',
            'last_offer' => 'decimal:2',
            'survey_at' => 'datetime',
        ];
    }

    public function timeline(): HasMany
    {
        return $this->hasMany(SaleRequestNote::class)->latest('id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(SaleRequestPhoto::class)->orderBy('id');
    }

    public function saleUnit(): BelongsTo
    {
        return $this->belongsTo(SaleUnit::class);
    }

    public function commission(): HasMany
    {
        return $this->hasMany(SaleCommission::class);
    }

    /** Catat entri timeline sekaligus siapa penulisnya. */
    public function addNote(string $type, string $body, ?float $amount = null): SaleRequestNote
    {
        return $this->hasMany(SaleRequestNote::class)->create([
            'type' => $type,
            'body' => $body,
            'amount' => $amount,
            'author' => auth('admin')->user()?->name ?? 'Sistem',
        ]);
    }
}
