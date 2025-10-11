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
            $gateway = $this->gatewayManager->gateway('wayforpay');
            $result = $gateway->handleWebhook($request);

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('WayForPay webhook error', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
