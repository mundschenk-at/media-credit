<?php

/*
	Plugin Name: Media Credit
	Plugin URI: https://code.mundschenk.at/media-credit/
	Description: This plugin adds a "Credit" field to the media uploading and editing tool and inserts this credit when the images appear on your blog.
	Version: 3.0.1
	Author: Peter Putzer
	Author: Scott Bressler
	Author URI: https://mundschenk.at/
	Text Domain: media-credit
	License: GPL2
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * An autoloader implementation for our classes.
 *
 * @param string $class_name
 */
function media_credit_autoloader( $class_name ) {
	if ( false === strpos( $class_name, 'Media_Credit' ) ) {
		return; // abort
	}

	static $classes_dir;
	if ( empty( $classes_dir ) ) {
		$classes_dir['default'] = realpath( plugin_dir_path( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR;
		$classes_dir['public']  = realpath( plugin_dir_path( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR;
		$classes_dir['admin']   = realpath( plugin_dir_path( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR;
	}
	$class_file = 'class-' . str_replace( '_', '-', strtolower( $class_name ) ) . '.php';

	if ( is_file( $class_file_path = $classes_dir['default'] . $class_file ) ||
	     is_file( $class_file_path = $classes_dir['admin']   . $class_file ) ||
	     is_file( $class_file_path = $classes_dir['public']  . $class_file ) ) {
		require_once( $class_file_path );
	}
}

/**
 * Load legacy template tags.
 */
require_once( plugin_dir_path( __FILE__ ) . 'includes/media-credit-template.php' );

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
	spl_autoload_register( 'media_credit_autoloader' );

	if( ! function_exists( 'get_plugin_data' ) ) {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}
	$plugin_data = get_plugin_data( __FILE__, false, false );

	$slug = 'media-credit';
	$version = $plugin_data['Version'];

	$setup = new Media_Credit_Setup( $slug, $version );
	$setup->register( __FILE__ );

	$plugin = new Media_Credit( $slug, $version, plugin_basename( __FILE__ ) );
	$plugin->run();

}
run_media_credit();
