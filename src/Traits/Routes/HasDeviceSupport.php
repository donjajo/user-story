<?php

namespace USER_STORY\Traits\Routes;

use USER_STORY\Components\Devices\Devices;
use USER_STORY\Objects\Device;
use WP_REST_Request;
use WP_User;

trait HasDeviceSupport {

	/**
	 * Retrieves a device based on the 'X-Device' header from the request.
	 *
	 * @param WP_REST_Request $request The request object, which must be an instance of WP_REST_Request.
	 * @return Device|false|null The device object if found, or false if no device ID is provided or null if the device cannot be found.
	 */
	public function get_device( $request ) {
		assert( $request instanceof WP_REST_Request );

		$device_id = (string) $request->get_header( 'X-Device' );
		$device    = false;

		if ( $device_id ) {
			$device = Devices::find( $device_id );
		}

		return $device;
	}
}
