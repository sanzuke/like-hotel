<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Owner;
use App\Support\Audit;
use Illuminate\Http\Request;

class OwnerController extends Controller
{
    public function index()
    {
        return view('admin.owners', ['owners' => Owner::withCount('apartments')->latest('id')->get()]);
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
