<?php
/**
 *  This file is part of Media Credit.
 *
 *  Copyright 2013-2016 Peter Putzer.
 *  Copyright 2010-2011 Scott Bressler.
 *
 *	This program is free software; you can redistribute it and/or
 *	modify it under the terms of the GNU General Public License,
 *	version 2 as published by the Free Software Foundation.
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *
 *	You should have received a copy of the GNU General Public License
 *	along with this program; if not, write to the Free Software
 *	Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 *  MA 02110-1301, USA.
 *
 * @link       https://mundschenk.at
 * @since      3.0.0
 *
 * @package    Media_Credit
 * @subpackage Media_Credit/includes
 */

/**
 * Fired during plugin de-/activation and uninstall.
 *
 * This class defines all code necessary to run during the plugin's setup and teardown.
 *
 * @since      3.0.0
 * @package    Media_Credit
 * @subpackage Media_Credit/includes
 * @author     Peter Putzer <github@mundschenk.at>
 */
class Media_Credit_Setup implements Media_Credit_Base {

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    3.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Create new Media_Credit_Setup object.
	 *
	 * @param string $slug    The plugin slug.
	 * @param string $version The version string.
	 */
	function __construct( $slug, $version ) {
		$this->plugin_name = $slug;
		$this->plugin_version = $version;
	}

	/**
	 * Register the de-/activation/uninstall hooks for the plugin.
	 *
	 * @param string $plugin_file The full path and filename to the main plugin file.
	 */
	public function register( $plugin_file ) {
		register_activation_hook( $plugin_file, array( $this, 'activate' ) );
		register_deactivation_hook( $plugin_file, array( $this, 'deactivate' ) );
		register_uninstall_hook( $plugin_file, __CLASS__ . '::uninstall' );
	}

	/**
	 * Fired during plugin activation.
	 *
	 * @since      3.0.0
	 */
	public function activate() {
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
			add_option( $this->plugin_name, $default_options );
			$installed_options = $default_options;
		} elseif ( ! isset( $installed_options['version'] ) ) { // Upgrade plugin to 1.0 (0.5.5 didn't have a version number).
			$installed_options['version']      = '1.0';
			$installed_options['install_date'] = $default_options['install_date'];
			update_option( $this->plugin_name, $installed_options );
		}

		// Upgrade plugin to 1.0.1.
		if ( version_compare( $installed_options['version'], '1.0.1', '<' ) ) {
			// Update all media-credit postmeta keys to _media_credit.
			global $wpdb;
			$wpdb->update( $wpdb->postmeta, array( 'meta_key' => self::POSTMETA_KEY ), array( 'meta_key' => 'media-credit' ) ); // WPSC: cache ok, tax_query ok.

			$installed_options['version'] = '1.0.1';
			update_option( $this->plugin_name, $installed_options );
		}

		// Upgrade plugin to 2.2.0.
		if ( version_compare( $installed_options['version'], '2.2.0', '<' ) ) {
			$installed_options['version']           = '2.2.0';
			$installed_options['no_default_credit'] = $default_options['no_default_credit'];
			update_option( $this->plugin_name, $installed_options );
		}

		// Upgrade plugin to 3.0.0.
		if ( version_compare( $installed_options['version'], '3.0.0', '<' ) ) {
			$installed_options['version']               = '3.0.0';
			$installed_options['post_thumbnail_credit'] = $default_options['post_thumbnail_credit'];
			update_option( $this->plugin_name, $installed_options );
		}

		// Upgrade plugin to 3.1.0.
		if ( version_compare( $installed_options['version'], '3.1.0', '<' ) ) {
			$installed_options['version']           = '3.1.0';
			$installed_options['schema_org_markup'] = $default_options['schema_org_markup'];
			update_option( $this->plugin_name, $installed_options );
		}
	}

	/**
	 * Fired during plugin deactivation.
	 *
	 * @since    3.0.0
	 */
	public function deactivate() {

	}

	/**
	 * Fired during uninstall.
	 *
	 * Long Description.
	 *
	 * @since    3.0.0
	 */
	static function uninstall() {
		delete_option( self::OPTION );
	}
}
