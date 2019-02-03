<?php
/**
 * This file is part of Media Credit.
 *
 * Copyright 2019 Peter Putzer.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
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
 * @package mundschenk-at/media-credit
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */

// Don't do anything if called directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die();
}

// Load requirements class in a PHP 5.2 compatible manner.
require_once dirname( __FILE__ ) . '/vendor/mundschenk-at/check-wp-requirements/class-mundschenk-wp-requirements.php';

/**
 * Uninstall the plugin after checking for the necessary PHP version.
 *
 * It's necessary to do this here because our classes rely on namespaces.
 */
function media_credit_uninstall() {
	// Define our requirements.
	$reqs = array(
		'php'       => '5.6.0',
		'multibyte' => false,
		'utf-8'     => false,
	);

	// Validate the requirements.
	$requirements = new Mundschenk_WP_Requirements( 'Media Credit', __FILE__, 'media-credit', $reqs );

	if ( $requirements->check() ) {
		// Autoload the rest of your classes.
		require_once __DIR__ . '/vendor/autoload.php'; // phpcs:ignore PHPCompatibility.Keywords.NewKeywords.t_dirFound

		// Create and start the uninstallation handler.
		$uninstaller = Media_Credit_Factory::get( __FILE__ )->create( 'Media_Credit\Components\Uninstallation' );
		$uninstaller->run();
	}
}
media_credit_uninstall();
