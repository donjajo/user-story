<?php

namespace USER_STORY\Components;

use USER_STORY\Components\AbstractComponent;
use USER_STORY\Objects\Link;
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
	 * Import associated REST Routes
	 *
	 * @return \class-string[]
	 */
	public static function rest_routes() {
		return array( Route::class );
	}
}
