<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLog extends BaseModel
{
    /** Catat aksi penting; aktor diambil otomatis dari guard yang sedang login. */
    public static function record(string $action, ?Model $subject = null, array $meta = []): void
    {
        $actor = null;
        $type = 'system';

        foreach (['admin', 'owner'] as $guard) {
            if (Auth::guard($guard)->check()) {
                $actor = Auth::guard($guard)->id();
                $type = $guard;
                break;
            }
        }

        static::create([
            'actor_type' => $type,
            'actor_id' => $actor,
            'action' => $action,
            'subject_type' => $subject ? class_basename($subject) : null,
            'subject_id' => $subject?->getKey(),
            'meta' => $meta ? json_encode($meta) : null,
        ]);
    }
}
