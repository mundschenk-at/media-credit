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

namespace Media_Credit\Tests\Media_Credit\Components;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use org\bovigo\vfs\vfsStream;

use Media_Credit\Tests\TestCase;

use Media_Credit\Components\REST_API;

use Media_Credit\Core;
use Media_Credit\Tools\Shortcodes_Filter;

/**
 * Media_Credit\Components\REST_API unit test.
 *
 * @coversDefaultClass \Media_Credit\Components\REST_API
 * @usesDefaultClass \Media_Credit\Components\REST_API
 *
 * @uses ::__construct
 */
class REST_API_Test extends TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var REST_API
	 */
	private $sut;

	/**
	 * Required helper object.
	 *
	 * @var Core
	 */
	private $core;

	/**
	 * Required helper object.
	 *
	 * @var Shortcodes_Filter
	 */
	private $shortcodes_filter;

	/**
	 * Class alias mock.
	 *
	 * @var WP_REST_Server
	 */
	private $rest_server;

	/**
	 * Class alias mock.
	 *
	 * @var WP_Rest_Response
	 */
	private $rest_response;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function set_up() {
		parent::set_up();

		// Set up virtual filesystem.
		$filesystem = [
			'plugin' => [
				'admin'       => [
					'partials' => [
						'media-credit-image-properties-tmpl.php' => 'IMAGE PROPERTY TEMPLATE',
					],
				],
			],
		];
		vfsStream::setup( 'root', null, $filesystem );
		set_include_path( 'vfs://root/' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_set_include_path

		m::getConfiguration()->setConstantsMap(
			[
				\WP_REST_Server::class => [
					'READABLE' => 'GET',
				],
			]
		);

		// We need to set up some REST API classes.
		$this->rest_server   = m::mock( 'alias:' . \WP_REST_Server::class );
		$this->rest_response = m::mock( 'overload:' . \WP_Rest_Response::class );

		$this->core              = m::mock( Core::class );
		$this->shortcodes_filter = m::mock( Shortcodes_Filter::class );

		$this->sut = m::mock( REST_API::class, [ $this->core, $this->shortcodes_filter ] )
			->makePartial()
			->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$core              = m::mock( Core::class );
		$shortcodes_filter = m::mock( Shortcodes_Filter::class );

		$sut = m::mock( REST_API::class )->makePartial();
		$sut->__construct( $core, $shortcodes_filter );

		$this->assert_attribute_same( $core, 'core', $sut );
		$this->assert_attribute_same( $shortcodes_filter, 'shortcodes_filter', $sut );
	}

	/**
	 * Tests ::run.
	 *
	 * @covers ::run
	 */
	public function test_run() {
		Actions\expectAdded( 'rest_api_init' )->once()->with( [ $this->sut, 'register_media_credit_fields' ] );
		Actions\expectAdded( 'rest_api_init' )->once()->with( [ $this->sut, 'register_custom_routes' ] );

		$this->assertNull( $this->sut->run() );
	}

	/**
	 * Tests ::register_media_credit_fields.
	 *
	 * @covers ::register_media_credit_fields
	 */
	public function test_register_media_credit_fields() {
		Functions\expect( 'register_rest_field' )->once()->with(
			'attachment',
			'media_credit',
			[
				'get_callback'    => [ $this->sut, 'prepare_media_credit_fields' ],
				'update_callback' => [ $this->sut, 'update_media_credit_fields' ],
				'auth_callback'   => [ $this->core, 'authorized_to_edit_media_credit' ],
				'schema'          => REST_API::SCHEMA_MEDIA_CREDIT_FIELDS,
			]
		);

		$this->assertNull( $this->sut->register_media_credit_fields() );
	}

	/**
	 * Tests ::register_custom_routes.
	 *
	 * @covers ::register_custom_routes
	 */
	public function test_register_custom_routes() {
		Functions\expect( 'register_rest_route' )->once()->with(
			REST_API::NAMESPACE_V1,
			'/replace_in_content',
			m::on(
				function( $args ) {
					$this->assertArrayHasKey( 'methods', $args );
					$this->assertArrayHasKey( 'callback', $args );
					$this->assertArrayHasKey( 'permission_callback', $args );
					$this->assertArrayHasKey( 'args', $args );

					$schema = $args['args'];
					if ( ! empty( $schema ) ) {
						$this->assertArrayHasKey( 'attachment_id', $schema );
						$this->assertArrayHasKey( 'content', $schema );
						$this->assertArrayHasKey( 'author_id', $schema );
						$this->assertArrayHasKey( 'freeform', $schema );
						$this->assertArrayHasKey( 'url', $schema );
						$this->assertArrayHasKey( 'nofollow', $schema );
					}

					return true;
				}
			)
		);

		$this->assertNull( $this->sut->register_custom_routes() );
	}

	/**
	 * Tests ::sanitize_text_field.
	 *
	 * @covers ::sanitize_text_field
	 */
	public function test_sanitize_text_field() {
		// Input data.
		$param   = 'my crazy value';
		$request = m::mock( \WP_REST_Request::class );
		$key     = 'my_key';

		// Expected result.
		$result = 'my sanitized value';

		Functions\expect( '_deprecated_function' )->once()->with( m::type( 'string' ), '4.2.0', 'sanitize_text_field' );
		Functions\expect( 'sanitize_text_field' )->once()->with( $param )->andReturn( $result );

		$this->assertSame( $result, $this->sut->sanitize_text_field( $param, $request, $key ) );
	}

	/**
	 * Tests ::prepare_media_credit_fields.
	 *
	 * @covers ::prepare_media_credit_fields
	 */
	public function test_prepare_media_credit_fields() {
		// Input data.
		$post_id    = 4711;
		$post       = [
			'id'  => $post_id,
			'foo' => 'bar',
		];
		$field_name = 'media_credit';
		$request    = m::mock( \WP_REST_Request::class );

		// Intermediary data.
		$attachment = m::mock( \WP_Post::class );

		// Expected result.
		$result = [
			'fake'   => 'json',
			'fields' => 'galore',
		];

		Functions\expect( 'get_post' )->once()->with( $post_id )->andReturn( $attachment );

		$this->core->shouldReceive( 'get_media_credit_json' )->once()->with( $attachment )->andReturn( $result );

		$this->assertSame( $result, $this->sut->prepare_media_credit_fields( $post, $field_name, $request ) );
	}

	/**
	 * Tests ::prepare_media_credit_fields.
	 *
	 * @covers ::prepare_media_credit_fields
	 */
	public function test_prepare_media_credit_fields_invalid_attachment() {
		// Input data.
		$post_id    = 4711;
		$post       = [
			'id'  => $post_id,
			'foo' => 'bar',
		];
		$field_name = 'media_credit';
		$request    = m::mock( \WP_REST_Request::class );

		Functions\expect( 'get_post' )->once()->with( $post_id )->andReturnNull();

		$this->core->shouldReceive( 'get_media_credit_json' )->never();

		$this->assertNull( $this->sut->prepare_media_credit_fields( $post, $field_name, $request ) );
	}

	/**
	 * Tests ::prepare_media_credit_fields.
	 *
	 * @covers ::prepare_media_credit_fields
	 */
	public function test_prepare_media_credit_fields_missing_id() {
		// Input data.
		$post       = [
			'foo' => 'bar',
		];
		$field_name = 'media_credit';
		$request    = m::mock( \WP_REST_Request::class );

		Functions\expect( 'get_post' )->never();

		$this->core->shouldReceive( 'get_media_credit_json' )->never();

		$this->assertNull( $this->sut->prepare_media_credit_fields( $post, $field_name, $request ) );
	}

	/**
	 * Tests ::update_media_credit_fields.
	 *
	 * @covers ::update_media_credit_fields
	 */
	public function test_update_media_credit_fields() {
		// Input data.
		$value = [
			'raw' => [
				'credit' => 'data',
			],
			'foo' => 'bar',
		];
		$post  = m::mock( \WP_Post::class );

		$this->core->shouldReceive( 'update_media_credit_json' )->once()->with( $post, $value['raw'] )->andReturnTrue();

		$this->assertTrue( $this->sut->update_media_credit_fields( $value, $post ) );
	}

	/**
	 * Tests ::update_media_credit_fields.
	 *
	 * @covers ::update_media_credit_fields
	 */
	public function test_update_media_credit_fields_invalid() {
		// Input data.
		$value = [
			'credit' => 'data',
			'foo'    => 'bar',
		];
		$post  = m::mock( \WP_Post::class );

		$this->core->shouldReceive( 'update_media_credit_json' )->never();

		$this->assertFalse( $this->sut->update_media_credit_fields( $value, $post ) );
	}

	/**
	 * Tests ::rest_filter_content.
	 *
	 * @covers ::rest_filter_content
	 */
	public function test_rest_filter_content() {
		// Input data.
		$request = m::mock( \WP_REST_Request::class );

		// Intermediary data.
		$params           = [
			'content'       => 'My original HTML content',
			'attachment_id' => 4711,
			'author_id'     => 42,
			'freeform'      => 'My freeform credit',
			'url'           => 'https://example.net/credit/url',
			'nofollow'      => true,
		];
		$filtered_content = 'My filtered HTML content';

		$request->shouldReceive( 'get_params' )->once()->withNoArgs()->andReturn( $params );
		$this->shortcodes_filter->shouldReceive( 'update_changed_media_credits' )->once()->with(
			$params['content'],
			$params['attachment_id'],
			$params['author_id'],
			$params['freeform'],
			$params['url'],
			$params['nofollow']
		)->andReturn( $filtered_content );

		$this->rest_response->shouldReceive( '__construct' )->once()->with( $filtered_content );

		$this->assertInstanceOf( \WP_REST_Response::class, $this->sut->rest_filter_content( $request ) );
	}
}
