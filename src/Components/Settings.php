<?php

namespace USER_STORY\Components;

use USER_STORY\Components\AbstractComponent;

class Settings extends AbstractComponent {


	/**
	 * Registers WordPress hooks for adding custom admin menu pages.
	 *
	 * @return void
	 */
	public function hooks() {
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Adds a new menu page to the WordPress admin dashboard with specified settings.
	 *
	 * @return void
	 */
	public function add_menu_page() {
		add_menu_page( esc_html__( 'User Story', 'user-story' ), esc_html__( 'User Story', 'user-story' ), 'manage_options', 'user-story-settings', array( $this, 'add_menu_page_html' ) );
	}

	/**
	 * Outputs the HTML for the menu page with a loading message.
	 *
	 * @return void
	 */
	public function add_menu_page_html() {
		printf(
			'<div class="wrap" id="user-story-settings">%s</div>',
			esc_html__( 'Loading...', 'user-story' )
		);
	}

	/**
	 * Enqueues the necessary scripts for the plugin's settings functionality.
	 *
	 * @return void
	 */
	public static function enqueue() {
		$asset_file = include USER_STORY_PLUGIN_ASSETS_DIR . '/js/settings.asset.php';

		wp_enqueue_style( 'wp-components' );
		wp_enqueue_script( 'user-story-settings-script', USER_STORY_PLUGIN_ASSETS_URL . '/js/settings.js', $asset_file['dependencies'], $asset_file['version'], true );
	}

	/**
	 * Finds and retrieves an entity by its unique identifier.
	 *
	 * @param mixed $id The unique identifier of the entity to be retrieved.
	 * @return mixed The entity object corresponding to the provided identifier, or null if not found.
	 */
	public static function find( $id ) {
		// TODO: Implement find() method.
	}
}
