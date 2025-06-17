<?php
defined( 'ABSPATH' ) || exit;

/**
 * Start DB transaction
 *
 * @param string $lock_query Optional query to run onlock.
 * @return void
 */
function user_story_db_start_transaction( $lock_query = '' ) {
	global $wpdb;

	if ( empty( $wpdb->has_transaction ) ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( 'START TRANSACTION;' );

		$wpdb->has_transaction       = true;
		$wpdb->transaction_savepoint = 0;
	} else {
		++$wpdb->transaction_savepoint;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( sprintf( 'SAVEPOINT pt_%d', $wpdb->transaction_savepoint ) );
	}

	if ( $lock_query ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query(
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$lock_query
		);
	}
}

/**
 * Commit the current DB transaction or release the latest savepoint.
 *
 * @return void
 */
function user_story_db_commit_transaction() {
	global $wpdb;

	if ( empty( $wpdb->transaction_savepoint ) ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( 'COMMIT;' );
		$wpdb->has_transaction = false;
	} else {
		--$wpdb->transaction_savepoint;
	}
}

/**
 * Set rollback flag
 *
 * @return void
 */
function user_story_db_mark_rollback() {
	global $wpdb;

	if ( ! empty( $wpdb->has_transaction ) ) {
		$wpdb->has_rollback = true;
	}
}

/**
 * Commit or rollback transaction if marked as rollback
 *
 * @return void
 */
function user_story_db_commit_transaction_or_rollback() {
	global $wpdb;

	if ( ! empty( $wpdb->has_rollback ) ) {
		user_story_db_rollback_transaction();
		$wpdb->has_rollback = false;
	} else {
		user_story_db_commit_transaction();
	}
}

/**
 * Rollback DB transaction
 *
 * Rolls back the current database transaction or savepoint if applicable.
 *
 * @return void
 */
function user_story_db_rollback_transaction() {
	global $wpdb;

	if ( empty( $wpdb->transaction_savepoint ) ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( 'ROLLBACK;' );
		$wpdb->has_transaction = false;
	} else {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( sprintf( 'ROLLBACK pt_%d;', $wpdb->transaction_savepoint-- ) );
	}
}

/**
 * Check if debug mode is enabled
 *
 * Determines whether the WordPress debug mode is enabled based on the WP_DEBUG constant.
 *
 * @return bool True if debug mode is enabled, false otherwise.
 */
function user_story_is_debug() {
	return defined( 'WP_DEBUG' ) && WP_DEBUG;
}

/**
 * Retrieves the client's IP address by checking various server variables.
 *
 * The method iterates through a list of common keys in the $_SERVER
 * superglobal that may hold the client's IP address. If a valid IP
 * address is found, it is returned. If no valid IP address is detected,
 * an empty string is returned.
 *
 * @return string The detected IP address of the client, or an empty string if no valid IP address is found.
 */
function user_story_get_ip() {
	foreach ( array( 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR' ) as $key ) {
		if ( array_key_exists( $key, $_SERVER ) === true ) {
			foreach ( array_map( 'trim', explode( ',', sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) ) ) ) as $ip ) {
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) !== false ) {
					return $ip;
				}
			}
		}
	}

	return '';
}

/**
 * Retrieve the site host
 *
 * Parses the site URL and returns the host component, including the port if specified.
 *
 * @return string The host of the site with optional port if present.
 */
function user_story_get_site_host() {
	$parsed = wp_parse_url( site_url() );

	return $parsed['host'] . ( isset( $parsed['port'] ) ? ':' . $parsed['port'] : '' );
}

/**
 * Generate a UUID version 4
 *
 * Creates a UUID (Universally Unique Identifier) version 4 using random or provided data.
 * The generated UUID conforms to the RFC 4122 specification.
 *
 * @param string|null $data Optional 16 bytes of binary data used for UUID generation. If not provided, random data will be used.
 * @return string A 36-character string representing the UUID v4.
 * @throws \Random\RandomException
 */
function user_story_uuid4($data = null) {
	// Generate 16 bytes (128 bits) of random data or use the data passed into the function.
	$data = $data ?? random_bytes(16);
	assert(strlen($data) == 16);

	// Set version to 0100
	$data[6] = chr(ord($data[6]) & 0x0f | 0x40);
	// Set bits 6-7 to 10
	$data[8] = chr(ord($data[8]) & 0x3f | 0x80);

	// Output the 36 character UUID.
	return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}
