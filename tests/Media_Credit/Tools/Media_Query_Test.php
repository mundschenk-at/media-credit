<?php
/**
 * This file is part of Media Credit.
 *
 * Copyright 2021 Peter Putzer.
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
 * @package mundschenk-at/media-credit/tests
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Media_Credit\Tests\Media_Credit\Tools;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use Media_Credit\Tools\Media_Query;

use Media_Credit\Core;
use Media_Credit\Data_Storage\Cache;

use Media_Credit\Tests\TestCase;

/**
 * Media_Credit\Tools\Media_Query unit test.
 *
 * @since 4.2.0
 *
 * @coversDefaultClass \Media_Credit\Tools\Media_Query
 * @usesDefaultClass \Media_Credit\Tools\Media_Query
 *
 * @uses ::__construct
 */
class Media_Query_Test extends TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Media_Query
	 */
	private $sut;

	/**
	 * Helper mock.
	 *
	 * @var Cache
	 */
	private $cache;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function set_up() {
		parent::set_up();

		// Initialize helpers.
		$this->cache = m::mock( Cache::Class );

		// Create system-under-test.
		$this->sut = m::mock( Media_Query::class, [ $this->cache ] )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Test ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$cache = m::mock( Cache::Class );

		$mock = m::mock( Media_Query::class )->makePartial();
		$mock->__construct( $cache );

		$this->assert_attribute_same( $cache, 'cache', $mock );
	}

	/**
	 * Test ::get_author_media_and_posts.
	 *
	 * @covers ::get_author_media_and_posts
	 */
	public function test_get_author_media_and_posts() {
		// Input data.
		$query = [
			'fake'   => 'query',
			'for'    => 'authors',
			'number' => 10,
			'paged'  => 2,
		];

		// Expected result.
		$result = [
			(object) [
				'ID'   => 5,
				'fake' => 'media',
			],
			(object) [
				'ID'   => 47,
				'fake' => 'media',
			],
		];

		Functions\expect( 'get_current_user_id' )->once()->andReturn( 666 );
		Functions\expect( 'wp_parse_args' )->once()->andReturnUsing(
			function( $parsed_args, $defaults ) {
				return \array_merge( $defaults, $parsed_args );
			}
		);

		$this->cache->shouldReceive( 'get' )->once()->with( m::pattern( '/author_media.*/' ) )->andReturn( false );
		$this->sut->shouldReceive( 'query' )->once()->with( m::type( 'array' ) )->andReturn( $result );
		$this->cache->shouldReceive( 'set' )->once()->with( m::pattern( '/author_media.*/' ), $result, \MINUTE_IN_SECONDS );

		$this->assertSame( $result, $this->sut->get_author_media_and_posts( $query ) );
	}

	/**
	 * Test ::get_author_media_and_posts.
	 *
	 * @covers ::get_author_media_and_posts
	 */
	public function test_get_author_media_and_posts_cached() {
		// Input data.
		$query = [
			'fake'   => 'query',
			'for'    => 'authors',
			'number' => 10,
			'paged'  => 2,
		];

		// Expected result.
		$result = [
			(object) [
				'ID'   => 5,
				'fake' => 'media',
			],
			(object) [
				'ID'   => 47,
				'fake' => 'media',
			],
		];

		Functions\expect( 'get_current_user_id' )->once()->andReturn( 666 );
		Functions\expect( 'wp_parse_args' )->once()->andReturnUsing(
			function( $parsed_args, $defaults ) {
				return \array_merge( $defaults, $parsed_args );
			}
		);

		$this->cache->shouldReceive( 'get' )->once()->with( m::pattern( '/author_media.*/' ) )->andReturn( $result );
		$this->sut->shouldReceive( 'query' )->never();
		$this->cache->shouldReceive( 'set' )->never();

		$this->assertSame( $result, $this->sut->get_author_media_and_posts( $query ) );
	}

	/**
	 * Provides data for testing ::query.
	 *
	 * @return array
	 */
	public function provide_query_data() {
		return [
			[
				[
					'author_id'          => 666,
					'offset'             => 0,
					'number'             => null,
					'paged'              => 1,
					'include_posts'      => false,
					'exclude_unattached' => true,
					'since'              => null,
				],
				[ 'author_id', '_postmeta_key' ],
			],
			[
				[
					'author_id'          => 666,
					'offset'             => 0,
					'number'             => null,
					'paged'              => 1,
					'include_posts'      => true,
					'exclude_unattached' => true,
					'since'              => null,
				],
				[ 'author_id', '_postmeta_key' ],
			],
			[
				[
					'author_id'          => 666,
					'offset'             => 0,
					'number'             => null,
					'paged'              => 1,
					'include_posts'      => false,
					'exclude_unattached' => true,
					'since'              => '2000-01-01',
				],
				[ 'author_id', 'since', '_postmeta_key' ],
			],
			[
				[
					'author_id'          => 666,
					'offset'             => 0,
					'number'             => 5,
					'paged'              => 1,
					'include_posts'      => false,
					'exclude_unattached' => true,
					'since'              => '2000-01-01',
				],
				[ 'author_id', 'since', '_postmeta_key', 'offset', 'number' ],
			],
		];
	}

	/**
	 * Test ::query.
	 *
	 * @covers ::query
	 *
	 * @dataProvider provide_query_data
	 *
	 * @param  array $query               The query array.
	 * @param  array $expected_query_vars An array of indexes to $query (and the
	 *                                    special pseudo-index '_postmeta_key' for
	 *                                    `\Media_Credit\Core::POSTMETA_KEY`).
	 */
	public function test_query( array $query, array $expected_query_vars ) {
		// Globals.
		global $wpdb;
		$wpdb           = m::mock( \wpdb::class ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride
		$wpdb->posts    = 'wp_posts';
		$wpdb->postmeta = 'wp_postmeta';

		// Expected result.
		$result = [
			(object) [
				'ID'   => 5,
				'fake' => 'media',
			],
			(object) [
				'ID'   => 47,
				'fake' => 'media',
			],
		];

		$wpdb->shouldReceive( 'prepare' )->once()->with(
			m::type( 'string' ),
			m::on(
				function( $query_vars ) use ( $query, $expected_query_vars ) {
					// Check if the $query_vars conform to expectations.
					return \is_array( $query_vars ) &&
						\array_reduce(
							\array_map(
								function( $index, $value ) use ( $query ) {
									return ( '_postmeta_key' === $index ? Core::POSTMETA_KEY : $query[ $index ] ) === $value;
								},
								$expected_query_vars,
								$query_vars
							),
							function( $carry, $item ) {
								return $carry && $item;
							},
							true
						);
				}
			)
		)->andReturn( 'PREPARED SQL' );
		$wpdb->shouldReceive( 'get_results' )->once()->with( 'PREPARED SQL' )->andReturn( $result );

		$this->assertSame( $result, $this->sut->query( $query ) );
	}
}
