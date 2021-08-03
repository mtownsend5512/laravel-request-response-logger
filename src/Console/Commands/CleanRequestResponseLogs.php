<?php

namespace Mtownsend\RequestResponseLogger\Console\Commands;

use Illuminate\Console\Command;

class CleanRequestResponseLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'request-response-logger:clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Truncate the request and response log table';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $model = config('log-requests-and-responses.logging_model');
        $model = (new $model)->truncate();

        $this->info('Request and response log table has been cleaned.');
    }
}
