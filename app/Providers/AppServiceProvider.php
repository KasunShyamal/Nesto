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
    }

    public function boot(): void
    {
        //
    }
}
