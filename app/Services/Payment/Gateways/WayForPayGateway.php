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

    /**
     * Створення платежу
     */
    public function createPayment(Subscription $subscription, array $data = []): array
    {
        $user = $subscription->user;
        $plan = $subscription->plan;

        $orderReference = 'SUB_' . $subscription->id . '_' . time();
        $orderDate = time();
        $amount = number_format($subscription->price, 2, '.', '');
        $currency = strtoupper($subscription->currency ?? 'USD');

        // Зберігаємо платіж
        $payment = Payment::create([
            'user_id'         => $user->id,
            'subscription_id' => $subscription->id,
            'payment_gateway' => 'wayforpay',
            'transaction_id'  => $orderReference,
            'status'          => Payment::STATUS_PENDING,
            'amount'          => $amount,
            'currency'        => $currency,
        ]);

        // Назва товару/підписки
        $productName = sprintf('%s - %s', $plan->name, ucfirst($subscription->billing_period));

        $paymentData = [
            'merchantAccount'     => $this->merchantAccount,
            'merchantDomainName'  => $this->merchantDomainName,
            'orderReference'      => $orderReference,
            'orderDate'           => $orderDate,
            'amount'              => $amount,
            'currency'            => $currency, // WayForPay підтримує лише UAH
            'productName'         => [$productName],
            'productCount'        => [1],
            'productPrice'        => [$amount],
            'clientEmail'         => $user->email,
            'clientFirstName'     => $user->first_name ?? $user->name ?? 'User',
            'clientLastName'      => $user->last_name ?? $user->name ?? 'User',
            'merchantAuthType'    => 'SimpleSignature',
            'clientPhone'         => $data['phone'] ?? '+380501665079',
            'returnUrl'           => route('checkout.success'),
            'serviceUrl'          => route('webhooks.wayforpay'),
            'defaultPaymentSystem' => 'card',

            // ⚠️ ДОДАТИ ЦІ ПАРАМЕТРИ ДЛЯ РЕГУЛЯРКИ:
            'regularMode' => $this->getRegularMode($subscription->billing_period),
            'regularAmount' => $amount,
            'dateNext' => $this->getNextPaymentDate($subscription),
            'regularCount' => 120, // або dateEnd
            'regularBehavior' => 'preset', // клієнт не може змінити
        ];

        // Генеруємо підпис
        $paymentData['merchantSignature'] = $this->generateSignature($paymentData);

        // Логування
        Log::info('WayForPay Payment Created', [
            'orderReference'   => $orderReference,
            'amount'           => $amount,
            'signature_string' => $this->getSignatureString($paymentData),
            'signature'        => $paymentData['merchantSignature'],
        ]);

        return [
            'success'     => true,
            'payment'     => $payment,
            'form_data'   => $paymentData,
            'action_url'  => 'https://secure.wayforpay.com/pay',
        ];
    }

    private function getRegularMode(string $billingPeriod): string
    {
        return match($billingPeriod) {
            'monthly' => 'monthly',
            'yearly' => 'yearly',
            default => 'monthly',
        };
    }

    private function getNextPaymentDate(Subscription $subscription): string
    {
        $next = match($subscription->billing_period) {
            'monthly' => now()->addMonth(),
            'yearly' => now()->addYear(),
            default => now()->addMonth(),
        };

        return $next->format('d.m.Y');
    }

    /**
     * Обробка webhook від WayForPay
     */
    public function handleWebhook(Request $request): array
    {
        Log::info('WayForPay Webhook Received', $request->all());

        if (!$this->verifyWebhookSignature($request)) {
            Log::error('WayForPay: Invalid webhook signature', [
                'received_signature' => $request->input('merchantSignature'),
                'expected_signature' => $this->generateWebhookSignature($request),
            ]);
            return ['status' => 'error', 'message' => 'Invalid signature'];
        }

        $orderReference    = $request->input('orderReference');
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
                'reason'         => $request->input('reason'),
                'reasonCode'     => $request->input('reasonCode'),
            ]);
        }

        return [
            'orderReference' => $orderReference,
            'status'         => 'accept',
            'time'           => now()->timestamp,
        ];
    }

    /**
     * Обробка успішної оплати
     */
    protected function handleSuccessfulPayment(Payment $payment): void
    {
        $payment->markAsCompleted();
        $subscription = $payment->subscription;

        if ($subscription) {
            if (request()->has('recToken')) {
                $subscription->update([
                    'gateway_subscription_id' => request('recToken'),
                ]);
            }

            if ($subscription->status === Subscription::STATUS_PENDING) {
                $subscription->activate();
                $subscription->update([
                    'starts_at' => now(),
                    'ends_at'   => $this->calculateEndDate($subscription),
                ]);
            } else {
                $subscription->renew();
            }
        }

        Log::info('Payment successful', [
            'payment_id'      => $payment->id,
            'subscription_id' => $subscription->id ?? null,
        ]);

        Log::info('recToken saved', [
            'subscription_id' => $subscription->id,
            'recToken' => request('recToken'),
        ]);
    }

    protected function calculateEndDate($subscription)
    {
        return match ($subscription->billing_period) {
            'monthly'  => now()->addMonth(),
            'yearly'   => now()->addYear(),
            'lifetime' => null,
            default    => now()->addMonth(),
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

    /**
     * Перевірка підпису webhook
     */
    public function verifyWebhookSignature(Request $request): bool
    {
        $receivedSignature = $request->input('merchantSignature');
        if (!$receivedSignature) {
            Log::error('WayForPay: merchantSignature missing in webhook');
            return false;
        }

        $generatedSignature = $this->generateWebhookSignature($request);
        $isValid = hash_equals($generatedSignature, $receivedSignature);

        if (!$isValid) {
            Log::error('Signature mismatch', [
                'received'  => $receivedSignature,
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
     * Генерація підпису для платежу
     * https://wiki.wayforpay.com/en/view/852102
     */
    protected function generateSignature(array $data): string
    {
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

        Log::debug('WayForPay signature string', ['string' => $signatureString]);

        return hash_hmac('md5', $signatureString, $this->merchantSecretKey);
    }

    /**
     * Генерація підпису для webhook
     * https://wiki.wayforpay.com/en/view/852136
     */
    protected function generateWebhookSignature(Request $request): string
    {
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

        return hash_hmac('md5', $signatureString, $this->merchantSecretKey);
    }

    /**
     * Debug helper — показує рядок підпису
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

    public function chargeRecurring(Subscription $subscription): array
    {
        if (!$subscription->gateway_subscription_id) {
            throw new \Exception('No recToken found for subscription');
        }

        $orderReference = 'RECURRING_' . $subscription->id . '_' . time();
        $orderDate = time();
        $amount = number_format($subscription->price, 2, '.', '');

        $data = [
            'transactionType' => 'CHARGE',
            'merchantAccount' => $this->merchantAccount,
            'merchantDomainName' => $this->merchantDomainName,
            'orderReference' => $orderReference,
            'orderDate' => $orderDate,
            'amount' => $amount,
            'currency' => strtoupper($subscription->currency),
            'productName' => [$subscription->plan->name],
            'productCount' => [1],
            'productPrice' => [$amount],
            'recToken' => $subscription->gateway_subscription_id, // ⚠️ ВИКОРИСТОВУЄМО ЗБЕРЕЖЕНИЙ TOKEN
        ];

        $data['merchantSignature'] = $this->generateChargeSignature($data);

        // Відправляємо запит до WayForPay API
        $response = Http::post('https://api.wayforpay.com/api', $data);

        Log::info('Recurring charge request', [
            'subscription_id' => $subscription->id,
            'orderReference' => $orderReference,
            'response' => $response->json(),
        ]);

        if (!$response->successful()) {
            return [
                'success' => false,
                'message' => 'API request failed',
            ];
        }

        $result = $response->json();

        // Створюємо запис платежу
        $payment = Payment::create([
            'user_id' => $subscription->user_id,
            'subscription_id' => $subscription->id,
            'payment_gateway' => 'wayforpay',
            'transaction_id' => $orderReference,
            'status' => Payment::STATUS_PENDING,
            'amount' => $amount,
            'currency' => $subscription->currency,
            'gateway_response' => $result,
        ]);

        // Перевіряємо статус
        if (isset($result['reasonCode']) && $result['reasonCode'] === 1100) {
            // ✅ Успішно списано
            $payment->markAsCompleted();
            $subscription->renew(); // Продовжуємо підписку на наступний період

            return [
                'success' => true,
                'payment' => $payment,
            ];
        }

        // ❌ Помилка списання
        $payment->markAsFailed();

        return [
            'success' => false,
            'message' => $result['reason'] ?? 'Payment failed',
            'reasonCode' => $result['reasonCode'] ?? null,
        ];
    }

    private function generateChargeSignature(array $data): string
    {
        $string = implode(';', [
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

        return hash_hmac('md5', $string, $this->merchantSecretKey);
    }

    public function checkPaymentStatus(string $orderReference): array
    {
        $data = [
            'transactionType' => 'CHECK_STATUS',
            'merchantAccount' => $this->merchantAccount,
            'orderReference' => $orderReference,
            'apiVersion' => 1,
        ];

        // Генеруємо підпис
        $signatureString = implode(';', [
            $data['merchantAccount'],
            $data['orderReference'],
        ]);
        $data['merchantSignature'] = hash_hmac('md5', $signatureString, $this->merchantSecretKey);

        $response = Http::post('https://api.wayforpay.com/api', $data);

        return $response->json();
    }
}
