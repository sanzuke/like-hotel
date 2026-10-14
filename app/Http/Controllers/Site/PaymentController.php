<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\MidtransService;
use Illuminate\Http\Request;
use RuntimeException;

class PaymentController extends Controller
{
    public function __construct(private MidtransService $midtrans) {}

    public function show(Booking $booking)
    {
        if ($booking->isPaid() || $booking->booking_status === Booking::STATUS_CANCELLED) {
            return redirect()->route('invoice.show', $booking);
        }

        return view('site.payment', [
            'booking' => $booking->load('apartment'),
            'configured' => $this->midtrans->isConfigured(),
            'snapJs' => $this->midtrans->snapJsUrl(),
        ]);
    }

    public function snap(Booking $booking)
    {
        if ($booking->isPaid() || $booking->booking_status === Booking::STATUS_CANCELLED) {
            return response()->json(['error' => 'Booking ini tidak dapat dibayar.'], 422);
        }

        try {
            return response()->json(['token' => $this->midtrans->createSnapToken($booking->load('apartment'))]);
        } catch (RuntimeException $e) {
            report($e);

            return response()->json(['error' => $e->getMessage()], 502);
        }
    }

    /** Halaman tampilan saja — TIDAK mengubah status. Status final datang dari notifikasi Midtrans. */
    public function success(Booking $booking)
    {
        return redirect()->route('invoice.show', $booking)
            ->with('status', 'Terima kasih! Pembayaran Anda sedang diverifikasi. Status invoice akan diperbarui otomatis.');
    }

    public function pending(Booking $booking)
    {
        return redirect()->route('invoice.show', $booking)
            ->with('status', 'Pembayaran Anda masih menunggu penyelesaian. Selesaikan pembayaran sesuai instruksi.');
    }

    public function notification(Request $request)
    {
        $raw = $request->getContent();
        $data = json_decode($raw, true);

        if (! is_array($data) || empty($data['order_id'])) {
            return response('Invalid notification', 400);
        }

        [$status, $message] = $this->midtrans->handleNotification($data, $raw);

        return response($message, $status);
    }
}
