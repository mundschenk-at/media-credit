<?php
/**
 * This file is part of Media Credit.
 *
 * Copyright 2021-2023 Peter Putzer.
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

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use Mockery as m;

use Media_Credit\Tools\Author_Query;

use Media_Credit\Data_Storage\Cache;

use Media_Credit\Tests\TestCase;

/**
 * Media_Credit\Tools\Author_Query unit test.
 *
 * @since 4.2.0
 *
 * @coversDefaultClass \Media_Credit\Tools\Author_Query
 * @usesDefaultClass \Media_Credit\Tools\Author_Query
 *
 * @uses ::__construct
 */
class Author_Query_Test extends TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Author_Query
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
		$this->cache = m::mock( Cache::class );

		// Create system-under-test.
		$this->sut = m::mock( Author_Query::class, [ $this->cache ] )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Test ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$cache = m::mock( Cache::class );

		$mock = m::mock( Author_Query::class )->makePartial();
		$mock->__construct( $cache );

		$this->assert_attribute_same( $cache, 'cache', $mock );
	}

	/**
	 * Test ::get_authors.
	 *
	 * @covers ::get_authors
	 */
	public function test_get_authors() {
		// Intermediary data.
		$query = [
			'fake' => 'query',
			'for'  => 'authors',
		];

		// Expected result.
		$result = [
			(object) [
				'ID'           => 5,
				'display_name' => 'User 1',
			],
			(object) [
				'ID'           => 47,
				'display_name' => 'User 2',
			],
		];

		$this->sut->shouldReceive( 'get_author_list_query' )->once()->andReturn( $query );
		$this->cache->shouldReceive( 'get' )->once()->with( m::pattern( '/author_list_[a-fA-F\d]{32}/' ) )->andReturn( false );

		Functions\expect( 'get_users' )->once()->with( $query )->andReturn( $result );

		$this->cache->shouldReceive( 'set' )->once()->with( m::pattern( '/author_list_[a-fA-F\d]{32}/' ), $result, \MINUTE_IN_SECONDS );

		$this->assertSame( $result, $this->sut->get_authors() );
	}

	/**
	 * Test ::get_authors.
	 *
	 * @covers ::get_authors
	 */
	public function test_get_authors_cached() {
		// Intermediary data.
		$query = [
			'fake' => 'query',
			'for'  => 'authors',
		];

		// Expected result.
		$result = [
			(object) [
				'ID'           => 5,
				'display_name' => 'User 1',
			],
			(object) [
				'ID'           => 47,
				'display_name' => 'User 2',
			],
		];

		$this->sut->shouldReceive( 'get_author_list_query' )->once()->andReturn( $query );
		$this->cache->shouldReceive( 'get' )->once()->with( m::pattern( '/author_list_[a-fA-F\d]{32}/' ) )->andReturn( $result );

		Functions\expect( 'get_users' )->never();

		$this->cache->shouldReceive( 'set' )->never();

		$this->assertSame( $result, $this->sut->get_authors() );
	}


	/**
	 * Provides data for testing ::query.
	 *
	 * @return array
	 */
	public function provide_get_author_list_query_data() {
		return [
			[
				'5.9-alpha',
				[ 'capability' => 'edit_posts' ],
			],
			[
				'5.9',
				[ 'capability' => 'edit_posts' ],
			],
			[
				'6.2',
				[ 'capability' => 'edit_posts' ],
			],
		];
	}

	/**
	 * Test ::get_author_list_query.
	 *
	 * @covers ::get_author_list_query
	 *
	 * @dataProvider provide_get_author_list_query_data
	 *
	 * @param string   $wp_version    The simulated WordPress version.
	 * @param string[] $initial_query The expected initial author query.
	 */
	public function test_get_author_list_query( $wp_version, $initial_query ) {
		Functions\expect( 'get_bloginfo' )->atMost()->once()->with( 'version' )->andReturn( $wp_version );
		Filters\expectApplied( 'media_credit_author_list_query' )->once()->with( $initial_query )->andReturn( $initial_query );

		$result = $this->sut->get_author_list_query();

		$this->assertArrayHasKey( 'orderby', $result );
		$this->assertArrayHasKey( 'number', $result );
		$this->assertArrayHasKey( 'offset', $result );
		$this->assertArrayHasKey( 'count_total', $result );
		$this->assertArrayHasKey( 'fields', $result );
		$this->assertSame( 'ID', $result['orderby'] );
		$this->assertSame( -1, $result['number'] );
		$this->assertSame( 0, $result['offset'] );
		$this->assertFalse( $result['count_total'] );
		$this->assertSame( [ 'ID', 'display_name' ], $result['fields'] );
	}

	/**
	 * Test ::get_author_by_name.
	 *
	 * @covers ::get_author_by_name
	 */
	public function test_get_author_by_name() {
		// Input data.
		$name = 'Jane Doe';

		// Intermediary data.
		$search_columns          = [ 'display_name', 'foo', 'bar' ];
		$initial_query           = [
			'fake'   => 'query',
			'for'    => 'authors',
			'fields' => [ 'ID', 'display_name' ],
		];
		$query                   = $initial_query;
		$query['search']         = $name;
		$query['search_columns'] = $search_columns;
		$query['fields']         = [ 'ID', 'display_name', 'foo', 'bar' ];
		$authors                 = [
			(object) [
				'ID'           => 5,
				'display_name' => 'John Doe',
				'foo'          => 'John',
				'bar'          => 'Doe',
			],
			(object) [
				'ID'           => 47,
				'display_name' => 'John D. Doe',
				'foo'          => 'John D.',
				'bar'          => 'Doe',
			],
			(object) [
				'ID'           => 42,
				'display_name' => 'Jane Doe',
				'foo'          => 'Jane',
				'bar'          => 'Doe',
			],
			(object) [
				'ID'           => 4711,
				'display_name' => 'Mary Doe',
				'foo'          => 'Mary',
				'bar'          => 'Doe',
			],
		];

		// Expected result.
		$result = 42;

		Filters\expectApplied( 'media_credit_author_name_search_columns' )->once()->with( m::type( 'array' ), $name )->andReturn( $search_columns );

		$this->sut->shouldReceive( 'get_author_list_query' )->once()->andReturn( $initial_query );

		Functions\expect( 'get_users' )->once()->with( $query )->andReturn( $authors );

		$this->assertSame( $result, $this->sut->get_author_by_name( $name ) );
	}

	/**
	 * Test ::get_author_by_name.
	 *
	 * @covers ::get_author_by_name
	 */
	public function test_get_author_by_name_no_exact_match() {
		// Input data.
		$name = 'Jane Doe';

		// Intermediary data.
		$search_columns          = [ 'display_name', 'foo', 'bar' ];
		$initial_query           = [
			'fake'   => 'query',
			'for'    => 'authors',
			'fields' => [ 'ID', 'display_name' ],
		];
		$query                   = $initial_query;
		$query['search']         = $name;
		$query['search_columns'] = $search_columns;
		$query['fields']         = [ 'ID', 'display_name', 'foo', 'bar' ];
		$authors                 = [
			(object) [
				'ID'           => 42,
				'display_name' => 'Jane Mariella Doe',
				'foo'          => 'Jane',
				'bar'          => 'Doe',
			],
			(object) [
				'ID'           => 4711,
				'display_name' => 'Mary Jane Doe',
				'foo'          => 'Mary',
				'bar'          => 'Doe',
			],
		];

		// Expected result.
		$result = 42;

		Filters\expectApplied( 'media_credit_author_name_search_columns' )->once()->with( m::type( 'array' ), $name )->andReturn( $search_columns );

		$this->sut->shouldReceive( 'get_author_list_query' )->once()->andReturn( $initial_query );

		Functions\expect( 'get_users' )->once()->with( $query )->andReturn( $authors );

		$this->assertSame( $result, $this->sut->get_author_by_name( $name ) );
	}

	/**
	 * Test ::get_author_by_name.
	 *
	 * @covers ::get_author_by_name
	 */
	public function test_get_author_by_name_no_match() {
		// Input data.
		$name = 'Jane Doe';

		// Intermediary data.
		$search_columns          = [ 'display_name', 'foo', 'bar' ];
		$initial_query           = [
			'fake'   => 'query',
			'for'    => 'authors',
			'fields' => [ 'ID', 'display_name' ],
		];
		$query                   = $initial_query;
		$query['search']         = $name;
		$query['search_columns'] = $search_columns;
		$query['fields']         = [ 'ID', 'display_name', 'foo', 'bar' ];

		Filters\expectApplied( 'media_credit_author_name_search_columns' )->once()->with( m::type( 'array' ), $name )->andReturn( $search_columns );

		$this->sut->shouldReceive( 'get_author_list_query' )->once()->andReturn( $initial_query );

		Functions\expect( 'get_users' )->once()->with( $query )->andReturn( [] );

		$this->assertFalse( $this->sut->get_author_by_name( $name ) );
	}
}
