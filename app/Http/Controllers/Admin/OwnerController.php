<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Owner;
use Illuminate\Http\Request;

class OwnerController extends Controller
{
    public function index()
    {
        return view('admin.owners', ['owners' => Owner::withCount('apartments')->latest('id')->get()]);
    }

    public function status(Request $request, Owner $owner)
    {
        $data = $request->validate(['status' => ['required', 'in:aktif,nonaktif,pending']]);

        $owner->update(['status' => $data['status']]);
        ActivityLog::record('owner.status_'.$data['status'], $owner);

        return back()->with('status', "Status owner {$owner->name} diubah menjadi {$data['status']}.");
    }
}
