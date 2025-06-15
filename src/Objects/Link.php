<?php

namespace USER_STORY\Objects;

use USER_STORY\Exceptions\DatabaseException;
use USER_STORY\Objects\AbstractObject;

class Link extends AbstractObject {


	/**
	 * ID
	 *
	 * @var int
	 */
	protected $id = 0;

	/**
	 * URL scheme
	 *
	 * @var string
	 */
	protected $scheme;

	/**
	 * Link hostname
	 *
	 * @var null|string
	 */
	protected $host_name = null;

	/**
	 * Link path
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * Query string
	 *
	 * @var null|string
	 */
	protected $query = null;

	/**
	 * Fragments
	 *
	 * @var null|string
	 */
	protected $fragment = null;

	/**
	 * Link ID
	 *
	 * @return int
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Set Link ID
	 *
	 * @param int $id ID.
	 *
	 * @return $this
	 */
	public function set_id( $id ) {
		$this->id = $id;

		return $this;
	}

	/**
	 * Set fragements
	 *
	 * @param string|null $fragment Link fragment.
	 *
	 * @return $this
	 */
	public function set_fragment( $fragment ) {
		$this->fragment = $fragment;

		return $this;
	}

	/**
	 * Get fragment
	 *
	 * @return string|null
	 */
	public function get_fragment() {
		return $this->fragment;
	}

	/**
	 * Get host name
	 *
	 * @return string|null
	 */
	public function get_host_name() {
		return $this->host_name;
	}

	/**
	 * Set host name
	 *
	 * @param string|null $host_name Host name.
	 *
	 * @return $this
	 */
	public function set_host_name( $host_name ) {
		$this->host_name = $host_name;

		return $this;
	}

	/**
	 * Set path
	 *
	 * @param string $path Link path.
	 *
	 * @return $this
	 */
	public function set_path( $path ) {
		$this->path = $path;

		return $this;
	}

	/**
	 * Get link path
	 *
	 * @return string
	 */
	public function get_path() {
		return $this->path;
	}

	/**
	 * Get link query
	 *
	 * @return string|null
	 */
	public function get_query() {
		return $this->query;
	}

	/**
	 * Set link query
	 *
	 * @param string|null $query link query.
	 *
	 * @return $this
	 */
	public function set_query( $query ) {
		$this->query = $query;

		return $this;
	}

	/**
	 * Set link scheme
	 *
	 * @param string $scheme link scheme.
	 *
	 * @return $this
	 */
	public function set_scheme( $scheme ) {
		$this->scheme = $scheme;

		return $this;
	}

	/**
	 * Get link scheme
	 *
	 * @return string
	 */
	public function get_scheme() {
		return $this->scheme;
	}

	/**
	 * Load rows to object
	 *
	 * @param object $row row object.
	 *
	 * @return self
	 */
	public static function load_from_object( $row ) {
		return ( new self() )
			->set_path( $row->path )
			->set_host_name( $row->hostname )
			->set_query( $row->query )
			->set_fragment( $row->fragment )
			->set_id( $row->ID );
	}

	/**
	 * Map fields to column
	 *
	 * @return array
	 */
	protected function column_map() {
		return array(
			'scheme'   => $this->scheme,
			'hostname' => $this->host_name,
			'path'     => $this->path,
			'query'    => $this->query,
			'fragment' => $this->fragment,
		);
	}

	/**
	 * Save object
	 *
	 * @throws DatabaseException Throws on error.
	 */
	public function save() {
		global $wpdb;

		if ( $this->id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$ret = $wpdb->update( $wpdb->visible_links, $this->column_map(), array( 'ID' => $this->id ) );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$ret = $wpdb->insert( $wpdb->visible_links, $this->column_map() );

			if ( false !== $ret ) {
				$this->id = $wpdb->insert_id;
			}
		}

		if ( false === $ret ) {
			throw new DatabaseException( $wpdb->last_error );
		}
	}
}
