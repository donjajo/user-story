<?php

namespace USER_STORY\Components\Links;

use USER_STORY\Components\AbstractComponent;
use USER_STORY\Exceptions\BaseException;
use USER_STORY\Exceptions\DatabaseException;
use USER_STORY\Objects\Device_IP;
use USER_STORY\Objects\Link;
use USER_STORY\Objects\Visit;
use USER_STORY\Routes\AbstractRoute;
use USER_STORY\Routes\Links\Links as Route;

class Links extends AbstractComponent {

	/**
	 * Find link by ID
	 *
	 * @param int $id link ID.
	 */
	public static function find( $id ) {
		return self::try_set_cache(
			$id,
			function () use ( $id ) {
				global $wpdb;

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->visible_links WHERE ID = %d", $id ) );

				return $row ? Link::load_from_object( $row ) : null;
			}
		);
	}

	/**
	 * Create a visit entry for a specified URL and device details.
	 *
	 * @param string    $url The URL to associate with the visit.
	 * @param Device_IP $device_ip The IP address of the device making the visit.
	 * @param int       $width The width of the device screen.
	 * @param int       $height The height of the device screen.
	 * @param int       $x The x-coordinate of the pointer during the visit.
	 * @param int       $y The y-coordinate of the pointer during the visit.
	 * @param string    $name The link name.
	 *
	 * @return Visit The newly created visit object.
	 *
	 * @throws BaseException    If an error occurs while creating the visit.
	 */
	public static function create( $url, $device_ip, $width, $height, $x, $y, $name ) {
		assert( is_string( $url ) );

		try {
			user_story_db_start_transaction();

			$link = self::get_link_by_url( $url, $name );
			if ( null === $link ) {
				$url_data = self::parse_url( $url );
				$link     = new Link();
				$link->set_path( $url_data['path'] )
					->set_query( $url_data['query'] )
					->set_fragment( $url_data['fragment'] )
					->set_host_name( $url_data['host'] )
					->set_scheme( $url_data['scheme'] )
					->set_name( $name )
					->save();
			}

			try {
				return Visits::create( $link, $device_ip, $width, $height, $x, $y, $name );
			} catch ( BaseException $e ) {
				user_story_db_mark_rollback();
				throw $e;
			}
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

	/**
	 * Prepare and deconstruct a URL into its components.
	 *
	 * @param string $url The URL to be processed.
	 * @return array An associative array containing 'scheme', 'host', 'path', 'query', and 'fragment' as keys.
	 */
	public static function parse_url( $url ) {
		$url_data  = wp_parse_url( $url );
		$site_host = wp_parse_url( site_url(), PHP_URL_HOST );

		$scheme   = ! empty( $url_data['scheme'] ) ? $url_data['scheme'] : 'https';
		$host     = ! empty( $url_data['host'] ) && strcasecmp( $url_data['host'], $site_host ) !== 0 ? $url_data['host'] : null;
		$path     = ! empty( $url_data['path'] ) ? $url_data['path'] : '/';
		$query    = array_key_exists( 'query', $url_data ) ? $url_data['query'] : null;
		$fragment = array_key_exists( 'fragment', $url_data ) ? $url_data['fragment'] : null;

		if ( null !== $host && ! empty( $url_data['port'] ) ) {
			$host .= ':' . $url_data['port'];
		}

		return compact( 'scheme', 'host', 'path', 'query', 'fragment' );
	}

	/**
	 * Retrieve a link record from the database by its URL.
	 *
	 * @param string      $url The URL to search for.
	 * @param string|null $name URL Link.
	 * @return Link|null
	 */
	public static function get_link_by_url( $url, $name = null ) {

		return self::try_set_cache(
			sha1( $url ),
			function () use ( $url, $name ) {
				global $wpdb;

				$url_parts = self::parse_url( $url );

				$sql    = "SELECT * FROM {$wpdb->visible_links} ";
				$where  = array( 'WHERE 1=1', ' AND scheme = %s' );
				$params = array( $url_parts['scheme'] );

				if ( null === $url_parts['host'] ) {
					$where[] = 'AND hostname IS NULL';
				} else {
					$where[]  = ' AND hostname = %s';
					$params[] = $url_parts['host'];

				}

				$where[]  = ' AND path = %s';
				$params[] = $url_parts['path'];

				if ( null === $url_parts['query'] ) {
					$where[] = ' AND query IS NULL';
				} else {
					$where[]  = 'AND query = %s';
					$params[] = $url_parts['query'];
				}

				if ( null === $url_parts['fragment'] ) {
					$where[] = ' AND fragment IS NULL';
				} else {
					$where[]  = ' AND fragment = %s';
					$params[] = $url_parts['fragment'];
				}

				if ( null !== $name ) {
					$where[]  = ' AND name = %s';
					$params[] = $name;
				}

				$sql .= implode( ' ', $where ) . ' FOR UPDATE';

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$row = $wpdb->get_row(
					$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
						$sql,
						$params
					)
				);

				return $row ? Link::load_from_object( $row ) : null;
			}
		);
	}

	/**
	 * Import associated REST Routes
	 *
	 * @return \class-string[]
	 */
	public static function rest_routes() {
		return array( Route::class );
	}
}
