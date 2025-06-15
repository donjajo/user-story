<?php

namespace USER_STORY\Routes\Links;

use USER_STORY\Components\Devices\Devices;
use USER_STORY\Exceptions\BaseException;
use USER_STORY\Routes\AbstractRoute;
use USER_STORY\Traits\Routes\HasDeviceSupport;
use WP_Error;

class Links extends AbstractRoute {

	use HasDeviceSupport;

	/**
	 * Register routes
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			$this->resource_name,
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'permission_callback' => array( $this, 'create_item_permissions_check' ),
				'callback'            => array( $this, 'create_item' ),
				'args'                => array(
					'links' => array(
						'required'          => true,
						'validate_callback' => array( self::class, 'validate_links' ),
					),
				),
			)
		);
	}

	/**
	 * Create item permission check
	 *
	 * @param \WP_REST_Request $request \WP_REST_Request object.
	 *
	 * @return true|WP_Error
	 */
	public function create_item_permissions_check( $request ) {
		$device = $this->get_device( $request );

		if ( null === $device ) {
			return new WP_Error( 'rest_forbidden', esc_html__( 'Unknown device', 'user-story' ), array( 'status' => 403 ) );
		} elseif ( false === $device ) {
			try {
				$device_ip = Devices::create( user_story_get_ip(), $request->get_header( 'User-Agent' ), null );
				header( 'X-Device: ' . $device_ip->get_device()->get_uuid() );
			} catch ( BaseException $e ) {
				return new WP_Error( 'unknown_error', $e->getMessage(), array( 'status' => 500 ) );
			}
		}

		return true;
	}

	/**
	 * Validate links parameter
	 *
	 * @param array $links array of links.
	 *
	 * @return WP_Error|true
	 */
	public static function validate_links( $links ) {
		if ( ! is_array( $links ) ) {
			return new WP_Error( 'rest_invalid_param', __( 'links is not of type array', 'user-story' ), array( 'status' => 400 ) );
		}

		foreach ( $links as $link ) {
			if ( ! is_string( $link ) || filter_var( $link, FILTER_VALIDATE_URL ) === false ) {
				/* translators: %s is replaced with "string" */
				return new WP_Error( 'rest_invalid_param', sprintf( __( 'Invalid link %s', 'user-story' ), $link ), array( 'status' => 400 ) );
			}
		}

		return true;
	}
}
