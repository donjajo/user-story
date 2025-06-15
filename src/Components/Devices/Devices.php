<?php

namespace USER_STORY\Components\Devices;

use USER_STORY\Components\AbstractComponent;
use USER_STORY\Components\Devices\DeviceIPs;
use USER_STORY\Exceptions\BaseException;
use USER_STORY\Exceptions\DatabaseException;
use USER_STORY\Objects\Device;
use USER_STORY\Objects\Device_IP;
use WP_User;

class Devices extends AbstractComponent {


	/**
	 * Find Device by UUID
	 *
	 * @param string $id UUID.
	 *
	 * @return null|Device
	 */
	public static function find( $id ) {
		return self::try_set_cache(
			$id,
			function () use ( $id ) {
				global $wpdb;

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->devices WHERE uuid = %s", $id ) );

				return $row ? Device::load_from_object( $row ) : null;
			}
		);
	}

	/**
	 * Create a Device
	 *
	 * @param string       $ip IP address.
	 * @param string|null  $user_agent User agent string.
	 * @param WP_User|null $user User object.
	 *
	 * @return Device_IP Created Device IP instance.
	 *
	 * @throws BaseException If an unknown error occurs during the creation process.
	 */
	public static function create( $ip, $user_agent, $user ) {
		assert( $user instanceof WP_User || is_null( $user ) );
		assert( is_string( $ip ) );
		assert( is_string( $user_agent ) || is_null( $user_agent ) );

		$user_agent = sanitize_text_field( $user_agent );

		try {
			user_story_db_start_transaction();

			$device = new Device();
			$device->set_user( $user )
				->set_user_agent( $user_agent )
				->save();

			try {
				return DeviceIPs::create( $device, $ip );
			} catch ( BaseException $e ) {
				user_story_db_mark_rollback();
				throw $e;
			}
		} catch ( DatabaseException $e ) {
			if ( defined( 'WP_DEBUG_DISPLAY' ) && WP_DEBUG_DISPLAY ) {
				error_log( $e->getMessage() );
			}

			throw new BaseException( esc_html__( 'Unknown error occurred', 'user-story' ) );
		} finally {
			user_story_db_commit_transaction_or_rollback();
		}
	}
}
