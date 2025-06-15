<?php

namespace USER_STORY\Components\Devices;

use USER_STORY\Components\AbstractComponent;
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
}
