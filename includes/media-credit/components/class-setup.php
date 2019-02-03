<?php
/**
 * This file is part of Media Credit.
 *
 * Copyright 2013-2019 Peter Putzer.
 * Copyright 2010-2011 Scott Bressler.
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

namespace Media_Credit\Components;

use Mundschenk\Data_Storage\Options;

/**
 * Handles plugin activation and deactivation.
 *
 * @since 3.0.0
 * @since 3.3.0 Moved to \Media_Credit\Components\Setup
 */
class Setup implements \Media_Credit\Component, \Media_Credit\Base {

	/**
	 * The full path to the main plugin file.
	 *
	 * @var string
	 */
	private $plugin_file;

	/**
	 * The plugin version string.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * The options handler.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Creates a new Setup instance.
	 *
	 * @param string     $plugin_file     The full path to the base plugin file.
	 * @param string     $version         The plugin version string.
	 * @param Options    $options         The options handler.
	 */
	public function __construct( $plugin_file, $version, Options $options ) {
		$this->plugin_file = $plugin_file;
		$this->version     = $version;
		$this->options     = $options;
	}

	/**
	 * Sets up the various hooks for the plugin component.
	 *
	 * @return void
	 */
	public function run() {
		// Register deactivation hook. Activation is handled by the update check instead.
		\register_deactivation_hook( $this->plugin_file, [ $this, 'deactivate' ] );

		// Update settings and database if necessary.
		\add_action( 'plugins_loaded', [ $this, 'update_check' ] );
	}

	/**
	 * Checks if the default settings or database schema need to be upgraded.
	 */
	public function update_check() {
		/**
		 * A hash containing the default options.
		 */
		$default_options = array(
			'version'               => $this->version,
			'install_date'          => date( 'Y-m-d' ),
			'separator'             => self::DEFAULT_SEPARATOR,
			'organization'          => get_bloginfo( 'name', 'display' ),
			'credit_at_end'         => false,
			'no_default_credit'     => false,
			'post_thumbnail_credit' => false,
			'schema_org_markup'     => false,
		);

		$installed_options = get_option( self::OPTION );

		if ( empty( $installed_options ) ) { // Install plugin for the first time.
			add_option( self::OPTION, $default_options );
			$installed_options = $default_options;
		} elseif ( ! isset( $installed_options['version'] ) ) { // Upgrade plugin to 1.0 (0.5.5 didn't have a version number).
			$installed_options['version']      = '1.0';
			$installed_options['install_date'] = $default_options['install_date'];
			update_option( self::OPTION, $installed_options );
		}

		// Upgrade plugin to 1.0.1.
		if ( version_compare( $installed_options['version'], '1.0.1', '<' ) ) {
			// Update all media-credit postmeta keys to _media_credit.
			global $wpdb;

			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update( $wpdb->postmeta, array( 'meta_key' => self::POSTMETA_KEY ), array( 'meta_key' => 'media-credit' ) );

			$installed_options['version'] = '1.0.1';
			update_option( self::OPTION, $installed_options );
		}

		// Upgrade plugin to 2.2.0.
		if ( version_compare( $installed_options['version'], '2.2.0', '<' ) ) {
			$installed_options['version']           = '2.2.0';
			$installed_options['no_default_credit'] = $default_options['no_default_credit'];
			update_option( self::OPTION, $installed_options );
		}

		// Upgrade plugin to 3.0.0.
		if ( version_compare( $installed_options['version'], '3.0.0', '<' ) ) {
			$installed_options['version']               = '3.0.0';
			$installed_options['post_thumbnail_credit'] = $default_options['post_thumbnail_credit'];
			update_option( self::OPTION, $installed_options );
		}

		// Upgrade plugin to 3.1.0.
		if ( version_compare( $installed_options['version'], '3.1.0', '<' ) ) {
			$installed_options['version']           = '3.1.0';
			$installed_options['schema_org_markup'] = $default_options['schema_org_markup'];
			update_option( self::OPTION, $installed_options );
		}

	}

	/**
	 * Handles plugin deactivation.
	 *
	 * @param  bool $network_wide A flag indicating if the plugin was network-activated.
	 */
	public function deactivate( /* @scrutinizer ignore-unused */ $network_wide ) {
		// Not used yet.
	}
}