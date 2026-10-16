<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class PaymentController extends Controller
{
    public function __construct(private MidtransService $midtrans) {}

    public function invoice(string $token)
    {
        $b = Booking::with('apartment')->where('access_token', $token)->firstOrFail();

        return view('site.invoice', ['b' => $b]);
    }

    public function pay(string $token)
    {
        $b = Booking::with('apartment')->where('access_token', $token)->firstOrFail();

        if ($b->isPaid() || $b->isCancelled()) {
            return redirect()->route('invoice.show', $token);
        }

        return view('site.payment', [
            'b' => $b,
            'clientKey' => $this->midtrans->clientKey(),
            'snapJs' => $this->midtrans->snapJsUrl(),
        ]);
    }

    public function snap(string $token): JsonResponse
    {
        $b = Booking::with('apartment')->where('access_token', $token)->firstOrFail();

        if ($b->isPaid() || $b->isCancelled()) {
            return response()->json(['error' => 'Booking ini sudah tidak dapat dibayar.'], 409);
        }

        try {
            return response()->json(['token' => $this->midtrans->createSnapToken($b)]);
        } catch (Throwable $e) {
            Log::error('Midtrans snap gagal', ['booking' => $b->booking_code, 'error' => $e->getMessage()]);

            return response()->json(['error' => 'Gagal membuat halaman pembayaran. Silakan coba lagi.'], 500);
        }
    }

    /**
     * Redirect dari popup Snap. Hanya TAMPILAN: status pembayaran
     * hanya diubah oleh notifikasi Midtrans yang bertanda tangan valid.
     */
    public function result(Request $request, string $token)
    {
        Booking::where('access_token', $token)->firstOrFail();

        $msg = $request->query('result') === 'success'
            ? 'Terima kasih! Pembayaran sedang diverifikasi dan status akan diperbarui otomatis.'
            : 'Pembayaran Anda masih menunggu penyelesaian.';

        return redirect()->route('invoice.show', $token)->with('success', $msg);
    }
}
