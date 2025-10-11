<?php

namespace App\Services\Payment\Contracts;

use App\Models\Subscription;
use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    public function createPayment(Subscription $subscription, array $data = []): array;
    public function handleWebhook(Request $request): array;
    public function cancelSubscription(Subscription $subscription): bool;
    public function getPaymentStatus(string $transactionId): string;
    public function verifyWebhookSignature(Request $request): bool;
    public function getPaymentUrl(array $paymentData): string;
}
