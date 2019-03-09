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

use Media_Credit\Core;

/**
 * A collection of static methods making up the Media Credit template tags API.
 *
 * While the legacy global functions-based API will continue to work for now, they
 * have been collectively deprecated and will be removed in the next major version.
 * The new API reduces the global namespace pollution and allows autoloading.
 *
 * @since 3.0.0
 * @since 4.0.0 Renamed to Media_Credit
 */
class Media_Credit {

	/**
	 * Returns the media credit as plain text for some media attachment.
	 *
	 * @param  int|\WP_Post $attachment An attachment ID or the corresponding \WP_Post object.
	 * @param  bool         $fancy      Optional. Fancy output (<user> <separator> <organization>)
	 *                                  for local user credits. Optional. Default false.
	 *
	 * @return string                   The media credit in plaintext format.
	 */
	public static function get_plaintext( $attachment, $fancy = false ) {

		// Get all the media credit fields.
		$credit = self::get_fields( $attachment );

		// We want the credit string in plain text format (but possibly "fancy").
		return $fancy ? $credit['fancy'] : $credit['plaintext'];
	}

	/**
	 * Outputs the media credit as plain text for some media attachment.
	 *
	 * @param  int|\WP_Post $attachment An attachment ID or the corresponding \WP_Post object.
	 * @param  bool         $fancy      Optional. Fancy output (<user> <separator> <organization>)
	 *                                  for local user credits. Optional. Default false.
	 */
	public static function plaintext( $attachment, $fancy = false ) {
		echo \esc_html( self::get_plaintext( $attachment, $fancy ) );
	}

	/**
	 * Returns the media credit as HTML with a link to the author page if one exists for some media attachment.
	 *
	 * @param  int|\WP_Post $attachment An attachment ID or the corresponding \WP_Post object.
	 *
	 * @return string                   The media credit HTML (or the empty string if no credit is set).
	 */
	public static function get_html( $attachment ) {

		// Get all the media credit fields.
		$credit = self::get_fields( $attachment );

		return $credit['rendered'];
	}

	/**
	 * Outputs the media credit as HTML with a link to the author page if one exists for some media attachment.
	 *
	 * @param  int|\WP_Post $attachment An attachment ID or the corresponding \WP_Post object.
	 */
	public static function html( $attachment ) {
		echo self::get_html( $attachment );  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- data is safe for output.
	}

	/**
	 * Returns the freeform media credit for a given post/attachment.
	 *
	 * Preferably, you should use the `Media_Credit::get_plaintext` or
	 * `Media_Credit::get_html``methods instead.
	 *
	 * @param int|\WP_Post $attachment An attachment ID or the corresponding \WP_Post object.
	 *
	 * @return string                  The freeform credit (or the empty string).
	 */
	public static function get_freeform( $attachment ) {

		// Get all the media credit fields.
		$credit = self::get_fields( $attachment );

		// Don't display our special "empty" string.
		if ( Core::EMPTY_META_STRING === $credit['raw']['freeform'] ) {
			return '';
		}

		return $credit['raw']['freeform'];
	}

	/**
	 * Returns the media credit URL as plain text for some media attachment.
	 *
	 * Preferably, you should use the `Media_Credit::get_plaintext` or
	 * `Media_Credit::get_html``methods instead.
	 *
	 * @param int|\WP_Post $attachment An attachment ID or the corresponding \WP_Post object.
	 *
	 * @return string                  The credit URL (or the empty string if none is set).
	 */
	public static function get_url( $attachment ) {

		// Get all the media credit fields.
		$credit = self::get_fields( $attachment );

		return $credit['raw']['url'];
	}

	/**
	 * Returns the optional media credit flags for a media attachment.
	 *
	 * Preferably, you should use the `Media_Credit::get_plaintext` or
	 * `Media_Credit::get_html``methods instead.
	 *
	 * @since 3.1.0
	 *
	 * @param int|\WP_Post $attachment An attachment ID or the corresponding \WP_Post object.
	 *
	 * @return array {
	 *     The optional flags stored for the media credit. Default [].
	 *
	 *     @type bool $nofollow Optional. Whether `rel=nofollow` should be added to the link. Default unset.
	 * }
	 */
	public static function get_flags( $attachment ) {

		// Get all the media credit fields.
		$credit = self::get_fields( $attachment );

		return $credit['raw']['flags'];
	}

	/**
	 * Returns the media credit as HTML with a link to the author page if one exists for a WordPress user.
	 *
	 * @param  int $id User ID of a WordPress user.
	 *
	 * @return string
	 */
	public static function get_html_by_user_id( $id ) {
		return Core::get_instance()->render_media_credit_html( $id );
	}

