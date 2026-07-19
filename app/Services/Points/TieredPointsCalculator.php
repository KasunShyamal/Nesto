<?php

namespace App\Services\Points;

use App\Contracts\Services\PointsCalculatorInterface;
use App\Models\Order;

class TieredPointsCalculator implements PointsCalculatorInterface
{
    private const MIN_ELIGIBLE_AMOUNT = 10000;
    private const LKR_PER_POINT = 100;

    // calculate loyalty points based on rules
    public function calculate(Order $order): int
    {
        if ($order->amount < self::MIN_ELIGIBLE_AMOUNT) {
            return 0;
        }

        return (int) ($order->amount / self::LKR_PER_POINT);
    }
}
