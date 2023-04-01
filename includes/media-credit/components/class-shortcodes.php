<?php
/**
 * This file is part of Media Credit.
 *
 * Copyright 2013-2023 Peter Putzer.
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

use Media_Credit\Tools\Shortcodes_Filter;
use Media_Credit\Tools\Template;

/**
 * The component providing the `[media-credit]` shortcode and patching `[caption]`
 * and `[wp_caption]`.
 *
 * @since 4.0.0
 *
 * @phpstan-type ShortcodeAttributes array{id:int, name:string, link:string, standalone:bool, align:string, width:int, nofollow:bool}
 * @phpstan-type ShortcodeAttributesOptional array{id?:int|string, name?:string, link?:string, standalone?:bool|string, align?:string, width?:int|string, nofollow?:bool|string}
 */
class Shortcodes implements \Media_Credit\Component {

	/**
	 * The default shortcode attributes for `[media-credit]`.
	 *
	 * @var array
	 *
	 * @phpstan-var ShortcodeAttributes
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
	 * The plugin settings API.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * The core API.
	 *
	 * @var Core
	 */
	private $core;

	/**
	 * The shortcodes filter helper.
	 *
	 * @var Shortcodes_Filter
	 */
	private $filter;

	/**
	 * The template handler.
	 *
	 * @since 4.2.0
	 *
	 * @var Template
	 */
	private $template;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 4.2.0 Unused parameter $options removed, new parameters $settings,
	 *              $template, and $filter added.
	 *
	 * @param Core              $core     The core plugin API.
	 * @param Settings          $settings The plugin settings API.
	 * @param Template          $template The template handler.
	 * @param Shortcodes_Filter $filter   The shortcodes filter helper.
	 */
	public function __construct( Core $core, Settings $settings, Template $template, Shortcodes_Filter $filter ) {
		$this->core     = $core;
		$this->settings = $settings;
		$this->template = $template;
		$this->filter   = $filter;
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
	 *
	 * @return void
	 */
	public function add_shortcodes() {
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
	 * @param  array  $attr    The `[caption]` shortcode attributes.
	 * @param  string $content Optional. Shortcode content. Default null.
	 *
	 * @return string The enriched caption markup.
	 *
	 * @phpstan-param array{ caption: string } $attr
	 */
	public function caption_shortcode( $attr, $content = null ) {
		// Options influencing the markup.
		$schema_org = ! empty( $this->settings->get( Settings::SCHEMA_ORG_MARKUP ) );

		// New-style shortcode with the caption inside the shortcode with the link and image tags.
		if (
			// Only handle new-style captions ...
			! isset( $attr['caption'] ) &&
			// ... but only if the shortcode content is not empty ...
			! empty( $content ) &&
			// ... and only if it contain a media credit.
			\preg_match( '#((?:\[media-credit[^\]]+\]\s*)(?:<a [^>]+>\s*)?<img [^>]+>(?:\s*</a>)?(?:\s*\[/media-credit\])?)(.*)#Sis', $content, $matches )
		) {
			$content         = $matches[1];
			$attr['caption'] = \trim( $matches[2] );

			if ( ! \current_theme_supports( 'html5', 'caption' ) ) {
				// Add attribute "standalone=0" to [media-credit] shortcode if present.
				$content = \preg_replace( '#\[media-credit([^]]+)\]#S', '[media-credit standalone=0$1]', $content );
			} elseif ( \preg_match( '#\[media-credit([^]]+)\]#S', $content, $matches ) ) {
				// Use improved HTML5 mode, i.e. remove shortcode from normal
				// source order flow and inject it into <figcaption> instead.
				$content = \str_replace( [ $matches[0], '[/media-credit]' ], '', $content );

				if ( empty( $this->settings->get( Settings::CREDIT_AT_END ) ) ) {

					// The byline.
					$credit_attr = $this->sanitize_attributes( $this->filter->parse_shortcode_attributes( $matches[1] ) );
					$credit      = $this->inline_media_credit( $credit_attr, $schema_org );

					// The original caption.
					$caption = $attr['caption'];

					/**
					 * Filters the HTML5 caption including the credit byline.
					 *
					 * @since 4.0.0
					 * @since 4.3.0 Fixed documentation for the `$nofollow` attribute
					 *              (previously incorrectly documented as `$no_follow`).
					 *
					 * @param string $caption     The caption including the credit.
					 *                            Default caption text followed by
					 *                            credit with a seprating space.
					 * @param string $old_caption The caption text.
					 * @param string $credit      The credit byline (including markup).
					 * @param array  $attr {
					 *     The sanitized `[media-credit]` shortcode attributes.
					 *
					 *     @type int    $id         A user ID (0 is used to indicate `$name`
					 *                              should take precedence).
					 *     @type string $name       The (freeform) credit to display.
					 *     @type string $link       A URL used for linking the credit (or '').
					 *     @type bool   $standalone A flag indicating that the shortcode was used
					 *                              without an enclosing `[caption]`.
					 *     @type string $align      The alignment to use for the image/figure (if
					 *                              used without `[caption]`).
					 *     @type int    $width      The width of the image/figure in pixels.
					 *     @type bool   $nofollow   A flag indicating that a `rel=nofollow`
					 *                              attribute should be added to the link tag (if
					 *                              `$url` is not empty).
					 * }
					 */
					$attr['caption'] = \apply_filters( 'media_credit_shortcode_html5_caption', "{$caption} {$credit}", $caption, $credit, $credit_attr );
				}
			}
		}

		// Get caption markup.
		$caption = \img_caption_shortcode( $attr, (string) $content );

		// Optionally add schema.org markup.
		if ( $schema_org ) {
			$caption = $this->core->maybe_add_schema_org_markup_to_figure( $caption );
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
	 * @since  4.3.0 Fixed documentation for the `$nofollow` attribute
	 *               (previously incorrectly documented as `$no_follow`).
	 *
	 * @param  array  $atts {
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
	 *     @type bool   $nofollow   Optional. A flag indicating that a `rel=nofollow`
	 *                              attribute should be added to the link tag.
	 * }
	 * @param  string $content Optional. Shortcode content. Default null.
	 *
	 * @return string          The HTML markup for the media credit.
	 *
	 * @phpstan-param ShortcodeAttributesOptional|string $atts
	 */
	public function media_credit_shortcode( $atts, $content = null ) {
		// Make sure that content is a string.
		$content = $content ?? '';

		if ( ! empty( $this->settings->get( Settings::CREDIT_AT_END ) ) ) {
			// Disable shortcode if credits should be shown after the post content.
			return \apply_shortcodes( $content );
		}

		// Make sure that $atts really is an array, might be an empty string in some edge cases.
		$atts = $this->sanitize_attributes( ! \is_array( $atts ) ? [] : $atts );

		/**
		 * Filters the `[media-credit]` shortcode to allow plugins and themes to
		 * override the default media credit template.
		 *
		 * If the returned string is non-empty, it will be used as the markup for
		 * the media credit.
		 *
		 * @since  4.3.0 Fixed documentation for the `$nofollow` attribute
		 *               (previously incorrectly documented as `$no_follow`).
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
		 *     @type bool   $nofollow   Optional. A flag indicating that a `rel=nofollow`
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

		// Required template variables.
		$args = [
			'content'             => \apply_shortcodes( $content ), // expand nested shortcodes.
			'html5'               => $html5,
			'schema_org'          => ! empty( $this->settings->get( Settings::SCHEMA_ORG_MARKUP ) ),
			'width'               => $width,
			'inline_media_credit' => function( array $attr, $include_schema_org = false ) {
				/**
				 * Workaround for PHPStan not parsing anoynous function PHPDocs.
				 *
				 * @phpstan-var ShortcodeAttributes $attr
				 */
				return $this->inline_media_credit( $attr, $include_schema_org ); // @codeCoverageIgnore
			},
			'atts'                => $atts,
		];

		return $this->template->get_partial( '/public/partials/media-credit-shortcode.php', $args );
	}

	/**
	 * Renders inline part of the shortcode (prepared for output).
	 *
	 * @param  array $attr {
	 *     The sanitized `[media-credit]` shortcode attributes (@see `Shortcodes::sanitize_attributes`).
	 *
	 *     @type int    $id         A user ID (0 is used to indicate $name should
	 *                              take precedence).
	 *     @type string $name       The (freeform) credit to display.
	 *     @type string $link       A URL used for linking the credit (or '').
	 *     @type bool   $standalone A flag indicating that the shortcode was used
	 *                              without an enclosing `[caption]`.
	 *     @type string $align      The alignment to use for the image/figure (if
	 *                              used without `[caption]`).
	 *     @type int    $width      The width of the image/figure in pixels.
	 *     @type bool   $nofollow   A flag indicating that a `rel=nofollow`
	 *                              attribute should be added to the link tag (if
	 *                              `$url` is not empty).
	 * }
	 * @param  bool  $include_schema_org Optional. Include schema.org markup. Default false.
	 *
	 * @return string
	 *
	 * @phpstan-param ShortcodeAttributes $attr
	 */
	protected function inline_media_credit( array $attr, $include_schema_org = false ) {
		// Prepare arguments for compatibility with old shortcode behavior (`id` trumps `naem`).
		$user_id  = $attr['id'];
		$freeform = $user_id > 0 ? '' : $attr['name'];
		$url      = $attr['link'];
		$flags    = [
			'nofollow' => $attr['nofollow'],
		];

		// Render shortcode.
		$credit = $this->core->render_media_credit_html( $user_id, $freeform, $url, $flags );

		// Finally, let's wrap up everything in a container <span>.
		$markup = $this->core->wrap_media_credit_markup( $credit, $include_schema_org );

		/**
		 * Filters the inline markup used for the credit line part of the `media-credit` shortcode.
		 *
		 * @since 4.0.0
		 * @since 4.3.0 Fixed documentation for the `$nofollow` attribute
		 *              (previously incorrectly documented as `$no_follow`).
		 *
		 * @param string $markup The inline part of the shortcode markup.
		 * @param array $attr {
		 *     The sanitized `[media-credit]` shortcode attributes.
		 *
		 *     @type int    $id         A user ID (0 is used to indicate `$name`
		 *                              should take precedence).
		 *     @type string $name       The (freeform) credit to display.
		 *     @type string $link       A URL used for linking the credit (or '').
		 *     @type bool   $standalone A flag indicating that the shortcode was used
		 *                              without an enclosing `[caption]`.
		 *     @type string $align      The alignment to use for the image/figure (if
		 *                              used without `[caption]`).
		 *     @type int    $width      The width of the image/figure in pixels.
		 *     @type bool   $nofollow   A flag indicating that a `rel=nofollow`
		 *                              attribute should be added to the link tag (if
		 *                              `$url` is not empty).
		 * }
		 * @param bool  $include_schema_org Optional. Include schema.org markup. Default false.
		 */
		return \apply_filters( 'media_credit_shortcode_inline_markup', $markup, $attr, $include_schema_org );
	}

	/**
	 * Ensures all required attributes are present and sanitized. Strings are converted to
	 * the parameters canonical type if necessary.
	 *
	 * @since 4.3.0 Fixed documentation for the `$nofollow` attribute
	 *              (previously incorrectly documented as `$no_follow`).
	 *
	 * @param  array $atts {
	 *     The `[media-credit]` shortcode attributes.
	 *
	 *     @type int    $id         Optional. A user ID. Default 0.
	 *     @type string $name       Optional. The (freeform) credit to display. Default ''.
	 *     @type string $link       Optional. A URL used for linking the credit. Default ''.
	 *     @type bool   $standalone Optional. A flag indicating that the shortcode
	 *                              was used without an enclosing `[caption]`. Default true.
	 *     @type string $align      Optional. The alignment to use for the image/figure
	 *                              (if used without `[caption]`). Default 'none'.
	 *     @type int    $width      Optional. The width of the image/figure. Default 0.
	 *     @type bool   $nofollow   Optional. A flag indicating that a `rel=nofollow`
	 *                              attribute should be added to the link tag. Default false.
	 * }
	 *
	 * @return array
	 *
	 * @phpstan-param  ShortcodeAttributesOptional $atts
	 * @phpstan-return ShortcodeAttributes
	 */
	protected function sanitize_attributes( array $atts ) {
		// Merge default shortcode attributes.
		$atts = \shortcode_atts( self::MEDIA_CREDIT_DEFAULTS, $atts, 'media-credit' );

		// Sanitize attribute values.
		$atts['id']         = \absint( $atts['id'] );
		$atts['name']       = \sanitize_text_field( $atts['name'] );
		$atts['link']       = \sanitize_url( $atts['link'] );
		$atts['standalone'] = \filter_var( $atts['standalone'], \FILTER_VALIDATE_BOOLEAN );
		$atts['align']      = \sanitize_html_class( $atts['align'] );
		$atts['width']      = \absint( $atts['width'] );
		$atts['nofollow']   = \filter_var( $atts['nofollow'], \FILTER_VALIDATE_BOOLEAN );

		// Strip 'align' prefix from legacy alignment values.
		if ( 'align' === \substr( $atts['align'], 0, 5 ) ) {
			$atts['align'] = \substr_replace( $atts['align'], '', 0, 5 );
		}

		return $atts;
	}
}
