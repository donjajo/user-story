<?php

namespace USER_STORY\Routes\Links;

use USER_STORY\Components\Devices\DeviceIPs;
use USER_STORY\Components\Devices\Devices;
use USER_STORY\Components\Links\Visits;
use USER_STORY\Exceptions\BaseException;
use USER_STORY\Objects\Device_IP;
use USER_STORY\Routes\AbstractRoute;
use USER_STORY\Traits\Routes\HasDeviceSupport;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

class Links extends AbstractRoute {

	use HasDeviceSupport;

	/**
	 * Device IP object for this request
	 *
	 * @var Device_IP
	 */
	private static $device_ip;

	/**
	 * Links Component
	 *
	 * @var \USER_STORY\Components\Links\Links
	 */
	protected $component;

	const CREATE_NONCE_ACTION = 'user-story-create-links';

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
					'links'  => array(
						'required'          => true,
						'validate_callback' => array( self::class, 'validate_links' ),
					),
					'height' => array(
						'required' => true,
						'type'     => 'integer',
					),
					'width'  => array(
						'required' => true,
						'type'     => 'integer',
					),
					'nonce'  => array(
						'required'          => true,
						'validate_callback' => array( $this, 'validate_nonce' ),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			$this->resource_name,
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'callback'            => array( $this, 'get_items' ),
				'args'                => array(
					'filter' => array(
						'required'          => true,
						'validate_callback' => array( self::class, 'validate_filter' ),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			$this->resource_name . '/filter-data',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'callback'            => array( $this, 'get_filter_data' ),
				'args'                => array(),
			)
		);
	}

	/**
	 * Validates a given nonce to verify its authenticity and prevent security vulnerabilities.
	 *
	 * @param string $nonce The nonce to validate.
	 * @return bool True if the nonce is valid, false otherwise.
	 */
	public function validate_nonce( $nonce ) {
		return wp_verify_nonce( $nonce, self::CREATE_NONCE_ACTION );
	}

	/**
	 * Checks the permissions for retrieving items.
	 *
	 * @param WP_REST_Request $request WordPress Request Object.
	 *
	 * @return bool
	 */
	public function get_items_permissions_check( $request ) {
		return is_user_logged_in() && current_user_can( 'manage_options' );
	}

	/**
	 * Validates the provided filter parameters.
	 *
	 * @param array $filter Associative array containing filter parameters. Expected keys are 'start_date' and 'end_date'
	 *                      with values in 'Y-m-d' date format.
	 *
	 * @return true|WP_Error True if the filter is valid. WP_Error if the filter is invalid.
	 */
	public static function validate_filter( $filter ) {
		if ( ! is_array( $filter ) ) {
			return new WP_Error( 'rest_invalid_param', esc_html__( 'filter is not an object', 'user-story' ), array( 'status' => 400 ) );
		}

		if ( empty( $filter['start_date'] ) || date_create_from_format( 'Y-m-d', $filter['start_date'] ) === false ) {
			return new WP_Error( 'rest_invalid_param', esc_html__( 'start_date is required in the filter. Or start_date is not a valid date', 'user-story' ), array( 'status' => 400 ) );
		}

		if ( empty( $filter['end_date'] ) || date_create_from_format( 'Y-m-d', $filter['end_date'] ) === false ) {
			return new WP_Error( 'rest_invalid_param', esc_html__( 'end_date is required in the filter. Or end_date is not a valid date', 'user-story' ), array( 'status' => 400 ) );
		}

		if ( ! empty( ( $filter['screen'] ) ) ) {
			list ($height, $width) = explode( 'x', $filter['screen'] );
			if ( ! is_numeric( $height ) || ! is_numeric( $width ) ) {
				return new WP_Error( 'rest_invalid_param', esc_html__( 'screen is not a valid screen size', 'user-story' ), array( 'status' => 400 ) );
			}
		}

		if ( ! empty( $filter['host'] ) && ! is_string( $filter['host'] ) ) {
			return new WP_Error( 'rest_invalid_param', esc_html__( 'host is not a valid host', 'user-story' ), array( 'status' => 400 ) );
		}

		return true;
	}

