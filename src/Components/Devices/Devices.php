<?php

namespace USER_STORY\Components;

use USER_STORY\Components\AbstractComponent;
use USER_STORY\Objects\Device;

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
}
