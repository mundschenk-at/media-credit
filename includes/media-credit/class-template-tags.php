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

/**
 * A container of static functions implementing the internals of the
 * plugin's template tags.
 *
 * @since 3.0.0
 * @since 4.0.0 Renamed to Media_Credit\Template_Tags
 */
class Template_Tags {

	/**
	 * Returns the media credit as plain text for some media attachment.
	 *
	 * @param  int|\WP_Post $attachment An attachment ID or the corresponding \WP_Post object.
	 * @param  bool         $fancy      Optional. Fancy output (<user> <separator> <organization>)
	 *                                  for local user credits. Optional. Default false.
	 *
	 * @return string                   The media credit in plaintext format.
	 */
	public static function get_media_credit( $attachment, $fancy = false ) {

		// Get all the media credit fields.
		$credit = self::get_media_credit_fields( $attachment );

		// We want the credit string in plain text format (but possibly "fancy").
		return $fancy ? $credit['fancy'] : $credit['plaintext'];
	}

	/**
	 * Returns the media credit URL as plain text for some media attachment.
	 *
	 * @param int|\WP_Post $attachment An attachment ID or the corresponding \WP_Post object.
	 *
	 * @return string                  The credit URL (or the empty string if none is set).
	 */
	public static function get_media_credit_url( $attachment ) {

		// Get all the media credit fields.
		$credit = self::get_media_credit_fields( $attachment );

		return $credit['raw']['url'];
	}

	/**
	 * Returns the optional media credit data array for a media attachment.
	 *
	 * @since 3.1.0
	 *
	 * @param int|\WP_Post $attachment An attachment ID or the corresponding \WP_Post object.
	 *
	 * @return array                   The data array.
	 */
	public static function get_media_credit_data( $attachment ) {

		// Get all the media credit fields.
		$credit = self::get_media_credit_fields( $attachment );

		return $credit['raw']['flags'];
	}

	/**
	 * Returns the media credit as HTML with a link to the author page if one exists for some media attachment.
	 *
	 * @param  int|\WP_Post $attachment An attachment ID or the corresponding \WP_Post object.
	 * @param  bool         $deprecated Optional. Argument is ignored. Default true.
	 *
	 * @return string                   The media credit HTML (or the empty string if no credit is set).
	 */
	public static function get_media_credit_html( $attachment, $deprecated = true ) {

		// Get all the media credit fields.
		$credit = self::get_media_credit_fields( $attachment );

		return $credit['rendered'];
	}

	/**
	 * Returns the media credit as HTML with a link to the author page if one exists for a WordPress user.
	 *
	 * @param  int $id User ID of a WordPress user.
	 *
	 * @return string
	 */
	public static function get_media_credit_html_by_user_id( $id ) {

		$credit_wp_author = get_the_author_meta( 'display_name', $id );
		$options          = Core::get_instance()->get_settings();

		return '<a href="' . get_author_posts_url( $id ) . '">' . $credit_wp_author . '</a>' . $options['separator'] . $options['organization'];
	}

	/**
	 * Returns the freeform media credit for a given post/attachment.
	 *
	 * @param int|\WP_Post $attachment An attachment ID or the corresponding \WP_Post object.
	 *
	 * @return string                  The freeform credit (or the empty string).
	 */
	public static function get_freeform_media_credit( $attachment ) {

		// Get all the media credit fields.
		$credit = self::get_media_credit_fields( $attachment );

		// Don't display our special "empty" string.
		if ( Core::EMPTY_META_STRING === $credit['raw']['freeform'] ) {
			return '';
		}

		return $credit['raw']['freeform'];
	}

	/**
	 * Returns the recently added media attachments and posts for a given author.
	 *
	 * @param int     $author_id          The user ID of the author.
	 * @param boolean $include_posts      Optional. Default true.
	 * @param int     $limit              Optional. Default 0.
	 * @param boolean $exclude_unattached Optional. Default true.
	 */
	public static function author_media_and_posts( $author_id, $include_posts = true, $limit = 0, $exclude_unattached = true ) {
		$cache_key = "author_media_and_posts_{$author_id}_i" . ( $include_posts ? '1' : '0' ) . "_l{$limit}_e" . ( $exclude_unattached ? '1' : '0' );
		$results   = wp_cache_get( $cache_key, 'media-credit' );

		if ( false === $results ) {
			global $wpdb;

			$posts_query = '';
			$attached    = '';
			$date_query  = '';
			$limit_query = '';
			$query_vars  = array( $author_id ); // always the first parameter.

			// Optionally include published posts as well.
			if ( $include_posts ) {
				$posts_query = "OR (post_type = 'post' AND post_parent = '0' AND post_status = 'publish')";
			}

			// Optionally exclude "unattached" attachments.
			if ( $exclude_unattached ) {
				$attached = " AND post_parent != '0' AND post_parent IN (SELECT id FROM {$wpdb->posts} WHERE post_status='publish')";
			}

			// Exclude attachments from before the install date of the Media Credit plugin.
			$options = Core::get_instance()->get_settings();
			if ( isset( $options['install_date'] ) ) {
				$start_date = $options['install_date'];

				if ( $start_date ) {
					$date_query   = ' AND post_date >= %s';
					$query_vars[] = $start_date; // second parameter.
				}
			}

			// We always need to include the meta key in our query.
			$query_vars[] = Core::POSTMETA_KEY;

			// Optionally set limit.
			if ( $limit > 0 ) {
				$limit_query  = ' LIMIT %d';
				$query_vars[] = $limit; // always the last parameter.
			}

			// Construct our query.
			$sql_query = "SELECT * FROM {$wpdb->posts}
				 		  WHERE post_author = %d {$date_query}
				 		  AND ( ( post_type = 'attachment' {$attached} ) {$posts_query} )
				 		  AND ID NOT IN ( SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s )
				 		  GROUP BY ID ORDER BY post_date DESC {$limit_query}";

			// Prepare and execute query.
			$results = $wpdb->get_results( $wpdb->prepare( $sql_query, $query_vars ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery

			// Cache results for a short time.
			wp_cache_set( $cache_key, $results, 'media-credit', MINUTE_IN_SECONDS );
		}

		return $results;
	}

	/**
	 * Displays the recently added media attachments for given author.
	 *
	 * @param int     $author_id           The user ID of the author.
	 * @param boolean $sidebar             Display as sidebar or inline. Optional. Default true.
	 * @param int     $limit               Optional. Default 10.
	 * @param boolean $link_without_parent Optional. Default false.
	 * @param string  $header              HTML-formatted heading. Optional. Default <h3>Recent Media</h3> (translated).
	 * @param boolean $exclude_unattached  Optional. Default true.
	 */
	public static function display_author_media( $author_id, $sidebar = true, $limit = 10, $link_without_parent = false, $header = null, $exclude_unattached = true ) {

		$media = self::author_media_and_posts( $author_id, false, $limit, $exclude_unattached );
		if ( empty( $media ) ) {
			return; // abort.
		}

		// Load the template part.
		require \dirname( MEDIA_CREDIT_PLUGIN_FILE ) . '/public/partials/author-media.php';
	}

	/**
	 * Ensures a valid post object and returns the media credit data fields for
	 * use in template methods. In case of an invalid attachment ID, all fields
	 * be empty or 0.
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
	private static function get_media_credit_fields( $attachment ) {

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
