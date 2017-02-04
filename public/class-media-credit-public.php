<?php
/**
 * This file is part of Media Credit.
 *
 * Copyright 2013-2017 Peter Putzer.
 * Copyright 2010-2011 Scott Bressler.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License,
 * version 2 as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 *
 * @link       https://mundschenk.at
 * @since      3.0.0
 *
 * @package    Media_Credit
 * @subpackage Media_Credit/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Media_Credit
 * @subpackage Media_Credit/public
 * @author     Peter Putzer <github@mundschenk.at>
 */
class Media_Credit_Public implements Media_Credit_Base {

	/**
	 * The ID of this plugin.
	 *
	 * @since    3.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    3.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    3.0.0
	 *
	 * @param string $plugin_name The name of the plugin.
	 * @param string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    3.0.0
	 */
	public function enqueue_styles() {
		$options = get_option( self::OPTION );

		// Do not display inline media credit if media credit is displayed at end of posts.
		if ( ! empty( $options['credit_at_end'] ) ) {
			wp_enqueue_style( 'media-credit-end', plugin_dir_url( __FILE__ ) . 'css/media-credit-end.css', array(), $this->version, 'all' );
		} else {
			wp_enqueue_style( 'media-credit', plugin_dir_url( __FILE__ ) . 'css/media-credit.css', array(), $this->version, 'all' );
		}
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    3.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Media_Credit_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Media_Credit_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		/* wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/media-credit-public.js', array( 'jquery' ), $this->version, false ); */
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
		// New-style shortcode with the caption inside the shortcode with the link and image tags.
		if ( ! isset( $attr['caption'] ) ) {
			if ( preg_match( '#((?:\[media-credit[^\]]+\]\s*)(?:<a [^>]+>\s*)?<img [^>]+>(?:\s*</a>)?(?:\s*\[/media-credit\])?)(.*)#is', $content, $matches ) ) {
				$content = $matches[1];
				$attr['caption'] = trim( $matches[2] );

				// Add attribute "standalone=0" to [media-credit] shortcode if present.
				$content = preg_replace( '#\[media-credit([^]]+)\]#', '[media-credit standalone=0$1]', $content );
			}
		}

		// Get caption markup.
		$caption = img_caption_shortcode( $attr, $content );

		// Optionally add schema.org markup.
		$options = get_option( self::OPTION );
		if ( ! empty( $options['schema_org_markup'] ) && empty( $options['credit_at_end'] ) ) {
			// Inject schema.org markup for figure.
			if ( ! preg_match( '/<figure[^>]*\bitemscope\b/', $caption ) ) {
				$caption = preg_replace( '/<figure\b/', '<figure itemscope itemtype="http://schema.org/ImageObject"', $caption );
			}

			// Inject schema.org markup for figcaption.
			if ( ! preg_match( '/<figcaption[^>]*\bitemprop\s*=\b/', $caption ) ) {
				$caption = preg_replace( '/<figcaption\b/', '<figcaption itemprop="caption"', $caption );
			}
		}

		return $caption;
	}

	/**
	 * New way (in core consideration) to fix the caption shortcode parsing. Proof of concept at this point.
	 * add_filter('img_caption_shortcode_content', array( $this, 'img_caption_shortcode_content' ), 10, 3);
	 *
	 * @param array  $matches An array of regex matches.
	 * @param string $content The matched content.
	 * @param string $regex   The regex.
	 *
	 * @return array
	 */
	function img_caption_shortcode_content( $matches, $content, $regex ) {
		$result = array();

		if ( preg_match( '#((?:\[media-credit[^\]]+\]\s*)(?:<a [^>]+>\s*)?<img [^>]+>(?:\s*</a>)?(?:\s*\[/media-credit\])?)(.*)#is', $content, $result ) ) {
			return $result;
		} else {
			return $matches;
		}
	}

