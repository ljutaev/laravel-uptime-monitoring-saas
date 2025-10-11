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

        $paymentData = [
            'merchantAccount' => $this->merchantAccount,
            'merchantDomainName' => $this->merchantDomainName,
            'orderReference' => $orderReference,
            'orderDate' => $orderDate,
            'amount' => $amount,
            'currency' => $currency,
            'productName' => [$productName],
            'productCount' => [1],
            'productPrice' => [$amount],
            'clientEmail' => $user->email,
            'clientFirstName' => $user->name ?? 'User',
            'clientLastName' => $user->name ?? 'User',
            'clientPhone' => $data['phone'] ?? '',
            'returnUrl' => route('subscription.payment.return'),
            'serviceUrl' => route('webhooks.wayforpay'),
        ];

        $paymentData['merchantSignature'] = $this->generateSignature($paymentData);

        return [
            'success' => true,
            'payment' => $payment,
            'form_data' => $paymentData,
            'action_url' => 'https://secure.wayforpay.com/pay',
        ];
    }

    public function handleWebhook(Request $request): array
    {
        Log::info('WayForPay Webhook', $request->all());

        if (!$this->verifyWebhookSignature($request)) {
            Log::error('Invalid signature');
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
        if (!$merchantSignature) return false;

        $data = [
            'merchantAccount' => $request->input('merchantAccount'),
            'orderReference' => $request->input('orderReference'),
            'amount' => $request->input('amount'),
            'currency' => $request->input('currency'),
            'authCode' => $request->input('authCode'),
            'cardPan' => $request->input('cardPan'),
            'transactionStatus' => $request->input('transactionStatus'),
            'reasonCode' => $request->input('reasonCode'),
        ];

        $generatedSignature = $this->generateWebhookSignature($data);
        return hash_equals($generatedSignature, $merchantSignature);
    }

    public function getPaymentUrl(array $paymentData): string
    {
        return 'https://secure.wayforpay.com/pay';
    }

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

        return hash_hmac('md5', $signatureString, $this->merchantSecretKey);
    }

    protected function generateWebhookSignature(array $data): string
    {
        $signatureString = implode(';', [
            $data['merchantAccount'],
            $data['orderReference'],
            $data['amount'],
            $data['currency'],
            $data['authCode'],
            $data['cardPan'],
            $data['transactionStatus'],
            $data['reasonCode'],
        ]);

        return hash_hmac('md5', $signatureString, $this->merchantSecretKey);
    }
}
