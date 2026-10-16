<?php

namespace App\Services;

use App\Models\Booking;
use App\Support\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MidtransService
{
    public function serverKey(): string
    {
        $key = (string) config('likehotel.midtrans.server_key');

        if ($key === '') {
            throw new RuntimeException('MIDTRANS_SERVER_KEY belum diisi di .env');
        }

        return $key;
    }

    public function clientKey(): string
    {
        return (string) config('likehotel.midtrans.client_key');
    }

    public function snapJsUrl(): string
    {
        return config('likehotel.midtrans.is_production')
            ? 'https://app.midtrans.com/snap/snap.js'
            : 'https://app.sandbox.midtrans.com/snap/snap.js';
    }

    private function snapUrl(): string
    {
        return config('likehotel.midtrans.is_production')
            ? 'https://app.midtrans.com/snap/v1/transactions'
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions';
    }

    public function createSnapToken(Booking $booking): string
    {
        $amount = (int) round((float) $booking->total_price);

        $response = Http::withBasicAuth($this->serverKey(), '')
            ->acceptJson()
            ->post($this->snapUrl(), [
                'transaction_details' => ['order_id' => $booking->booking_code, 'gross_amount' => $amount],
                'customer_details' => [
                    'first_name' => $booking->guest_name,
                    'email' => $booking->guest_email,
                    'phone' => $booking->guest_phone,
                ],
                'item_details' => [[
                    'id' => 'APT-'.$booking->apartment_id,
                    'price' => $amount,
                    'quantity' => 1,
                    'name' => mb_substr('Booking '.$booking->apartment->name, 0, 50),
                ]],
            ]);

        if (! $response->successful() || ! $response->json('token')) {
            throw new RuntimeException('Gagal membuat Snap Token: '.$response->body());
        }

        $token = $response->json('token');
        $booking->update(['snap_token' => $token, 'payment_status' => 'snap dibuat']);

        return $token;
    }

    /** SHA512(order_id + status_code + gross_amount + server_key). */
    public function validSignature(array $n): bool
    {
        if (empty($n['signature_key']) || ! isset($n['order_id'], $n['status_code'], $n['gross_amount'])) {
            return false;
        }

        $expected = hash('sha512', $n['order_id'].$n['status_code'].$n['gross_amount'].$this->serverKey());

        return hash_equals($expected, (string) $n['signature_key']);
    }

    /**
     * Proses notifikasi. Mengembalikan [httpStatus, pesan].
     * Menolak signature kosong/salah, order tak dikenal, dan nominal tak cocok.
     * Idempotent: booking yang sudah lunas tidak diturunkan statusnya.
     */
    public function handleNotification(array $n): array
    {
        if (! $this->validSignature($n)) {
            return [403, 'Invalid signature'];
        }

        return DB::transaction(function () use ($n) {
            $booking = Booking::where('booking_code', $n['order_id'])->lockForUpdate()->first();

            if (! $booking) {
                return [404, 'Order not found'];
            }

            if ((int) round((float) $n['gross_amount']) !== (int) round((float) $booking->total_price)) {
                return [400, 'Amount mismatch'];
            }

            $status = $n['transaction_status'] ?? 'unknown';
            $fraud = $n['fraud_status'] ?? '';
            $booking->midtrans_response = json_encode($n);

            if ($status === 'settlement' || ($status === 'capture' && $fraud === 'accept')) {
                $booking->save();
                $wasPaid = $booking->isPaid();
                $booking->markPaid('lunas');

                if (! $wasPaid) {
                    Audit::system('booking.paid_midtrans', "Booking {$booking->booking_code} lunas via Midtrans ({$status})", $booking, ['gross_amount' => $n['gross_amount'], 'payment_type' => $n['payment_type'] ?? null]);
                }
            } elseif ($booking->isPaid()) {
                // Sudah lunas: abaikan notifikasi susulan (pending/expire/dll).
                $booking->save();
            } elseif ($status === 'pending') {
                $booking->payment_status = 'pending';
                $booking->save();
            } elseif (in_array($status, ['deny', 'expire', 'cancel'], true)) {
                $booking->save();
                $booking->markCancelled('gagal/kedaluwarsa');
                Audit::system('booking.cancelled_midtrans', "Booking {$booking->booking_code} dibatalkan oleh Midtrans ({$status})", $booking);
            } else {
                $booking->save();
            }

            return [200, 'OK'];
        });
    }
}
