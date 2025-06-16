<?php
/**
 * Plugin main class
 *
 * @package     TO FILL
 * @since       TO FILL
 * @author      Mathieu Lamiot
 * @license     GPL-2.0-or-later
 */

namespace USER_STORY;

use USER_STORY\Components\AbstractComponent;
use USER_STORY\Components\Links\Links;

/**
 * Main plugin class. It manages initialization, install, and activations.
 */
class User_Story_Plugin {

	/**
	 * Components the require initialization
	 */
	const COMPONENTS = array(
		Links::class,
	);

	/**
	 * Manages plugin initialization
	 *
	 * @return void
	 */
	public function __construct() {

		// Register plugin lifecycle hooks.
		register_deactivation_hook( USER_STORY_PLUGIN_FILENAME, array( $this, 'wpc_deactivate' ) );
		self::define_tables();

		$this->init_components();
	}

	/**
	 * Initialize components
	 *
	 * @return void
	 */
	private function init_components() {
		foreach ( self::COMPONENTS as $component ) {
			/**
			 * Component instance
			 *
			 * @var AbstractComponent $component
			 */
			$component = new $component();
			if ( $component::rest_routes() ) {
				foreach ( $component::rest_routes() as $route ) {
					add_action(
						'rest_api_init',
						function () use ( $route, $component ) {
							$route = new $route( $component );
							$route->register_routes();
						}
					);
				}
			}
		}
	}

	/**
	 * Definee table variables in wpdb
	 *
	 * @return void
	 */
	private static function define_tables() {
		global $wpdb;

		$wpdb->devices             = $wpdb->prefix . 'devices';
		$wpdb->device_ips          = $wpdb->prefix . 'device_ips';
		$wpdb->visible_links       = $wpdb->prefix . 'visible_links';
		$wpdb->visible_link_visits = $wpdb->prefix . 'visible_link_visits';
	}

	/**
	 * Enqueues the necessary assets for the plugin.
	 *
	 * This method includes the asset file which contains the dependencies and version
	 * of the JavaScript file, and enqueues the script for use in WordPress.
	 *
	 * @return void
	 */
	public static function enqueue_assets() {
		$embed_asset_file = include USER_STORY_PLUGIN_ASSETS_DIR . '/js/embed.asset.php';

		wp_enqueue_script( 'embed-script', USER_STORY_PLUGIN_ASSETS_URL . '/js/embed.js', $embed_asset_file['dependencies'], $embed_asset_file['version'], true );
	}

	/**
	 * Create MySQL tables
	 *
	 * @return void
	 */
	private static function create_tables() {
		global $wpdb;

		self::define_tables();
		$wpdb->show_errors();
		$tables = array(
			"CREATE TABLE IF NOT EXISTS {$wpdb->devices} (
				uuid CHAR(36) NOT NULL PRIMARY KEY,
				user_id BIGINT UNSIGNED NULL,
				user_agent VARCHAR(255) NULL,
				created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				FOREIGN KEY users_fk (user_id) REFERENCES {$wpdb->users} (ID) ON DELETE SET NULL ON UPDATE CASCADE
			)",
			"CREATE TABLE IF NOT EXISTS {$wpdb->device_ips} (
				ID BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
				device_uuid CHAR(36) NOT NULL,
				ip CHAR(45) NOT NULL,
				created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				FOREIGN KEY devices_fk (device_uuid) REFERENCES {$wpdb->devices} (uuid) ON DELETE CASCADE ON UPDATE CASCADE
            )",
			"CREATE TABLE IF NOT EXISTS {$wpdb->visible_links} (
				ID BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
				scheme CHAR(20) NOT NULL,
				hostname VARCHAR(261) NULL, -- max domain name is 255, max port is 65535, plus a colon
				path VARCHAR(255) NOT NULL,
				query VARCHAR(100) NULL,
				fragment VARCHAR(50) NULL,
				UNIQUE KEY url (hostname, path, query, fragment),
				KEY scheme (scheme)
            )",
			"CREATE TABLE IF NOT EXISTS {$wpdb->visible_link_visits} (
				ID BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
				visible_link_id BIGINT UNSIGNED NOT NULL,
				device_ip_id BIGINT UNSIGNED NOT NULL,
				height SMALLINT UNSIGNED NOT NULL,
				width SMALLINT UNSIGNED NOT NULL,
				created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				KEY (height),
				KEY (width),
				KEY (created_at),
				FOREIGN KEY link_fk (visible_link_id) REFERENCES {$wpdb->visible_links} (ID) ON DELETE RESTRICT ON UPDATE CASCADE,
				FOREIGN KEY devices_fk (device_ip_id) REFERENCES {$wpdb->device_ips} (ID) ON DELETE RESTRICT ON UPDATE CASCADE
            )",
		);

		foreach ( $tables as $table ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->query( $table );
		}
	}

	/**
	 * Handles plugin activation:
	 *
	 * @return void
	 */
	public static function wpc_activate() {
		// Security checks.
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		$plugin = isset( $_REQUEST['plugin'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['plugin'] ) ) : '';
		check_admin_referer( "activate-plugin_{$plugin}" );

		self::create_tables();
	}

	/**
	 * Handles plugin deactivation
	 *
	 * @return void
	 */
	public function wpc_deactivate() {
		// Security checks.
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		$plugin = isset( $_REQUEST['plugin'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['plugin'] ) ) : '';
		check_admin_referer( "deactivate-plugin_{$plugin}" );
	}

	/**
	 * Handles plugin uninstall
	 *
	 * @return void
	 */
	public static function wpc_uninstall() {

		// Security checks.
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
	}
}