	/**
	 * Check permissions for creating an item.
	 *
	 * @param WP_REST_Request $request The current request object containing parameters and headers.
	 * @return true|WP_Error True if the permission is granted, WP_Error if the permission is denied or an error occurs.
	 */
	public function create_item_permissions_check( $request ) {
		$device = $this->get_device( $request );

		try {

			if ( null === $device ) {
				return new WP_Error( 'rest_forbidden', esc_html__( 'Unknown device', 'user-story' ), array( 'status' => 403 ) );
			} elseif ( false === $device ) {
				self::$device_ip = Devices::create( user_story_get_ip(), $request->get_header( 'User-Agent' ), null );
			} else {
				self::$device_ip = DeviceIPs::create( $device, user_story_get_ip() );
			}

			header( 'X-Device: ' . self::$device_ip->get_device()->get_uuid() );

			return true;
		} catch ( BaseException $e ) {
			return new WP_Error( 'unknown_error', $e->getMessage(), array( 'status' => 500 ) );
		}
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
			if ( ! is_array( $link ) ) {
				return new WP_Error( 'rest_invalid_param', __( 'links items is not an object', 'user-story' ), array( 'status' => 400 ) );
			}

			if ( empty( $link['url'] ) ) {
				return new WP_Error( 'rest_invalid_param', __( 'links item is missing url property', 'user-story' ), array( 'status' => 400 ) );
			}

			if ( empty( $link['name'] ) ) {
				return new WP_Error( 'rest_invalid_param', __( 'links item is missing name property', 'user-story' ), array( 'status' => 400 ) );
			}

			if ( ! array_key_exists( 'x', $link ) ) {
				return new WP_Error( 'rest_invalid_param', __( 'links item is missing x property', 'user-story' ), array( 'status' => 400 ) );
			}

			if ( ! array_key_exists( 'y', $link ) ) {
				return new WP_Error( 'rest_invalid_param', __( 'links item is missing y property', 'user-story' ), array( 'status' => 400 ) );
			}

			if ( ! is_string( $link['url'] ) || filter_var( $link['url'], FILTER_VALIDATE_URL ) === false ) {
				/* translators: %s is replaced with "string" */
				return new WP_Error( 'rest_invalid_param', sprintf( __( 'Invalid link %s', 'user-story' ), $link ), array( 'status' => 400 ) );
			}

			if ( ! is_string( $link['name'] ) ) {
				/* translators: %s is replaced with "string" */
				return new WP_Error( 'rest_invalid_param', sprintf( __( 'name cannot be empty for link: %s', 'user-story' ), $link['url'] ), array( 'status' => 400 ) );
			}

			if ( ! is_numeric( $link['x'] ) || ! is_numeric( $link['y'] ) ) {
				return new WP_Error( 'rest_invalid_param', __( 'x and y property must be an integer', 'user-story' ), array( 'status' => 400 ) );
			}
		}

		return true;
	}

	/**
	 * Create an item based on the provided request data.
	 *
	 * @param WP_REST_Request $request The REST request object containing the input parameters.
	 *                                  - links (array) A list of links to be processed, required.
	 *                                  - width (integer) The width of the item, required.
	 *                                  - height (integer) The height of the item, required.
	 *
	 * @return WP_REST_Response|WP_Error A response object on success, or a WP_Error object if an error occurs.
	 */
	public function create_item( $request ) {
		try {
			foreach ( $request['links'] as $link ) {
				$this->component::create( $link['url'], self::$device_ip, $request['width'], $request['height'], $link['x'], $link['y'], $link['name'] );
			}

			return rest_ensure_response( '' );
		} catch ( BaseException $e ) {
			return new WP_Error( 'unknown_error', $e->getMessage(), array( 'status' => 500 ) );
		}
	}

	/**
	 * Retrieves the filter data for the API response.
	 *
	 * @param WP_REST_Request $request WordPress Request Object.
	 *
	 * @return WP_REST_Response Filter data wrapped in a REST response object.
	 */
	public function get_filter_data( $request ) {
		return rest_ensure_response(
			array(
				'screens' => Visits::get_available_screens(),
				'hosts'   => Visits::get_available_hosts(),
			)
		);
	}

	/**
	 * Retrieves items based on the provided filters.
	 *
	 * @param WP_REST_Request $request WordPress Request Object containing parameters.
	 *
	 * @return WP_REST_Response
	 */
	public function get_items( $request ) {
		$filter               = $request['filter'];
		$filter['start_date'] = date_create_from_format( 'Y-m-d', $filter['start_date'] );
		$filter['end_date']   = date_create_from_format( 'Y-m-d', $filter['end_date'] );
		$filter['height']     = 0;
		$filter['width']      = 0;

		if ( ! empty( $filter['screen'] ) ) {
			list ($filter['height'], $filter['width']) = explode( 'x', $filter['screen'] );
			$filter['height']                          = (int) $filter['height'];
			$filter['width']                           = (int) $filter['width'];
		}

		if ( ! empty( $filter['host'] ) ) {
			$filter['host'] = sanitize_text_field( $filter['host'] );
		}

		return rest_ensure_response( Visits::get_reports( $filter ) );
	}
}
