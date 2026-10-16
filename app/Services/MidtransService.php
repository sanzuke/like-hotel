<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\MidtransLog;
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
     * Setiap notifikasi (diproses, diabaikan, ditolak) dicatat di midtrans_logs.
     */
    public function handleNotification(array $n): array
    {
        $raw = json_encode($n);
        $orderId = (string) ($n['order_id'] ?? '');
        $status = (string) ($n['transaction_status'] ?? 'unknown');

        $log = fn (string $result, ?string $note = null) => MidtransLog::create([
            'order_id' => $orderId !== '' ? $orderId : null,
            'transaction_status' => $status,
            'result' => $result,
            'note' => $note,
            'payload' => $raw,
        ]);

        if (! $this->validSignature($n)) {
            $log('rejected', 'signature tidak valid');

            return [403, 'Invalid signature'];
        }

        return DB::transaction(function () use ($n, $orderId, $status, $raw, $log) {
            $booking = Booking::where('booking_code', $orderId)->lockForUpdate()->first();

            if (! $booking) {
                $log('rejected', 'order tidak ditemukan');

                return [404, 'Order not found'];
            }

            if ((int) round((float) $n['gross_amount']) !== (int) round((float) $booking->total_price)) {
                $log('rejected', 'nominal tidak cocok');

                return [400, 'Amount mismatch'];
            }

            $fraud = $n['fraud_status'] ?? '';
            $paid = $status === 'settlement' || ($status === 'capture' && $fraud === 'accept');
            $booking->midtrans_response = $raw;

            if ($paid) {
                if ($booking->isPaid()) {
                    $booking->save();
                    $log('ignored', 'booking sudah lunas');

                    return [200, 'OK'];
                }

                $booking->save();
                $booking->markPaid('lunas');
                Audit::system('booking.paid_midtrans', "Booking {$booking->booking_code} lunas via Midtrans ({$status})", $booking, ['gross_amount' => $n['gross_amount'], 'payment_type' => $n['payment_type'] ?? null]);
                $log('processed');
            } elseif ($booking->isPaid()) {
                // Sudah lunas: abaikan notifikasi susulan (pending/expire/dll).
                $booking->save();
                $log('ignored', 'booking sudah lunas');
            } elseif ($status === 'pending') {
                $booking->payment_status = 'pending';
                $booking->save();
                $log('processed');
            } elseif (in_array($status, ['deny', 'expire', 'cancel'], true)) {
                $booking->save();
                $booking->markCancelled('gagal/kedaluwarsa');
                Audit::system('booking.cancelled_midtrans', "Booking {$booking->booking_code} dibatalkan oleh Midtrans ({$status})", $booking);
                $log('processed');
            } else {
                $booking->save();
                $log('ignored', 'status tidak diproses');
            }

            return [200, 'OK'];
        });
    }
}
