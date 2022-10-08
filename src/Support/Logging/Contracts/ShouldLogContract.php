<?php

namespace Mtownsend\RequestResponseLogger\Support\Logging\Contracts;

interface ShouldLogContract
{
	public function shouldLog(): bool;
}