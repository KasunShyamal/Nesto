<?php

namespace App\Contracts\Services;

use App\Models\Order;

interface PointsCalculatorInterface
{
    public function calculate(Order $order): int;
}
