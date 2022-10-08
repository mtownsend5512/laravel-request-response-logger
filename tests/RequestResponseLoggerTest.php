<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Mtownsend\RequestResponseLogger\Middleware\LogRequestsAndResponses;
use Mtownsend\RequestResponseLogger\Models\RequestResponseLog;
use Mtownsend\RequestResponseLogger\RequestResponseLogger;
use Mtownsend\RequestResponseLogger\Support\Logging\LogAll;
use Orchestra\Testbench\TestCase;

class RequestResponseLoggerTest extends TestCase
{
    use RefreshDatabase;

    protected $loadEnvironmentVariables = false;

    public $defaultConfig = [
        'logging_model' => RequestResponseLog::class,
        'logging_should_queue' => false,
        'get_json_values_as_array' => true,
        'should_log_handler' => LogAll::class,
    ];

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
            'should_log_handler' => LogAll::class,
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

        $router->any('/html/{code?}', function ($code = 200) {
            return response('<div><h1>An HTML header</h1><p>Some paragraph text below it</p></div>', $code);
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

        $this->requestHeaders = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'X-API-Token' => '39a0jg08j-4f8as0-f9a83jd'
        ];

        $this->requestData = [
            'email' => 'john.doe@email.com',
            'name' => 'John Doe',
            'password' => 'secret'
        ];

        // Code after application created.
    }

    /** @test */
    public function middlewareSavesAGetRequest()
    {
        $request = $this->get('/test');
        $log = RequestResponseLog::first();

        $this->assertSame($log->request_method, 'GET');
    }

    /** @test */
    public function middlewareSavesAPostRequest()
    {
        $request = $this->post('/test');
        $log = RequestResponseLog::first();

        $this->assertSame($log->request_method, 'POST');
    }

    /** @test */
    public function middlewareSavesPostData()
    {
        $request = $this->post('/test', $this->requestData);
        $log = RequestResponseLog::first();

        $this->assertSame($log->request_body['email'], $this->requestData['email']);
    }

    /** @test */
    public function middlewareSavesJsonPostData()
    {
        $request = $this->postJson('/test', $this->requestData);
        $log = RequestResponseLog::first();

        $this->assertSame($log->request_body['email'], $this->requestData['email']);
    }

    /** @test */
    public function middlewareSavesXmlPostData()
    {
        $headers = array_merge($this->requestHeaders, [
            'Content-Type' => 'text/xml'
        ]);
        $xml = file_get_contents(__DIR__ . '/dummy/test.xml');
        $request = $this->call('post',
            '/test',
            [],
            [],
            [],
            $this->transformHeadersToServerVars($headers),
            $xml
        );
        $log = RequestResponseLog::first();

        $this->assertSame($log->request_body, $xml);
    }

    /** @test */
    public function middlewareSavesHeaderData()
    {
        $request = $this->postJson('/test', [], $this->requestHeaders);
        $log = RequestResponseLog::first();

        $this->assertSame($log->request_headers['x-api-token'], $this->requestHeaders['X-API-Token']);
    }

    /** @test */
    public function modelSuccessfulScopeWorks()
    {
        $badLog = RequestResponseLog::create([
            'response_http_code' => 401
        ]);
        $goodLogs = RequestResponseLog::insert([[
            'response_http_code' => 201,
            'created_at' => now(),
            'updated_at' => now()
        ], [
            'response_http_code' => 200,
            'created_at' => now(),
            'updated_at' => now()
        ]]);

        $this->assertSame(RequestResponseLog::successful()->count(), 2);
    }

    /** @test */
    public function modelFailedScopeWorks()
    {
        $goodLog = RequestResponseLog::create([
            'response_http_code' => 200
        ]);
        $badLogs = RequestResponseLog::insert([[
            'response_http_code' => 401,
            'created_at' => now(),
            'updated_at' => now()
        ], [
            'response_http_code' => 500,
            'created_at' => now(),
            'updated_at' => now()
        ]]);

        $this->assertSame(RequestResponseLog::failed()->count(), 2);
    }
}
