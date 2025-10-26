<?php

namespace App\Http\Controllers;

use App\Services\Payment\PaymentGatewayManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        protected PaymentGatewayManager $gatewayManager
    ) {}

    /**
     * Webhook для WayForPay
     */
    public function wayforpay(Request $request)
    {
        try {
            // ⚠️ КРИТИЧНО: Читаємо RAW JSON з php://input
            $rawData = file_get_contents('php://input');
            $data = json_decode($rawData, true);

            Log::info('WayForPay Webhook RAW', [
                'raw' => $rawData,
                'parsed' => $data,
                'headers' => $request->headers->all(),
            ]);

            if (!$data) {
                Log::error('Failed to parse webhook data', [
                    'raw' => $rawData,
                    'request_all' => $request->all(),
                ]);
                return response()->json(['error' => 'Invalid JSON'], 400);
            }

            $gateway = $this->gatewayManager->gateway('wayforpay');

            // ⚠️ Передаємо МАСИВ, а не Request
            $result = $gateway->handleWebhook($data);

            // ⚠️ КРИТИЧНО: Повертаємо JSON рядок напряму
            return response($result, 200)
                ->header('Content-Type', 'application/json');

        } catch (\Exception $e) {
            Log::error('WayForPay webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
