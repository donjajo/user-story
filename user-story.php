<?php
/**
 * Plugin Template
 *
 * @package     user-story
 * @author      James Etu
 * @copyright   2025
 * @license     GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name: User Story Plugin
 * Version:     1.0.0
 * Description: User Story plugin for WP Media Test
 * Author:      James Etu
 * Requires at least: 6.0
 * Requires PHP: 7.3
 */

namespace USER_STORY;

define( 'USER_STORY_PLUGIN_FILENAME', __FILE__ ); // Filename of the plugin, including the file.
define( 'USER_STORY_PLUGIN_DIR', __DIR__ ); // Plugin root directory.
define( 'USER_STORY_PLUGIN_ASSETS_DIR', __DIR__ . '/assets' ); // Plugin assets directory.
define( 'USER_STORY_PLUGIN_ASSETS_URL', plugins_url( 'assets', __FILE__ ) ); // Assets base URL.
define( 'USER_STORY_EXPIRY_DAYS', 7 );

if ( ! defined( 'ABSPATH' ) ) { // If WordPress is not loaded.
	exit( 'WordPress not loaded. Can not load the plugin' );
}

// Load the dependencies installed through composer.
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/src/plugin.php';
require_once __DIR__ . '/vendor/autoload.php';

new User_Story_Plugin();

register_activation_hook( __FILE__, __NAMESPACE__ . '\User_Story_Plugin::wpc_activate' );
register_uninstall_hook( __FILE__, __NAMESPACE__ . '\User_Story_Plugin::wpc_uninstall' );
