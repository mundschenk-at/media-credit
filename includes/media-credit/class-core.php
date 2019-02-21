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

namespace Media_Credit;

use Media_Credit\Data_Storage\Options;

/**
 * The main API for the Media Credit plugin. To allow for static template functions,
 * it is instantiated as a singleton.
 *
 * The class provides access to the plugin settings and utility methods for manipulating
 * the postmeta data making up the credit information for individual attachments.
 *
 * @since 3.3.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class Core {

	/**
	 * The singleton instance.
	 *
	 * @var Core
	 */
	private static $instance;

	/**
	 * The plugin version.
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
	 * The default settings.
	 *
	 * @var Settings
	 */
	private $settings_template;

	/**
	 * Creates a new instance.
	 *
	 * @param string   $version           The plugin version string (e.g. "3.0.0-beta.2").
	 * @param Options  $options           The options handler.
	 * @param Settings $settings_template The default settings template.
	 */
	public function __construct( $version, Options $options, Settings $settings_template ) {
		$this->version           = $version;
		$this->options           = $options;
		$this->settings_template = $settings_template;
	}

	/**
	 * Sets this API instance as the plugin singleton. Should not be called outside of plugin set-up.
	 *
	 * @internal
	 *
	 * @throws \BadMethodCallException Thrown when Media_Credit\Core::make_singleton is called after plugin initialization.
	 */
	public function make_singleton() {
		if ( null !== self::$instance ) {
			throw new \BadMethodCallException( __METHOD__ . ' called more than once.' );
		}

		self::$instance = $this;
	}

	/**
	 * Retrieves the plugin API instance.
	 *
	 * @throws \BadMethodCallException Thrown when Media_Credit\Core::get_instance is called before plugin initialization.
	 *
	 * @return Core
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			throw new \BadMethodCallException( __METHOD__ . ' called without prior plugin intialization.' );
		}

		return self::$instance;
	}

	/**
	 * Retrieves the plugin version.
	 *
	 * @var string
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * If the given media is attached to a post, edit the media-credit info in the attached (parent) post.
	 *
	 * @param int|\WP_Post $post     Object of attachment containing all fields from get_post().
	 * @param string       $freeform Credit for attachment with freeform string. Empty if attachment should be credited to the attachment author.
	 * @param string       $url      Credit URL for linking. Empty means default link for user of this blog, no link for freeform credit.
	 */
	public function update_media_credit_in_post( $post, $freeform = '', $url = '' ) {
		if ( is_int( $post ) ) {
			$post = get_post( $post, ARRAY_A );
		}

		if ( ! empty( $post['post_parent'] ) ) {
			$parent                 = get_post( $post['post_parent'], ARRAY_A );
			$parent['post_content'] = $this->filter_changed_media_credits( $parent['post_content'], $post['ID'], $post['post_author'], $freeform, $url );

			wp_update_post( $parent );
		}
	}

	/**
	 * Filters post content for changed media credits.
	 *
	 * @param string $content   The current post content.
	 * @param int    $image_id  The attachment ID.
	 * @param int    $author_id The author ID.
	 * @param string $freeform  The freeform credit.
	 * @param string $url       The credit URL. Optional. Default ''.
	 *
	 * @return string           The filtered post content.
	 */
	public function filter_changed_media_credits( $content, $image_id, $author_id, $freeform, $url = '' ) {
		preg_match_all( '/' . get_shortcode_regex() . '/s', $content, $matches, PREG_SET_ORDER );

		if ( ! empty( $matches ) ) {
			foreach ( $matches as $shortcode ) {
				if ( 'media-credit' === $shortcode[2] ) {
					$img              = $shortcode[5];
					$image_attributes = wp_get_attachment_image_src( $image_id );
					$image_filename   = $this->get_image_filename_from_full_url( $image_attributes[0] );

					// Ensure that $attr is an array.
					$attr = shortcode_parse_atts( $shortcode[3] );
					$attr = '' === $attr ? [] : $attr;

					if ( preg_match( '/src=".*' . $image_filename . '/', $img ) && preg_match( '/wp-image-' . $image_id . '/', $img ) ) {
						if ( $author_id > 0 ) {
							$attr['id'] = $author_id;
							unset( $attr['name'] );
						} else {
							$attr['name'] = $freeform;
							unset( $attr['id'] );
						}

						if ( ! empty( $url ) ) {
							$attr['link'] = $url;
						} else {
							unset( $attr['link'] );
						}

						$new_shortcode = '[media-credit';
						if ( isset( $attr['id'] ) ) {
							$new_shortcode .= ' id=' . $attr['id'];
							unset( $attr['id'] );
						} elseif ( isset( $attr['name'] ) ) {
							$new_shortcode .= ' name="' . $attr['name'] . '"';
							unset( $attr['name'] );
						}
						foreach ( $attr as $name => $value ) {
							$new_shortcode .= ' ' . $name . '="' . $value . '"';
						}
						$new_shortcode .= ']' . $img . '[/media-credit]';

						$content = str_replace( $shortcode[0], $new_shortcode, $content );
					}
				} elseif ( ! empty( $shortcode[5] ) && has_shortcode( $shortcode[5], 'media-credit' ) ) {
					$content = str_replace( $shortcode[5], $this->filter_changed_media_credits( $shortcode[5], $image_id, $author_id, $freeform, $url ), $content );
				}
			}
		}

		return $content;
	}

	/**
	 * Returns the filename of an image in the wp_content directory (normally, could be any dir really) given the full URL to the image, ignoring WP sizes.
	 * E.g.:
	 * Given http://localhost/wordpress/wp-content/uploads/2010/08/ParksTrip2010_100706_1487-150x150.jpg, returns ParksTrip2010_100706_1487 (ignores size at end of string)
	 * Given http://localhost/wordpress/wp-content/uploads/2010/08/ParksTrip2010_100706_1487-thumb.jpg, return ParksTrip2010_100706_1487-thumb
	 * Given http://localhost/wordpress/wp-content/uploads/2010/08/ParksTrip2010_100706_1487-1.jpg, return ParksTrip2010_100706_1487-1
	 *
	 * @param  string $image Full URL to an image.
	 * @return string        The filename of the image excluding any size or extension, as given in the example above.
	 */
	protected function get_image_filename_from_full_url( $image ) {
		// Drop "-{$width}x{$height}".
		return \preg_replace( '/(.*?)(\-\d+x\d+)?\.\w+/S', '$1', \wp_basename( $image ) );
	}
}
