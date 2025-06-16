<?php

namespace USER_STORY\Components\Links;

use USER_STORY\Components\AbstractComponent;
use USER_STORY\Exceptions\BaseException;
use USER_STORY\Exceptions\DatabaseException;
use USER_STORY\Objects\Device_IP;
use USER_STORY\Objects\Link;
use USER_STORY\Objects\Visit;

/**
 * Class Visits
 *
 * Manages operations related to visit records in the database.
 */
class Visits extends AbstractComponent {


	/**
	 * Creates a new visit record with the specified details and stores it in the database.
	 *
	 * @param Link      $link The link object associated with the visit.
	 * @param Device_IP $device_ip The device IP object associated with the visit.
	 * @param int       $width The width of the device screen in pixels.
	 * @param int       $height The height of the device screen in pixels.
	 * @param int       $x The x-coordinate for the visit position.
	 * @param int       $y The y-coordinate for the visit position.
	 * @return Visit The newly created visit object.
	 * @throws BaseException If creating the visit record fails due to a database error.
	 */
	public static function create( $link, $device_ip, $width, $height, $x, $y ) {
		global $wpdb;

		assert( $link instanceof Link );
		assert( $device_ip instanceof Device_IP );
		assert( is_int( $width ) );
		assert( is_int( $height ) );

		try {
			user_story_db_start_transaction( $wpdb->prepare( "SELECT * FROM {$wpdb->visible_link_visits} WHERE visible_link_id = %d FOR UPDATE", $link->get_id() ) );

			$visit = new Visit();
			$visit->set_link( $link )
				->set_device_ip( $device_ip )
				->set_width( $width )
				->set_height( $height )
				->set_position_xy( sprintf( '%f,%f', (float) $x, (float) $y ) )
				->save();

			return $visit;
		} catch ( DatabaseException $e ) {
			if ( user_story_is_debug() ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( $e->getMessage() );
			}

			throw new BaseException( esc_html__( 'Error creating visit record', 'user-story' ) );
		} finally {
			user_story_db_commit_transaction();
		}
	}

