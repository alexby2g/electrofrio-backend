<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use ReflectionProperty;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('database.default') !== 'pgsql') {
            return;
        }

        /*
         * Neon can abort Laravel schema transactions before a Blueprint's
         * follow-up statements (unique indexes and foreign keys) are run.
         * Keep normal runtime transactions enabled while disabling only the
         * automatic transaction wrapper used by database migrations.
         */
        $connection = DB::connection();
        $connection->useDefaultSchemaGrammar();

        $grammar = $connection->getSchemaGrammar();
        $transactions = new ReflectionProperty($grammar::class, 'transactions');
        $transactions->setAccessible(true);
        $transactions->setValue($grammar, false);
    }
}
