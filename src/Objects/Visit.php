<?php

namespace USER_STORY\Objects;

use USER_STORY\Components\Devices\DeviceIPs;
use USER_STORY\Components\Links;
use USER_STORY\Exceptions\DatabaseException;
use USER_STORY\Objects\AbstractObject;

class Visit extends AbstractObject {


	/**
	 * ID
	 *
	 * @var int
	 */
	protected $id = 0;

	/**
	 * Link Object
	 *
	 * @var Link
	 */
	protected $link;

	/**
	 * Device IP object
	 *
	 * @var Device_IP
	 */
	protected $device_ip;

	/**
	 * Screen height
	 *
	 * @var int
	 */
	protected $height;

	/**
	 * Screen width
	 *
	 * @var int
	 */
	protected $width;

	/**
	 * Created date
	 *
	 * @var \DateTime
	 */
	protected $created_at;

	/**
	 * X,Y Position
	 *
	 * @var string
	 */
	protected $position_xy;



	/**
	 * Load object from row
	 *
	 * @param object $row row.
	 *
	 * @return $this
	 */
	public static function load_from_object( $row ) {
		return ( new self() )
			->set_id( $row->ID )
			->set_created_at( new \DateTime( $row->created_at ) )
			->set_link( Links::find( $row->visible_link_id ) )
			->set_height( $row->height )
			->set_width( $row->width )
			->set_position_xy( $row->position_xy )
			->set_device_ip( DeviceIPs::find( $row->device_ip_id ) );
	}

	/**
	 * Mmap fields to column
	 *
	 * @return array
	 */
	protected function column_map() {
		return array(
			'visible_link_id' => $this->link->get_id(),
			'device_ip_id'    => $this->device_ip->get_id(),
			'height'          => $this->height,
			'width'           => $this->width,
			'position_xy'     => $this->position_xy,
		);
	}

	/**
	 * Persist row
	 *
	 * @throws DatabaseException Throws database exception on error.
	 */
	public function save() {
		global $wpdb;

		if ( $this->id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$ret = $wpdb->update( $wpdb->visible_link_visits, $this->column_map(), array( 'ID' => $this->id ) );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$ret = $wpdb->insert( $wpdb->visible_link_visits, $this->column_map() );

			if ( false !== $ret ) {
				$this->id = $wpdb->insert_id;
			}
		}

		if ( false === $ret ) {
			throw new DatabaseException( $wpdb->last_error );
		}
	}

	/**
	 * Get X,Y link position
	 *
	 * @return string
	 */
	public function get_position_xy() {
		return $this->position_xy;
	}

	/**
	 * Set X,Y position
	 *
	 * @param string $position_xy X,Y position.
	 *
	 * @return $this
	 */
	public function set_position_xy( $position_xy ) {
		$this->position_xy = $position_xy;

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
	 * @return self
	 */
	public function set_id( $id ) {
		$this->id = $id;
		return $this;
	}

	/**
	 * Get Link object
	 *
	 * @return Link
	 */
	public function get_link() {
		return $this->link;
	}

	/**
	 * Set Link object
	 *
	 * @param Link $link Link object.
	 * @return self
	 */
	public function set_link( $link ) {
		$this->link = $link;
		return $this;
	}

	/**
	 * Get Device IP object
	 *
	 * @return Device_IP
	 */
	public function get_device_ip() {
		return $this->device_ip;
	}

	/**
	 * Set Device IP object
	 *
	 * @param Device_IP $device_ip Device IP object.
	 * @return self
	 */
	public function set_device_ip( $device_ip ) {
		$this->device_ip = $device_ip;
		return $this;
	}

	/**
	 * Get screen height
	 *
	 * @return int
	 */
	public function get_height() {
		return $this->height;
	}

	/**
	 * Set screen height
	 *
	 * @param int $height Screen height.
	 * @return self
	 */
	public function set_height( $height ) {
		$this->height = $height;
		return $this;
	}

	/**
	 * Get screen width
	 *
	 * @return int
	 */
	public function get_width() {
		return $this->width;
	}

	/**
	 * Set screen width
	 *
	 * @param int $width Screen width.
	 * @return self
	 */
	public function set_width( $width ) {
		$this->width = $width;
		return $this;
	}

	/**
	 * Get created date
	 *
	 * @return \DateTime
	 */
	public function get_created_at() {
		return $this->created_at;
	}

	/**
	 * Set created date
	 *
	 * @param \DateTime $created_at Created date.
	 * @return self
	 */
	public function set_created_at( $created_at ) {
		$this->created_at = $created_at;
		return $this;
	}
}
