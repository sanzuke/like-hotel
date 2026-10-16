<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'actor_type' => 'nullable|in:admin,owner,guest,system',
            'group' => 'nullable|string|max:30',
            'q' => 'nullable|string|max:100',
        ]);

        $logs = AuditLog::query()
            ->when($filters['actor_type'] ?? null, fn ($q, $v) => $q->where('actor_type', $v))
            ->when($filters['group'] ?? null, fn ($q, $v) => $q->where('action', 'like', $v.'.%'))
            ->when($filters['q'] ?? null, fn ($q, $v) => $q->where('description', 'like', '%'.$v.'%'))
            ->latest('id')
            ->paginate(50)
            ->withQueryString();

        return view('admin.audit-logs', [
            'logs' => $logs,
            'filters' => $filters,
            'groups' => AuditLog::query()
                ->selectRaw("substring_index(action, '.', 1) as g")
                ->distinct()->orderBy('g')->pluck('g'),
        ]);
    }
}
