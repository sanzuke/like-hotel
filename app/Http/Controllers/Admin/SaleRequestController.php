<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SaleRequest;
use App\Models\SaleRequestPhoto;
use App\Services\BookingException;
use App\Services\SaleRequestService;
use App\Support\ImageUploader;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SaleRequestController extends Controller
{
    public function __construct(private SaleRequestService $crm) {}

    /** Jalankan aksi service; kesalahan bisnis ditampilkan sebagai pesan validasi. */
    private function run(callable $fn, string $success)
    {
        try {
            $fn();
        } catch (BookingException $e) {
            throw ValidationException::withMessages(['crm' => $e->getMessage()]);
        }

        return back()->with('status', $success);
    }

    public function index(Request $request)
    {
        $all = SaleRequest::latest('id')->get();
        $filter = $request->query('status');
        $term = mb_strtolower(trim((string) $request->query('q')));

        $rows = $all
            ->when($filter && isset(SaleRequest::STATUSES[$filter]), fn ($c) => $c->where('status', $filter))
            ->when($term !== '', fn ($c) => $c->filter(fn ($r) => str_contains(mb_strtolower($r->owner_name.' '.$r->apartment_name.' '.$r->owner_phone), $term)));

        return view('admin.sale-requests', ['rows' => $rows->values(), 'counts' => $all->countBy('status'), 'total' => $all->count(), 'filter' => $filter, 'term' => $request->query('q')]);
    }

    public function show(SaleRequest $saleRequest)
    {
        return view('admin.sale-request-show', [
            'r' => $saleRequest->load(['events.admin', 'photos', 'saleUnit', 'saleCommission']),
        ]);
    }

    public function status(Request $request, SaleRequest $saleRequest)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(SaleRequest::STATUSES))],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->crm->changeStatus($saleRequest, $data['status'], $data['note'] ?? null);

        return back()->with('status', 'Status pengajuan berhasil diperbarui.');
    }

    public function note(Request $request, SaleRequest $saleRequest)
    {
        $data = $request->validate(['note' => ['required', 'string', 'max:2000']]);
        $this->crm->addNote($saleRequest, $data['note']);

        return back()->with('status', 'Catatan ditambahkan.');
    }

    public function survey(Request $request, SaleRequest $saleRequest)
    {
        $data = $request->validate(['survey_at' => ['required', 'date', 'after:now'], 'note' => ['nullable', 'string', 'max:1000']], [
            'survey_at.after' => 'Jadwal survey harus di masa depan.',
        ]);

        $this->crm->scheduleSurvey($saleRequest, Carbon::parse($data['survey_at']), $data['note'] ?? null);

        return back()->with('status', 'Jadwal survey disimpan.');
    }

    public function offer(Request $request, SaleRequest $saleRequest)
    {
        $data = $request->validate(['amount' => ['required', 'numeric', 'gt:0'], 'note' => ['nullable', 'string', 'max:1000']]);
        $this->crm->addOffer($saleRequest, (float) $data['amount'], $data['note'] ?? null);

        return back()->with('status', 'Penawaran dicatat.');
    }

    public function photos(Request $request, SaleRequest $saleRequest)
    {
        $request->validate(['photos' => ['required', 'array', 'max:20'], 'photos.*' => ImageUploader::rules()]);
        $n = $this->crm->addPhotos($saleRequest, $request->file('photos'));

        return back()->with('status', "{$n} foto ditambahkan.");
    }

    public function destroyPhoto(SaleRequest $saleRequest, SaleRequestPhoto $photo)
    {
        abort_unless($photo->sale_request_id === $saleRequest->id, 404);
        $this->crm->deletePhoto($saleRequest, $photo);

        return back()->with('status', 'Foto dihapus.');
    }

    public function publish(Request $request, SaleRequest $saleRequest)
    {
        $data = $request->validate(['price' => ['required', 'numeric', 'gt:0'], 'title' => ['nullable', 'string', 'max:150']]);

        return $this->run(
            fn () => $this->crm->publish($saleRequest, (float) $data['price'], $data['title'] ?? null),
            'Unit dipublikasikan ke halaman Jual Beli.'
        );
    }

    public function close(Request $request, SaleRequest $saleRequest)
    {
        $data = $request->validate([
            'deal_price' => ['required', 'numeric', 'gt:0'],
            'commission_type' => ['required', 'in:percent,fixed'],
            'commission_value' => ['required', 'numeric', 'min:0', $request->input('commission_type') === 'percent' ? 'max:100' : 'max:99999999999'],
            'other_cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        return $this->run(
            fn () => $this->crm->close($saleRequest, (float) $data['deal_price'], $data['commission_type'], (float) $data['commission_value'], (float) ($data['other_cost'] ?? 0), $data['notes'] ?? null),
            'Closing dicatat dan data komisi penjualan dibuat.'
        );
    }

    public function destroy(SaleRequest $saleRequest)
    {
        foreach ($saleRequest->photos as $p) {
            ImageUploader::delete(SaleRequestPhoto::FOLDER, $p->image);
        }

        $saleRequest->delete();

        return redirect()->route('admin.sale-requests')->with('status', 'Pengajuan berhasil dihapus.');
    }
}
