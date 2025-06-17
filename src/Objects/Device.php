<?php

namespace USER_STORY\Objects;

use DateTime;
use USER_STORY\Exceptions\DatabaseException;
use WP_User;

class Device extends AbstractObject {

	/**
	 * Device UUID
	 *
	 * @var string
	 */
	protected $uuid = null;

	/**
	 * Device user
	 *
	 * @var ?WP_User
	 */
	protected $user;

	/**
	 * Device user agent
	 *
	 * @var ?string
	 */
	protected $user_agent;

	/**
	 * Device create date
	 *
	 * @var DateTime
	 */
	protected $created_at;

	/**
	 * Get device UUID
	 *
	 * @return string
	 */
	public function get_uuid() {
		return $this->uuid;
	}

	/**
	 * Set Device UUID
	 *
	 * @param Device $uuid UUID.
	 */
	public function set_uuid( $uuid ) {
		$this->uuid = $uuid;

		return $this;
	}

	/**
	 * Get device user
	 *
	 * @return WP_User|null
	 */
	public function get_user() {
		return $this->user;
	}

	/**
	 * Set device user
	 *
	 * @param WP_User|null $user WP_User object.
	 *
	 * @return Device
	 */
	public function set_user( $user ) {
		$this->user = $user;

		return $this;
	}

	/**
	 * Get device user agent
	 *
	 * @return string|null
	 */
	public function get_user_agent() {
		return $this->user_agent;
	}

	/**
	 * Set user agent
	 *
	 * @param string|null $user_agent user agent.
	 *
	 * @return Device
	 */
	public function set_user_agent( $user_agent ) {
		$this->user_agent = $user_agent;

		return $this;
	}

	/**
	 * Get created_at
	 *
	 * @return DateTime
	 */
	public function get_created_at() {
		return $this->created_at;
	}

	/**
	 * Set created_at
	 *
	 * @param DateTime $created_at Date created.
	 *
	 * @return $this
	 */
	public function set_created_at( $created_at ) {
		$this->created_at = $created_at;
		return $this;
	}

	/**
	 * Load from row
	 *
	 * @param \StdClass $row row object.
	 * @return Device
	 */
	public static function load_from_object( $row ) {
		return ( new self() )
			->set_uuid( $row->uuid )
			->set_user( $row->user_id ? get_user_by( 'ID', $row->user ) : null )
			->set_user_agent( $row->user_agent )
			->set_created_at( new DateTime( $row->created_at ) );
	}

	/**
	 * Map fields to column
	 *
	 * @return array
	 */
	public function column_map() {
		return array(
			'user_id'    => $this->user ? $this->user->ID : null,
			'user_agent' => $this->user_agent,
		);
	}

	/**
	 * Save device object
	 *
	 * @return void
	 *
	 * @throws DatabaseException Throws database exception on database error.
	 */
	public function save() {
		global $wpdb;

		if ( $this->uuid ) {
			$ret = $wpdb->update( $wpdb->devices, $this->column_map(), array( 'uuid' => $this->uuid ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		} else {
			$values         = $this->column_map();
			$values['uuid'] = user_story_uuid4();
			$this->uuid     = $values['uuid'];
			$ret            = $wpdb->insert( $wpdb->devices, $values ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		}

		if ( false === $ret ) {
			throw new DatabaseException( esc_html( $wpdb->last_error ) );
		}
	}
}
