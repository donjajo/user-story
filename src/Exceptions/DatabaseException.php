<?php

namespace USER_STORY\Exceptions;

use USER_STORY\Exceptions\BaseException;

class DatabaseException extends BaseException
{
	/**
	 * Exception code
	 *
	 * @var int error code
	 */
	protected $code = 500;
}
