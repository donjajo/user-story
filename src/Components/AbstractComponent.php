<?php

namespace USER_STORY\Components;

use USER_STORY\Cronies\AbstractCronie;

/**
 * Abstract class representing a generic component with utility methods for caching,
 * and abstract methods for extending component functionality.
 */
abstract class AbstractComponent {

	/**
	 * Component name
	 *
	 * @var null|string
	 */
	private static $component_name = null;

	/**
	 * Get component name
	 *
	 * @return null
	 */
	public static function component_name() {
		if ( ! static::$component_name ) {
			$ref                    = new \ReflectionClass( static::class );
			static::$component_name = $ref->getShortName();
		}

		return static::$component_name;
	}

	/**
	 * Add cache object
	 *
	 * @param string|int $key cache object key.
	 * @param mixed      $data cache data.
	 *
	 * @return bool
	 */
	protected static function add_cache( $key, $data ) {
		return wp_cache_add( $key, $data, static::component_name() );
	}

	/**
	 * Set cache object
	 *
	 * @param string|int $key cache key.
	 * @param mixed      $data cache data.
	 *
	 * @return bool
	 */
	protected static function set_cache( $key, $data ) {
		return wp_cache_set( $key, $data, static::component_name() );
	}

	/**
	 * Get cache data
	 *
	 * @param string|int $key Cache key.
	 * @param bool       $force Whether to force an update of the local cache from the persistent cache.
	 * @param bool|null  $found returns boolean if found or not.
	 *
	 * @return mixed
	 */
	protected static function get_cache( $key, $force = false, &$found = null ) {
		return wp_cache_get( $key, static::component_name(), $force, $found );
	}

	/**
	 * Delete cache object
	 *
	 * @param string|int $key Cache key.
	 *
	 * @return bool
	 */
	protected static function delete_cache( $key ) {
		return wp_cache_delete( $key, static::component_name() );
	}

	/**
	 * Tries to set cache if not available. And returns cache object
	 *
	 * @param string|int $key cache key.
	 * @param callable   $setter setter callback.
	 *
	 * @return mixed
	 */
	protected static function try_set_cache( $key, $setter ) {
		$data = static::get_cache( $key, false, $found );
		if ( false === $found ) {
			$data = $setter();
			static::set_cache( $key, $data );
		}

		return $data;
	}

	/**
	 * Find component object
	 *
	 * @param int|string $id Object ID/uuid.
	 *
	 * @return mixed
	 */
	abstract public static function find( $id );

	/**
	 * Provide component's rest routes
	 *
	 * @return array
	 */
	public static function rest_routes() {
		return array();
	}

	/**
	 * Provide component related cronies
	 *
	 * @return array<AbstractCronie>
	 */
	public static function cronies() {
		return array();
	}

	/**
	 * Override to add hooks to run
	 *
	 * @return void
	 */
	public function hooks() {
	}
}
