<?php
/**
 * This file is part of Media Credit.
 *
 * Copyright 2019-2023 Peter Putzer.
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
 * @since 4.2.0 The method `get_image_filename_from_full_url` has been replaced
 *              by `get_quoted_image_basename`.
 *
 * @author Peter Putzer <github@mundschenk.at>
 *
 * @phpstan-import-type ShortcodeAttributesOptional from \Media_Credit\Components\Shortcodes
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

		// Get the image basename without the size for use in a regular expression.
		$basename = $this->get_quoted_image_basename( $image_id, '/' );
		if ( empty( $basename ) ) {
			// Invalid image ID.
			return $content;
		}

		// Look at every matching shortcode.
		\preg_match_all( '/' . \get_shortcode_regex( [ 'media-credit' ] ) . '/Ss', $content, $matches, PREG_SET_ORDER );

		foreach ( $matches as $shortcode ) {
			// Grab the contained <img> tag and check if it is the right one.
			$img = $shortcode[5];
			if ( ! \preg_match( "/src=([\"'])(?:(?!\1).)*{$basename}/S", $img ) || false === \strpos( $img, "wp-image-{$image_id}" ) ) {
				// This shortcode is for another image.
				continue;
			}

			/**
			 * Replace the old shortcode with then new one. We have to treat
			 * all attributes in the old shortcode as optional.
			 *
			 * @phpstan-var ShortcodeAttributesOptional $attr
			 */
			$attr    = $this->parse_shortcode_attributes( $shortcode[3] );
			$updated = [
				'id'       => $author_id,
				'name'     => $freeform,
				'link'     => $url,
				'nofollow' => $nofollow,
			];
			$content = $this->update_shortcode( $content, $shortcode[0], $img, $attr, $updated );
		}

		return $content;
	}

	/**
	 * Updates the shortcode using new data.
	 *
	 * @since  4.2.0
	 * @since  4.3.0 Fixed documentation of the `$link` attribute
	 *               (previously incorrectly documented as `$url`).
	 *
	 * @param  string $content    The current post content.
	 * @param  string $shortcode  The shortcode to update.
	 * @param  string $img        The contained `<img>` tag.
	 * @param  array  $attr {
	 *     The parsed shortcode attributes. All attributes are optional. Additional
	 *     attributes not listed here will be preserved in the output.
	 *
	 *     @type int    $id       The author ID.
	 *     @type string $name     The freeform credit.
	 *     @type string $link     The credit URL.
	 *     @type bool   $nofollow The "rel=nofollow" flag.
	 * }
	 * @param  array  $updated {
	 *     The shortcode attributes to update. All attributes are mandatory.
	 *
	 *     @type int    $id       The author ID.
	 *     @type string $name     The freeform credit.
	 *     @type string $link     The credit URL.
	 *     @type bool   $nofollow The "rel=nofollow" flag.
	 * }
	 *
	 * @return string             The updated post content.
	 *
	 * @phpstan-param ShortcodeAttributesOptional $attr
	 * @phpstan-param array{ id: int, name: string, link: string, nofollow: bool } $updated
	 */
	protected function update_shortcode( string $content, string $shortcode, string $img, array $attr, array $updated ) {
		// Drop the old id/name attributes (if any).
		unset( $attr['id'] );
		unset( $attr['name'] );

		// Prefer author ID if present & valid.
		$id_or_name = $updated['id'] > 0 ? "id={$updated['id']}" : "name=\"{$updated['name']}\"";

		// Update link attribute.
		if ( ! empty( $updated['link'] ) ) {
			$attr['link'] = $updated['link'];
		} else {
			unset( $attr['link'] );
		}

		// Update nofollow attribute.
		if ( ! empty( $updated['link'] ) && ! empty( $updated['nofollow'] ) ) {
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
	 * @return string[]
	 */
	public function parse_shortcode_attributes( string $attributes ) {
		$attr = \shortcode_parse_atts( $attributes );
		if ( ! \is_array( $attr ) ) {
			// Workaround for messed up WP Core syntax.
			// See https://core.trac.wordpress.org/ticket/23307 for details.
			$attr = [];
		}

		return $attr;
	}

	/**
	 * Retrieves the image basename (without file extension and size suffix) for
	 * the given image ID and runs it through `preg_quote`.
	 *
	 * Examples:
	 * Given http://localhost/wordpress/wp-content/uploads/2010/08/ParksTrip2010_100706_1487-150x150.jpg, returns ParksTrip2010_100706_1487 (ignores size at end of string)
	 * Given http://localhost/wordpress/wp-content/uploads/2010/08/ParksTrip2010_100706_1487-thumb.jpg, return ParksTrip2010_100706_1487\-thumb
	 * Given http://localhost/wordpress/wp-content/uploads/2010/08/ParksTrip2010_100706_1487-1.jpg, return ParksTrip2010_100706_1487\-1
	 *
	 * @since  4.2.0
	 *
	 * @param  int    $image_id  The attachment ID.
	 * @param  string $delimiter Optional. The regex delimiter. Default '/'.
	 *
	 * @return string|false      The quoted image basename or false in case of error.
	 */
	protected function get_quoted_image_basename( int $image_id, string $delimiter = '/' ) {
		// Get the image source URL.
		$src = \wp_get_attachment_image_src( $image_id );
		if ( ! empty( $src ) && ! empty( $src[0] ) ) {
			$result = \preg_replace( '/(.*?)(\-\d+x\d+)?\.\w+/S', '$1', \wp_basename( $src[0] ) );
			if ( ! empty( $result ) ) {
				return \preg_quote( $result, $delimiter );
			}
		}

		return false;
	}
}
