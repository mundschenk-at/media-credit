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
use Media_Credit\Data_Storage\Options;

/**
 * The component providing the `[media-credit]` shortcode and patching `[caption]`
 * and `[wp_caption]`.
 *
 * @since 4.0.0
 */
class Shortcodes implements \Media_Credit\Component {

	/**
	 * The default shortcode attributes for `[media-credit]`.
	 *
	 * @var array
	 */
	const MEDIA_CREDIT_DEFAULTS = [
		'id'         => 0,
		'name'       => '',
		'link'       => '',
		'standalone' => true,
		'align'      => 'none',
		'width'      => 0,
		'nofollow'   => false,
	];

	/**
	 * The plugin settings.
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * The options handler.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * The core API.
	 *
	 * @var Core
	 */
	private $core;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param Core    $core    The core plugin API.
	 * @param Options $options     The options handler.
	 */
	public function __construct( Core $core, Options $options ) {
		$this->core    = $core;
		$this->options = $options;
	}

	/**
	 * Sets up the various hooks for the plugin component.
	 *
	 * @return void
	 */
	public function run() {
		// Initialize shortcodes after WordPress has loaded.
		\add_action( 'init', [ $this, 'add_shortcodes' ] );
	}

	/**
	 * Adds our shortcode and overrdies the WordPress caption shortcodes to allow nesting.
	 */
	public function add_shortcodes() {
		// Load plugin options.
		$this->settings = $this->core->get_settings();

		// Override WordPress caption shortcodes.
		\add_shortcode( 'wp_caption',   [ $this, 'caption_shortcode' ] );
		\add_shortcode( 'caption',      [ $this, 'caption_shortcode' ] );

		// Add new media credit shortcode.
		\add_shortcode( 'media-credit', [ $this, 'media_credit_shortcode' ] );
	}

	/**
	 * Allows `[media-credit]` shortcodes inside `[caption]`.
	 *
	 * Fixes the new style caption shortcode parsing and then calls the stock
	 * shortcode function. Optionally adds schema.org microdata.
	 *
	 * @param array  $attr    The `[caption]` shortcode attributes.
	 * @param string $content Optional. Shortcode content. Default null.
	 *
	 * @return string The enriched caption markup.
	 */
	public function caption_shortcode( $attr, $content = null ) {
		// Options influencing the markup.
		$html5      = \current_theme_supports( 'html5', 'caption' );
		$schema_org = ! empty( $this->settings['schema_org_markup'] );

		// New-style shortcode with the caption inside the shortcode with the link and image tags.
		if ( ! isset( $attr['caption'] ) ) {
			if ( \preg_match( '#((?:\[media-credit[^\]]+\]\s*)(?:<a [^>]+>\s*)?<img [^>]+>(?:\s*</a>)?(?:\s*\[/media-credit\])?)(.*)#Sis', $content, $matches ) ) {
				$content         = $matches[1];
				$attr['caption'] = \trim( $matches[2] );

				if ( ! $html5 ) {
					// Add attribute "standalone=0" to [media-credit] shortcode if present.
					$content = \preg_replace( '#\[media-credit([^]]+)\]#S', '[media-credit standalone=0$1]', $content );
				} elseif ( \preg_match( '#\[media-credit([^]]+)\]#S', $content, $matches ) ) {
					// Use improved HTML5 mode.
					$shortcode = $matches[0];
					$content   = \str_replace( [ $shortcode, '[/media-credit]' ], '', $content );

					if ( empty( $this->settings['credit_at_end'] ) ) {
						// The byline.
						$credit_attr = $this->sanitize_attributes( (array) \shortcode_parse_atts( $matches[1] ) );
						$credit      = $this->inline_media_credit( $credit_attr, $schema_org );

						// The original caption.
						$caption = $attr['caption'];

						/**
						 * Filters the HTML5 caption including the credit byline.
						 *
						 * @since 4.0.0
						 *
						 * @param string $caption     The caption including the credit.
						 *                            Default caption text followed by
						 *                            credit with a seprating space.
						 * @param string $old_caption The caption text.
						 * @param string $credit      The credit byline (including markup).
						 * @param array  $attr {
						 *     An array of shortcode attributes.
						 *
						 *     @type int    $id         Optional. A user ID. Default 0.
						 *     @type string $name       Optional. The (freeform) credit to display. Default ''.
						 *     @type string $link       Optional. A URL used for linking the credit.
						 *     @type bool   $standalone Optional. A flag indicating that the shortcode
						 *                              was used without an enclosing `[caption]`. Default true.
						 *     @type string $align      Optional. The alignment to use for the image/figure
						 *                              (if used without `[caption]`). Default 'none'.
						 *     @type int    $width      Optional. The width of the image/figure. Default 0.
						 *     @type bool   $no_follow  Optional. A flag indicating that a `rel=nofollow`
						 *                              attribute should be added to the link tag.
						 * }
						 */
						$attr['caption'] = \apply_filters( 'media_credit_shortcode_html5_caption', "{$caption} {$credit}", $caption, $credit, $credit_attr );
					}
				}
			}
		}

		// Get caption markup.
		$caption = \img_caption_shortcode( $attr, $content );

		// Optionally add schema.org markup.
		if ( $schema_org ) {
			// Inject schema.org markup for figure.
			if ( ! \preg_match( '/<figure[^>]*\bitemscope\b/S', $caption ) ) {
				$caption = \preg_replace( '/<figure\b/S', '<figure itemscope itemtype="http://schema.org/ImageObject"', $caption );
			}

			// Inject schema.org markup for figcaption.
			if ( ! \preg_match( '/<figcaption[^>]*\bitemprop\s*=\b/S', $caption ) ) {
				$caption = \preg_replace( '/<figcaption\b/S', '<figcaption itemprop="caption"', $caption );
			}
		}

		return $caption;
	}

