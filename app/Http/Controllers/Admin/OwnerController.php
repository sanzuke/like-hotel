<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Owner;
use App\Support\Audit;
use Illuminate\Http\Request;
use App\Support\TableFilter;

class OwnerController extends Controller
{
    public function index(Request $request)
    {
        $filters = TableFilter::active([
            'name' => TableFilter::text($request, 'name'),
            'email' => TableFilter::text($request, 'email'),
            'status' => TableFilter::enum($request, 'status', ['pending', 'aktif', 'nonaktif']),
        ]);

        $owners = Owner::withCount('apartments')
            ->when($filters['name'] ?? null, fn ($q, $v) => $q->where('name', 'like', '%'.$v.'%'))
            ->when($filters['email'] ?? null, fn ($q, $v) => $q->where('email', 'like', '%'.$v.'%'))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.owners', ['owners' => $owners, 'filters' => $filters]);
    }

    public function updateStatus(Request $request, Owner $owner)
    {
        $status = $request->validate(['status' => 'required|in:aktif,nonaktif'])['status'];
        $from = $owner->status;
        $owner->update(['status' => $status]);
        Audit::log('owner.status', "Status owner {$owner->name}: {$from} → {$status}", $owner, ['from' => $from, 'to' => $status]);

        return back()->with('success', "Status {$owner->name} diubah menjadi {$status}.");
    }
}