	/**
	 * Adds shortcode for media credit. Allows for credit to be specified for media attached to a post
	 * by either specifying the ID of a WordPress user or with a raw string for the name assigned credit.
	 * If an ID is present, it will take precedence over a name.
	 *
	 * Usage: [media-credit id=1 align="aligncenter" width="300"] or [media-credit name="Another User" align="aligncenter" width="300"]
	 *
	 * @param array  $atts    Shortcode attributes.
	 * @param string $content Content enclosed by the shortcode. Optional. Default null.
	 *
	 * @return string
	 */
	function media_credit_shortcode( $atts, $content = null ) {

		// Allow plugins/themes to override the default media credit template.
		/**
		 * Replaces the `[media-credit]` shortcode with custom markup.
		 *
		 * If the returned string is non-empty, it will be used as the markup for
		 * the media credit.
		 *
		 * @param string $markup  The media credit markup. Default ''.
		 * @param array  $atts    The `[media-credit]` shortcode attributes.
		 * @param string $content The image element, possibly wrapped in a hyperlink.
		 *                        Should be integrated into the returned `$markup`.
		 */
		$output = apply_filters( 'media_credit_shortcode', '', $atts, $content );
		if ( '' !== $output ) {
			return $output;
		}

		$options = get_option( self::OPTION );

		if ( ! empty( $options['credit_at_end'] ) ) {
			return do_shortcode( $content );
		}

		$atts = shortcode_atts(	array(
			'id'         => -1,
			'name'       => '',
			'link'       => '',
			'standalone' => 'true',
			'align'      => 'alignnone',
			'width'      => '',
			'nofollow'   => '',
		),	$atts, 'media-credit' );

		$atts['standalone'] = filter_var( $atts['standalone'], FILTER_VALIDATE_BOOLEAN );
		$atts['nofollow']   = filter_var( $atts['nofollow'], FILTER_VALIDATE_BOOLEAN );

		if ( -1 !== $atts['id'] ) {
			$url              = empty( $link ) ? get_author_posts_url( $atts['id'] ) : $atts['link'];
			$credit_wp_author = get_the_author_meta( 'display_name', $atts['id'] );
			$author_link      = '<a href="' . esc_url( $url ) . '">' . $credit_wp_author . '</a>' . $options['separator'] . $options['organization'];
		} else {
			if ( ! empty( $atts['link'] ) ) {
				$nofollow = ! empty( $atts['nofollow'] ) ? ' rel="nofollow"' : '';
				$author_link = '<a href="' . esc_attr( $atts['link'] ) . '"' . $nofollow . '>' . $atts['name'] . '</a>';
			} else {
				$author_link = $atts['name'];
			}
		}

		$html5_enabled = current_theme_supports( 'html5', 'caption' );
		$credit_width  = (int) $atts['width'] + ( $html5_enabled ? 0 : 10 );

		/**
		 * Filters the width of an image's credit/caption.
		 * We could use a media-credit specific filter, but we don't to be more compatible
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
		$credit_width = apply_filters( 'img_caption_shortcode_width', $credit_width, $atts, $content );

		// Apply credit width via style attribute.
		$style = '';
		if ( $credit_width ) {
			$style = ' style="width: ' . (int) $credit_width . 'px"';
		}

		// Prepare media content.
		$content = do_shortcode( $content );

		// Optional schema.org markup.
		$schema_org        = '';
		$figure_schema_org = '';
		if ( ! empty( $options['schema_org_markup'] ) && empty( $options['credit_at_end'] ) ) {
			$schema_org        = ' itemprop="copyrightHolder"';
			$figure_schema_org = ' itemscope itemtype="http://schema.org/ImageObject"';

			if ( ! preg_match( '/\bitemprop\s*=/', $content ) ) {
				$content = preg_replace( '/<img\b/', '<img itemprop="contentUrl"', $content );
			}
		}

		$output = '<div class="media-credit-container ' . esc_attr( $atts['align'] ) . '"' . $style . '>' .
				      $content . '<span class="media-credit"' . $schema_org . '>' . $author_link . '</span></div>';

		// Wrap output in <figure> if HTML5 is supported & the shortcode is a standalone one.
		if ( ! empty( $atts['standalone'] ) && $html5_enabled ) {
			$output = '<figure class="wp-caption ' . esc_attr( $atts['align'] ) . '"' . $style . $figure_schema_org . '>' .
					      $output .
					  '</figure>';
		}

		return $output;
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

		// Check if we're inside the main loop in a single post page.
		if ( ! is_single() || ! in_the_loop() || ! is_main_query() ) {
			return $content; // abort.
		}

		// Look at the plugin options.
		$options                = get_option( self::OPTION );
		$include_default_credit = empty( $options['no_default_credit'] );
		$include_post_thumbnail = ! empty( $options['post_thumbnail_credit'] );

		// Find the attachment_IDs of all media used in $content.
		if ( ! preg_match_all( '/' . self::WP_IMAGE_CLASS_NAME_PREFIX . '(\d+)/', $content, $images ) && ! $include_post_thumbnail ) {
			return $content; // no images found.
		}

		// Get a list of credits for the page.
		$credit_unique = array();
		foreach ( $images[1] as $image_id ) {
			$credit = Media_Credit_Template_Tags::get_media_credit_html( $image_id, $include_default_credit );

			if ( ! empty( $credit ) ) {
				$credit_unique[] = $credit;
			}
		}

		// Optionally include post thumbnail credit.
		if ( $include_post_thumbnail ) {
			$post_thumbnail_id = get_post_thumbnail_id();

			if ( '' != $post_thumbnail_id ) {
				$credit = Media_Credit_Template_Tags::get_media_credit_html( $post_thumbnail_id, $include_default_credit );

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
		 * @param string $markup        The generated end credit mark-up.
		 * @param string $content       The original content before the end credits were added.
		 * @param arrray $credit_unique An array of unique media credits contained in the current post.
		 */
		return apply_filters( 'media_credit_at_end', $content . '<div class="media-credit-end">' . $image_credit . '</div>', $content, $credit_unique );
	}

