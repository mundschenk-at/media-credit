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

namespace Media_Credit\Tests;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use Media_Credit\Tests\TestCase;

use Media_Credit;

/**
 * Media Credit legacy template tags unit test.
 *
 * @since 4.2.0
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class Media_Credit_Template_Test extends TestCase {

	const FIELDS = [
		'fancy'     => 'Someone | My Cool Organization',
		'plaintext' => 'Someone',
		'rendered'  => '<a href="https://example.org">Someone</a> | My Cool Organization',
		'raw'       => [
			'freeform' => 'Fake Freeform Credit',
			'url'      => 'https://example.org/some/url',
			'flags'    => [
				'foo' => 'bar',
				'bar' => 'baz',
			],
		],
	];

	/**
	 * Helper alias mock.
	 *
	 * @var Media_Credit
	 */
	private $media_credit;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function set_up() {
		parent::set_up();

		$this->media_credit = m::mock( 'alias:' . Media_Credit::class );
	}

	/**
	 * Test get_media_credit.
	 *
	 * @covers get_media_credit
	 */
	public function test_get_media_credit() {
		// Input data.
		$attachment_id = 55;

		// Expected result.
		$result = self::FIELDS['plaintext'];

		Functions\expect( '_deprecated_function' )->once()->with( m::type( 'string' ), m::type( 'string' ), m::type( 'string' ) );
		Functions\expect( '_doing_it_wrong' )->never();

		$this->media_credit->shouldReceive( 'get_plaintext' )->once()->with( $attachment_id )->andReturn( $result );

		$this->assertSame( $result, \get_media_credit( $attachment_id ) );
	}

	/**
	 * Test get_media_credit.
	 *
	 * @covers get_media_credit
	 */
	public function test_get_media_credit_no_post() {
		Functions\expect( '_deprecated_function' )->once()->with( m::type( 'string' ), m::type( 'string' ), m::type( 'string' ) );
		Functions\expect( '_doing_it_wrong' )->once()->with( m::type( 'string' ), m::type( 'string' ), '4.2.0' );

		$this->media_credit->shouldReceive( 'get_plaintext' )->never();

		$this->assertSame( '', \get_media_credit() );
	}

	/**
	 * Test the_media_credit.
	 *
	 * @covers the_media_credit
	 */
	public function test_the_media_credit() {
		// Input data.
		$attachment_id = 55;

		Functions\expect( '_deprecated_function' )->once()->with( m::type( 'string' ), m::type( 'string' ), m::type( 'string' ) );
		Functions\expect( '_doing_it_wrong' )->never();

		$this->media_credit->shouldReceive( 'plaintext' )->once()->with( $attachment_id )->andReturnNull();

		$this->assertNull( \the_media_credit( $attachment_id ) );
	}

	/**
	 * Test the_media_credit.
	 *
	 * @covers the_media_credit
	 */
	public function test_the_media_credit_no_post() {
		Functions\expect( '_deprecated_function' )->once()->with( m::type( 'string' ), m::type( 'string' ), m::type( 'string' ) );
		Functions\expect( '_doing_it_wrong' )->once()->with( m::type( 'string' ), m::type( 'string' ), '4.2.0' );

		$this->media_credit->shouldReceive( 'plaintext' )->never();

		$this->assertNull( \the_media_credit() );
	}

	/**
	 * Test get_media_credit_url.
	 *
	 * @covers get_media_credit_url
	 */
	public function test_get_media_credit_url() {
		// Input data.
		$attachment_id = 55;

		// Expected result.
		$result = self::FIELDS['raw']['url'];

		Functions\expect( '_deprecated_function' )->once()->with( m::type( 'string' ), m::type( 'string' ), m::type( 'string' ) );
		Functions\expect( '_doing_it_wrong' )->never();

		$this->media_credit->shouldReceive( 'get_url' )->once()->with( $attachment_id )->andReturn( $result );

		$this->assertSame( $result, \get_media_credit_url( $attachment_id ) );
	}

	/**
	 * Test get_media_credit_url.
	 *
	 * @covers get_media_credit_url
	 */
	public function test_get_media_credit_url_no_post() {
		Functions\expect( '_deprecated_function' )->once()->with( m::type( 'string' ), m::type( 'string' ), m::type( 'string' ) );
		Functions\expect( '_doing_it_wrong' )->once()->with( m::type( 'string' ), m::type( 'string' ), '4.2.0' );

		$this->media_credit->shouldReceive( 'get_url' )->never();

		$this->assertSame( '', \get_media_credit_url() );
	}

	/**
	 * Test the_media_credit_url.
	 *
	 * @covers the_media_credit_url
	 * @uses get_media_credit_url
	 */
	public function test_the_media_credit_url() {
		// Input data.
		$attachment_id = 55;

		// Expected result.
		$result = self::FIELDS['raw']['url'];

		Functions\expect( '_deprecated_function' )->once()->with( m::type( 'string' ), m::type( 'string' ) );
		Functions\expect( '_doing_it_wrong' )->never();

		// Inlined get_media_credit_url.
		Functions\expect( '_deprecated_function' )->once()->with( m::type( 'string' ), m::type( 'string' ), m::type( 'string' ) );
		$this->media_credit->shouldReceive( 'get_url' )->once()->with( $attachment_id )->andReturn( $result );

		Functions\expect( 'sanitize_url' )->once()->with( m::type( 'string' ) )->andReturnArg( 0 );

		$this->expectOutputString( $result );
		$this->assertNull( \the_media_credit_url( $attachment_id ) );
	}

	/**
	 * Test the_media_credit_url.
	 *
	 * @covers the_media_credit_url
	 */
	public function test_the_media_credit_url_no_post() {
		Functions\expect( '_deprecated_function' )->once()->with( m::type( 'string' ), m::type( 'string' ) );
		Functions\expect( '_doing_it_wrong' )->once()->with( m::type( 'string' ), m::type( 'string' ), '4.2.0' );

		$this->media_credit->shouldReceive( 'get_url' )->never();
		Functions\expect( 'sanitize_url' )->never();

		$this->assertNull( \the_media_credit_url() );
	}

	/**
	 * Test get_media_credit_html.
	 *
	 * @covers get_media_credit_html
	 */
	public function test_get_media_credit_html() {
		// Input data.
		$attachment_id = 55;

		// Expected result.
		$result = self::FIELDS['rendered'];

		Functions\expect( '_deprecated_function' )->once()->with( m::type( 'string' ), m::type( 'string' ), m::type( 'string' ) );
		Functions\expect( '_doing_it_wrong' )->never();

		$this->media_credit->shouldReceive( 'get_html' )->once()->with( $attachment_id )->andReturn( $result );

		$this->assertSame( $result, \get_media_credit_html( $attachment_id ) );
	}

	/**
	 * Test get_media_credit_html.
	 *
	 * @covers get_media_credit_html
	 */
	public function test_get_media_credit_html_no_post() {
		Functions\expect( '_deprecated_function' )->once()->with( m::type( 'string' ), m::type( 'string' ), m::type( 'string' ) );
		Functions\expect( '_doing_it_wrong' )->once()->with( m::type( 'string' ), m::type( 'string' ), '4.2.0' );

		$this->media_credit->shouldReceive( 'get_html' )->never();

		$this->assertSame( '', \get_media_credit_html() );
	}

	/**
	 * Test the_media_credit_html.
	 *
	 * @covers the_media_credit_html
	 */
	public function test_the_media_credit_html() {
		// Input data.
		$attachment_id = 55;

		Functions\expect( '_deprecated_function' )->once()->with( m::type( 'string' ), m::type( 'string' ), m::type( 'string' ) );
		Functions\expect( '_doing_it_wrong' )->never();

		$this->media_credit->shouldReceive( 'html' )->once()->with( $attachment_id )->andReturnNull();

		$this->assertNull( \the_media_credit_html( $attachment_id ) );
	}

	/**
	 * Test the_media_credit_html.
	 *
	 * @covers the_media_credit_html
	 */
	public function test_the_media_credit_html_no_post() {
		Functions\expect( '_deprecated_function' )->once()->with( m::type( 'string' ), m::type( 'string' ), m::type( 'string' ) );
		Functions\expect( '_doing_it_wrong' )->once()->with( m::type( 'string' ), m::type( 'string' ), '4.2.0' );

		$this->media_credit->shouldReceive( 'html' )->never();

		$this->assertNull( \the_media_credit_html() );
	}

	/**
	 * Test get_media_credit_html_by_user_id.
	 *
	 * @covers get_media_credit_html_by_user_id
	 */
	public function test_get_media_credit_html_by_user_id() {
		// Input data.
		$user_id = 4711;

		// Expected result.
		$result = self::FIELDS['rendered'];

		Functions\expect( '_deprecated_function' )->once()->with( m::type( 'string' ), m::type( 'string' ), m::type( 'string' ) );

		$this->media_credit->shouldReceive( 'get_html_by_user_id' )->once()->with( $user_id )->andReturn( $result );

		$this->assertSame( $result, \get_media_credit_html_by_user_id( $user_id ) );
	}

	/**
	 * Test the_media_credit_html_by_user_id.
	 *
	 * @covers the_media_credit_html_by_user_id
	 */
	public function test_the_media_credit_html_by_user_id() {
		// Input data.
		$user_id = 4711;

		Functions\expect( '_deprecated_function' )->once()->with( m::type( 'string' ), m::type( 'string' ), m::type( 'string' ) );

		$this->media_credit->shouldReceive( 'html_by_user_id' )->once()->with( $user_id )->andReturnNull();

		$this->assertNull( \the_media_credit_html_by_user_id( $user_id ) );
	}

	/**
	 * Test display_author_media.
	 *
	 * @covers display_author_media
	 */
	public function test_display_author_media() {
		// Input data.
		$author_id           = 4711;
		$sidebar             = false;
		$limit               = 5;
		$link_without_parent = true;
		$header              = '<h2>Foobar</h2>';
		$exclude_unattached  = false;

		// Internal data.
		$query = [
			'sidebar'             => $sidebar,
			'link_without_parent' => $link_without_parent,
			'header'              => $header,
			'author_id'           => $author_id,
			'number'              => $limit,
			'exclude_unattached'  => $exclude_unattached,
		];

		Functions\expect( '_deprecated_function' )->once()->with( m::type( 'string' ), m::type( 'string' ), m::type( 'string' ) );

		$this->media_credit->shouldReceive( 'display_author_media' )->once()->with( $query )->andReturnNull();

		$this->assertNull( \display_author_media( $author_id, $sidebar, $limit, $link_without_parent, $header, $exclude_unattached ) );
	}

		/**
	 * Test display_author_media.
	 *
	 * @covers display_author_media
	 */
	public function test_display_author_media_no_limit() {
		// Input data.
		$author_id           = 4711;
		$sidebar             = false;
		$limit               = null;
		$link_without_parent = true;
		$header              = '<h2>Foobar</h2>';
		$exclude_unattached  = false;

		// Internal data.
		$query = [
			'sidebar'             => $sidebar,
			'link_without_parent' => $link_without_parent,
			'header'              => $header,
			'author_id'           => $author_id,
			'exclude_unattached'  => $exclude_unattached,
		];

		Functions\expect( '_deprecated_function' )->once()->with( m::type( 'string' ), m::type( 'string' ), m::type( 'string' ) );

		$this->media_credit->shouldReceive( 'display_author_media' )->once()->with( $query )->andReturnNull();

		$this->assertNull( \display_author_media( $author_id, $sidebar, $limit, $link_without_parent, $header, $exclude_unattached ) );
	}

	/**
	 * Test author_media_and_posts.
	 *
	 * @covers author_media_and_posts
	 */
	public function test_author_media_and_posts() {
		// Input data.
		$author_id          = 4711;
		$include_posts      = false;
		$limit              = 5;
		$exclude_unattached = false;

		// Internal data.
		$args = [
			'author_id'          => $author_id,
			'number'             => $limit,
			'include_posts'      => $include_posts,
			'exclude_unattached' => $exclude_unattached,
		];

		// Expected result.
		$result = [
			1  => (object) [ 'foo' => 'bar' ],
			55 => (object) [ 'foo' => 'baz' ],
		];

		Functions\expect( '_deprecated_function' )->once()->with( m::type( 'string' ), m::type( 'string' ), m::type( 'string' ) );

		$this->media_credit->shouldReceive( 'author_media_and_posts' )->once()->with( $args )->andReturn( $result );

		$this->assertSame( $result, \author_media_and_posts( $author_id, $include_posts, $limit, $exclude_unattached ) );
	}

	/**
	 * Test author_media_and_posts.
	 *
	 * @covers author_media_and_posts
	 */
	public function test_author_media_and_posts_no_limit() {
		// Input data.
		$author_id          = 4711;
		$include_posts      = false;
		$limit              = null;
		$exclude_unattached = false;

		// Internal data.
		$args = [
			'author_id'          => $author_id,
			'include_posts'      => $include_posts,
			'exclude_unattached' => $exclude_unattached,
		];

		// Expected result.
		$result = [
			1  => (object) [ 'foo' => 'bar' ],
			55 => (object) [ 'foo' => 'baz' ],
		];

		Functions\expect( '_deprecated_function' )->once()->with( m::type( 'string' ), m::type( 'string' ), m::type( 'string' ) );

		$this->media_credit->shouldReceive( 'author_media_and_posts' )->once()->with( $args )->andReturn( $result );

		$this->assertSame( $result, \author_media_and_posts( $author_id, $include_posts, $limit, $exclude_unattached ) );
	}

	/**
	 * Test author_media.
	 *
	 * @covers author_media
	 */
	public function test_author_media() {
		// Input data.
		$author_id          = 4711;
		$limit              = 5;
		$exclude_unattached = false;

		// Internal data.
		$args = [
			'author_id'          => $author_id,
			'number'             => $limit,
			'include_posts'      => false,
			'exclude_unattached' => $exclude_unattached,
		];

		// Expected result.
		$result = [
			1  => (object) [ 'foo' => 'bar' ],
			55 => (object) [ 'foo' => 'baz' ],
		];

		Functions\expect( '_deprecated_function' )->once()->with( m::type( 'string' ), m::type( 'string' ), m::type( 'string' ) );

		$this->media_credit->shouldReceive( 'author_media_and_posts' )->once()->with( $args )->andReturn( $result );

		$this->assertSame( $result, \author_media( $author_id, $limit, $exclude_unattached ) );
	}

	/**
	 * Test author_media.
	 *
	 * @covers author_media
	 */
	public function test_author_media_no_limit() {
		// Input data.
		$author_id          = 4711;
		$limit              = null;
		$exclude_unattached = false;

		// Internal data.
		$args = [
			'author_id'          => $author_id,
			'include_posts'      => false,
			'exclude_unattached' => $exclude_unattached,
		];

		// Expected result.
		$result = [
			1  => (object) [ 'foo' => 'bar' ],
			55 => (object) [ 'foo' => 'baz' ],
		];

		Functions\expect( '_deprecated_function' )->once()->with( m::type( 'string' ), m::type( 'string' ), m::type( 'string' ) );

		$this->media_credit->shouldReceive( 'author_media_and_posts' )->once()->with( $args )->andReturn( $result );

		$this->assertSame( $result, \author_media( $author_id, $limit, $exclude_unattached ) );
	}
}
