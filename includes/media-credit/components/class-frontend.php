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
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @since 3.0.0
 * @since 3.3.0 Shortcodes moved to Media_Credit\Components\Shortcodes class.
 */
class Frontend implements \Media_Credit\Base, \Media_Credit\Component {

	/**
	 * The version of this plugin.
	 *
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

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
	 * Initialize the class and set its properties.
	 *
	 * @param string  $version The version of this plugin.
	 * @param Options $options The options handler.
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
		// Retrieve plugin settings.
		$this->settings = $this->options->get( Options::OPTION, [] );

		// Enqueue frontend styles.
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_styles' ] );

		// Optional credits after the main content.
		if ( ! empty( $this->settings['credit_at_end'] ) ) {
			add_filter( 'the_content', [ $this, 'add_media_credits_to_end' ], 10, 1 );
		}

		// Post thumbnail credits.
		if ( ! empty( $this->settings['post_thumbnail_credit'] ) ) {
			add_filter( 'post_thumbnail_html', [ $this, 'add_media_credit_to_post_thumbnail' ], 10, 3 );
		}
	}

	/**
	 * Registers the stylesheets for the public-facing side of the site.
	 */
	public function enqueue_styles() {
		// Set up file suffix.
		$suffix = SCRIPT_DEBUG ? '' : '.min';
		$url    = \plugin_dir_url( MEDIA_CREDIT_PLUGIN_FILE );

		// Do not display inline media credit if media credit is displayed at end of posts.
		if ( ! empty( $this->settings['credit_at_end'] ) ) {
			\wp_enqueue_style( 'media-credit-end', "{$url}public/css/media-credit-end{$suffix}.css", [], $this->version, 'all' );
		} else {
			\wp_enqueue_style( 'media-credit', "{$url}public/css/media-credit{$suffix}.css", [], $this->version, 'all' );
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
		if ( ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
			return $content; // abort.
		}

		// Look at the plugin options.
		$include_default_credit = empty( $this->settings['no_default_credit'] );
		$include_post_thumbnail = ! empty( $this->settings['post_thumbnail_credit'] );

		// Find the attachment_IDs of all media used in $content.
		if ( ! preg_match_all( '/' . self::WP_IMAGE_CLASS_NAME_PREFIX . '(\d+)/', $content, $images ) && ! $include_post_thumbnail ) {
			return $content; // no images found.
		}

		// Get a list of credits for the page.
		$credit_unique = [];
		foreach ( $images[1] as $image_id ) {
			$credit = Template_Tags::get_media_credit_html( $image_id, $include_default_credit );

			if ( ! empty( $credit ) ) {
				$credit_unique[] = $credit;
			}
		}

		// Optionally include post thumbnail credit.
		if ( $include_post_thumbnail ) {
			$post_thumbnail_id = get_post_thumbnail_id();

			if ( '' !== $post_thumbnail_id ) {
				$credit = Template_Tags::get_media_credit_html( (int) $post_thumbnail_id, $include_default_credit );

				if ( ! empty( $credit ) ) {
					array_unshift( $credit_unique, $credit );
				}
			}
		}

		// Make credit list unique.
		$credit_unique = array_unique( $credit_unique );

		// If no images are left, don't display credit line.
		if ( empty( $credit_unique ) ) {
			return $content;
		}

		// Prepare credit line string.
		/* translators: 1: last credit 2: concatenated other credits (empty in singular) */
		$image_credit = _n(
			'Image courtesy of %2$s%1$s', // %2$s will be empty
			'Images courtesy of %2$s and %1$s',
			count( $credit_unique ),
			'media-credit'
		);

		// Construct actual credit line from list of unique credits.
		$last_credit   = array_pop( $credit_unique );
		$other_credits = implode( _x( ', ', 'String used to join multiple image credits for "Display credit after post"', 'media-credit' ), $credit_unique );
		$image_credit  = sprintf( $image_credit, $last_credit, $other_credits );

		// Restore credit array for filter.
		$credit_unique[] = $last_credit;

		/**
		 * Filters the credits at the end of a post.
		 *
		 * @param string   $markup        The generated end credit mark-up.
		 * @param string   $content       The original content before the end credits were added.
		 * @param string[] $credit_unique An array of unique media credits contained in the current post.
		 */
		return apply_filters( 'media_credit_at_end', $content . '<div class="media-credit-end">' . $image_credit . '</div>', $content, $credit_unique );
	}

	/**
	 * Adds media credit to post thumbnails (in the loop).
	 *
	 * @param string $html              The post thumbnail HTML.
	 * @param int    $post_id           The post ID.
	 * @param int    $post_thumbnail_id The post thumbnail ID.
	 */
	public function add_media_credit_to_post_thumbnail( $html, $post_id, $post_thumbnail_id ) {
		// Return early if we are not in the main loop or credits are to displayed at end of posts.
		if ( ! in_the_loop() || ! empty( $this->settings['credit_at_end'] ) ) {
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
		$output = apply_filters( 'media_credit_post_thumbnail', '', $html, $post_id, $post_thumbnail_id );
		if ( '' !== $output ) {
			return $output;
		}

		// Look at our options.
		$include_default_credits = empty( $this->settings['no_default_credit'] );

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
		if ( apply_filters( 'media_credit_post_thumbnail_include_links', false, $post_id, $post_thumbnail_id ) ) {
			$credit = Template_Tags::get_media_credit_html( $post_thumbnail_id, $include_default_credits );
		} elseif ( $include_default_credits ) {
			$credit = Template_Tags::get_media_credit( $post_thumbnail_id, true );
		} else {
			$credit = Template_Tags::get_freeform_media_credit( $post_thumbnail_id );
		}

		// Don't print the default credit.
		if ( empty( $credit ) ) {
			return $html;
		}

		// Extract image width.
		if ( preg_match( "/<img[^>]+width=([\"'])([0-9]+)\\1/", $html, $match ) ) {
			$credit_width = $match[2];
		}

		// Set optional style attribute.
		$style = '';
		if ( ! empty( $credit_width ) ) {
			$style = ' style="width: ' . (int) $credit_width . 'px"';
		}

		// Return styled credit mark-up.
		return $html . '<span class="media-credit"' . $style . '>' . $credit . '</span>';
	}
}