	/**
	 * Adds media credit to post thumbnails (in the loop).
	 *
	 * @param string       $html              The post thumbnail HTML.
	 * @param int          $post_id           The post ID.
	 * @param string 	   $post_thumbnail_id The post thumbnail ID.
	 * @param string|array $size              The post thumbnail size. Image size or array of width and height values (in that order). Default 'post-thumbnail'.
	 * @param string|array $attr              Query string or array of attributes. Default ''.
	 */
	public function add_media_credit_to_post_thumbnail( $html, $post_id, $post_thumbnail_id, $size, $attr ) {
		if ( ! in_the_loop() ) {
			return $html; // abort.
		}

		// Allow plugins/themes to override the default media credit template.
		/**
		 * Replaces the post thumbnail media credits with custom markup. If the returned
		 * string is non-empty, it will be used as the post thumbnail markup.
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
		$options                 = get_option( self::OPTION );
		$include_default_credits = empty( $options['no_default_credit'] );

		// Return early if credits are displayed at end.
		if ( ! empty( $options['credit_at_end'] ) ) {
			return $html; // abort.
		}

		/**
		 * Filters whether link tags should be included in the post thumbnail credit. By default, both custom
		 * and default links are disabled because post thumbnails are often wrapped in `<a></a>`.
		 *
		 * @since 3.1.5
		 *
		 * @param bool $include_links     Default false.
		 * @param int  $post_id           The post ID.
		 * @param int  $post_thumbnail_id The post thumbnail's attachment ID.
		 */
		if ( apply_filters( 'media_credit_post_thumbnail_include_links', false, $post_id, $post_thumbnail_id ) ) {
			$credit = Media_Credit_Template_Tags::get_media_credit_html( $post_thumbnail_id, $include_default_credits );
		} elseif ( $include_default_credits ) {
			$credit = Media_Credit_Template_Tags::get_media_credit( $post_thumbnail_id, true );
		} else {
			$credit = Media_Credit_Template_Tags::get_freeform_media_credit( $post_thumbnail_id );
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
