<?php
/**
 * This file is part of Media Credit.
 *
 * Copyright 2020 Peter Putzer.
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
 * A utility class for querying valid media authors.
 *
 * @since 4.1.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class Author_Query {

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
	 * Returns the site users valid for being used as media authors.
	 *
	 * @return array {
	 *     An array of eligible WordPress users.
	 *
	 *     int    $ID           The user ID.
	 *     string $display_name The display name.
	 * }
	 */
	public function get_authors() {
		$query      = $this->get_author_list_query();
		$query_hash = \md5( /* @scrutinizer ignore-type */ \wp_json_encode( $query ) );
		$cache_key  = "author_list_{$query_hash}";
		$results    = $this->cache->get( $cache_key );

		if ( ! \is_array( $results ) ) {
			$results = \get_users( $query );

			// Cache results for a short time.
			$this->cache->set( $cache_key, $results, MINUTE_IN_SECONDS );
		}

		return $results;
	}

	/**
	 * Builds the author list query.
	 *
	 * @return array A query specification suitable for WP_User_Query.
	 */
	protected function get_author_list_query() {
		$query = [
			'who' => 'authors',
		];

		/**
		 * Filters the query for retrieving the list of users eligible to be used
		 * in media credits.
		 *
		 * @since 4.1.0
		 *
		 * @param array $query {
		 *     A partial user query (@see WP_User_Query::prepare_query for possibilities).
		 *
		 *     Please note that the query will always retrieve a full list of IDs
		 *     and display names. Any `orderby`, `number`, `offset`, `count_total`,
		 *     and `fields` clauses set here will be ignored.
		 *
		 *     string $who Default 'authors'.
		 * }
		 */
		$query = \apply_filters( 'media_credit_author_list_query', $query );

		// Ensure we return all IDs and display names without pagination.
		$query['orderby']     = 'ID';
		$query['number']      = -1;
		$query['offset']      = 0;
		$query['count_total'] = false;
		$query['fields']      = [ 'ID', 'display_name' ];

		return $query;
	}
}
