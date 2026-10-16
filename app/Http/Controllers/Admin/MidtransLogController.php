<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MidtransLog;
use Illuminate\Http\Request;

class MidtransLogController extends Controller
{
    public function index(Request $request)
    {
        $f = $request->validate([
            'result' => 'nullable|in:processed,ignored,rejected',
            'q' => 'nullable|string|max:80',
        ]);

        $logs = MidtransLog::query()
            ->when($f['result'] ?? null, fn ($q, $v) => $q->where('result', $v))
            ->when($f['q'] ?? null, fn ($q, $v) => $q->where('order_id', 'like', '%'.$v.'%'))
            ->latest('id')
            ->paginate(50)
            ->withQueryString();

        return view('admin.midtrans-logs', [
            'logs' => $logs,
            'filters' => $f,
            'rejected' => MidtransLog::where('result', 'rejected')->where('created_at', '>=', now()->subDay())->count(),
        ]);
    }
}
