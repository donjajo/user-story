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
	 * Creates a new visit record associated with a link, device IP, and dimensions.
	 *
	 * @param Link      $link The link object associated with the visit.
	 * @param Device_IP $device_ip The device IP object associated with the visit.
	 * @param int       $width The width of the device screen in pixels.
	 * @param int       $height The height of the device screen in pixels.
	 * @return Visit The newly created visit object.
	 * @throws BaseException If an error occurs during the creation of the visit record.
	 */
	public static function create( $link, $device_ip, $width, $height ) {
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
				->save();

			return $visit;
		} catch ( DatabaseException $e ) {
			if ( user_story_is_debug() ) {
				error_log( $e->getMessage() );
			}

			throw new BaseException( esc_html__( 'Error creating visit record', 'user-story' ) );
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
