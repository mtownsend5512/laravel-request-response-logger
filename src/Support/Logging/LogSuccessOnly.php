<?php

namespace Mtownsend\RequestResponseLogger\Support\Logging;

use Illuminate\Http\Request;
use Mtownsend\RequestResponseLogger\Support\Logging\Contracts\ShouldLogContract;

class LogSuccessOnly implements ShouldLogContract
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
		$code = (int) $this->response->status();
		return (bool) ($code >= 200 && $code <= 299);
	}
}
