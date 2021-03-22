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

namespace Media_Credit\Tests;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use org\bovigo\vfs\vfsStream;

use Media_Credit\Tests\TestCase;

use Media_Credit\Core;

/**
 * Media_Credit unit test.
 *
 * @since 4.2.0
 *
 * @coversDefaultClass \Media_Credit
 * @usesDefaultClass \Media_Credit
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class Media_Credit_Test extends TestCase {

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
	 * @var Core
	 */
	private $core;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function set_up() {
		parent::set_up();

		// Set up virtual filesystem.
		$filesystem = [
			'plugin' => [
				'public'       => [
					'partials' => [
						'author-media.php' => 'AUTHOR_MEDIA',
					],
				],
			],
		];
		vfsStream::setup( 'root', null, $filesystem );
		set_include_path( 'vfs://root/' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_set_include_path

		m::getConfiguration()->setConstantsMap(
			[
				Core::class => [
					'EMPTY_META_STRING'    => ' ',
					'POSTMETA_KEY'         => '_media_credit',
					'URL_POSTMETA_KEY'     => '_media_credit_url',
					'DATA_POSTMETA_KEY'    => '_media_credit_data',
					'INVALID_MEDIA_CREDIT' => [
						'rendered'  => '',
						'plaintext' => '',
						'fancy'     => '',
						'raw'       => [
							'user_id'  => 0,
							'freeform' => '',
							'url'      => '',
							'flags'    => [
								'nofollow' => 0,
							],
						],
					],
				],
			]
		);

		$this->core = m::mock( 'alias:' . Core::class );
	}

	/**
	 * Test ::get_plaintext.
	 *
	 * @covers ::get_plaintext
	 * @uses ::get_fields
	 */
	public function test_get_plaintext() {
		// Input data.
		$attachment_id = 55;

		// Internal data.
		$attachment = m::mock( \WP_Post::class );

		// Inlined ::get_fields method.
		Functions\expect( 'get_post' )->once()->with( $attachment_id )->andReturn( $attachment );
		$this->core->shouldReceive( 'get_instance' )->once()->andReturn( $this->core );
		$this->core->shouldReceive( 'get_media_credit_json' )->once()->with( $attachment )->andReturn( self::FIELDS );

		$this->assertSame( self::FIELDS['plaintext'], \Media_Credit::get_plaintext( $attachment_id, false ) );
	}

	/**
	 * Test ::get_plaintext.
	 *
	 * @covers ::get_plaintext
	 * @uses ::get_fields
	 */
	public function test_get_plaintext_fancy() {
		// Input data.
		$attachment_id = 55;

		// Internal data.
		$attachment = m::mock( \WP_Post::class );

		// Inlined ::get_fields method.
		Functions\expect( 'get_post' )->once()->with( $attachment_id )->andReturn( $attachment );
		$this->core->shouldReceive( 'get_instance' )->once()->andReturn( $this->core );
		$this->core->shouldReceive( 'get_media_credit_json' )->once()->with( $attachment )->andReturn( self::FIELDS );

		$this->assertSame( self::FIELDS['fancy'], \Media_Credit::get_plaintext( $attachment_id, true ) );
	}

	/**
	 * Test ::plaintext.
	 *
	 * @covers ::plaintext
	 * @uses ::get_plaintext
	 * @uses ::get_fields
	 */
	public function test_plaintext() {
		// Input data.
		$attachment_id = 55;

		// Internal data.
		$attachment = m::mock( \WP_Post::class );

		// Inlined ::get_fields method.
		Functions\expect( 'get_post' )->once()->with( $attachment_id )->andReturn( $attachment );
		$this->core->shouldReceive( 'get_instance' )->once()->andReturn( $this->core );
		$this->core->shouldReceive( 'get_media_credit_json' )->once()->with( $attachment )->andReturn( self::FIELDS );

		Functions\expect( 'esc_html' )->once()->with( m::type( 'string' ) )->andReturnFirstArg();

		$this->expectOutputString( self::FIELDS['fancy'] );
		$this->assertNull( \Media_Credit::plaintext( $attachment_id, true ) );
	}

	/**
	 * Test ::get_html.
	 *
	 * @covers ::get_html
	 * @uses ::get_fields
	 */
	public function test_get_html() {
		// Input data.
		$attachment_id = 55;

		// Internal data.
		$attachment = m::mock( \WP_Post::class );

		// Inlined ::get_fields method.
		Functions\expect( 'get_post' )->once()->with( $attachment_id )->andReturn( $attachment );
		$this->core->shouldReceive( 'get_instance' )->once()->andReturn( $this->core );
		$this->core->shouldReceive( 'get_media_credit_json' )->once()->with( $attachment )->andReturn( self::FIELDS );

		$this->assertSame( self::FIELDS['rendered'], \Media_Credit::get_html( $attachment_id ) );
	}

	/**
	 * Test ::html.
	 *
	 * @covers ::html
	 * @uses ::get_html
	 * @uses ::get_fields
	 */
	public function test_html() {
		// Input data.
		$attachment_id = 55;

		// Internal data.
		$attachment = m::mock( \WP_Post::class );

		// Inlined ::get_fields method.
		Functions\expect( 'get_post' )->once()->with( $attachment_id )->andReturn( $attachment );
		$this->core->shouldReceive( 'get_instance' )->once()->andReturn( $this->core );
		$this->core->shouldReceive( 'get_media_credit_json' )->once()->with( $attachment )->andReturn( self::FIELDS );

		$this->expectOutputString( self::FIELDS['rendered'] );
		$this->assertNull( \Media_Credit::html( $attachment_id, true ) );
	}

	/**
	 * Test ::get_freeform.
	 *
	 * @covers ::get_freeform
	 * @uses ::get_fields
	 */
	public function test_get_freeform() {
		// Input data.
		$attachment_id = 55;

		// Internal data.
		$attachment = m::mock( \WP_Post::class );

		// Inlined ::get_fields method.
		Functions\expect( 'get_post' )->once()->with( $attachment_id )->andReturn( $attachment );
		$this->core->shouldReceive( 'get_instance' )->once()->andReturn( $this->core );
		$this->core->shouldReceive( 'get_media_credit_json' )->once()->with( $attachment )->andReturn( self::FIELDS );

		$this->assertSame( self::FIELDS['raw']['freeform'], \Media_Credit::get_freeform( $attachment_id ) );
	}

	/**
	 * Test ::get_freeform.
	 *
	 * @covers ::get_freeform
	 * @uses ::get_fields
	 */
	public function test_get_freeform_empty_credit() {
		// Input data.
		$attachment_id = 55;

		// Internal data.
		$fields                    = self::FIELDS;
		$fields['raw']['freeform'] = Core::EMPTY_META_STRING;
		$attachment                = m::mock( \WP_Post::class );

		// Inlined ::get_fields method.
		Functions\expect( 'get_post' )->once()->with( $attachment_id )->andReturn( $attachment );
		$this->core->shouldReceive( 'get_instance' )->once()->andReturn( $this->core );
		$this->core->shouldReceive( 'get_media_credit_json' )->once()->with( $attachment )->andReturn( $fields );

		$this->assertSame( '', \Media_Credit::get_freeform( $attachment_id ) );
	}

	/**
	 * Test ::get_url.
	 *
	 * @covers ::get_url
	 * @uses ::get_fields
	 */
	public function test_get_url() {
		// Input data.
		$attachment_id = 55;

		// Internal data.
		$attachment = m::mock( \WP_Post::class );

		// Inlined ::get_fields method.
		Functions\expect( 'get_post' )->once()->with( $attachment_id )->andReturn( $attachment );
		$this->core->shouldReceive( 'get_instance' )->once()->andReturn( $this->core );
		$this->core->shouldReceive( 'get_media_credit_json' )->once()->with( $attachment )->andReturn( self::FIELDS );

		$this->assertSame( self::FIELDS['raw']['url'], \Media_Credit::get_url( $attachment_id ) );
	}

	/**
	 * Test ::get_flags.
	 *
	 * @covers ::get_flags
	 * @uses ::get_fields
	 */
	public function test_get_flags() {
		// Input data.
		$attachment_id = 55;

		// Internal data.
		$attachment = m::mock( \WP_Post::class );

		// Inlined ::get_fields method.
		Functions\expect( 'get_post' )->once()->with( $attachment_id )->andReturn( $attachment );
		$this->core->shouldReceive( 'get_instance' )->once()->andReturn( $this->core );
		$this->core->shouldReceive( 'get_media_credit_json' )->once()->with( $attachment )->andReturn( self::FIELDS );

		$this->assertSame( self::FIELDS['raw']['flags'], \Media_Credit::get_flags( $attachment_id ) );
	}

	/**
	 * Test ::get_html_by_user_id.
	 *
	 * @covers ::get_html_by_user_id
	 */
	public function test_get_html_by_user_id() {
		// Input data.
		$user_id = 4711;

		// Internal data.
		$result = self::FIELDS['rendered'];

		$this->core->shouldReceive( 'get_instance' )->once()->andReturn( $this->core );
		$this->core->shouldReceive( 'render_media_credit_html' )->once()->with( $user_id )->andReturn( $result );

		$this->assertSame( $result, \Media_Credit::get_html_by_user_id( $user_id ) );
	}

	/**
	 * Test ::html_by_user_id.
	 *
	 * @covers ::html_by_user_id
	 * @uses  ::get_html_by_user_id
	 */
	public function test_html_by_user_id() {
		// Input data.
		$user_id = 4711;

		// Internal data.
		$result = self::FIELDS['rendered'];

		$this->core->shouldReceive( 'get_instance' )->once()->andReturn( $this->core );
		$this->core->shouldReceive( 'render_media_credit_html' )->once()->with( $user_id )->andReturn( $result );

		$this->expectOutputString( self::FIELDS['rendered'] );
		$this->assertNull( \Media_Credit::html_by_user_id( $user_id ) );
	}

	/**
	 * Test ::author_media_and_posts.
	 *
	 * @covers ::author_media_and_posts
	 */
	public function test_author_media_and_posts() {
		// Input data.
		$query = [
			'my' => 'query',
		];

		// Expected result.
		$result = [
			1  => (object) [ 'foo' => 'bar' ],
			55 => (object) [ 'foo' => 'baz' ],
		];

		$this->core->shouldReceive( 'get_instance' )->once()->andReturn( $this->core );
		$this->core->shouldReceive( 'get_author_media_and_posts' )->once()->with( $query )->andReturn( $result );

		$this->assertSame( $result, \Media_Credit::author_media_and_posts( $query ) );
	}

	/**
	 * Test ::display_author_media.
	 *
	 * @covers ::display_author_media
	 * @uses ::author_media_and_posts
	 */
	public function test_display_author_media() {
		// Input data.
		$query = [
			'my' => 'query',
		];

		$merged_query                        = $query;
		$merged_query['sidebar']             = true;
		$merged_query['link_without_parent'] = false;
		$merged_query['header']              = null;
		$merged_query['number']              = 20;
		$merged_query['exclude_unattached']  = true;

		$final_query                  = $merged_query;
		$final_query['include_posts'] = false;

		// Internal data.
		$result = 'some HTML result';

		Functions\expect( 'wp_parse_args' )->once()->with( m::type( 'array' ), m::type( 'array' ) )->andReturn( $merged_query );

		$this->core->shouldReceive( 'get_instance' )->once()->andReturn( $this->core );
		$this->core->shouldReceive( 'get_author_media_and_posts' )->once()->with( $final_query )->andReturn( $result );

		$this->expectOutputString( 'AUTHOR_MEDIA' );
		$this->assertNull( \Media_Credit::display_author_media( $query ) );
	}

	/**
	 * Test ::display_author_media.
	 *
	 * @covers ::display_author_media
	 * @uses ::author_media_and_posts
	 */
	public function test_display_author_media_no_media() {
		// Input data.
		$query = [
			'my' => 'query',
		];

		$merged_query                        = $query;
		$merged_query['sidebar']             = true;
		$merged_query['link_without_parent'] = false;
		$merged_query['header']              = null;
		$merged_query['number']              = 20;
		$merged_query['exclude_unattached']  = true;

		$final_query                  = $merged_query;
		$final_query['include_posts'] = false;

		Functions\expect( 'wp_parse_args' )->once()->with( m::type( 'array' ), m::type( 'array' ) )->andReturn( $merged_query );

		$this->core->shouldReceive( 'get_instance' )->once()->andReturn( $this->core );
		$this->core->shouldReceive( 'get_author_media_and_posts' )->once()->with( $final_query )->andReturn( null );

		$this->expectOutputString( '' );
		$this->assertNull( \Media_Credit::display_author_media( $query ) );
	}

	/**
	 * Test ::get_fields.
	 *
	 * @covers ::get_fields
	 */
	public function test_get_fields() {
		// Input data.
		$attachment_id = 55;

		// Internal data.
		$attachment = m::mock( \WP_Post::class );

		// Inlined ::get_fields method.
		Functions\expect( 'get_post' )->once()->with( $attachment_id )->andReturn( $attachment );
		$this->core->shouldReceive( 'get_instance' )->once()->andReturn( $this->core );
		$this->core->shouldReceive( 'get_media_credit_json' )->once()->with( $attachment )->andReturn( self::FIELDS );

		$this->assertSame( self::FIELDS, \Media_Credit::get_fields( $attachment_id ) );
	}

	/**
	 * Test ::get_fields.
	 *
	 * @covers ::get_fields
	 */
	public function test_get_fields_invalid_attachment() {
		// Input data.
		$attachment_id = 55;

		// Inlined ::get_fields method.
		Functions\expect( 'get_post' )->once()->with( $attachment_id )->andReturn( null );
		$this->core->shouldReceive( 'get_instance' )->never();
		$this->core->shouldReceive( 'get_media_credit_json' )->never();

		$this->assertSame( Core::INVALID_MEDIA_CREDIT, \Media_Credit::get_fields( $attachment_id ) );
	}
}
