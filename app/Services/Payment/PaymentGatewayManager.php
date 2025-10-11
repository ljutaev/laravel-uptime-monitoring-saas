<?php

namespace App\Services\Payment;

use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\Gateways\WayForPayGateway;
use InvalidArgumentException;

class PaymentGatewayManager
{
    protected array $gateways = [];

    public function __construct()
    {
        $this->registerGateway('wayforpay', WayForPayGateway::class);
    }

    public function registerGateway(string $name, string $class): void
    {
        $this->gateways[$name] = $class;
    }

    public function gateway(string $name): PaymentGatewayInterface
    {
        if (!isset($this->gateways[$name])) {
            throw new InvalidArgumentException("Gateway [{$name}] not found.");
        }

        return app($this->gateways[$name]);
    }

    public function getAvailableGateways(): array
    {
        return array_keys($this->gateways);
    }
}
