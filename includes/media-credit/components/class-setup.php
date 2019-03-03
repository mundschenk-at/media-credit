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

use Media_Credit\Core;
use Media_Credit\Settings;
use Media_Credit\Data_Storage\Options;

/**
 * Handles plugin activation and deactivation.
 *
 * @since 3.0.0
 * @since 4.0.0 Moved to \Media_Credit\Components\Setup
 */
class Setup implements \Media_Credit\Component {

	/**
	 * The plugin version string.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * The core API.
	 *
	 * @var Core
	 */
	private $core;

	/**
	 * The options handler.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Creates a new Setup instance.
	 *
	 * @param string  $version         The plugin version string.
	 * @param Core    $core            The core plugin API.
	 * @param Options $options         The options handler.
	 */
	public function __construct( $version, Core $core, Options $options ) {
		$this->version = $version;
		$this->core    = $core;
		$this->options = $options;
	}

	/**
	 * Sets up the various hooks for the plugin component.
	 *
	 * @return void
	 */
	public function run() {
		// Register deactivation hook. Activation is handled by the update check instead.
		\register_deactivation_hook( MEDIA_CREDIT_PLUGIN_FILE, [ $this, 'deactivate' ] );

		// Update settings and database if necessary.
		\add_action( 'plugins_loaded', [ $this, 'update_check' ] );

		// Register the meta fields.
		\add_action( 'init', [ $this, 'register_meta_fields' ] );
	}

	/**
	 * Checks if the default settings or database schema need to be upgraded.
	 */
	public function update_check() {
		// The default plugin options.
		$default_options = [
			'version'               => $this->version,
			'install_date'          => \date( 'Y-m-d' ),
			'separator'             => Settings::DEFAULT_SEPARATOR,
			'organization'          => \get_bloginfo( 'name', 'display' ),
			'credit_at_end'         => false,
			'no_default_credit'     => false,
			'post_thumbnail_credit' => false,
			'schema_org_markup'     => false,
		];

		// Retrieve options.
		$original_options  = $this->options->get( Options::OPTION, [] );
		$installed_options = $original_options;

		// Also look for legacy options.
		if ( empty( $original_options ) ) {
			$installed_options = $this->load_legacy_options();
		}

		if ( empty( $installed_options ) ) {
			// The plugin was installed for the frist time.
			$installed_options = $default_options;
		} elseif ( ! isset( $installed_options['version'] ) ) {
			// Upgrade plugin to 1.0 (0.5.5 didn't have a version number).
			$installed_options['install_date'] = $default_options['install_date'];
		}

		// Upgrade plugin to 1.0.1.
		if ( \version_compare( $installed_options['version'], '1.0.1', '<' ) ) {
			// Update all media-credit postmeta keys to _media_credit.
			global $wpdb;

			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update( $wpdb->postmeta, [ 'meta_key' => Core::POSTMETA_KEY ], [ 'meta_key' => 'media-credit' ] );
		}

		// Upgrade plugin to 2.2.0.
		if ( \version_compare( $installed_options['version'], '2.2.0', '<' ) ) {
			$installed_options['no_default_credit'] = $default_options['no_default_credit'];
		}

		// Upgrade plugin to 3.0.0.
		if ( \version_compare( $installed_options['version'], '3.0.0', '<' ) ) {
			$installed_options['post_thumbnail_credit'] = $default_options['post_thumbnail_credit'];
		}

		// Upgrade plugin to 3.1.0.
		if ( \version_compare( $installed_options['version'], '3.1.0', '<' ) ) {
			$installed_options['schema_org_markup'] = $default_options['schema_org_markup'];
		}

		// Update installed version.
		$installed_options['version'] = $this->version;

		// Store upgraded options.
		if ( $original_options !== $installed_options ) {
			$this->options->set( Options::OPTION, $installed_options );
		}
	}

	/**
	 * Retrieves legacy options and deletes them from the database.
	 *
	 * @return array   The legacy options, or an empty array.
	 */
	protected function load_legacy_options() {
		$legacy_options = $this->options->get( 'media-credit', [], true );
		if ( empty( $legacy_options ) ) {
			// No legacy options found, abort.
			return [];
		}

		// Delete legacy options.
		$this->options->delete( 'media-credit', true );

		return $legacy_options;
	}

	/**
	 * Handles plugin deactivation.
	 *
	 * @param  bool $network_wide A flag indicating if the plugin was network-activated.
	 */
	public function deactivate( /* @scrutinizer ignore-unused */ $network_wide ) {
		// Not used yet.
	}

	/**
	 * Sets up the meta fields with proper authorization and sanitization callbacks.
	 *
	 * @since 4.0.0
	 */
	public function register_meta_fields() {
		\register_meta(
			'post',
			Core::POSTMETA_KEY,
			[
				'object_subtype'    => 'attachment',
				'type'              => 'string',
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
				'description'       => 'Optional flags for the copyright information (or the link)',
				'single'            => true,
				'sanitize_callback' => [ $this->core, 'sanitize_media_credit_meta_field' ],
				'auth_callback'     => [ $this->core, 'authorized_to_edit_media_credit' ],
				'show_in_rest'      => false,
			]
		);
	}
}
