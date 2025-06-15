<?php

namespace USER_STORY\Routes;

use ReflectionClass;
use USER_STORY\Components\AbstractComponent;
use WP_REST_Controller;

abstract class AbstractRoute extends WP_REST_Controller {

	/**
	 * Component instance
	 *
	 * @var AbstractComponent
	 */
	protected $component;

	/**
	 * Base namespace for REST route
	 *
	 * @var string
	 */
	protected $namespace;

	/**
	 * Resource name for REST route
	 *
	 * @var string
	 */
	protected $resource_name;

	/**
	 * Abstract route construct
	 *
	 * @param AbstractComponent $component Component instance.
	 */
	public function __construct( $component ) {
		$this->component = $component;

		$this->namespace     = 'user-story';
		$reflection          = new ReflectionClass( $this );
		$this->resource_name = strtolower( $reflection->getShortName() );
	}
}
