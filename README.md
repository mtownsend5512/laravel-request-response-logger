<p align="center">
<img src="https://banners.beyondco.de/Laravel%20Request%20%26%20Response%20Logger.png?theme=dark&packageManager=composer+require&packageName=mtownsend%2Flaravel-request-response-logger&pattern=plus&style=style_1&description=Capture+your+incoming+requests+and+corresponding+responses+with+ease&md=1&showWatermark=0&fontSize=75px&images=https%3A%2F%2Flaravel.com%2Fimg%2Flogomark.min.svg">
</p>

Easily capture every incoming request and the corresponding outgoing response in your Laravel app.

*This package is designed to work only with the Laravel framework.*

## Installation

Install via composer:

```
composer require mtownsend/laravel-request-response-logger
```

## Upgrading from v1.0.X -> v2.0.X

[Please see the release notes here.](https://github.com/mtownsend5512/laravel-request-response-logger/releases/tag/2.0.0)

### Registering the service provider (Laravel users)

For Laravel 5.4 and lower, add the following line to your ``config/app.php``:

```php
/*
 * Package Service Providers...
 */
Mtownsend\RequestResponseLogger\Providers\RequestResponseLoggerServiceProvider::class,
```

For Laravel 5.5 and greater, the package will auto register the provider for you.


### Publish the migration and config files

You will need to publish the migration and configuration file before you can begin using the package. To do so run the following in your console:

````
php artisan vendor:publish --provider="Mtownsend\RequestResponseLogger\Providers\RequestResponseLoggerServiceProvider"
````

Next, you need to run the migration for the new database table. Run the following in your console:

````
php artisan migrate
````

### Setting up the middleware (important!)

In order to begin logging requests and responses you will have to attach the middleware to the routes you want to log. The package *does not* make this assumption for you, since not everyone may want to log every route.

Now, navigate to your `/app/Http/Kernel.php`

You can choose which method you would like to use. We've provided a few different options below:

#### OPTION 1: Bind the middleware to a name you can call only on the routes you want

```php
protected $routeMiddleware = [
    ...
    'log.requests.responses' => \Mtownsend\RequestResponseLogger\Middleware\LogRequestsAndResponses::class,
];
```

Then apply the named middleware to whatever routes you want:

```php
Route::post('/some/route', SomeController::class)->middleware(['log.requests.responses']);
```

#### OPTION 2: Assign the middleware to a route group

```php
protected $middlewareGroups = [
    ...
    'api' => [
        ...
        \Mtownsend\RequestResponseLogger\Middleware\LogRequestsAndResponses::class,
    ],
];
```

#### OPTION 3: Assign the middleware to every route

```php
protected $middleware = [
    ...
    \Mtownsend\RequestResponseLogger\Middleware\LogRequestsAndResponses::class,
];
```

That's it! The middleware will log every incoming request it receives!

### Customizing your configuration (optional)

This package provides a few customizations you can make.

When you navigation to `app/config` you will see a `log-requests-and-responses.php` file. It will contain the following:

```php
return [

    /**
     * The model used to manage the database table for storing request and response logs.
     */
    'logging_model' => \Mtownsend\RequestResponseLogger\Models\RequestResponseLog::class,

    /**
     * When logging requests and responses, should the logging action be
     * passed off to the queue (true) or run synchronously (false)?
     */
    'logging_should_queue' => false,

    /**
     * If stored json should be transformed into an array when retrieved from the database.
     * Set to `false` to receive as a regular php object.
     */
    'get_json_values_as_array' => true,

    /**
     * The class responsible for determining if a request should be logged.
     * 
     * Out of the box options are:
     * Mtownsend\RequestResponseLogger\Support\Logging\LogAll::class,
     * Mtownsend\RequestResponseLogger\Support\Logging\LogClientErrorsOnly::class,
     * Mtownsend\RequestResponseLogger\Support\Logging\LogSuccessOnly::class,
     */
    'should_log_handler' => \Mtownsend\RequestResponseLogger\Support\Logging\LogAll::class,

];
```

## Housekeeping

You may want to utilize some housekeeping to prevent your logs table from getting too large. This package supplies a preregistered command for wiping the table clean. You may run it manually

````
php artisan request-response-logger:clean
````

You may also schedule it to be run automatically in `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    ...
    $schedule->command('request-response-logger:clean')->quarterly();
}
```

# Advanced Usage

## Conditional logging

This package provides support for specifying custom conditions before a request/response is logged to the database.

It comes with 3 default options out of the box:

* LogAll - log request & response
* LogClientErrorsOnly - log only responses that have an http status code of 4XX
* LogSuccessOnly - log only responses that have an http status code of 2XX
* ...or your own!

Creating your own conditional logic is pretty straightforward and can be done in 2 simple steps:

1. First, create a custom class that will perform your conditional checks for logging. For demonstration purposes let's say we're going to create a conditional logic check to only log requests made from external services and not your own web app. You can use the following code as a template.

```php
<?php

namespace App\Support\Logging;

use Illuminate\Http\Request;
use Mtownsend\RequestResponseLogger\Support\Logging\Contracts\ShouldLogContract;

class LogExternalRequests implements ShouldLogContract
{
    public $request;
    public $response;

    public function __construct(Request $request, $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * Return a truth-y value to log the request and response.
     * Return false-y value to skip logging.
     * 
     * @return bool
     */
    public function shouldLog(): bool
    {
        // Custom logic goes here...
    }
}
```

2. Open up your `config/log-requests-and-responses.php` and set the `should_log_handler` key to your class.

```php
[
    //...
    'should_log_handler' => \App\Support\Logging\LogExternalRequests::class,
]
```

...and that's it! Your custom logging logic will now be used any time the middleware is executed.

## Model scopes

If you would like to work with the data you've logged, you may want to retrieve data based on the http code your app returned for that request.

```php
use Mtownsend\RequestResponseLogger\Models\RequestResponseLog;

// Get every logged item with an http response code of 2XX:
RequestResponseLog::successful()->get();

// Get every logged item with an http response code that ISN'T 2XX:
RequestResponseLog::failed()->get();
```

## Replacing the `RequestResponseLog` model with your own

You may want to extend the base `RequestResponseLog` model with your own. This is entirely possible.

First, create your own model

````
php artisan make:model RequestResponseLog
````

Then in your model, extend the base model:

```php
<?php

namespace App\Models;

use Mtownsend\RequestResponseLogger\Models\RequestResponseLog as BaseRequestResponseLog;

class RequestResponseLog extends BaseRequestResponseLog
{
    //
}
```

Then in your `app/config/log-requests-and-responses.php`:

```php
'logging_model' => \App\Models\RequestResponseLog::class,
```

Now the package will utilize your model instead of the default one.


## Testing

You can run the tests with:

```bash
vendor/bin/phpunit
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
