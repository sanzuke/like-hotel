<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\MidtransService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class MidtransController extends Controller
{
    public function notification(Request $request, MidtransService $midtrans)
    {
        $payload = $request->json()->all();

        if (empty($payload['order_id'])) {
            return response('Invalid notification', 400);
        }

        try {
            [$status, $message] = $midtrans->handleNotification($payload);
        } catch (RuntimeException $e) {
            Log::error('Midtrans notification error', ['error' => $e->getMessage()]);

            return response('Server error', 500);
        }

        if ($status !== 200) {
            Log::warning('Midtrans notification ditolak', ['order_id' => $payload['order_id'], 'reason' => $message]);
        }

        return response($message, $status);
    }
}
