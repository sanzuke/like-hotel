<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Tabel lama hanya punya created_at (tanpa updated_at). */
abstract class BaseModel extends Model
{
    const UPDATED_AT = null;

    protected $guarded = [];
}
