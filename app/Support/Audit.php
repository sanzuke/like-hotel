<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Throwable;

/**
 * Pencatat jejak perubahan penting (status booking, pencairan, CRM, login).
 * Pelaku diambil dari guard yang sedang login; tanpa login dicatat sebagai "system"
 * (webhook Midtrans, scheduler) kecuali dipaksa lewat parameter $actor.
 */
class Audit
{
    /**
     * @param  string  $action  contoh: booking.paid, settlement.approved
     * @param  array{type:string,id?:int,name?:string}|null  $actor
     */
    public static function log(string $action, string $description, ?Model $subject = null, array $properties = [], ?array $actor = null): void
    {
        try {
            $actor ??= self::currentActor();

            AuditLog::create([
                'actor_type' => $actor['type'],
                'actor_id' => $actor['id'] ?? null,
                'actor_name' => $actor['name'] ?? null,
                'action' => $action,
                'subject_type' => $subject ? Str::snake(class_basename($subject)) : null,
                'subject_id' => $subject?->getKey(),
                'description' => Str::limit($description, 500, ''),
                'properties' => $properties ?: null,
                'ip' => app()->runningInConsole() ? null : request()->ip(),
            ]);
        } catch (Throwable $e) {
            // Kegagalan pencatatan tidak boleh menggagalkan transaksi utama.
            report($e);
        }
    }

    public static function system(string $action, string $description, ?Model $subject = null, array $properties = []): void
    {
        self::log($action, $description, $subject, $properties, ['type' => 'system', 'name' => 'Sistem']);
    }

    private static function currentActor(): array
    {
        if ($admin = auth('admin')->user()) {
            return ['type' => 'admin', 'id' => $admin->id, 'name' => $admin->name];
        }

        if ($owner = auth('owner')->user()) {
            return ['type' => 'owner', 'id' => $owner->id, 'name' => $owner->name];
        }

        return ['type' => 'system', 'name' => 'Sistem'];
    }
}
