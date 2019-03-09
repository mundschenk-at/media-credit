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

use Dice\Dice;

use Media_Credit\Core;
use Media_Credit\Components;
use Media_Credit\Tools;

use Mundschenk\Data_Storage;

/**
 * A factory for creating Media_Credit instances via dependency injection.
 *
 * @since 4.0.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
abstract class Media_Credit_Factory {
	const SHARED = [ 'shared' => true ];

	/**
	 * The factory instance.
	 *
	 * @var Dice
	 */
	private static $factory;

	/**
	 * Retrieves a factory set up for creating Media_Credit instances.
	 *
	 * @param string $full_plugin_path The full path to the main plugin file (i.e. __FILE__).
	 *
	 * @return Dice
	 */
	public static function get( $full_plugin_path ) {
		if ( ! isset( self::$factory ) ) {
			// Load version from plugin data.
			if ( ! \function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			// The plugin version.
			$version = \get_plugin_data( $full_plugin_path, false, false )['Version'];

			// Rule helper.
			$version_shared_rule = [
				'shared'          => true,
				'constructParams' => [ $version ],
			];

			// Define rules.
			$rules = [
				// Core API.
				Core::class                         => [
					'shared'          => true,
					'constructParams' => [ $version ],
					'call'            => [
						[ 'make_singleton', [] ],
					],
				],

				// Shared helpers.
				Data_Storage\Cache::class           => self::SHARED,
				Data_Storage\Transients::class      => self::SHARED,
				Data_Storage\Site_Transients::class => self::SHARED,
				Data_Storage\Options::class         => self::SHARED,
				Data_Storage\Network_Options::class => self::SHARED,
				Tools\Media_Query::class            => self::SHARED,
				Tools\Shortcodes_Filter::class      => self::SHARED,

				// Components.
				Components\Block_Editor::class      => $version_shared_rule,
				Components\Classic_Editor::class    => $version_shared_rule,
				Components\Frontend::class          => $version_shared_rule,
				Components\Media_Library::class     => $version_shared_rule,
				Components\REST_API::class          => self::SHARED,
				Components\Settings_Page::class     => $version_shared_rule,
				Components\Setup::class             => $version_shared_rule,
			];

			// Create factory.
			self::$factory = new Dice();

			// Add rules.
			foreach ( $rules as $classname => $rule ) {
				self::$factory->addRule( $classname, $rule );
			}
		}

		return self::$factory;
	}
}
