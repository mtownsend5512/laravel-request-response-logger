<?php

namespace Mtownsend\RequestResponseLogger\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Mtownsend\RequestResponseLogger\Jobs\LogResponseRequest;

class LogRequestsAndResponses
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }

    /**
     * Execute terminable actions after the response is returned.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Illuminate\Http\Response $response
     * @return void
     */
    public function terminate($request, $response): void
    {
        $data = [
            'request_method' => $request->method(),
            'request_headers' => Collection::make($request->headers->all())->transform(function ($item) {
                return head($item);
            }) ?? [],
            'request_body' => $this->handleRequestBody($request),
            'request_url' => $request->url(),
            'response_headers' => Collection::make($response->headers->all())->transform(function ($item) {
                return head($item);
            }) ?? [],
            'response_body' => $response->getContent(),
            'response_http_code' => $response->status()
        ];

        $method = $this->dispatchType();
        LogResponseRequest::$method($data);
    }

    /**
     * Legacy support for older versions of Laravel that named the synchronous
     * `dispatchNow` as well as newer versions that use `dispatchSync`.
     *
     * @return string
     */
    private function dispatchType()
    {
        if (app()->version() < 8 && config('log-requests-and-responses.logging_should_queue')) {
            return 'dispatchNow';
        } elseif (app()->version() >= 8 && config('log-requests-and-responses.logging_should_queue')) {
            return 'dispatchSync';
        } else {
            return 'dispatch';
        }
    }

    /**
     * Determine if the incoming request is json, arrayable (by Laravel)
     * or is XML or some other form of plain text that isn't arrayble.
     *
     * @param  \Illuminate\Http\Request $request
     * @return mixed
     */
    private function handleRequestBody($request)
    {
        if (!empty($request->all())) {
            return $request->all();
        } elseif (empty($request->all()) && !empty($request->getContent())) {
            return $request->getContent();
        }

        return null;
    }
}
