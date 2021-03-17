<?php
/**
 * This file is part of Media Credit.
 *
 * Copyright 2019-2021 Peter Putzer.
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

namespace Media_Credit;

use Media_Credit\Components\Uninstallation;

// Don't do anything if called directly.
if ( ! \defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die();
}

// Make plugin file path available globally (even if we probably don't need it during uninstallaton).
if ( ! \defined( 'MEDIA_CREDIT_PLUGIN_FILE' ) ) {
	\define( 'MEDIA_CREDIT_PLUGIN_FILE', \dirname( __FILE__ ) . '/media-credit.php' );
}
if ( ! \defined( 'MEDIA_CREDIT_PLUGIN_PATH' ) ) {
	\define( 'MEDIA_CREDIT_PLUGIN_PATH', __DIR__ );
}

// Initialize autoloader.
require_once __DIR__ . '/vendor/autoload.php';

/**
 * Uninstall the plugin after checking for the necessary PHP version.
 *
 * It's necessary to do this here because our classes rely on namespaces.
 *
 * @return void
 */
function media_credit_uninstall() {
	// Validate the requirements.
	if ( ( new Requirements() )->check() ) {
		/**
		 * Create and start the uninstallation handler.
		 *
		 * @var Uninstallation
		 */
		$uninstaller = \Media_Credit_Factory::get()->create( Uninstallation::class );
		$uninstaller->run();
	}
}
media_credit_uninstall();
