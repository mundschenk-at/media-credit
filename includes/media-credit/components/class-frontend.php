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
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @since 3.0.0
 * @since 4.0.0 Shortcodes moved to Media_Credit\Components\Shortcodes class.
 */
class Frontend implements \Media_Credit\Component {

	/**
	 * The prefix used for image CSS classes generated by WordPress.
	 *
	 * @var string
	 */
	const WP_IMAGE_CLASS_NAME_PREFIX = 'wp-image-';

	/**
	 * The prefix used for attachment CSS classes generated by WordPress.
	 *
	 * @var string
	 */
	const WP_ATTACHMENT_CLASS_NAME_PREFIX = 'attachment_';

	/**
	 * A regular expression to collect all image IDs in the post content.
	 *
	 * @since 4.2.0
	 *
	 * @var string
	 */
	const IMAGE_ID_REGEX = '/' . self::WP_IMAGE_CLASS_NAME_PREFIX . '(\d+)/';

	/**
	 * The core API.
	 *
	 * @var Core
	 */
	private $core;

	/**
	 * The plugin settings API.
	 *
	 * @since 4.2.0 The property is now of type Media_Credit\Settings.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 4.2.0 Parameter $version removed, parameter $settings added.
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
		// Enqueue frontend styles.
		\add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_styles' ] );

		// Optional credits after the main content.
		if ( ! empty( $this->settings->get( Settings::CREDIT_AT_END ) ) ) {
			\add_filter( 'the_content', [ $this, 'add_media_credits_to_end' ], 10, 1 );
		} elseif ( ! empty( $this->settings->get( Settings::FEATURED_IMAGE_CREDIT ) ) ) {
			// Featured Image credits are only added "inline".
			\add_filter( 'post_thumbnail_html', [ $this, 'add_media_credit_to_post_thumbnail' ], 10, 3 );
		}
	}

	/**
	 * Registers the stylesheets for the public-facing side of the site.
	 *
	 * @return void
	 */
	public function enqueue_styles() {
		// Set up file suffix.
		$suffix  = ( defined( 'SCRIPT_DEBUG' ) && \SCRIPT_DEBUG ) ? '' : '.min';
		$url     = \plugin_dir_url( \MEDIA_CREDIT_PLUGIN_FILE );
		$version = $this->settings->get_version();

		// Do not display inline media credit if media credit is displayed at end of posts.
		if ( ! empty( $this->settings->get( Settings::CREDIT_AT_END ) ) ) {
			\wp_enqueue_style( 'media-credit-end', "{$url}public/css/media-credit-end{$suffix}.css", [], $version, 'all' );
		} else {
			\wp_enqueue_style( 'media-credit', "{$url}public/css/media-credit{$suffix}.css", [], $version, 'all' );
		}
	}

	/**
	 * Adds image credits to the end of a post.
	 *
	 * @since 3.1.5 The function checks if it's in the main loop in a single post page.
	 *              If credits for featured images are enabled, they will also show up here.
	 *
	 * @param string $content The post content.
	 *
	 * @return string The post content with the credit line added.
	 */
	public function add_media_credits_to_end( $content ) {

		// Check if we're inside the main loop in a single post/page/CPT.
		if ( ! \is_singular() || ! \in_the_loop() || ! \is_main_query() ) {
			return $content; // abort.
		}

		// Get a list of (unique) credits contained in the post (if a featured
		// image has been set, it will be first in the list).
		$credits = $this->get_unique_image_credits( $content );

		// Don't display the credit line if there are no images.
		if ( empty( $credits ) ) {
			return $content;
		}

		/**
		 * Filters whether to use a shorter label (e.g. 'Images:' instead of
		 * 'Images courtesy of').
		 *
		 * @param string $short_form Default false.
		 */
		$use_short_label = \apply_filters( 'media_credit_at_end_use_short_label', false );

		// Prepare credit line strings.
		$credit_count = \count( $credits );
		if ( $use_short_label ) {
			/* translators: 1: last credit 2: concatenated other credits (empty in singular) */
			$image_credit = \_n(
				'Image: %2$s%1$s', // %2$s will be empty
				'Images: %2$s and %1$s',
				$credit_count,
				'media-credit'
			);
		} else {
			/* translators: 1: last credit 2: concatenated other credits (empty in singular) */
			$image_credit = \_n(
				'Image courtesy of %2$s%1$s', // %2$s will be empty
				'Images courtesy of %2$s and %1$s',
				$credit_count,
				'media-credit'
			);
		}

		// Construct actual credit line from list of unique credits.
		$last_credit   = \array_pop( $credits );
		$other_credits = \implode( \_x( ', ', 'String used to join multiple image credits for "Display credit after post"', 'media-credit' ), $credits );
		$image_credit  = \sprintf( $image_credit, $last_credit, $other_credits );

		// Restore credit array for filter.
		$credits[] = $last_credit;

		/**
		 * Filters the credits at the end of a post.
		 *
		 * @since 4.2.0 Parameter $credit_unique renamed to $credits.
		 *
		 * @param string   $markup  The generated end credit mark-up.
		 * @param string   $content The original content before the end credits were added.
		 * @param string[] $credits An array of unique media credits contained in the current post.
		 */
		return \apply_filters( 'media_credit_at_end', $content . '<div class="media-credit-end">' . $image_credit . '</div>', $content, $credits );
	}

