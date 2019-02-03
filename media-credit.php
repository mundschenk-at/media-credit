<?php
/**
 * This file is part of Media Credit.
 *
 * Copyright 2013-2019 Peter Putzer.
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
 * Version: 3.3.0-alpha.1
 * Author: Peter Putzer
 * Author: Scott Bressler
 * Author URI: https://mundschenk.at/
 * Text Domain: media-credit
 * License: GPL2
 */

// Don't do anything if called directly.
if ( ! defined( 'ABSPATH' ) || ! defined( 'WPINC' ) ) {
	die();
}

// Load requirements class in a PHP 5.2 compatible manner.
require_once dirname( __FILE__ ) . '/vendor/mundschenk-at/check-wp-requirements/class-mundschenk-wp-requirements.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since 3.0.0
 * @since 3.3.0 Renamed to media_credit_run
 */
function media_credit_run() {
	// Define our requirements.
	$reqs = array(
		'php'       => '5.6.0',
		'multibyte' => false,
		'utf-8'     => false,
	);

	// Validate the requirements.
	$requirements = new Mundschenk_WP_Requirements( 'Media Credit', __FILE__, 'media-credit', $reqs );
	if ( $requirements->check() ) {
		// Autoload the rest of our classes.
		require_once __DIR__ . '/vendor/autoload.php'; // phpcs:ignore PHPCompatibility.Keywords.NewKeywords.t_dirFound

		// Load version from plugin data.
		if ( ! function_exists( 'get_plugin_data' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$plugin_data = get_plugin_data( __FILE__, false, false );
		$version     = $plugin_data['Version'];

		// Create the plugin instance.
		$plugin = new Media_Credit( 'media-credit', $version, plugin_basename( __FILE__ ) );

		// Register activation & deactivation hooks.
		$setup = new Media_Credit_Setup( 'media-credit', $version );
		$setup->register( __FILE__ );

		// Start the plugin for real.
		$plugin->run();
	}
}
media_credit_run();