	/**
	 * Outputs the media credit as HTML with a link to the author page if one exists for a WordPress user.
	 *
	 * @param  int $id User ID of a WordPress user.
	 */
	public static function html_by_user_id( $id ) {
		echo self::get_html_by_user_id( $id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- data is safe for output.
	}

	/**
	 * Returns the recently added media attachments and posts for a given author.
	 *
	 * @param array $query {
	 *    Optional. The query variables.
	 *
	 *    @type int    $author_id          A user ID. Default current user.
	 *    @type int    $offset             Number of attachment/posts to offset
	 *                                     in retrieved results. Can be used in
	 *                                     conjunction with pagination. Default 0.
	 *    @type int    $number             Number of users to limit the query for.
	 *                                     Can be used in conjunction with pagination.
	 *                                     Value -1 (all) is supported, but should
	 *                                     be used with caution on larger sites.
	 *                                     Default empty (all attachments/posts).
	 *    @type int    $paged              When used with number, defines the page
	 *                                     of results to return. Default 1.
	 *    @type bool   $include_posts      A flag indicating whether posts (as well
	 *                                     as attachments) should be included in the
	 *                                     results. Default false.
	 *    @type bool   $exclude_unattached A flag indicating whether media items
	 *                                     not currently attached to a parent post
	 *                                     should be excluded from the results.
	 *                                     Default true.
	 * }
	 *
	 * @return array
	 */
	public static function author_media_and_posts( $query ) {
		return Core::get_instance()->get_author_media_and_posts( $query );
	}

	/**
	 * Displays the recently added media attachments for given author.
	 *
	 * @param array $args {
	 *    Optional. The arguments.
	 *
	 *    @type int    $author_id          A user ID. Default current user.
	 *    @type bool   $sidebar            A flag indicating the media should be
	 *                                     displayed as a sidebar or inline. Default true.
	 *    @type int    $offset             Number of attachment/posts to offset
	 *                                     in retrieved results. Can be used in
	 *                                     conjunction with pagination. Default 0.
	 *    @type int    $number             Number of users to limit the query for.
	 *                                     Can be used in conjunction with pagination.
	 *                                     Value -1 (all) is supported, but should
	 *                                     be used with caution on larger sites.
	 *                                     Default 10.
	 *    @type int    $paged              When used with number, defines the page
	 *                                     of results to return. Default 1.
	 *    @type bool   $exclude_unattached A flag indicating whether media items
	 *                                     not currently attached to a parent post
	 *                                     should be excluded from the results.
	 *                                     Default true.
	 * }
	 */
	public static function display_author_media( array $args = [] ) {

		// Ensure default values.
		$defaults = [
			// Non-query variables.
			'sidebar'             => true,
			'link_without_parent' => false,
			'header'              => null,

			// Query variables.
			'number'              => 10,
			'exclude_unattached'  => true,
		];
		$args     = \wp_parse_args( $args, $defaults );

		// No posts!
		$args['include_posts'] = false;

		$media = self::author_media_and_posts( $args );
		if ( empty( $media ) ) {
			return; // abort.
		}

		// Extract variables for template.
		$sidebar             = $args['sidebar'];
		$link_without_parent = $args['link_without_parent'];
		$header              = $args['header'];

		// Load the template part.
		require \dirname( MEDIA_CREDIT_PLUGIN_FILE ) . '/public/partials/author-media.php';
	}

	/**
	 * Retrieves the complete set of media credit fields.
	 *
	 * Ensures a valid post object and returns the media credit data fields for
	 * use in template methods. In case of an invalid attachment ID, all fields
	 * be empty or 0.
	 *
	 * @since 4.0.0
	 *
	 * @param int|\WP_Post $attachment An attachment ID or the corresponding \WP_Post object.
	 *
	 * @return array {
	 *     The media credit fields.
	 *
	 *     @type string $rendered  The HTML representation of the credit (i.e. including links).
	 *     @type string $plaintext The plain text representation of the credit (i.e. without any markup).
	 *     @type array  $raw {
	 *         The raw data used to store the media credit. On error, an empty array is returned.
	 *
	 *         @type int    $user_id  Optional. The ID of the media item author. Default 0 (invalid).
	 *         @type string $freeform Optional. The media credit string (if $user_id is not used). Default ''.
	 *         @type string $url      Optional. A URL the credit should link to. Default ''.
	 *         @type array  $flags {
	 *             Optional. An array of flags to modify the rendering of the media credit. Default [].
	 *
	 *             @type bool $nofollow Optional. A flag indicating that `rel=nofollow` should be added to the link. Default false.
	 *         }
	 *     }
	 * }
	 */
	public static function get_fields( $attachment ) {

		// Load the attachment data if handed an ID.
		if ( ! $attachment instanceof \WP_Post ) {
			$attachment = \get_post( $attachment );
		}

		// Make sure this is a valid attachment object.
		if ( ! $attachment instanceof \WP_Post ) {
			return Core::INVALID_MEDIA_CREDIT;
		}

		return Core::get_instance()->get_media_credit_json( $attachment );
	}
}
