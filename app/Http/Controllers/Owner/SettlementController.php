<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Services\BookingException;
use App\Services\SettlementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class SettlementController extends Controller
{
    public function __construct(private SettlementService $settlements) {}

    public function index()
    {
        $owner = Auth::guard('owner')->user();

        return view('owner.settlement', [
            'owner' => $owner,
            'balance' => $this->settlements->balance($owner->id),
            'rows' => $owner->settlements()->latest('id')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $owner = Auth::guard('owner')->user();

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'bank_name' => ['required', 'string', 'max:100'],
            'bank_account_name' => ['required', 'string', 'max:150'],
            'bank_account_number' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->settlements->request($owner, $data);
        } catch (BookingException $e) {
            throw ValidationException::withMessages(['amount' => $e->getMessage()]);
        }

        return redirect()->route('owner.settlement')
            ->with('status', 'Pengajuan pencairan berhasil dikirim. Menunggu persetujuan admin.');
    }
}