	/**
	 * Retrieves the image credits for the current post.
	 *
	 * @since  4.2.0
	 *
	 * @param  string $content The post content.
	 *
	 * @return string[]
	 */
	protected function get_unique_image_credits( $content ) {
		// Get a list of credits for the page.
		$credits = [];
		foreach ( $this->get_image_ids( $content ) as $image_id ) {
			$attachment = \get_post( $image_id );
			if ( empty( $attachment ) ) {
				continue;
			}

			$credit = $this->core->get_media_credit_json( $attachment );
			if ( ! empty( $credit['rendered'] ) ) {
				$credits[] = $credit['rendered'];
			}
		}

		// Make credit list unique and re-index numerically.
		return \array_values( \array_unique( $credits ) );
	}

	/**
	 * Retrieves the attachment IDs for all images in the current post (including
	 * the featured image).
	 *
	 * @since  4.2.0
	 *
	 * @param  string       $content The post content.
	 * @param  int|\WP_Post $post    Optional. Post ID or WP_Post object. Defaults
	 *                               to the global `$post`.
	 *
	 * @return int[]
	 */
	protected function get_image_ids( $content, $post = null ) {
		$image_ids = [];

		// Optionally include featured image credit.
		if ( ! empty( $this->settings->get( Settings::FEATURED_IMAGE_CREDIT ) ) ) {
			$featured_image_id = \get_post_thumbnail_id( $post );

			if ( ! empty( $featured_image_id ) ) {
				$image_ids[] = $featured_image_id;
			}
		}

		// Find the attachment IDs of all media used in $content.
		\preg_match_all( self::IMAGE_ID_REGEX, $content, $images );
		foreach ( $images[1] as $image_id_string ) {
			$image_ids[] = (int) $image_id_string;
		}

		return $image_ids;
	}

	/**
	 * Adds media credit to post thumbnails (in the loop).
	 *
	 * @param  string $html              The post thumbnail HTML.
	 * @param  int    $post_id           The post ID.
	 * @param  int    $post_thumbnail_id The post thumbnail ID.
	 *
	 * @return string
	 */
	public function add_media_credit_to_post_thumbnail( $html, $post_id, $post_thumbnail_id ) {
		// Return early if we are not in the main loop.
		if ( ! \in_the_loop() ) {
			return $html;
		}

		/**
		 * Replaces the post thumbnail media credits with custom markup. If the returned
		 * string is non-empty, it will be used as the post thumbnail media credit markup.
		 *
		 * @param string $content           The generated markup. Default ''.
		 * @param string $html              The post thumbnail `<img>` markup. Should be integrated in the returned `$content`.
		 * @param int    $post_id           The current post ID.
		 * @param int    $post_thumbnail_id The attachment ID of the post thumbnail.
		 */
		$output = \apply_filters( 'media_credit_post_thumbnail', '', $html, $post_id, $post_thumbnail_id );
		if ( '' !== $output ) {
			return $output;
		}

		$credit = $this->get_featured_image_credit( $post_id, $post_thumbnail_id );
		if ( ! empty( $credit ) ) {
			// Add styled & wrapped credit markup.
			$html .= $this->core->wrap_media_credit_markup( $credit, false, $this->get_featured_image_credit_style( $html ) );
		}

		return $html;
	}

	/**
	 * Retrieves the credit for a featured image.
	 *
	 * @since  4.2.0
	 *
	 * @param  int $post_id           The current post ID.
	 * @param  int $post_thumbnail_id The attachment ID of the post thumbnail.
	 *
	 * @return string                 The featured image credit (or '').
	 */
	protected function get_featured_image_credit( $post_id, $post_thumbnail_id ) {
		// Retrieve the featured image.
		$attachment = \get_post( $post_thumbnail_id );
		if ( empty( $attachment ) ) {
			// Abort if the ID does not correspond to a valid attachment.
			return '';
		}

		// Load the media credit fields.
		$fields = $this->core->get_media_credit_json( $attachment );
		if ( empty( $fields['rendered'] ) || empty( $fields['fancy'] ) ) {
			// There was an error retrieving the credit (or the credit was empty).
			return '';
		}

		/**
		 * Filters whether link tags should be included in the post thumbnail credit.
		 * By default, both custom and default links are disabled because post
		 * thumbnails are often wrapped in `<a></a>`.
		 *
		 * @since 3.1.5
		 *
		 * @param bool $include_links     Default false.
		 * @param int  $post_id           The post ID.
		 * @param int  $post_thumbnail_id The post thumbnail's attachment ID.
		 */
		$include_links = \apply_filters( 'media_credit_post_thumbnail_include_links', false, $post_id, $post_thumbnail_id );

		// Return either the full "rendered" credit including posible links, or
		// the "fancy" plain text one without any markup.
		return $include_links ? $fields['rendered'] : \esc_html( $fields['fancy'] );
	}

	/**
	 * Generates a style attribute suitable for direct inclusion the featured
	 * image credit markup.
	 *
	 * @since  4.2.0
	 *
	 * @param  string $html The post thumbnail HTML.
	 *
	 * @return string
	 */
	protected function get_featured_image_credit_style( $html ) {
		// Extract image width.
		if ( \preg_match( "/<img[^>]+width=([\"'])([0-9]+)\\1/", $html, $match ) ) {
			$image_width = $match[2];
		}

		if ( empty( $image_width ) ) {
			return '';
		}

		return ' style="max-width: ' . (int) $image_width . 'px"';
	}
}
