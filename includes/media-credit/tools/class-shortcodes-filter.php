<?php
/**
 * This file is part of Media Credit.
 *
 * Copyright 2019-2021 Peter Putzer.
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

namespace Media_Credit\Tools;

/**
 * A utility class for updating shortcode inside existing content.
 *
 * @since 4.0.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class Shortcodes_Filter {

	/**
	 * Filters post content for changed media credits.
	 *
	 * @param string $content   The current post content.
	 * @param int    $image_id  The attachment ID.
	 * @param int    $author_id The author ID.
	 * @param string $freeform  The freeform credit.
	 * @param string $url       The credit URL.
	 * @param bool   $nofollow  The "rel=nofollow" flag.
	 *
	 * @return string           The filtered post content.
	 */
	public function update_changed_media_credits( $content, $image_id, $author_id, $freeform, $url, $nofollow ) {

		// Get the image source URL.
		$src = \wp_get_attachment_image_src( $image_id );
		if ( false === $src || empty( $src[0] ) ) {
			// Invalid image ID.
			return $content;
		}

		// Extract the image basename without the size for use in a regular expression.
		$filename = \preg_quote( $this->get_image_filename_from_full_url( $src[0] ), '/' );

		// Look at every matching shortcode.
		\preg_match_all( '/' . \get_shortcode_regex( [ 'media-credit' ] ) . '/Ss', $content, $matches, PREG_SET_ORDER );

		foreach ( $matches as $shortcode ) {
			// Grab the contained <img> tag and check if it is the right one.
			$img = $shortcode[5];
			if ( ! \preg_match( "/src=([\"'])(?:(?!\1).)*{$filename}/S", $img ) || ! \preg_match( "/wp-image-{$image_id}/S", $img ) ) {
				// This shortcode is for another image.
				continue;
			}

			// Replace the old shortcode with then new one.
			$content = $this->update_shortcode( $content, $shortcode[0], $this->parse_shortcode_attributes( $shortcode[3] ), $img, $author_id, $freeform, $url, $nofollow );
		}

		return $content;
	}

	/**
	 * Updates the shortcode using new data.
	 *
	 * @since  4.2.0
	 *
	 * @param  string $content    The current post content.
	 * @param  string $shortcode  The shortcode to update.
	 * @param  array  $attr {
	 *     The parsed shortcode attributes. All attributes are optional.
	 *
	 *     @type int    $id       The author ID.
	 *     @type string $name     The freeform credit.
	 *     @type string $url      The credit URL.
	 *     @type bool   $nofollow The "rel=nofollow" flag.
	 * }
	 * @param  string $img        The contained `<img>` tag.
	 * @param  int    $author_id  The new author ID.
	 * @param  string $freeform   The new freeform credit.
	 * @param  string $url        The new credit URL.
	 * @param  bool   $nofollow   The new "rel=nofollow" flag.
	 *
	 * @return string             The updated post content.
	 */
	protected function update_shortcode( string $content, string $shortcode, array $attr, string $img, int $author_id, string $freeform, string $url, bool $nofollow ) {

		// Drop the old id/name attributes (if any).
		unset( $attr['id'] );
		unset( $attr['name'] );

		// Prefer author ID if present & valid.
		$id_or_name = $author_id > 0 ? "id={$author_id}" : "name=\"{$freeform}\"";

		// Update link attribute.
		if ( ! empty( $url ) ) {
			$attr['link'] = $url;
		} else {
			unset( $attr['link'] );
		}

		// Update nofollow attribute.
		if ( ! empty( $url ) && ! empty( $nofollow ) ) {
			$attr['nofollow'] = true;
		} else {
			unset( $attr['nofollow'] );
		}

		// Start reconstructing the shortcode.
		$new_shortcode = "[media-credit {$id_or_name}";

		// Add the rest of the attributes.
		foreach ( $attr as $name => $value ) {
			$new_shortcode .= " {$name}=\"{$value}\"";
		}

		// Finish up with the closing bracket and the <img> content.
		$new_shortcode .= "]{$img}[/media-credit]";

		return \str_replace( $shortcode, $new_shortcode, $content );
	}

	/**
	 * Parses the shortcode attributes from a string. Always returns an array,
	 * unlike the WordPress Core function `shortcode_parse_atts`.
	 *
	 * @since  4.2.0
	 *
	 * @param  string $attributes The attributes matching the shortcode regex.
	 *
	 * @return array
	 */
	protected function parse_shortcode_attributes( string $attributes ) {
		$attr = \shortcode_parse_atts( $attributes );
		if ( ! \is_array( $attr ) ) {
			// Workaround for messed up WP Core syntax.
			// See https://core.trac.wordpress.org/ticket/23307 for details.
			$attr = [];
		}

		return $attr;
	}

	/**
	 * Returns the filename of an image in the wp_content directory (normally, could be any dir really) given the full URL to the image, ignoring WP sizes.
	 *
	 * Examples:
	 * Given http://localhost/wordpress/wp-content/uploads/2010/08/ParksTrip2010_100706_1487-150x150.jpg, returns ParksTrip2010_100706_1487 (ignores size at end of string)
	 * Given http://localhost/wordpress/wp-content/uploads/2010/08/ParksTrip2010_100706_1487-thumb.jpg, return ParksTrip2010_100706_1487-thumb
	 * Given http://localhost/wordpress/wp-content/uploads/2010/08/ParksTrip2010_100706_1487-1.jpg, return ParksTrip2010_100706_1487-1
	 *
	 * @param  string $image Full URL to an image.
	 * @return string        The filename of the image excluding any size or extension, as given in the example above.
	 */
	protected function get_image_filename_from_full_url( $image ) {
		// Drop "-{$width}x{$height}".
		return (string) \preg_replace( '/(.*?)(\-\d+x\d+)?\.\w+/S', '$1', \wp_basename( $image ) );
	}
}
