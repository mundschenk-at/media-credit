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

namespace Media_Credit\Components;

use Media_Credit\Settings;

use Media_Credit\Data_Storage\Options;

use Mundschenk\UI\Control_Factory;
use Mundschenk\UI\Controls;

/**
 * Handles additions to the "Media" settings page.
 *
 * @since 4.0.0
 */
class Settings_Page implements \Media_Credit\Component {

	const SETTINGS_SECTION = 'media-credit';

	/**
	 * The options handler.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * The default settings.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * The current version of this plugin.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Some strings for displaying the preview.
	 *
	 * @var    array   $preview_data {
	 *         Strings used for generating the preview.
	 *
	 *         @type string $pattern The pattern string for credits with two names.
	 *         @type string $name1   A male example name.
	 *         @type string $name2   A female example name.
	 *         @type string $joiner  The string used to join multiple image credits.
	 * }
	 */
	private $preview_data = [];

	/**
	 * Creates a new instance.
	 *
	 * @param string   $version     The plugin version.
	 * @param Options  $options     The options handler.
	 * @param Settings $settings    The default settings.
	 */
	public function __construct( $version, Options $options, Settings $settings ) {
		$this->version  = $version;
		$this->options  = $options;
		$this->settings = $settings;
	}

	/**
	 * Sets up the various hooks for the plugin component.
	 *
	 * @return void
	 */
	public function run() {
		if ( \is_admin() ) {
			// Register the settings.
			\add_action( 'admin_init', [ $this, 'register_settings' ] );

			// Enqueue some scripts and styles.
			\add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts_and_styles' ] );

			// Add settings link to plugins page.
			$basename = \plugin_basename( MEDIA_CREDIT_PLUGIN_FILE );
			\add_filter( "plugin_action_links_{$basename}", [ $this, 'add_action_links' ] );
		}
	}

	/**
	 * Registers the settings with the Settings API. This is only used to display
	 * an explanation of the wrong gravatar settings.
	 */
	public function register_settings() {
		\add_settings_section( self::SETTINGS_SECTION, __( 'Media Credit', 'media-credit' ), [ $this, 'print_settings_section' ], 'media' );

		\register_setting( 'media', $this->options->get_name( Options::OPTION ), [ $this, 'sanitize_settings' ] );

		// Initialize preview strings with translations.
		$this->preview_data = [
			/* translators: 1: last credit 2: concatenated other credits (empty in singular) */
			'pattern' => \_n( 'Image courtesy of %2$s%1$s', 'Images courtesy of %2$s and %1$s', 3, 'media-credit' ),
			'name1'   => \_x( 'John Smith', 'Male example name for preview', 'media-credit' ),
			'name2'   => \_x( 'Jane Doe', 'Female example name for preview', 'media-credit' ),
			'joiner'  => \_x( ', ', 'String used to join multiple image credits for "Display credit after post"', 'media-credit' ),
		];

		// Finalize the field defintions.
		$field_definitions = $this->settings->get_fields();
		$field_definitions[ Settings::MEDIA_CREDIT_PREVIEW ]['elements'] = [ $this->get_preview_markup() ];

		// Register control render callbacks.
		$controls = Control_Factory::initialize( $field_definitions, $this->options, Options::OPTION );
		foreach ( $controls as $control ) {
			$control->register( 'media' );
		}
	}

	/**
	 * Loads the preview partial.
	 *
	 * @return string
	 */
	protected function get_preview_markup() {
		// Start buffering.
		\ob_start();

		// The partial needs access to the plugin options.
		$options = $this->options->get( Options::OPTION, [] );

		// Require partial.
		require \dirname( MEDIA_CREDIT_PLUGIN_FILE ) . '/admin/partials/settings/preview.php';

		// Retrieve buffer.
		return \ob_get_clean();
	}

	/**
	 * Register the styles and scripts for the settings page.
	 *
	 * @param  string $hook_suffix The current admin page.
	 */
	public function enqueue_scripts_and_styles( $hook_suffix ) {
		if ( 'options-media.php' !== $hook_suffix ) {
			return;
		}

		// Set up resource file information.
		$url    = \plugin_dir_url( MEDIA_CREDIT_PLUGIN_FILE );
		$suffix = SCRIPT_DEBUG ? '' : '.min';

		// Style the preview area of the settings page.
		\wp_enqueue_style( 'media-credit-preview-style', "{$url}/admin/css/media-credit-preview{$suffix}.css", [], $this->version, 'screen' );

		// Preview script for the settings page.
		\wp_enqueue_script( 'media-credit-preview', "{$url}/admin/js/media-credit-preview{$suffix}.js", [ 'jquery' ], $this->version, true );
		\wp_localize_script( 'media-credit-preview', 'mediaCreditPreviewData', $this->preview_data );
	}

	/**
	 * Sanitize plugin settings array.
	 *
	 * @param  array $input The plugin settings.
	 *
	 * @return array The sanitized plugin settings.
	 */
	public function sanitize_settings( $input ) {
		// Blank out checkboxes because unset checkbox don't get sent by the browser.
		foreach ( $this->settings->get_fields() as $key => $info ) {
			if ( Controls\Checkbox_Input::class === $info['ui'] ) {
				$input[ $key ] = ! empty( $input[ $key ] );
			}
		}

		// Prepare valid options to preserve the version number.
		$valid_options = $this->options->get( Options::OPTION, [] );

		// Sanitize the actual input values.
		foreach ( $input as $key => $value ) {
			switch ( $key ) {
				case Settings::SEPARATOR:
					// We can't use sanitize_text_field because we want to keep enclosing whitespace.
					$valid_options[ $key ] = \wp_kses( $value, [] );
					break;

				default:
					$valid_options[ $key ] = \sanitize_text_field( $value );
			}
		}

		return $valid_options;
	}

	/**
	 * Print HTML for settings section.
	 *
	 * @param array $args The argument array.
	 */
	public function print_settings_section( $args ) {
		require \dirname( MEDIA_CREDIT_PLUGIN_FILE ) . '/admin/partials/settings/section.php';
	}

	/**
	 * Adds a Settings link for the plugin.
	 *
	 * @param  string[] $links An array of plugin action links. By default this
	 *                         can include 'activate', 'deactivate', and 'delete'.
	 *                         With Multisite active this can also include
	 *                         'network_active' and 'network_only' items.
	 *
	 * @return string[]        The modified list of action links.
	 */
	public function add_action_links( $links ) {
		$settings_link = '<a href="options-media.php#media-credit">' . \__( 'Settings', 'media-credit' ) . '</a>';
		\array_unshift( $links, $settings_link );

		return $links;
	}
}
