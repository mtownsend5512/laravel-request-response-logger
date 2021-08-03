<?php

namespace Mtownsend\RequestResponseLogger\Providers;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Mtownsend\RequestResponseLogger\Console\Commands\CleanRequestResponseLogs;

class RequestResponseLoggerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/log-requests-and-responses.php' => config_path('log-requests-and-responses.php')
        ], 'config');

        if (! $this->migrationExists()) {
            $this->publishes([
                __DIR__ . '/../database/migrations/create_request_response_logs_table.php' => database_path('migrations/' . date('Y_m_d_His_', time()) . 'create_request_response_logs_table.php')
            ], 'migrations');
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->commands([
            CleanRequestResponseLogs::class
        ]);
    }

    /**
     * Determine if the user has already published the migration before.
     *
     * @return bool
     */
    private function migrationExists()
    {
        $files = (new Filesystem())->files(database_path('migrations'));
        foreach ($files as $file) {
            if (Str::endsWith(basename($file), '_create_request_response_logs_table.php')) {
                return true;
            } else {
                continue;
            }
        }
        return false;
    }
}
