<?php

/**
 * This file is part of Media Credit.
 *
 * Copyright 2013-2015 Peter Putzer.
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
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    3.0.0
	 */
	public function enqueue_styles() {

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

		$options = get_option( self::OPTION );
		if ( ! empty( $options['credit_at_end'] ) ) { // Do not display inline media credit if media credit is displayed at end of posts.
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

		//wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/media-credit-public.js', array( 'jquery' ), $this->version, false );

	}



	/**
	 * Modified caption shortcode.
	 *
	 * Fixes the new style caption shortcode parsing and then calls the stock
	 * shortcode function.
	 *
	 * @param array $attr Attributes attributed to the shortcode.
	 * @param string $content Optional. Shortcode content.
	 * @return string
	 */
	public function caption_shortcode( $attr, $content = null ) {
		// New-style shortcode with the caption inside the shortcode with the link and image tags.
		if ( ! isset( $attr['caption'] ) ) {
			if ( preg_match( '#((?:\[media-credit[^\]]+\]\s*)(?:<a [^>]+>\s*)?<img [^>]+>(?:\s*</a>)?(?:\s*\[/media-credit\])?)(.*)#is', $content, $matches ) ) {
				$content = $matches[1];
				$attr['caption'] = trim( $matches[2] );
			}
		}

		return img_caption_shortcode( $attr, $content );
	}

	/**
	 * New way (in core consideration) to fix the caption shortcode parsing. Proof of concept at this point.
	 *
	 * @param array $matches
	 * @param string $content
	 * @param string $regex
	 */
// 	function img_caption_shortcode_content($matches, $content, $regex) {
// 		$result = array();

// 		if ( preg_match( '#((?:\[media-credit[^\]]+\]\s*)(?:<a [^>]+>\s*)?<img [^>]+>(?:\s*</a>)?(?:\s*\[/media-credit\])?)(.*)#is', $content, $result ) )
// 			return $result;
// 			else
// 				return $matches;
// 	}
	//add_filter('img_caption_shortcode_content', array( $this, 'img_caption_shortcode_content' ), 10, 3);

	/**
	 * Add shortcode for media credit. Allows for credit to be specified for media attached to a post
	 * by either specifying the ID of a WordPress user or with a raw string for the name assigned credit.
	 * If an ID is present, it will take precedence over a name.
	 *
	 * Usage: [media-credit id=1 align="aligncenter" width="300"] or [media-credit name="Another User" align="aligncenter" width="300"]
	 */
	function media_credit_shortcode( $atts, $content = null ) {
		// Allow plugins/themes to override the default media credit template.
		$output = apply_filters( 'media_credit_shortcode', '', $atts, $content );
		if ( $output != '' ) {
			return $output;
		}

		$options = get_option( self::OPTION );

		if ( ! empty( $options['credit_at_end'] ) ) {
			return do_shortcode( $content );
		}

		$atts = shortcode_atts(	array( 'id'    => -1,
				   					   'name'  => '',
									   'link'  => '',
									   'align' => 'alignnone',
									   'width' => '' ),	$atts, 'media-credit' );

		if ( -1 !== $atts['id'] ) {
			$url = empty( $link ) ? get_author_posts_url( $atts['id'] ) : $atts['link'];
			$credit_wp_author = get_the_author_meta( 'display_name', $atts['id'] );
			$options = get_option( self::OPTION );
			$author_link = '<a href="' . esc_url( $url ) . '">' . $credit_wp_author . '</a>' . $options['separator'] .
			$options['organization'];
		} else {
			if ( ! empty( $atts['link'] ) ) {
				$author_link = '<a href="' . esc_attr( $atts['link'] ) . '">' . $atts['name'] . '</a>';
			} else {
				$author_link = $atts['name'];
			}
		}

		$credit_width = (int) $atts['width'] + current_theme_supports( 'html5', 'caption' ) ? 0 : 10;

		/**
		 * Filter the width of an image's credit/caption.
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

		$style = '';
		if ( $credit_width ) {
			$style = ' style="width: ' . (int) $credit_width . 'px"';
		}

		return '<div class="media-credit-container ' . esc_attr( $atts['align'] ) . '"' . $style . '>' .
			   do_shortcode( $content ) . '<span class="media-credit">' . $author_link . '</span></div>';
	}

	/**
	 * Adds image credits to the end of a post.
	 *
	 * @param string $content The post content.
	 * @return string The filtered post content.
	 */
	public function add_media_credits_to_end( $content ) {
		// Find the attachment_IDs of all media used in $content
		preg_match_all( '/' . self::WP_IMAGE_CLASS_NAME_PREFIX . '(\d+)/', $content, $matches );
		$images = $matches[1];

		if ( 0 === count( $images ) ) {
			return $content; // no images found
		}

		// Look at "no default credits" option
		$options = get_option( self::OPTION );
		$include_default_credit = empty( $options['no_default_credit'] );

		$credit_unique = array();
		foreach ( $images as $image ) {
			$credit = Media_Credit_Template_Tags::get_media_credit_html( $image, $include_default_credit );

			if ( ! empty( $credit ) ) {
				$credit_unique[] = $credit;
			}
		}
		$credit_unique = array_unique( $credit_unique );

		// If no images are left, don't display credit line
		if ( 0 === count( $credit_unique ) ) {
			return $content;
		}

		$image_credit = _nx( 'Image courtesy of %1$s', 'Images courtesy of %2$s and %1$s', count( $credit_unique ),
			'%1$s is always the position of the last credit, %2$s of the concatenated other credits', 'media-credit' );

		$last_credit = array_pop( $credit_unique );
		$other_credits = implode( _x( ', ', 'String used to join multiple image credits for "Display credit after post"', 'media-credit'), $credit_unique );

		$image_credit = sprintf( $image_credit, $last_credit, $other_credits );

		// restore credit array for filter
		$credit_unique[] = $last_credit;

		/**
		 * Filter hook to modify the end credits.
		 *
		 * @param $value - default end credit mark-up
		 * @param $content - the original content
		 * @param $credit_unique - a unique array of media credits for the post.
		 */
		return apply_filters( 'media_credit_at_end', $content . '<div class="media-credit-end">' . $image_credit . '</div>', $content, $credit_unique );
	}
}
