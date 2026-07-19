<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
     /* Register application services.*/
    public function register(): void
    {

        $this->app->bind(
            \App\Contracts\Repositories\CustomerRepositoryInterface::class,
            \App\Repositories\Eloquent\EloquentCustomerRepository::class
        );

        $this->app->bind(
            \App\Contracts\Repositories\UserRepositoryInterface::class,
            \App\Repositories\Eloquent\EloquentUserRepository::class
        );

        $this->app->bind(
            \App\Contracts\Repositories\OrderRepositoryInterface::class,
            \App\Repositories\Eloquent\EloquentOrderRepository::class
        );

        $this->app->bind(
            \App\Contracts\Repositories\LoyaltyTransactionRepositoryInterface::class,
            \App\Repositories\Eloquent\EloquentLoyaltyTransactionRepository::class
        );

        $this->app->bind(
            \App\Contracts\Repositories\BranchRepositoryInterface::class,
            \App\Repositories\Eloquent\EloquentBranchRepository::class
        );

        $this->app->bind(
            \App\Contracts\Services\PointsCalculatorInterface::class,
            \App\Services\Points\TieredPointsCalculator::class
        );
    }

    public function boot(): void
    {
        
    }
}
