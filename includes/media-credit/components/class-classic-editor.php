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

/**
 * The component handling the integration with the Classic Editor (i.e. TinyMCE).
 *
 * @since 4.0.0
 */
class Classic_Editor implements \Media_Credit\Component {

	/**
	 * The version of this plugin.
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
	 * @param string $version     The plugin version.
	 * @param Core   $core    The core plugin API.
	 */
	public function __construct( $version, Core $core ) {
		$this->version = $version;
		$this->core    = $core;
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
	 * @param  string $html          The image HTML markup to send.
	 * @param  int    $attachment_id The attachment id.
	 * @param  string $caption       The image caption.
	 * @param  string $title         The image title.
	 * @param  string $align         The image alignment.
	 *
	 * @return string
	 */
	public function add_media_credit_to_image( $html, $attachment_id, $caption, $title, $align ) {
		// Get the attachment object.
		$attachment = \get_post( $attachment_id );

		// Bail if we don't have a valid attachment.
		if ( ! $attachment instanceof \WP_Post ) {
			return $html;
		}

		// Retrieve credit for image.
		$credit = $this->core->get_media_credit_json( $attachment );

		// Set freeform or site user credit.
		if ( Core::EMPTY_META_STRING === $credit['raw']['freeform'] || empty( $credit['plaintext'] ) ) {
			// No credit to add.
			return $html;
		} elseif ( ! empty( $credit['raw']['freeform'] ) ) {
			// Add the freeform credit.
			$shortcode_arguments = "name='{$credit['raw']['freeform']}'";
		} else {
			// Add the user credit.
			$shortcode_arguments = "id={$credit['raw']['user_id']}";
		}

		// Add link URL.
		if ( ! empty( $credit['raw']['url'] ) ) {
			$shortcode_arguments .= " link='{$credit['raw']['url']}'";

			// Optionally add "nofollow" parameter.
			if ( ! empty( $credit['raw']['flags']['nofollow'] ) ) {
				$shortcode_arguments .= ' nofollow=1';
			}
		}

		// Try to extract the image width and add it to the shortcode arguments if possible.
		if ( \preg_match( '/width=["\']([0-9]+)/S', $html, $width ) ) {
			$shortcode_arguments .= " width=$width[1]";
		}

		// Add alignment to shortcode arguments and strip it from the image markup.
		$shortcode_arguments .= " align='{$align}'";
		$html                 = \preg_replace( "/(class=[\"'][^\"']*)align{$align}\s*/S", '$1', $html );

		// Put it all together.
		$shortcode = "[media-credit {$shortcode_arguments}]{$html}[/media-credit]";

		// @todo Document filter.
		return \apply_filters( 'media_add_credit_shortcode', $shortcode, $html );
	}
}
