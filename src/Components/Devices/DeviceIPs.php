<?php

namespace USER_STORY\Components\Devices;

use USER_STORY\Components\AbstractComponent;
use USER_STORY\Exceptions\BaseException;
use USER_STORY\Exceptions\DatabaseException;
use USER_STORY\Objects\Device;
use USER_STORY\Objects\Device_IP;

class DeviceIPs extends AbstractComponent {


	/**
	 * Get Device IP by ID
	 *
	 * @param int $id ID.
	 *
	 * @return null|Device_IP
	 */
	public static function find( $id ) {
		return self::try_set_cache(
			$id,
			function () use ( $id ) {
				global $wpdb;

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->device_ips} WHERE ID = %d", $id ) );

				return $row ? Device_IP::load_from_object( $row ) : null;
			}
		);
	}

	/**
	 * Create a new Device IP record.
	 *
	 * @param Device $device Device instance.
	 * @param string $ip IP address.
	 *
	 * @return Device_IP The newly created Device IP instance.
	 *
	 * @throws BaseException If an error occurs during creation.
	 */
	public static function create( $device, $ip ) {
		assert( $device instanceof Device );
		assert( is_string( $ip ) );

		try {
			user_story_db_start_transaction();
			$device_ip = new Device_IP();
			$device_ip->set_device( $device )
				->set_ip( $ip )
				->save();

			return $device_ip;
		} catch ( DatabaseException $e ) {
			if ( user_story_is_debug() ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( $e->getMessage() );
			}

			throw new BaseException( esc_html__( 'Unknown error occurred', 'user-story' ) );
		} finally {
			user_story_db_commit_transaction_or_rollback();
		}
	}
}
