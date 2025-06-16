<?php

namespace USER_STORY\Objects;

use USER_STORY\Components\Devices;
use USER_STORY\Exceptions\DatabaseException;
use USER_STORY\Objects\AbstractObject;

class Device_IP extends AbstractObject {

	/**
	 * Object ID
	 *
	 * @var int
	 */
	protected $id = 0;

	/**
	 * Device object
	 *
	 * @var Device
	 */
	protected $device;

	/**
	 * IP value
	 *
	 * @var string
	 */
	protected $ip;

	/**
	 * Date created
	 *
	 * @var \DateTime
	 */
	protected $created_at;

	/**
	 * Get created date
	 *
	 * @return \DateTime
	 */
	public function get_created_at() {
		return $this->created_at;
	}

	/**
	 * Get created date
	 *
	 * @param \DateTime $created_at Datetime.
	 *
	 * @return $this
	 */
	public function set_created_at( $created_at ) {
		$this->created_at = $created_at;

		return $this;
	}

	/**
	 * Get device
	 *
	 * @return Device
	 */
	public function get_device() {
		return $this->device;
	}

	/**
	 * Set device
	 *
	 * @param Device $device Device object.
	 *
	 * @return $this
	 */
	public function set_device( $device ) {
		$this->device = $device;

		return $this;
	}

	/**
	 * Get ID
	 *
	 * @return int
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Set ID
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
	 * Get IP
	 *
	 * @return string
	 */
	public function get_ip() {
		return $this->ip;
	}

	/**
	 * Set IP
	 *
	 * @param string $ip IP.
	 *
	 * @return $this
	 */
	public function set_ip( $ip ) {
		$this->ip = $ip;

		return $this;
	}

	/**
	 * Load from row
	 *
	 * @param object $row Database row.
	 *
	 * @return $this
	 */
	public static function load_from_object( $row ) {
		return ( new Device_IP() )
			->set_id( $row->ID )
			->set_device( Devices::find( $row->device_id ) )
			->set_ip( $row->ip )
			->set_created_at( new \DateTime( $row->created_at ) );
	}

	/**
	 * Map fields to columns
	 *
	 * @return array
	 */
	protected function column_map() {
		return array(
			'device_uuid' => $this->device->get_uuid(),
			'ip'          => $this->ip,
		);
	}

	/**
	 * Save object
	 *
	 * @throws DatabaseException Throws exception on database error.
	 */
	public function save() {
		global $wpdb;

		if ( $this->id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$ret = $wpdb->update( $wpdb->device_ips, $this->column_map(), array( 'ID' => $this->id ) );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$ret = $wpdb->insert( $wpdb->device_ips, $this->column_map() );
			if ( $ret ) {
				$this->id = $wpdb->insert_id;
			}
		}

		if ( false === $ret ) {
			throw new DatabaseException( esc_html( $wpdb->last_error ) );
		}
	}
}
