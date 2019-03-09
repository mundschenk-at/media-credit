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

namespace Media_Credit\Tools;

use Media_Credit\Core;
use Media_Credit\Data_Storage\Cache;

/**
 * A utility class for querying media.
 *
 * @since 4.0.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class Media_Query {

	/**
	 * The object cache handler.
	 *
	 * @var Cache
	 */
	private $cache;

	/**
	 * Creates a new instance.
	 *
	 * @param Cache $cache The object cache handler.
	 */
	public function __construct( Cache $cache ) {
		$this->cache = $cache;
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
	 *    @type string $since              If set, only attachments and posts younger
	 *                                     than this date will be returned. Default empty.
	 * }
	 */
	public function get_author_media_and_posts( array $query = [] ) {

		// Ensure default values.
		$defaults = [
			'author_id'          => \get_current_user_id(),
			'offset'             => 0,
			'number'             => null,
			'paged'              => 1,
			'include_posts'      => false,
			'exclude_unattached' => true,
			'since'              => null,
		];
		$query    = \wp_parse_args( $query, $defaults );

		// Pre-calculate offset and limit for caching.
		$limit_key = 'all';
		if ( isset( $query['number'] ) && $query['number'] > 0 ) {
			$query['offset'] = $query['offset'] ? $query['offset'] : $query['number'] * ( $query['paged'] - 1 );
			$limit_key       = "{$query['number']}+{$query['offset']}";
		}

		$cache_key = "author_media_{$query['author_id']}_i" . ( $query['include_posts'] ? '1' : '0' ) . '_e' . ( $query['exclude_unattached'] ? '1' : '0' ) . "_{$limit_key}_s{$query['since']}";
		$results   = $this->cache->get( $cache_key );

		if ( false === $results ) {
			$results = $this->query( $query );

			// Cache results for a short time.
			$this->cache->set( $cache_key, $results, MINUTE_IN_SECONDS );
		}

		return $results;
	}

	/**
	 * Builds and executes the query the recently added media attachments and posts for a given author.
	 *
	 * @param array $query {
	 *    The query variables (already filled with defaults if necessary).
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
	 *    @type bool   $include_posts      A flag indicating whether posts (as well
	 *                                     as attachments) should be included in the
	 *                                     results. Default false.
	 *    @type bool   $exclude_unattached A flag indicating whether media items
	 *                                     not currently attached to a parent post
	 *                                     should be excluded from the results.
	 *                                     Default true.
	 *    @type string $since              If set, only attachments and posts younger
	 *                                     than this date will be returned. Default empty.
	 * }
	 */
	protected function query( array $query = [] ) {
		global $wpdb;

		$posts_query    = '';
		$attached_query = '';
		$date_query     = '';
		$limit_query    = '';
		$query_vars     = [ $query['author_id'] ]; // always the first parameter.

		// Optionally include published posts as well.
		if ( $query['include_posts'] ) {
			$posts_query = "OR (post_type = 'post' AND post_parent = '0' AND post_status = 'publish')";
		}

		// Optionally exclude "unattached" attachments.
		if ( $query['exclude_unattached'] ) {
			$attached_query = " AND post_parent != '0' AND post_parent IN (SELECT id FROM {$wpdb->posts} WHERE post_status='publish')";
		}

		// Exclude attachments from before the install date of the Media Credit plugin.
		if ( ! empty( $query['since'] ) ) {
			$date_query   = ' AND post_date >= %s';
			$query_vars[] = $query['since']; // second parameter.
		}

		// We always need to include the meta key in our query.
		$query_vars[] = Core::POSTMETA_KEY;

		// Optionally set limit (we pre-calculated the offset above).
		if ( ! empty( $query['number'] ) ) {
			$limit_query  = ' LIMIT %d, %d';
			$query_vars[] = $query['offset'];
			$query_vars[] = $query['number'];
		}

		// Construct our query.
		$sql_query = "SELECT * FROM {$wpdb->posts}
			 		  WHERE post_author = %d {$date_query}
			 		  AND ( ( post_type = 'attachment' {$attached_query} ) {$posts_query} )
			 		  AND ID NOT IN ( SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s )
			 		  GROUP BY ID ORDER BY post_date DESC {$limit_query}";

		// Prepare and execute query.
		return $wpdb->get_results( $wpdb->prepare( $sql_query, $query_vars ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}
}
