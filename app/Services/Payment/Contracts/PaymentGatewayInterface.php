<?php

namespace App\Services\Payment\Contracts;

use App\Models\Subscription;

interface PaymentGatewayInterface
{
    /**
     * Створення платежу
     */
    public function createPayment(Subscription $subscription, array $data = []): array;

    /**
     * Обробка webhook
     * ⚠️ ПРИЙМАЄ МАСИВ, ПОВЕРТАЄ JSON STRING
     */
    public function handleWebhook(array $data): string;

    /**
     * Скасування підписки
     */
    public function cancelSubscription(Subscription $subscription): bool;

    /**
     * Отримання статусу платежу
     */
    public function getPaymentStatus(string $transactionId): string;

    /**
     * Перевірка підпису webhook
     * ⚠️ ПРИЙМАЄ МАСИВ
     */
    public function verifyWebhookSignature(array $data): bool;

    /**
     * Отримання URL для оплати
     */
    public function getPaymentUrl(array $paymentData): string;

    /**
     * Регулярне списання
     */
    public function chargeRecurring(Subscription $subscription): array;

    /**
     * Перевірка статусу платежу через API
     */
    public function checkPaymentStatus(string $orderReference): array;
}
