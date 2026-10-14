<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\MidtransLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MidtransService
{
    public function isConfigured(): bool
    {
        return filled(config('likehotel.midtrans.server_key')) && filled(config('likehotel.midtrans.client_key'));
    }

    public function snapJsUrl(): string
    {
        return config('likehotel.midtrans.is_production')
            ? 'https://app.midtrans.com/snap/snap.js'
            : 'https://app.sandbox.midtrans.com/snap/snap.js';
    }

    private function snapApiUrl(): string
    {
        return config('likehotel.midtrans.is_production')
            ? 'https://app.midtrans.com/snap/v1/transactions'
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions';
    }

    /** Buat Snap token untuk booking. */
    public function createSnapToken(Booking $booking): string
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Midtrans belum dikonfigurasi (MIDTRANS_SERVER_KEY / MIDTRANS_CLIENT_KEY).');
        }

        $amount = (int) $booking->total_price;

        $response = Http::withBasicAuth(config('likehotel.midtrans.server_key'), '')
            ->acceptJson()->asJson()->timeout(20)
            ->post($this->snapApiUrl(), [
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

        $token = $response->json('token');

        if (! $response->successful() || ! $token) {
            throw new RuntimeException('Gagal membuat Snap Token.');
        }

        $booking->update(['snap_token' => $token, 'payment_status' => 'snap dibuat']);

        return $token;
    }

    public function signatureFor(array $n): string
    {
        return hash('sha512', ($n['order_id'] ?? '').($n['status_code'] ?? '').($n['gross_amount'] ?? '').config('likehotel.midtrans.server_key'));
    }

    /**
     * Proses notifikasi Midtrans. Mengembalikan [httpStatus, pesan].
     * Menolak signature kosong/salah, order tidak dikenal, dan nominal tidak cocok; idempoten.
     */
    public function handleNotification(array $n, string $raw): array
    {
        $orderId = (string) ($n['order_id'] ?? '');
        $status = (string) ($n['transaction_status'] ?? 'unknown');

        $log = fn (string $result, ?string $note = null) => MidtransLog::create([
            'order_id' => $orderId ?: null, 'transaction_status' => $status,
            'result' => $result, 'note' => $note, 'payload' => $raw,
        ]);

        $signature = (string) ($n['signature_key'] ?? '');

        if (! filled(config('likehotel.midtrans.server_key')) || $signature === ''
            || ! hash_equals($this->signatureFor($n), $signature)) {
            $log('rejected', 'signature tidak valid');

            return [403, 'Invalid signature'];
        }

        return DB::transaction(function () use ($n, $orderId, $status, $raw, $log) {
            $booking = Booking::where('booking_code', $orderId)->lockForUpdate()->first();

            if (! $booking) {
                $log('rejected', 'order tidak ditemukan');

                return [404, 'Order not found'];
            }

            if ((int) round((float) ($n['gross_amount'] ?? 0)) !== (int) $booking->total_price) {
                $log('rejected', 'nominal tidak cocok');

                return [400, 'Amount mismatch'];
            }

            $fraud = $n['fraud_status'] ?? '';
            $paid = $status === 'settlement' || ($status === 'capture' && $fraud === 'accept');
            $failed = in_array($status, ['deny', 'expire', 'cancel'], true);

            // Booking yang sudah lunas tidak boleh diturunkan oleh notifikasi susulan.
            if ($booking->isPaid() && ! $paid) {
                $log('ignored', 'booking sudah lunas');

                return [200, 'OK'];
            }

            if ($booking->booking_status === Booking::STATUS_CANCELLED && ! $paid) {
                $log('ignored', 'booking sudah dibatalkan');

                return [200, 'OK'];
            }

            if ($paid) {
                $update = ['payment_status' => 'lunas', 'booking_status' => Booking::STATUS_PAID];
            } elseif ($failed) {
                $update = ['payment_status' => 'gagal/kedaluwarsa', 'booking_status' => Booking::STATUS_CANCELLED];
            } elseif ($status === 'pending') {
                $update = ['payment_status' => 'pending'];
            } else {
                $log('ignored', 'status tidak diproses');

                return [200, 'OK'];
            }

            $booking->update($update + ['midtrans_response' => $raw]);
            ActivityLog::record('booking.midtrans_'.$status, $booking, ['payment_status' => $update['payment_status']]);
            $log('processed');

            return [200, 'OK'];
        });
    }
}