	/**
	 * Adds shortcode for media credit. Allows for credit to be specified for media attached to a post
	 * by either specifying the ID of a WordPress user or with a raw string for the name assigned credit.
	 * If an ID is present, it will take precedence over a name.
	 *
	 * Usage: `[media-credit id=1 align="center" width="300"]` or
	 *        `[media-credit name="Another User" align="center" width="300"]`
	 *
	 * @param array  $atts {
	 *     An array of shortcode attributes.
	 *
	 *     @type int    $id         Optional. A user ID. Default 0.
	 *     @type string $name       Optional. The (freeform) credit to display. Default ''.
	 *     @type string $link       Optional. A URL used for linking the credit.
	 *     @type bool   $standalone Optional. A flag indicating that the shortcode
	 *                              was used without an enclosing `[caption]`. Default true.
	 *     @type string $align      Optional. The alignment to use for the image/figure
	 *                              (if used without `[caption]`). Default 'none'.
	 *     @type int    $width      Optional. The width of the image/figure. Default 0.
	 *     @type bool   $no_follow  Optional. A flag indicating that a `rel=nofollow`
	 *                              attribute should be added to the link tag.
	 * }
	 * @param string $content Optional. Shortcode content. Default null.
	 *
	 * @return string         The HTML markup for the media credit.
	 */
	public function media_credit_shortcode( $atts, $content = null ) {
		// Disable shortcode if credits should be shown after the post content.
		if ( ! empty( $this->settings['credit_at_end'] ) ) {
			return \do_shortcode( $content );
		}

		// Make sure that $atts really is an array, might be an empty string in some edge cases.
		$atts = $this->sanitize_attributes( empty( $atts ) ? [] : $atts );

		/**
		 * Filters the `[media-credit]` shortcode to allow plugins and themes to
		 * override the default media credit template.
		 *
		 * If the returned string is non-empty, it will be used as the markup for
		 * the media credit.
		 *
		 * @param string $markup  The media credit markup. Default ''.
		 * @param array  $atts {
		 *     The `[media-credit]` shortcode attributes.
		 *
		 *     @type int    $id         Optional. A user ID. Default 0.
		 *     @type string $name       Optional. The (freeform) credit to display. Default ''.
		 *     @type string $link       Optional. A URL used for linking the credit.
		 *     @type bool   $standalone Optional. A flag indicating that the shortcode
		 *                              was used without an enclosing `[caption]`. Default true.
		 *     @type string $align      Optional. The alignment to use for the image/figure
		 *                              (if used without `[caption]`). Default 'none'.
		 *     @type int    $width      Optional. The width of the image/figure. Default 0.
		 *     @type bool   $no_follow  Optional. A flag indicating that a `rel=nofollow`
		 *                              attribute should be added to the link tag.
		 * }
		 * @param string $content The image element, possibly wrapped in a hyperlink.
		 *                        Should be integrated into the returned `$markup`.
		 */
		$output = \apply_filters( 'media_credit_shortcode', '', $atts, $content );
		if ( '' !== $output ) {
			return $output;
		}

		// Check for HTML5 support.
		$html5 = \current_theme_supports( 'html5', 'caption' );

		// Calculate default width in pixels for the media credit.
		$width = $atts['width'] + ( $html5 ? 0 : 10 );

		/**
		 * Filters the width of an image's credit/caption.
		 *
		 * We could use a media-credit specific filter, but we want to be compatible
		 * with existing themes.
		 *
		 * By default, the caption is 10 pixels greater than the width of the image,
		 * to prevent post content from running up against a floated image.
		 *
		 * @see img_caption_shortcode()
		 *
		 * @param int    $caption_width Width of the caption in pixels. To remove this inline style,
		 *                              return zero.
		 * @param array  $atts          Attributes of the media-credit shortcode.
		 * @param string $content       The image element, possibly wrapped in a hyperlink.
		 */
		$width = \apply_filters( 'img_caption_shortcode_width', $width, $atts, $content ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

		// Prepare media content (nested shortcodes).
		$content = \do_shortcode( $content );

		// Start buffering.
		\ob_start();

		// Require partial.
		require \dirname( MEDIA_CREDIT_PLUGIN_FILE ) . '/public/partials/media-credit-shortcode.php';

		// Retrieve buffer.
		return \ob_get_clean();
	}

	/**
	 * Renders inline part of the shortcode (prepared for output).
	 *
	 * @param array $attr {
	 *     The `[media-credit]` shortcode attributes.
	 *
	 *     @type int    $id         Optional. A user ID. Default 0.
	 *     @type string $name       Optional. The (freeform) credit to display. Default ''.
	 *     @type string $link       Optional. A URL used for linking the credit.
	 *     @type bool   $standalone Optional. A flag indicating that the shortcode
	 *                              was used without an enclosing `[caption]`. Default true.
	 *     @type string $align      Optional. The alignment to use for the image/figure
	 *                              (if used without `[caption]`). Default 'none'.
	 *     @type int    $width      Optional. The width of the image/figure. Default 0.
	 *     @type bool   $no_follow  Optional. A flag indicating that a `rel=nofollow`
	 *                              attribute should be added to the link tag.
	 * }
	 * @param bool  $include_schema_org Optional. Include schema.org markup. Default false.
	 *
	 * @return string
	 */
	protected function inline_media_credit( array $attr, $include_schema_org = false ) {

		// The default credit and link.
		$credit        = $attr['name'];
		$credit_suffix = '';
		$url           = $attr['link'];

		// If present, use the user ID.
		if ( $attr['id'] > 0 ) {
			$credit        = \get_the_author_meta( 'display_name', $attr['id'] );
			$credit_suffix = $this->core->get_organization_suffix();
			$url           = $url ?: \get_author_posts_url( $attr['id'] );
		}

		// Construct the credit line.
		$credit_line = \esc_html( $credit );
		if ( $url ) {
			$credit_line = '<a href="' . \esc_url( $url ) . '"' . ( ! empty( $attr['nofollow'] ) ? ' rel="nofollow"' : '' ) . '>' . $credit_line . '</a>';
		}
		$credit_line .= \esc_html( $credit_suffix );

		// Wrap the credit in a container <span>.
		$markup = $this->core->wrap_media_credit_markup( $credit_line, $include_schema_org );

		/**
		 * Filters the inline markup used for the credit line part of the `media-credit` shortcode.
		 *
		 * @since 4.0.0
		 *
		 * @param string $markup The inline part of the shortcode markup.
		 * @param array $attr {
		 *     The `[media-credit]` shortcode attributes.
		 *
		 *     @type int    $id         Optional. A user ID. Default 0.
		 *     @type string $name       Optional. The (freeform) credit to display. Default ''.
		 *     @type string $link       Optional. A URL used for linking the credit.
		 *     @type bool   $standalone Optional. A flag indicating that the shortcode
		 *                              was used without an enclosing `[caption]`. Default true.
		 *     @type string $align      Optional. The alignment to use for the image/figure
		 *                              (if used without `[caption]`). Default 'none'.
		 *     @type int    $width      Optional. The width of the image/figure. Default 0.
		 *     @type bool   $no_follow  Optional. A flag indicating that a `rel=nofollow`
		 *                              attribute should be added to the link tag.
		 * }
		 * @param bool  $include_schema_org Optional. Include schema.org markup. Default false.
		 */
		return \apply_filters( 'media_credit_shortcode_inline_markup', $markup, $attr, $include_schema_org );
	}

	/**
	 * Ensures all required attributes are present and sanitized.
	 *
	 * @param array $atts {
	 *     The `[media-credit]` shortcode attributes.
	 *
	 *     @type int    $id         Optional. A user ID. Default 0.
	 *     @type string $name       Optional. The (freeform) credit to display. Default ''.
	 *     @type string $link       Optional. A URL used for linking the credit.
	 *     @type bool   $standalone Optional. A flag indicating that the shortcode
	 *                              was used without an enclosing `[caption]`. Default true.
	 *     @type string $align      Optional. The alignment to use for the image/figure
	 *                              (if used without `[caption]`). Default 'none'.
	 *     @type int    $width      Optional. The width of the image/figure. Default 0.
	 *     @type bool   $no_follow  Optional. A flag indicating that a `rel=nofollow`
	 *                              attribute should be added to the link tag.
	 * }
	 *
	 * @return array
	 */
	protected function sanitize_attributes( array $atts ) {
		// Merge default shortcode attributes.
		$atts = \shortcode_atts( self::MEDIA_CREDIT_DEFAULTS, $atts, 'media-credit' );

		// Sanitize attribute values.
		$atts['id']         = \absint( $atts['id'] );
		$atts['name']       = \sanitize_text_field( $atts['name'] );
		$atts['link']       = \esc_url_raw( $atts['link'] );
		$atts['standalone'] = \filter_var( $atts['standalone'], FILTER_VALIDATE_BOOLEAN );
		$atts['align']      = \sanitize_html_class( $atts['align'] );
		$atts['width']      = \absint( $atts['width'] );
		$atts['nofollow']   = \filter_var( $atts['nofollow'], FILTER_VALIDATE_BOOLEAN );

		// Strip 'align' prefix from legacy alignment values.
		if ( 'align' === \substr( $atts['align'], 0, 5 ) ) {
			$atts['align'] = \substr_replace( $atts['align'], '', 0, 5 );
		}

		return $atts;
	}
}
