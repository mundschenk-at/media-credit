<?php
/**
 * This file is part of Media Credit.
 *
 * Copyright 2019 Peter Putzer.
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
		if ( empty( $src[0] ) ) {
			// Invalid image ID.
			return $content;
		}

		// Extract the image basename without the size for use in a regular expression.
		$filename = \preg_quote( $this->get_image_filename_from_full_url( $src[0] ), '/' );

		// Look at every matching shortcode.
		\preg_match_all( '/' . \get_shortcode_regex( [ 'media-credit' ] ) . '/Ss', $content, $matches, PREG_SET_ORDER );

		foreach ( $matches as $shortcode ) {

			// Grab the shortcode attributes ...
			$attr = \shortcode_parse_atts( $shortcode[3] );
			$attr = $attr ?: [];

			// ... and the contained <img> tag.
			$img = $shortcode[5];

			if ( ! \preg_match( "/src=([\"'])(?:(?!\1).)*{$filename}/S", $img ) || ! \preg_match( "/wp-image-{$image_id}/S", $img ) ) {
				// This shortcode is for another image.
				continue;
			}

			// Check for credit type.
			if ( $author_id > 0 ) {
				// The new credit should use the ID.
				$id_or_name = "id={$author_id}";
			} else {
				// No valid ID, so use the freeform credit.
				$id_or_name = "name=\"{$freeform}\"";
			}

			// Drop the old id/name attributes (if any).
			unset( $attr['id'] );
			unset( $attr['name'] );

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
			$new_shortcode .= ']' . $img . '[/media-credit]';

			// Replace the old shortcode with then new one.
			$content = \str_replace( $shortcode[0], $new_shortcode, $content );
		}

		return $content;
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
		return \preg_replace( '/(.*?)(\-\d+x\d+)?\.\w+/S', '$1', \wp_basename( $image ) );
	}
}
