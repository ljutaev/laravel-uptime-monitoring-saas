<?php

namespace App\Services\Payment\Gateways;

use App\Models\Payment;
use App\Models\Subscription;
use App\Services\Payment\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WayForPayGateway implements PaymentGatewayInterface
{
    protected string $merchantAccount;
    protected string $merchantSecretKey;
    protected string $merchantDomainName;

    // ⚠️ ДОДАТИ ЦІ КОНСТАНТИ ЯК У ПРИКЛАДІ
    const SIGNATURE_SEPARATOR = ';';

    protected array $keysForResponseSignature = [
        'merchantAccount',
        'orderReference',
        'amount',
        'currency',
        'authCode',
        'cardPan',
        'transactionStatus',
        'reasonCode'
    ];

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
        $currency = strtoupper($subscription->currency ?? 'UAH'); // ⚠️ WayForPay підтримує UAH

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
            'currency'            => $currency,
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

            // ⚠️ ПАРАМЕТРИ ДЛЯ РЕГУЛЯРКИ:
            'regularMode' => $this->getRegularMode($subscription->billing_period),
            'regularAmount' => $amount,
            'dateNext' => $this->getNextPaymentDate($subscription),
            'regularCount' => 120,
            'regularBehavior' => 'preset',
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
     * ⚠️ ПРИЙМАЄ МАСИВ, ПОВЕРТАЄ JSON STRING
     */
    public function handleWebhook(array $data): string
    {
        Log::info('WayForPay Webhook Processing', $data);

        // Перевірка підпису
        if (!$this->verifyWebhookSignature($data)) {
            Log::error('WayForPay: Invalid webhook signature', [
                'received_signature' => $data['merchantSignature'] ?? 'missing',
                'expected_signature' => $this->generateWebhookSignature($data),
                'signature_string' => $this->getWebhookSignatureString($data),
            ]);

            return json_encode([
                'status' => 'error',
                'message' => 'Invalid signature'
            ]);
        }

        $orderReference = $data['orderReference'];
        $transactionStatus = $data['transactionStatus'];

        $payment = Payment::where('transaction_id', $orderReference)->first();

        if (!$payment) {
            Log::error('Payment not found', ['orderReference' => $orderReference]);
            // ⚠️ ВСЕ ОДНО ПОВЕРТАЄМО ПРАВИЛЬНУ ВІДПОВІДЬ
            return $this->getAnswerToGateway($data);
        }

        $payment->update(['gateway_response' => $data]);

        if ($transactionStatus === 'Approved') {
            $this->handleSuccessfulPayment($payment, $data);
        } else {
            $payment->markAsFailed();
            Log::warning('Payment declined', [
                'orderReference' => $orderReference,
                'reason' => $data['reason'] ?? 'Unknown',
                'reasonCode' => $data['reasonCode'] ?? null,
            ]);
        }

        // ⚠️ ПОВЕРТАЄМО JSON РЯДОК З ПІДПИСОМ
        return $this->getAnswerToGateway($data);
    }

    /**
     * Обробка успішної оплати
     */
    protected function handleSuccessfulPayment(Payment $payment, array $data): void
    {
        $payment->markAsCompleted();

        $subscription = $payment->subscription;

        if ($subscription) {
            // ⚠️ ЗБЕРІГАЄМО recToken
            if (isset($data['recToken']) && !empty($data['recToken'])) {
                $subscription->update([
                    'gateway_subscription_id' => $data['recToken'],
                ]);

                Log::info('recToken saved', [
                    'subscription_id' => $subscription->id,
                    'recToken' => $data['recToken'],
                ]);
            }

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

    /**
     * ⚠️ НОВИЙ МЕТОД: Генерує відповідь для WayForPay (як у прикладі)
     */
    protected function getAnswerToGateway(array $data): string
    {
        $signatureData = [];

        foreach ($this->keysForResponseSignature as $key) {
            $signatureData[] = $data[$key] ?? '';
        }

        $signatureString = implode(self::SIGNATURE_SEPARATOR, $signatureData);
        $signature = hash_hmac('md5', $signatureString, $this->merchantSecretKey);

        $answer = [
            'orderReference' => $data['orderReference'],
            'status' => 'accept',
            'time' => time(),
            'signature' => $signature
        ];

        Log::info('Gateway response', [
            'answer' => $answer,
            'signature_string' => $signatureString,
        ]);

        return json_encode($answer);
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
     * ⚠️ ПРИЙМАЄ МАСИВ
     */
    public function verifyWebhookSignature(array $data): bool
    {
        $receivedSignature = $data['merchantSignature'] ?? null;

        if (!$receivedSignature) {
            Log::error('WayForPay: merchantSignature missing in webhook');
            return false;
        }

        $generatedSignature = $this->generateWebhookSignature($data);
        $isValid = hash_equals($generatedSignature, $receivedSignature);

        if (!$isValid) {
            Log::error('Signature mismatch', [
                'received' => $receivedSignature,
                'generated' => $generatedSignature,
                'signature_string' => $this->getWebhookSignatureString($data),
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
     * ⚠️ ПРИЙМАЄ МАСИВ
     */
    protected function generateWebhookSignature(array $data): string
    {
        $signatureString = $this->getWebhookSignatureString($data);
        return hash_hmac('md5', $signatureString, $this->merchantSecretKey);
    }

    /**
     * ⚠️ НОВИЙ МЕТОД: Формує рядок для підпису webhook
     */
    protected function getWebhookSignatureString(array $data): string
    {
        return implode(';', [
            $data['merchantAccount'] ?? '',
            $data['orderReference'] ?? '',
            $data['amount'] ?? '',
            $data['currency'] ?? '',
            $data['authCode'] ?? '',
            $data['cardPan'] ?? '',
            $data['transactionStatus'] ?? '',
            $data['reasonCode'] ?? '',
        ]);
    }

    /**
     * Debug helper
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

    /**
     * Регулярне списання
     */
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
            'recToken' => $subscription->gateway_subscription_id,
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
            $payment->markAsCompleted();
            $subscription->renew();

            return [
                'success' => true,
                'payment' => $payment,
            ];
        }

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

        $signatureString = implode(';', [
            $data['merchantAccount'],
            $data['orderReference'],
        ]);
        $data['merchantSignature'] = hash_hmac('md5', $signatureString, $this->merchantSecretKey);

        $response = Http::post('https://api.wayforpay.com/api', $data);

        return $response->json();
    }
}