	/**
	 * Retrieves a list of unique screen dimensions from the database.
	 *
	 * @return array|null An array of objects containing the distinct screen height and width, or null on failure.
	 */
	public static function get_available_screens() {
		global $wpdb;

		$data = get_transient( 'user_story_available_screens' );

		if ( $data ) {
			return $data;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$data = $wpdb->get_results(
			"
			SELECT DISTINCT
				height,
				width
			FROM
				`{$wpdb->visible_link_visits}`
				"
		);

		set_transient( 'user_story_available_screens', $data, MINUTE_IN_SECONDS * 15 );

		return $data;
	}

	/**
	 * Retrieves a list of available hosts from the database or cached transient.
	 *
	 * This method fetches distinct hosts from the database table or retrieves the cached transient if available.
	 * If a host value is null, it defaults to the site's host URL.
	 *
	 * @return array An array of available hosts.
	 */
	public static function get_available_hosts() {
		global $wpdb;

		$data = get_transient( 'user_story_available_hosts' );
		if ( $data ) {
			return $data;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$data = $wpdb->get_results( "SELECT DISTINCT hostname FROM {$wpdb->visible_links}" );

		$data = array_map(
			function ( $item ) {
				if ( null === $item->hostname ) {
					return user_story_get_site_host();
				}

				return $item->hostname;
			},
			$data
		);

		set_transient( 'user_story_available_hosts', $data, MINUTE_IN_SECONDS * 15 );

		return $data;
	}

	/**
	 * Retrieves report data based on the provided date range filter.
	 *
	 * @param array $filter An associative array containing 'start_date' and 'end_date' as keys.
	 *                      Both must be instances of \DateTime.
	 * @return array|object|null The report data retrieved from the database, or null if no data is available.
	 */
	public static function get_reports( $filter ) {
		global $wpdb;

		assert( is_array( $filter ) );
		assert( isset( $filter['start_date'] ) && isset( $filter['end_date'] ) );
		assert( $filter['start_date'] instanceof \DateTime );
		assert( $filter['end_date'] instanceof \DateTime );

		if ( ! empty( $filter['host'] ) ) {
			assert( is_string( $filter['host'] ) );
		}

		if ( isset( $filter['width'] ) ) {
			assert( is_int( $filter['width'] ) );
		}

		if ( isset( $filter['height'] ) ) {
			assert( is_int( $filter['height'] ) );
		}

		$cache_key = sha1(
			sprintf(
				'%s_%s_%d_%d_%s',
				$filter['start_date']->format( 'Y-m-d' ),
				$filter['end_date']->format( 'Y-m-d' ),
				$filter['height'],
				$filter['width'],
				! empty( $filter['host'] ) ? $filter['host'] : ''
			)
		);

		$data = get_transient( $cache_key );
		if ( $data ) {
			return $data;
		}

		$additional_where = array();
		$params           = array();

		if ( ! empty( $filter['host'] ) ) {
			if ( user_story_get_site_host() === $filter['host'] ) {
				$additional_where[] = ' AND l.hostname IS NULL';
			} else {
				$additional_where[] = ' AND l.hostname = %s';
				$params[]           = $filter['host'];
			}
		}

		$additional_where[] = ' AND DATE(created_at) BETWEEN %s AND %s';
		$params[]           = $filter['start_date']->format( 'Y-m-d' );
		$params[]           = $filter['end_date']->format( 'Y-m-d' );

		if ( $filter['width'] && $filter['height'] ) {
			$additional_where[] = ' AND height = %d AND width = %d';
			$params[]           = $filter['height'];
			$params[]           = $filter['width'];
		}

		$additional_where = implode( ' ', $additional_where );

		// phpcs:disable
		$data = $wpdb->get_results(
			$wpdb->prepare(
				"
			SELECT CASE WHEN
				l.hostname IS NULL THEN CONCAT(
					(
					SELECT
						option_value
					FROM
						{$wpdb->options}
					WHERE
						option_name = 'siteurl'
				),
				l.path,
				CASE WHEN l.query IS NOT NULL THEN '?' ELSE ''
				END,
				IFNULL(l.query, ''),
				CASE WHEN l.fragment IS NOT NULL THEN '#' ELSE ''
			END,
			IFNULL(l.fragment, '')
			) ELSE CONCAT(
				l.scheme,
				'://',
				l.hostname,
				l.path,
				CASE WHEN l.query IS NOT NULL THEN '?' ELSE ''
			END,
			IFNULL(l.query, ''),
			CASE WHEN l.fragment IS NOT NULL THEN '#' ELSE ''
			END,
			IFNULL(l.fragment, '')
			)
			END link,
			l.name,
			height,
			width,
			DATE(created_at) date
			FROM
				`{$wpdb->visible_link_visits}`
			INNER JOIN {$wpdb->visible_links} l ON
				visible_link_id = l.ID

			WHERE 1=1 {$additional_where}
			GROUP BY
				visible_link_id,
				height,
				width,
				DATE(created_at);
		",
				$params
			)
		);
		// phpcs:enable

		set_transient( $cache_key, $data, MINUTE_IN_SECONDS * 15 );

		return $data;
	}

	/**
	 * Removes expired visit records from the database.
	 *
	 * This method deletes visit records that were created more than seven days ago.
	 *
	 * @return void
	 */
	public static function cleanup_expired_visits() {
		global $wpdb;

		try {
			user_story_db_start_transaction();

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->visible_link_visits} WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)", USER_STORY_EXPIRY_DAYS ) );
		} finally {
			user_story_db_commit_transaction();
		}
	}

	/**
	 * Retrieves a specific visit record from the database by its ID.
	 *
	 * @param int $id The unique identifier of the record to be retrieved.
	 * @return Visit|null The visit object if found, or null otherwise.
	 */
	public static function find( $id ) {
		return self::try_set_cache(
			$id,
			function () use ( $id ) {
				global $wpdb;

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->visible_link_visits} WHERE ID = %d", $id ) );

				return $row ? Visit::load_from_object( $row ) : null;
			}
		);
	}
}
