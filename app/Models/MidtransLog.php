<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MidtransLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['order_id', 'transaction_status', 'result', 'note', 'payload'];
}
