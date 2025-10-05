<?php

namespace App\Services;

use App\Models\User;
use App\Models\Subscription;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WayForPayService
{
    private string $merchantAccount;
    private string $merchantSecretKey;
    private string $merchantDomain;

    public function __construct()
    {
        $this->merchantAccount = config('services.wayforpay.merchant_account');
        $this->merchantSecretKey = config('services.wayforpay.secret_key');
        $this->merchantDomain = config('services.wayforpay.domain');
    }

    public function createSubscriptionPayment(User $user, string $plan): array
    {
        $planConfig = Subscription::limits()[$plan];
        $orderId = 'SUB-' . $user->id . '-' . time();

        $orderDate = time();
        $amount = $planConfig['price'];
        $currency = 'USD';
        $productName = ["Підписка {$plan}"];
        $productCount = [1];
        $productPrice = [$amount];

        $data = [
            'merchantAccount' => $this->merchantAccount,
            'merchantDomainName' => $this->merchantDomain,
            'orderReference' => $orderId,
            'orderDate' => $orderDate,
            'amount' => $amount,
            'currency' => $currency,
            'productName' => $productName,
            'productCount' => $productCount,
            'productPrice' => $productPrice,
            'clientEmail' => $user->email,
            'clientFirstName' => $user->name,
            'clientLastName' => $user->name,
            'language' => 'UA',
        ];

        $data['merchantSignature'] = $this->generateSignature($data);

        return $data;
    }

    public function createRecurringPayment(User $user, string $plan, string $recToken): bool
    {
        $planConfig = Subscription::limits()[$plan];
        $orderId = 'REC-' . $user->id . '-' . time();

        $data = [
            'orderReference' => $orderId,
            'orderDate' => time(),
            'amount' => $planConfig['price'],
            'currency' => 'USD',
            'merchantAccount' => $this->merchantAccount,
            'productName' => ["Підписка {$plan}"],
            'productCount' => [1],
            'productPrice' => [$planConfig['price']],
            'recToken' => $recToken,
        ];

        $data['merchantSignature'] = $this->generateSignature($data);

        $response = Http::post('https://api.wayforpay.com/api', $data);

        return $response->successful() && $response->json('reasonCode') === 1100;
    }

    public function handleCallback(array $data): bool
    {
        // Перевірка підпису
        if (!$this->verifySignature($data)) {
            return false;
        }

        $orderReference = $data['orderReference'];
        $transactionStatus = $data['transactionStatus'];

        // Розбираємо orderReference
        if (str_starts_with($orderReference, 'SUB-')) {
            // Нова підписка
            [$prefix, $userId, $timestamp] = explode('-', $orderReference);

            $user = User::find($userId);
            if (!$user) return false;

            if ($transactionStatus === 'Approved') {
                // Визначаємо план з суми
                $plan = $this->determinePlanFromAmount($data['amount']);

                Subscription::create([
                    'user_id' => $userId,
                    'plan' => $plan,
                    'status' => 'active',
                    'starts_at' => now(),
                    'ends_at' => now()->addMonth(),
                    'wayforpay_order_id' => $orderReference,
                ]);

                // Зберігаємо recToken для автоплатежів
                if (isset($data['recToken'])) {
                    $user->update(['wayforpay_rec_token' => $data['recToken']]);
                }
            }
        }

        return true;
    }

    private function generateSignature(array $data): string
    {
        $signatureString = implode(';', [
            $data['merchantAccount'],
            $data['merchantDomainName'] ?? $this->merchantDomain,
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

    private function verifySignature(array $data): bool
    {
        $receivedSignature = $data['merchantSignature'];

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

        $calculatedSignature = hash_hmac('md5', $signatureString, $this->merchantSecretKey);

        return $receivedSignature === $calculatedSignature;
    }

    private function determinePlanFromAmount(float $amount): string
    {
        return match($amount) {
            5.0 => 'pro',
            15.0 => 'business',
            default => 'free',
        };
    }
}


// app/Http/Controllers/SubscriptionController.php
namespace App\Http\Controllers;

use App\Services\WayForPayService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SubscriptionController extends Controller
{
    public function __construct(
        private WayForPayService $wayforpay
    ) {}

    public function index()
    {
        return Inertia::render('Subscription/Index', [
            'plans' => \App\Models\Subscription::limits(),
            'currentSubscription' => auth()->user()->subscription,
        ]);
    }

    public function initiate(Request $request)
    {
        $request->validate([
            'plan' => 'required|in:pro,business'
        ]);

        $paymentData = $this->wayforpay->createSubscriptionPayment(
            auth()->user(),
            $request->plan
        );

        return Inertia::render('Subscription/Payment', [
            'paymentData' => $paymentData,
        ]);
    }

    public function callback(Request $request)
    {
        $success = $this->wayforpay->handleCallback($request->all());

        if ($success) {
            return response()->json(['status' => 'success']);
        }

        return response()->json(['status' => 'error'], 400);
    }
}


// config/services.php

// routes/web.php

