<?php

namespace App\Listeners;

use App\Contracts\Repositories\LoyaltyTransactionRepositoryInterface;
use App\Contracts\Services\PointsCalculatorInterface;
use App\Events\OrderCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CalculateLoyaltyPoints implements ShouldQueue
{
    use InteractsWithQueue;

    protected PointsCalculatorInterface $pointsCalculator;
    protected LoyaltyTransactionRepositoryInterface $transactionRepository;

    public function __construct(
        PointsCalculatorInterface $pointsCalculator,
        LoyaltyTransactionRepositoryInterface $transactionRepository
    ) {
        $this->pointsCalculator = $pointsCalculator;
        $this->transactionRepository = $transactionRepository;
    }

    // handle event asynchronously to calculate and log points
    public function handle(OrderCreated $event): void
    {
        $order = $event->order;

        // Idempotency guard: prevent duplicate point awards if queue jobs are retried
        if ($this->transactionRepository->existsForOrder($order->id)) {
            return;
        }

        $points = $this->pointsCalculator->calculate($order);

        if ($points > 0) {
            // Load branch relationship for detailed description
            $order->load('branch');

            $this->transactionRepository->create([
                'customer_id' => $order->customer_id,
                'order_id' => $order->id,
                'points' => $points,
                'type' => 'earn',
                'description' => "Earned {$points} points from Invoice {$order->invoice_number} at {$order->branch->name}.",
            ]);
        }
    }
}
