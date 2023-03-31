<?php
/**
 * This file is part of Media Credit.
 *
 * Copyright 2013-2021 Peter Putzer.
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

use Media_Credit\Core;
use Media_Credit\Settings;

/**
 * Handles plugin activation and deactivation.
 *
 * @since 3.0.0
 * @since 4.0.0 Moved to \Media_Credit\Components\Setup
 */
class Setup implements \Media_Credit\Component {

	/**
	 * The core API.
	 *
	 * @var Core
	 */
	private $core;

	/**
	 * The settings handler.
	 *
	 * @since 4.2.0
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Creates a new Setup instance.
	 *
	 * @since 4.2.0 Parameter $version removed. Parameter $options replaced by $settings.
	 *
	 * @param Core     $core     The core plugin API.
	 * @param Settings $settings The settings handler.
	 */
	public function __construct( Core $core, Settings $settings ) {
		$this->core     = $core;
		$this->settings = $settings;
	}

	/**
	 * Sets up the various hooks for the plugin component.
	 *
	 * @return void
	 */
	public function run() {
		// Register deactivation hook. Activation is handled by the update check instead.
		\register_deactivation_hook( \MEDIA_CREDIT_PLUGIN_FILE, [ $this, 'deactivate' ] );

		// Update settings and database if necessary.
		\add_action( 'plugins_loaded', [ $this, 'update_check' ] );

		// Register the meta fields.
		\add_action( 'init', [ $this, 'register_meta_fields' ] );
	}

	/**
	 * Checks if the default settings or database schema need to be upgraded.
	 *
	 * @return void
	 */
	public function update_check() {
		// Force reading the settings from the DB.
		$current_settings = $this->settings->get_all_settings( true );

		// Check if the plugin data needs to be updated.
		$previous_version = $current_settings[ Settings::INSTALLED_VERSION ];
		$version          = $this->settings->get_version();
		$update_needed    = $version !== $previous_version;

		if ( $update_needed ) {
			$this->maybe_update_postmeta_keys( $previous_version );
		}

		// Update installed version.
		$this->settings->set( Settings::INSTALLED_VERSION, $version );
	}

	/**
	 * Updates the postmeta keys if necessary.
	 *
	 * @since  4.2.0
	 *
	 * @param  string $previous_version The previously installed version.
	 *
	 * @return void
	 */
	protected function maybe_update_postmeta_keys( $previous_version ) {
		if ( \version_compare( $previous_version, '1.0.1', '<' ) ) {
			// Update all media-credit postmeta keys to _media_credit.
			global $wpdb;

			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update( $wpdb->postmeta, [ 'meta_key' => Core::POSTMETA_KEY ], [ 'meta_key' => 'media-credit' ] );
		}
	}

	/**
	 * Handles plugin deactivation.
	 *
	 * @param  bool $network_wide A flag indicating if the plugin was network-activated.
	 *
	 * @return void
	 */
	public function deactivate( /* @scrutinizer ignore-unused */ $network_wide ) {
		// Not used yet.
	}

	/**
	 * Sets up the meta fields with proper authorization and sanitization callbacks.
	 *
	 * @since  4.0.0
	 *
	 * @return void
	 */
	public function register_meta_fields() {
		\register_meta(
			'post',
			Core::POSTMETA_KEY,
			[
				'object_subtype'    => 'attachment',
				'type'              => 'string',
				'default'           => '',
				'description'       => 'The copyright line itself (if not overridden by the `user_id`)',
				'single'            => true,
				'sanitize_callback' => [ $this->core, 'sanitize_media_credit_meta_field' ],
				'auth_callback'     => [ $this->core, 'authorized_to_edit_media_credit' ],
				'show_in_rest'      => false,
			]
		);
		\register_meta(
			'post',
			Core::URL_POSTMETA_KEY,
			[
				'object_subtype'    => 'attachment',
				'type'              => 'string',
				'default'           => '',
				'description'       => 'A URL to link from the copyright information (overriding the default link to author pages)',
				'single'            => true,
				'sanitize_callback' => [ $this->core, 'sanitize_media_credit_meta_field' ],
				'auth_callback'     => [ $this->core, 'authorized_to_edit_media_credit' ],
				'show_in_rest'      => false,
			]
		);
		\register_meta(
			'post',
			Core::DATA_POSTMETA_KEY,
			[
				'object_subtype'    => 'attachment',
				'type'              => 'array',
				'default'           => [],
				'description'       => 'Optional flags for the copyright information (or the link)',
				'single'            => true,
				'sanitize_callback' => [ $this->core, 'sanitize_media_credit_meta_field' ],
				'auth_callback'     => [ $this->core, 'authorized_to_edit_media_credit' ],
				'show_in_rest'      => false,
			]
		);
	}
}
