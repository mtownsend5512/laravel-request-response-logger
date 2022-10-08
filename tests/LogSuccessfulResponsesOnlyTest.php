<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Mtownsend\RequestResponseLogger\Middleware\LogRequestsAndResponses;
use Mtownsend\RequestResponseLogger\Models\RequestResponseLog;
use Mtownsend\RequestResponseLogger\RequestResponseLogger;
use Mtownsend\RequestResponseLogger\Support\Logging\LogSuccessOnly;
use Orchestra\Testbench\TestCase;

class LogSuccessfulResponsesOnlyTest extends TestCase
{
    use RefreshDatabase;

    protected $loadEnvironmentVariables = false;

    /**
     * Ignore package discovery from.
     *
     * @return array
     */
    public function ignorePackageDiscoveriesFrom()
    {
        return [];
    }

    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [];
    }

    /**
     * Override application aliases.
     *
     * @param  \Illuminate\Foundation\Application  $app
     *
     * @return array
     */
    protected function overrideApplicationProviders($app)
    {
        return [];
    }

    /**
     * Get application timezone.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return string|null
     */
    protected function getApplicationTimezone($app)
    {
        return 'UTC';
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function defineEnvironment($app)
    {
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => ''
        ]);

        $app['config']->set('log-requests-and-responses', [
            'logging_model' => RequestResponseLog::class,
            'logging_should_queue' => false,
            'get_json_values_as_array' => true,
            'should_log_handler' => LogSuccessOnly::class,
        ]);
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function setUpDatabase($app)
    {
        include_once __DIR__ . '/../src/database/migrations/create_request_response_logs_table.php';

        (new CreateRequestResponseLogsTable())->up();
    }

    /**
     * Define routes setup.
     *
     * @param  \Illuminate\Routing\Router  $router
     *
     * @return void
     */
    protected function defineRoutes($router)
    {
        $router->any('/test/{code?}', function ($code = 200) {
            return response()->json([
                'status' => 'success',
                'received' => request()->all()
            ], $code);
        })->middleware(LogRequestsAndResponses::class);
    }

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        // Code before application created.

        parent::setUp();

        $this->setUpDatabase($this->app);

        // Code after application created.
    }

    /**
     * @test
     */
    public function logSuccessfulResponsesOnly()
    {
        $request = $this->post('/test/401'); // error
        $request = $this->post('/test/200'); // success
        $request = $this->post('/test/404'); // error
        $log = RequestResponseLog::count();

        $this->assertSame($log, 1);
    }
}
