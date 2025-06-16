<?php
/**
 * Plugin Template
 *
 * @package     user-story
 * @author      Mathieu Lamiot
 * @copyright   TO FILL
 * @license     GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name: User Story Plugin
 * Version:     1.0.0
 * Description: TO FILL
 * Author:      Mathieu Lamiot
 */

namespace USER_STORY;

define( 'USER_STORY_PLUGIN_FILENAME', __FILE__ ); // Filename of the plugin, including the file.
define( 'USER_STORY_PLUGIN_DIR', __DIR__ ); // Plugin root directory.
define( 'USER_STORY_PLUGIN_ASSETS_DIR', __DIR__ . '/assets' ); // Plugin assets directory.
define( 'USER_STORY_PLUGIN_ASSETS_URL', plugins_url( 'assets', __FILE__ ) ); // Assets base URL.

if ( ! defined( 'ABSPATH' ) ) { // If WordPress is not loaded.
	exit( 'WordPress not loaded. Can not load the plugin' );
}

// Load the dependencies installed through composer.
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/src/plugin.php';
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/support/exceptions.php';

// Plugin initialization.
/**
 * Creates the plugin object on plugins_loaded hook
 *
 * @return void
 */
function wpc_crawler_plugin_init() {
	$wpc_crawler_plugin = new User_Story_Plugin();
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\wpc_crawler_plugin_init' );
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\User_Story_Plugin::enqueue_assets' );

register_activation_hook( __FILE__, __NAMESPACE__ . '\User_Story_Plugin::wpc_activate' );
register_uninstall_hook( __FILE__, __NAMESPACE__ . '\User_Story_Plugin::wpc_uninstall' );
