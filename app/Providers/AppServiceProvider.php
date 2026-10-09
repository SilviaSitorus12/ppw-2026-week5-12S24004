<?php

namespace App\Providers;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Tampilan pagination memakai markup Bootstrap 5.
        Paginator::useBootstrapFive();

        // Audit kueri SQL → storage/logs/laravel.log (hanya di lingkungan lokal).
        if ($this->app->environment('local')) {
            DB::listen(function (QueryExecuted $query) {
                Log::info(sprintf(
                    '[SQL AUDIT] (%.2f ms) %s | bindings: %s',
                    $query->time,
                    $query->sql,
                    json_encode($query->bindings)
                ));
            });
        }
    }
}