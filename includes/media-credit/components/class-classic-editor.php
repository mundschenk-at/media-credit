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

use Media_Credit\Template_Tags;
use Media_Credit\Data_Storage\Options;

/**
 * The component handling the integration with the Classic Editor (i.e. TinyMCE).
 *
 * @since 3.3.0
 */
class Classic_Editor implements \Media_Credit\Component, \Media_Credit\Base {

	/**
	 * The version of this plugin.
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
	 * The base URL for loading ressources.
	 *
	 * @var string
	 */
	private $url;

	/**
	 * The file suffix for loading ressources.
	 *
	 * @var string
	 */
	private $suffix;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string  $version     The plugin version.
	 * @param Options $options     The options handler.
	 */
	public function __construct( $version, Options $options ) {
		$this->version = $version;
		$this->options = $options;
	}

	/**
	 * Sets up the various hooks for the plugin component.
	 *
	 * @return void
	 */
	public function run() {
		// Initialize editor integration after WordPress has loaded.
		\add_action( 'init', [ $this, 'initialize_editor_integration' ] );
	}

	/**
	 * Initialize settings.
	 */
	public function initialize_editor_integration() {
		// Don't bother doing this stuff if the current user lacks permissions as they'll never see the pages.
		if ( \user_can_richedit() ) {
			// Set up resource files.
			$this->url    = \plugin_dir_url( MEDIA_CREDIT_PLUGIN_FILE );
			$this->suffix = SCRIPT_DEBUG ? '' : '.min';

			// Load our TinyMCE plugins and styles.
			\add_filter( 'tiny_mce_plugins',     [ $this, 'tinymce_internal_plugins' ] );
			\add_filter( 'mce_external_plugins', [ $this, 'tinymce_external_plugins' ] );
			\add_filter( 'mce_css',              [ $this, 'tinymce_css' ] );

			// Set up the scripts and templates for the "image properties" dialog.
			\add_action( 'wp_enqueue_editor',     [ $this, 'enqueue_editor' ] );
			\add_action( 'print_media_templates', [ $this, 'image_properties_template' ] );
		}

		// Include [media-credit] shortcodes when adding an image to the editor.
		\add_filter( 'image_send_to_editor', [ $this, 'add_media_credit_to_image' ], 10, 5 );
	}

	/**
	 * Add styling for media credits in the rich editor.
	 *
	 * @param string $css A comma separated list of CSS files.
	 *
	 * @return string A comma separated list of CSS files.
	 */
	public function tinymce_css( $css ) {
		return ( ! empty( $css ) ? "{$css}," : '' ) . "{$this->url}/admin/css/media-credit-tinymce{$this->suffix}.css";
	}

	/**
	 * Enqueue scripts & styles for displaying media credits in the rich-text editor.
	 *
	 * @param array $to_load An array containing boolean values whether TinyMCE and Quicktags are being loaded.
	 */
	public function enqueue_editor( $to_load ) {
		if ( ! empty( $to_load['tinymce'] ) ) {
			// Note: An additional dependency "media-views" is not listed below
			// because in some cases such as /wp-admin/press-this.php the media
			// library isn't enqueued and shouldn't be. The script includes
			// safeguards to avoid errors in this situation.
			\wp_enqueue_script( 'media-credit-image-properties', "{$this->url}/admin/js/tinymce4/media-credit-image-properties{$this->suffix}.js", [ 'jquery', 'media-credit-attachment-details' ], $this->version, true );
			\wp_enqueue_script( 'media-credit-tinymce-switch',   "{$this->url}/admin/js/tinymce4/media-credit-tinymce-switch{$this->suffix}.js",   [ 'jquery' ], $this->version, true );

			// Edit in style.
			\wp_enqueue_style( 'media-credit-image-properties-style', "{$this->url}/admin/css/tinymce4/media-credit-image-properties{$this->suffix}.css", [], $this->version, 'screen' );
		}
	}

	/**
	 * Removes the default wpeditimage plugin.
	 *
	 * @param array $plugins An array of plugins to load.
	 *
	 * @return array         The array of plugins to load.
	 */
	public function tinymce_internal_plugins( $plugins ) {
		$key = \array_search( 'wpeditimage', $plugins, true );

		if ( false !== $key ) {
			unset( $plugins[ $key ] );
		}

		return $plugins;
	}

	/**
	 * Add our own version of the wpeditimage plugin.
	 * The plugins depend on the global variable echoed in admin_head().
	 *
	 * @param array $plugins An array of plugins to load.
	 *
	 * @return array         The array of plugins to load.
	 */
	public function tinymce_external_plugins( $plugins ) {
		$plugins['mediacredit'] = "{$this->url}/admin/js/tinymce4/media-credit-tinymce{$this->suffix}.js";
		$plugins['noneditable'] = "{$this->url}/admin/js/tinymce4/tinymce-noneditable{$this->suffix}.js";

		return $plugins;
	}

	/**
	 * Template for setting Media Credit in image properties.
	 */
	public function image_properties_template() {
		include \dirname( MEDIA_CREDIT_PLUGIN_FILE ) . '/admin/partials/media-credit-image-properties-tmpl.php';
	}

	/**
	 * Add media credit information to media using shortcode notation before sending to editor.
	 *
	 * @param string $html          The image HTML markup to send.
	 * @param int    $attachment_id The attachment id.
	 * @param string $caption       The image caption.
	 * @param string $title         The image title.
	 * @param string $align         The image alignment.
	 *
	 * @return string
	 */
	public function add_media_credit_to_image( $html, $attachment_id, $caption, $title, $align ) {
		$attachment  = \get_post( $attachment_id );
		$credit_meta = Template_Tags::get_freeform_media_credit( $attachment );
		$credit_url  = Template_Tags::get_media_credit_url( $attachment );
		$credit_data = Template_Tags::get_media_credit_data( $attachment );
		$options     = $this->options->get( Options::OPTION, [] );

		// Set freeform or blog user credit.
		if ( self::EMPTY_META_STRING === $credit_meta ) {
			return $html;
		} elseif ( ! empty( $credit_meta ) ) {
			$credit = 'name="' . $credit_meta . '"';
		} elseif ( empty( $options['no_default_credit'] ) ) {
			$credit = 'id=' . $attachment->post_author;
		} else {
			return $html;
		}

		// Add link URL.
		if ( ! empty( $credit_url ) ) {
			$credit .= ' link="' . $credit_url . '"';

			// Optionally add nofollow parameter.
			if ( ! empty( $credit_data['nofollow'] ) ) {
				$credit .= ' nofollow=' . $credit_data['nofollow'] . '';
			}
		}

		// Extract image width.
		if ( ! \preg_match( '/width="([0-9]+)/', $html, $width ) ) {
			return $html;
		}
		$width = $width[1];

		// Extract alignment.
		$html = \preg_replace( '/(class=["\'][^\'"]*)align(none|left|right|center)\b/S', '$1', $html );
		if ( empty( $align ) ) {
			$align = 'none';
		}

		// Put it all together.
		$shcode = '[media-credit ' . $credit . ' align="align' . $align . '" width="' . $width . '"]' . $html . '[/media-credit]';

		// @todo Document filter.
		return \apply_filters( 'media_add_credit_shortcode', $shcode, $html );
	}
}
