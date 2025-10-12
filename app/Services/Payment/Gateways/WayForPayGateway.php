<?php

namespace App\Services\Payment\Gateways;

use App\Models\Payment;
use App\Models\Subscription;
use App\Services\Payment\Contracts\PaymentGatewayInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WayForPayGateway implements PaymentGatewayInterface
{
    protected string $merchantAccount;
    protected string $merchantSecretKey;
    protected string $merchantDomainName;

    public function __construct()
    {
        $this->merchantAccount = config('services.wayforpay.merchant_account');
        $this->merchantSecretKey = config('services.wayforpay.secret_key');
        $this->merchantDomainName = config('services.wayforpay.domain');
    }

    public function createPayment(Subscription $subscription, array $data = []): array
    {
        $user = $subscription->user;
        $plan = $subscription->plan;

        $orderReference = 'SUB_' . $subscription->id . '_' . time();
        $orderDate = time();
        $amount = $subscription->price;
        $currency = $subscription->currency;

        $payment = Payment::create([
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'payment_gateway' => 'wayforpay',
            'transaction_id' => $orderReference,
            'status' => Payment::STATUS_PENDING,
            'amount' => $amount,
            'currency' => $currency,
        ]);

        $productName = sprintf(
            '%s - %s',
            $plan->name,
            ucfirst($subscription->billing_period)
        );

        // ВАЖЛИВО: Всі суми мають бути у форматі з 2 знаками після коми
        $formattedAmount = number_format($amount, 2, '.', '');

        $paymentData = [
            'merchantAccount' => $this->merchantAccount,
            'merchantDomainName' => $this->merchantDomainName,
            'orderReference' => $orderReference,
            'orderDate' => $orderDate,
            'amount' => $formattedAmount,
            'currency' => $currency,
            'productName' => [$productName],
            'productCount' => [1],
            'productPrice' => [$formattedAmount],
            'clientEmail' => $user->email,
            'clientFirstName' => $user->name ?? 'User',
            'clientLastName' => $user->name ?? 'User',
            'clientPhone' => $data['phone'] ?? '+380501665079',
            'returnUrl' => route('checkout.success'),
            'serviceUrl' => route('webhooks.wayforpay'),
        ];

        // Генеруємо підпис
        $paymentData['merchantSignature'] = $this->generateSignature($paymentData);

        // Логуємо для дебагу
        Log::info('WayForPay Payment Created', [
            'orderReference' => $orderReference,
            'amount' => $formattedAmount,
            'signature_string' => $this->getSignatureString($paymentData),
        ]);

        return [
            'success' => true,
            'payment' => $payment,
            'form_data' => $paymentData,
            'action_url' => 'https://secure.wayforpay.com/pay',
        ];
    }

    public function handleWebhook(Request $request): array
    {
        Log::info('WayForPay Webhook Received', $request->all());

        if (!$this->verifyWebhookSignature($request)) {
            Log::error('WayForPay: Invalid webhook signature', [
                'received_signature' => $request->input('merchantSignature'),
                'expected_signature' => $this->generateWebhookSignature($request),
                'data' => $request->all(),
            ]);
            return ['status' => 'error', 'message' => 'Invalid signature'];
        }

        $orderReference = $request->input('orderReference');
        $transactionStatus = $request->input('transactionStatus');

        $payment = Payment::where('transaction_id', $orderReference)->first();

        if (!$payment) {
            Log::error('Payment not found', ['orderReference' => $orderReference]);
            return ['status' => 'error', 'message' => 'Payment not found'];
        }

        $payment->update(['gateway_response' => $request->all()]);

        if ($transactionStatus === 'Approved') {
            $this->handleSuccessfulPayment($payment);
        } else {
            $payment->markAsFailed();
            Log::warning('Payment declined', [
                'orderReference' => $orderReference,
                'reason' => $request->input('reason'),
                'reasonCode' => $request->input('reasonCode'),
            ]);
        }

        return ['orderReference' => $orderReference, 'status' => 'accept', 'time' => time()];
    }

    protected function handleSuccessfulPayment(Payment $payment)
    {
        $payment->markAsCompleted();
        $subscription = $payment->subscription;

        if ($subscription) {
            if ($subscription->status === Subscription::STATUS_PENDING) {
                $subscription->activate();
                $subscription->update([
                    'starts_at' => now(),
                    'ends_at' => $this->calculateEndDate($subscription),
                ]);
            } else {
                $subscription->renew();
            }
        }

        Log::info('Payment successful', [
            'payment_id' => $payment->id,
            'subscription_id' => $subscription->id ?? null,
        ]);
    }

    protected function calculateEndDate($subscription)
    {
        return match ($subscription->billing_period) {
            'monthly' => now()->addMonth(),
            'yearly' => now()->addYear(),
            'lifetime' => null,
            default => now()->addMonth(),
        };
    }

    public function cancelSubscription(Subscription $subscription): bool
    {
        $subscription->cancel();
        return true;
    }

    public function getPaymentStatus(string $transactionId): string
    {
        $payment = Payment::where('transaction_id', $transactionId)->first();
        return $payment ? $payment->status : Payment::STATUS_PENDING;
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        $merchantSignature = $request->input('merchantSignature');
        if (!$merchantSignature) {
            Log::error('WayForPay: merchantSignature not provided in webhook');
            return false;
        }

        $generatedSignature = $this->generateWebhookSignature($request);

        $isValid = hash_equals($generatedSignature, $merchantSignature);

        if (!$isValid) {
            Log::error('Signature mismatch', [
                'received' => $merchantSignature,
                'generated' => $generatedSignature,
            ]);
        }

        return $isValid;
    }

    public function getPaymentUrl(array $paymentData): string
    {
        return 'https://secure.wayforpay.com/pay';
    }

    /**
     * Генерація підпису для створення платежу
     * Згідно документації: https://wiki.wayforpay.com/en/view/852102
     */
    protected function generateSignature(array $data): string
    {
        // Порядок полів КРИТИЧНО ВАЖЛИВИЙ!
        $signatureString = implode(';', [
            $data['merchantAccount'],
            $data['merchantDomainName'],
            $data['orderReference'],
            $data['orderDate'],
            $data['amount'],
            $data['currency'],
            implode(';', $data['productName']),
            implode(';', $data['productCount']),
            implode(';', $data['productPrice']),
        ]);

        Log::debug('Signature string for payment', [
            'string' => $signatureString,
            'secret_key_length' => strlen($this->merchantSecretKey),
        ]);

        return hash_hmac('md5', $signatureString, $this->merchantSecretKey);
    }

    /**
     * Генерація підпису для webhook
     */
    protected function generateWebhookSignature($request): string
    {
        // Порядок полів для webhook (з документації)
        $signatureString = implode(';', [
            $request->input('merchantAccount'),
            $request->input('orderReference'),
            $request->input('amount'),
            $request->input('currency'),
            $request->input('authCode'),
            $request->input('cardPan'),
            $request->input('transactionStatus'),
            $request->input('reasonCode'),
        ]);

        Log::debug('Webhook signature string', [
            'string' => $signatureString,
        ]);

        return hash_hmac('md5', $signatureString, $this->merchantSecretKey);
    }

    /**
     * Helper для дебагу - показує signature string
     */
    private function getSignatureString(array $data): string
    {
        return implode(';', [
            $data['merchantAccount'],
            $data['merchantDomainName'],
            $data['orderReference'],
            $data['orderDate'],
            $data['amount'],
            $data['currency'],
            implode(';', $data['productName']),
            implode(';', $data['productCount']),
            implode(';', $data['productPrice']),
        ]);
    }
}
