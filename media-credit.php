<?php
/**
 * This file is part of Media Credit.
 *
 * Copyright 2013-2018 Peter Putzer.
 * Copyright 2010-2011 Scott Bressler.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * version 2 as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 *  ***
 *
 * @package Media_Credit
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 *
 * @wordpress-plugin
 * Plugin Name: Media Credit
 * Plugin URI: https://code.mundschenk.at/media-credit/
 * Description: This plugin adds a "Credit" field to the media uploading and editing tool and inserts this credit when the images appear on your blog.
 * Version: 3.2.0
 * Author: Peter Putzer
 * Author: Scott Bressler
 * Author URI: https://mundschenk.at/
 * Text Domain: media-credit
 * License: GPL2
 */

/**
 * If this file is called directly, abort.
 */
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * An autoloader implementation for our classes.
 *
 * @param string $class_name  The class name (including namespaces).
 */
function media_credit_autoloader( $class_name ) {
	if ( false === strpos( $class_name, 'Media_Credit' ) ) {
		return; // abort.
	}

	static $classes_dir;
	if ( empty( $classes_dir ) ) {
		$classes_dir['default'] = realpath( plugin_dir_path( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR;
		$classes_dir['public']  = realpath( plugin_dir_path( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR;
		$classes_dir['admin']   = realpath( plugin_dir_path( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR;
	}
	$class_file = 'class-' . str_replace( '_', '-', strtolower( $class_name ) ) . '.php';

	if ( is_file( $class_file_path = $classes_dir['default'] . $class_file ) || // @codingStandardsIgnoreStart
	     is_file( $class_file_path = $classes_dir['admin']   . $class_file ) ||
	     is_file( $class_file_path = $classes_dir['public']  . $class_file ) ) { // @codingStandardsIgnoreEnd
		require_once $class_file_path;
	}
}

/**
 * Load legacy template tags.
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/media-credit-template.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    3.0.0
 */
function run_media_credit() {

	// Set up autoloader.
	spl_autoload_register( 'media_credit_autoloader' );

	// Define plugin slug.
	$slug = 'media-credit';

	// Load version from plugin data.
	if ( ! function_exists( 'get_plugin_data' ) ) {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	$plugin_data = get_plugin_data( __FILE__, false, false );
	$version     = $plugin_data['Version'];

	// Create the plugin instance.
	$plugin = new Media_Credit( $slug, $version, plugin_basename( __FILE__ ) );

	// Register activation & deactivation hooks.
	$setup = new Media_Credit_Setup( $slug, $version );
	$setup->register( __FILE__ );

	// Start the plugin for real.
	$plugin->run();
}
run_media_credit();
