<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'actor_type', 'actor_id', 'actor_name', 'action', 'subject_type', 'subject_id',
        'description', 'properties', 'ip',
    ];

    protected function casts(): array
    {
        return ['properties' => 'array'];
    }
}
