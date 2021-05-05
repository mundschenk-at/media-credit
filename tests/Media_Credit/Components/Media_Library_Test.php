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

use Media_Credit\Components\Media_Library;

use Media_Credit\Core;
use Media_Credit\Settings;
use Media_Credit\Tools\Author_Query;

/**
 * Media_Credit\Components\Media_Library unit test.
 *
 * @coversDefaultClass \Media_Credit\Components\Media_Library
 * @usesDefaultClass \Media_Credit\Components\Media_Library
 *
 * @uses ::__construct
 */
class Media_Library_Test extends TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Media_Library
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
	 * @var Settings
	 */
	private $settings;

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
						'media-credit-attachment-details-tmpl.php' => 'ATTACHMENT DETAILS TEMPLATE',
					],
				],
			],
		];
		vfsStream::setup( 'root', null, $filesystem );
		set_include_path( 'vfs://root/' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_set_include_path

		$this->core         = m::mock( Core::class );
		$this->settings     = m::mock( Settings::class );
		$this->author_query = m::mock( Author_Query::class );

		$this->sut = m::mock( Media_Library::class, [ $this->core, $this->settings, $this->author_query ] )
			->makePartial()
			->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$core         = m::mock( Core::class );
		$settings     = m::mock( Settings::class );
		$author_query = m::mock( Author_Query::class );

		$sut = m::mock( Media_Library::class )->makePartial();
		$sut->__construct( $core, $settings, $author_query );

		$this->assert_attribute_same( $core, 'core', $sut );
		$this->assert_attribute_same( $settings, 'settings', $sut );
		$this->assert_attribute_same( $author_query, 'author_query', $sut );
	}

	/**
	 * Tests ::run.
	 *
	 * @covers ::run
	 */
	public function test_run() {
		Actions\expectAdded( 'admin_init' )->once()->with( [ $this->sut, 'admin_init' ] );
		Actions\expectAdded( 'admin_enqueue_scripts' )->once()->with( [ $this->sut, 'enqueue_scripts_and_styles' ] );

		Actions\expectAdded( 'add_attachment' )->once()->with( [ $this->sut, 'add_default_media_credit_for_attachment' ] );

		Actions\expectAdded( 'print_media_templates' )->once()->with( [ $this->sut, 'attachment_details_template' ] );
		Filters\expectAdded( 'wp_prepare_attachment_for_js' )->once()->with( [ $this->sut, 'prepare_attachment_media_credit_for_js' ], 10, 2 );
		Filters\expectAdded( 'attachment_fields_to_edit' )->once()->with( [ $this->sut, 'add_media_credit_fields' ], 10, 2 );
		Filters\expectAdded( 'attachment_fields_to_save' )->once()->with( [ $this->sut, 'save_media_credit_fields' ], 10, 2 );

		Actions\expectAdded( 'wp_ajax_crop_image_pre_save' )->once()->with( [ $this->sut, 'store_cropped_image_parent' ], 10, 2 );
		Filters\expectAdded( 'wp_ajax_cropped_attachment_metadata' )->once()->with( [ $this->sut, 'add_credit_to_cropped_attachment_metadata' ] );
		Filters\expectAdded( 'wp_header_image_attachment_metadata' )->once()->with( [ $this->sut, 'add_credit_to_cropped_header_metadata' ] );

		Filters\expectAdded( 'wp_generate_attachment_metadata' )->once()->with( [ $this->sut, 'maybe_add_credit_from_exif_metadata' ], 10, 3 );

		Filters\expectAdded( 'wp_update_attachment_metadata' )->once()->with( [ $this->sut, 'maybe_update_image_credit' ], 10, 2 );

		$this->assertNull( $this->sut->run() );
	}

	/**
	 * Provides data for testing enqueue_scripts_and_styles.
	 *
	 * @return array
	 */
	public function provide_enqueue_scripts_and_styles_data() {
		return [
			[ false, false ],
			[ true, false ],
			[ false, true ],
			[ true, true ],
		];
	}

	/**
	 * Tests ::enqueue_scripts_and_styles.
	 *
	 * @covers ::enqueue_scripts_and_styles
	 *
	 * @dataProvider provide_enqueue_scripts_and_styles_data
	 *
	 * @param  bool $is_legacy_media_page Optional. Whether the test should be run on a legacy media page. Default false.
	 * @param  bool $enqueued_media       Optional. Whether the WP media scripts were enqueued. Default false.
	 */
	public function test_enqueue_scripts_and_styles( $is_legacy_media_page = false, $enqueued_media = false ) {
		$url     = 'https://exmample.org/plugin/base/url';
		$version = '47.1.1';

		Functions\expect( 'plugin_dir_url' )->once()->with( \MEDIA_CREDIT_PLUGIN_FILE )->andReturn( $url );
		$this->settings->shouldReceive( 'get_version' )->once()->withNoArgs()->andReturn( $version );

		Functions\expect( 'wp_register_script' )->once()->with( 'media-credit-bootstrap', m::type( 'string' ), m::type( 'array' ), $version, true )->andReturnTrue();
		Functions\expect( 'wp_register_script' )->once()->with( 'media-credit-legacy-autocomplete', m::type( 'string' ), m::type( 'array' ), $version, true )->andReturnTrue();
		Functions\expect( 'wp_register_script' )->once()->with( 'media-credit-attachment-details', m::type( 'string' ), m::type( 'array' ), $version, true )->andReturnTrue();

		Functions\expect( 'wp_register_style' )->once()->with( 'media-credit-legacy-edit-media-style', m::type( 'string' ), m::type( 'array' ), $version, 'screen' )->andReturnTrue();
		Functions\expect( 'wp_register_style' )->once()->with( 'media-credit-attachment-details-style', m::type( 'string' ), m::type( 'array' ), $version, 'screen' )->andReturnTrue();

		$this->sut->shouldReceive( 'add_inline_script_data' )->once()->withNoArgs();
		$this->sut->shouldReceive( 'is_legacy_media_edit_page' )->once()->withNoArgs()->andReturn( $is_legacy_media_page );

		Functions\expect( 'wp_enqueue_script' )->times( (int) $is_legacy_media_page )->with( 'media-credit-legacy-autocomplete' );
		Functions\expect( 'wp_enqueue_style' )->times( (int) $is_legacy_media_page )->with( 'media-credit-legacy-edit-media-style' );

		Functions\expect( 'did_action' )->once()->with( 'wp_enqueue_media' )->andReturn( $enqueued_media );

		Functions\expect( 'wp_enqueue_script' )->times( (int) $enqueued_media )->with( 'media-credit-attachment-details' );
		Functions\expect( 'wp_enqueue_style' )->times( (int) $enqueued_media )->with( 'media-credit-attachment-details-style' );

		$this->assertNull( $this->sut->enqueue_scripts_and_styles() );
	}

	/**
	 * Tests ::attachment_details_template.
	 *
	 * @covers ::attachment_details_template
	 */
	public function test_attachment_details_template() {
		$this->expectOutputString( 'ATTACHMENT DETAILS TEMPLATE' );
		$this->assertNull( $this->sut->attachment_details_template() );
	}

	/**
	 * Tests ::add_inline_script_data.
	 *
	 * @covers ::add_inline_script_data
	 */
	public function test_add_inline_script_data() {
		$authors  = [
			(object) [
				'ID'           => 47,
				'display_name' => 'Author One',
			],
			(object) [
				'ID'           => 11,
				'display_name' => 'Author Due',
			],
			(object) [
				'ID'           => 42,
				'display_name' => 'Author Tri',
			],
		];
		$settings = [
			Settings::SEPARATOR         => 'My Separator',
			Settings::ORGANIZATION      => 'My Organization',
			Settings::NO_DEFAULT_CREDIT => false,
		];

		$this->author_query->shouldReceive( 'get_authors' )->once()->withNoArgs()->andReturn( $authors );
		$this->settings->shouldReceive( 'get_all_settings' )->once()->withNoArgs()->andReturn( $settings );

		Functions\expect( 'wp_json_encode' )->once()->with(
			m::on(
				function( $authors ) {
					return ! empty( $authors[47] ) && ! empty( $authors[11] ) && ! empty( $authors[42] );
				}
			)
		)->andReturn( 'JSON-encoded authors' );
		Functions\expect( 'wp_json_encode' )->once()->with(
			m::on(
				function( $options ) {
					return isset( $options['separator'] ) && isset( $options['organization'] ) && isset( $options['noDefaultCredit'] );
				}
			)
		)->andReturn( 'JSON-encoded options' );

		Functions\expect( 'wp_add_inline_script' )->once()->with( 'media-credit-bootstrap', m::type( 'string' ), 'after' )->andReturnTrue();

		$this->assertNull( $this->sut->add_inline_script_data() );
	}

	/**
	 * Tests ::admin_init.
	 *
	 * @covers ::admin_init
	 */
	public function test_admin_init() {
		Filters\expectAdded( 'the_author' )->once()->with( [ $this->sut, 'filter_the_author' ] );

		$this->assertNull( $this->sut->admin_init() );
	}

	/**
	 * Tests ::filter_the_author.
	 *
	 * @covers ::filter_the_author
	 */
	public function test_filter_the_author() {
		// Input data.
		$display_name = 'Jane Doe III';

		// Intermediary data.
		$attachment            = m::mock( \WP_Post::class );
		$attachment->post_type = 'attachment';
		$fields                = [
			'foo'       => 'bar',
			'plaintext' => 'Jane Doe\'s Credit Line',
		];

		// Expected result.
		$result = $fields['plaintext'];

		Functions\expect( 'get_post' )->once()->withNoArgs()->andReturn( $attachment );
		$this->core->shouldReceive( 'get_media_credit_json' )->once()->with( $attachment )->andReturn( $fields );

		$this->assertSame( $result, $this->sut->filter_the_author( $display_name ) );
	}

	/**
	 * Tests ::filter_the_author.
	 *
	 * @covers ::filter_the_author
	 */
	public function test_filter_the_author_invalid_post_type() {
		// Input data.
		$display_name = 'Jane Doe III';

		// Intermediary data.
		$attachment            = m::mock( \WP_Post::class );
		$attachment->post_type = 'page';

		Functions\expect( 'get_post' )->once()->withNoArgs()->andReturn( $attachment );
		$this->core->shouldReceive( 'get_media_credit_json' )->never();

		$this->assertSame( $display_name, $this->sut->filter_the_author( $display_name ) );
	}

	/**
	 * Tests ::filter_the_author.
	 *
	 * @covers ::filter_the_author
	 */
	public function test_filter_the_author_invalid_post() {
		// Input data.
		$display_name = 'Jane Doe III';

		Functions\expect( 'get_post' )->once()->withNoArgs()->andReturnNull();
		$this->core->shouldReceive( 'get_media_credit_json' )->never();

		$this->assertSame( $display_name, $this->sut->filter_the_author( $display_name ) );
	}

	/**
	 * Provides data for testing is_legacy_media_edit_page.
	 *
	 * @return array
	 */
	public function provide_is_legacy_media_edit_page_data() {
		return [
			[ true, 'post', 'attachment' ],
			[ false, 'post', 'post' ],
			[ false, 'edit', 'attachment' ],
			[ false, 'foo', 'bar' ],
			[ false, null, null ],
		];
	}

	/**
	 * Tests ::is_legacy_media_edit_page.
	 *
	 * @covers ::is_legacy_media_edit_page
	 *
	 * @dataProvider provide_is_legacy_media_edit_page_data
	 *
	 * @param bool        $result The expected result.
	 * @param string|null $base   The base type of the screen.
	 * @param string|null $id     The unique ID of the screen.
	 */
	public function test_is_legacy_media_edit_page( bool $result, $base, $id ) {
		if ( ! empty( $base ) && ! empty( $id ) ) {
			$screen       = m::mock( \WP_Screen::class );
			$screen->base = $base;
			$screen->id   = $id;
		} else {
			$screen = null;
		}

		Functions\expect( 'get_current_screen' )->once()->withNoArgs()->andReturn( $screen );

		$this->assertSame( $result, $this->sut->is_legacy_media_edit_page() );
	}

	/**
	 * Tests ::prepare_attachment_media_credit_for_js.
	 *
	 * @covers ::prepare_attachment_media_credit_for_js
	 */
	public function test_prepare_attachment_media_credit_for_js() {
		// Input data.
		$response   = [
			'id'  => 42,
			'foo' => 'bar',
		];
		$attachment = m::mock( \WP_Post::class );

		// Intermediary data.
		$credit       = [
			'rendered'  => 'My rendered credit (HTML)',
			'plaintext' => 'My plaintext credit',
			'fancy'     => 'My fancy credit',
			'raw'       => [
				'user_id'  => 4711,
				'freeform' => 'My freeform credit',
				'url'      => 'https://example.org/some/url',
				'flags'    => [
					'nofollow' => true,
				],
			],
		];
		$nonce_save   = 1234567;
		$nonce_update = 9876532;

		// Set up expectations.
		$this->core->shouldReceive( 'get_media_credit_json' )->once()->with( $attachment )->andReturn( $credit );

		Functions\expect( 'get_the_author_meta' )->atMost()->once()->with( 'display_name', m::type( 'int' ) )->andReturn( 'Jane Doe III' );
		Functions\expect( 'wp_create_nonce' )->once()->with( "save-attachment-{$response['id']}-media-credit" )->andReturn( $nonce_save );
		Functions\expect( 'wp_create_nonce' )->once()->with( "update-attachment-{$response['id']}-media-credit-in-editor" )->andReturn( $nonce_update );

		$this->sut->shouldReceive( 'get_placeholder_text' )->once()->with( $attachment )->andReturn( 'My custom placeholder' );

		// Verify result.
		$result = $this->sut->prepare_attachment_media_credit_for_js( $response, $attachment );

		$this->assertArrayHasKey( 'id', $result );
		$this->assertArrayHasKey( 'foo', $result );
		$this->assertArrayHasKey( 'mediaCreditText', $result );
		$this->assertArrayHasKey( 'mediaCreditLink', $result );
		$this->assertArrayHasKey( 'mediaCreditAuthorID', $result );
		$this->assertArrayHasKey( 'mediaCreditAuthorDisplay', $result );
		$this->assertArrayHasKey( 'mediaCreditAuthorID', $result );
		$this->assertArrayHasKey( 'mediaCreditNoFollow', $result );
		$this->assertArrayHasKey( 'mediaCredit', $result );
		$this->assertArrayHasKey( 'placeholder', $result['mediaCredit'] );
		$this->assertArrayHasKey( 'placeholder', $result['mediaCredit'] );
		$this->assertArrayHasKey( 'nonces', $result );
		$this->assertArrayHasKey( 'mediaCredit', $result['nonces'] );
		$this->assertArrayHasKey( 'update', $result['nonces']['mediaCredit'] );
		$this->assertArrayHasKey( 'content', $result['nonces']['mediaCredit'] );
	}

	/**
	 * Tests ::add_media_credit_fields.
	 *
	 * @covers ::add_media_credit_fields
	 */
	public function test_add_media_credit_fields() {
		// Input data.
		$fields                  = [
			'foobar' => [
				'label' => 'foo',
				'input' => 'text',
				'value' => 'something',
				'helps' => 'some help text',
			],
		];
		$attachment              = m::mock( \WP_Post::class );
		$attachment->ID          = 42;
		$attachment->post_author = 4711;

		// Intermediary data.
		$credit = [
			'rendered'  => 'My rendered credit (HTML)',
			'plaintext' => 'My plaintext credit',
			'fancy'     => 'My fancy credit',
			'raw'       => [
				'user_id'  => 4711,
				'freeform' => 'My freeform credit',
				'url'      => 'https://example.org/some/url',
				'flags'    => [
					'nofollow' => true,
				],
			],
		];

		// Set up expectations.
		$this->core->shouldReceive( 'get_media_credit_json' )->once()->with( $attachment )->andReturn( $credit );
		$this->settings->shouldReceive( 'get' )->once()->with( Settings::NO_DEFAULT_CREDIT )->andReturn( true );
		$this->sut->shouldReceive( 'get_placeholder_text' )->once()->with( $attachment )->andReturn( 'Some placeholder' );

		Functions\when( '__' )->returnArg();
		Functions\when( 'esc_attr' )->returnArg();
		Functions\when( 'esc_url' )->returnArg();
		Functions\expect( 'checked' )->once()->with( m::type( 'bool' ), true, false )->andReturn( " checked='checked'" );

		// Verify result.
		$result = $this->sut->add_media_credit_fields( $fields, $attachment );

		// Original fields.
		$this->assertArrayHasKey( 'foobar', $result );
		$this->assertSame( $fields['foobar'], $result['foobar'] );

		// New fields.
		$expected_fields = [
			'media-credit',
			'media-credit-url',
			'media-credit-data',
			'media-credit-hidden',
		];

		foreach ( $expected_fields as $field ) {
			$this->assertArrayHasKey( $field, $result );
			$this->assertArrayHasKey( 'label', $result[ $field ] );
			$this->assertArrayHasKey( 'input', $result[ $field ] );
			$this->assertArrayHasKey( 'html', $result[ $field ] );
			$this->assertArrayHasKey( 'show_in_edit', $result[ $field ] );
			$this->assertArrayHasKey( 'show_in_modal', $result[ $field ] );
		}
	}

	/**
	 * Tests ::save_media_credit_fields.
	 *
	 * @covers ::save_media_credit_fields
	 */
	public function test_save_media_credit_fields() {
		// Input data.
		$post_id    = 42;
		$user_id    = 4711;
		$post       = [
			'ID'          => $post_id,
			'foo'         => 'bar',
			'post_author' => $user_id,
		];
		$freeform   = '';
		$url        = 'https://example.org';
		$nofollow   = true;
		$attachment = [
			'media-credit-hidden'   => $user_id,
			'media-credit'          => $freeform,
			'media-credit-url'      => $url,
			'media-credit-nofollow' => $nofollow,
		];

		// Intermediary data.
		$post_object = m::mock( \WP_Post::class );
		$fields      = [
			'user_id'  => $user_id,
			'freeform' => $freeform,
			'url'      => $url,
			'flags'    => [
				'nofollow' => $nofollow,
			],
		];

		// Set up expectations.
		Functions\expect( 'get_post' )->once()->with( $post_id )->andReturn( $post_object );
		$this->core->shouldReceive( 'update_media_credit_json' )->once()->with( $post_object, $fields );

		// Verify result.
		$new_post = $this->sut->save_media_credit_fields( $post, $attachment );
		$this->assertArrayNotHasKey( 'post_author', $new_post );
		$this->assertSame( $new_post['ID'], $post['ID'] );
		$this->assertSame( $new_post['foo'], $post['foo'] );
	}

	/**
	 * Tests ::save_media_credit_fields.
	 *
	 * @covers ::save_media_credit_fields
	 */
	public function test_save_media_credit_fields_invalid_post_id() {
		// Input data.
		$post_id    = 42;
		$user_id    = 4711;
		$post       = [
			'ID'          => $post_id,
			'foo'         => 'bar',
			'post_author' => $user_id,
		];
		$freeform   = '';
		$url        = 'https://example.org';
		$nofollow   = true;
		$attachment = [
			'media-credit-hidden'   => $user_id,
			'media-credit'          => $freeform,
			'media-credit-url'      => $url,
			'media-credit-nofollow' => $nofollow,
		];

		// Set up expectations.
		Functions\expect( 'get_post' )->once()->with( $post_id )->andReturn( null );
		$this->core->shouldReceive( 'update_media_credit_json' )->never();

		// Verify result.
		$new_post = $this->sut->save_media_credit_fields( $post, $attachment );
		$this->assertArrayNotHasKey( 'post_author', $new_post );
		$this->assertSame( $new_post['ID'], $post['ID'] );
		$this->assertSame( $new_post['foo'], $post['foo'] );
	}

	/**
	 * Tests ::save_media_credit_fields.
	 *
	 * @covers ::save_media_credit_fields
	 */
	public function test_save_media_credit_fields_freeform() {
		// Input data.
		$post_id    = 42;
		$user_id    = 4711;
		$post       = [
			'ID'          => $post_id,
			'foo'         => 'bar',
			'post_author' => $user_id,
		];
		$freeform   = 'My freeform credit';
		$url        = 'https://example.org';
		$nofollow   = true;
		$attachment = [
			'media-credit-hidden'   => '',
			'media-credit'          => $freeform,
			'media-credit-url'      => $url,
			'media-credit-nofollow' => $nofollow,
		];

		// Intermediary data.
		$post_object = m::mock( \WP_Post::class );
		$fields      = [
			'user_id'  => '',
			'freeform' => $freeform,
			'url'      => $url,
			'flags'    => [
				'nofollow' => $nofollow,
			],
		];

		// Set up expectations.
		Functions\expect( 'get_post' )->once()->with( $post_id )->andReturn( $post_object );
		$this->core->shouldReceive( 'update_media_credit_json' )->once()->with( $post_object, $fields );

		// Verify result.
		$this->assertSame( $post, $this->sut->save_media_credit_fields( $post, $attachment ) );
	}

	/**
	 * Tests ::get_placeholder_text.
	 *
	 * @covers ::get_placeholder_text
	 */
	public function test_get_placeholder_text() {
		$attachment = m::mock( \WP_Post::class );

		$filtered_placeholder = 'Filtered placeholder text';

		Functions\expect( '__' )->once()->with( m::type( 'string' ), 'media-credit' )->andReturnArg( 0 );
		Filters\expectApplied( 'media_credit_placeholder_text' )->once()->with( m::type( 'string' ), $attachment )->andReturn( $filtered_placeholder );

		$this->assertSame( $filtered_placeholder, $this->sut->get_placeholder_text( $attachment ) );
	}

	/**
	 * Tests ::add_default_media_credit_for_attachment.
	 *
	 * @covers ::add_default_media_credit_for_attachment
	 */
	public function test_add_default_media_credit_for_attachment() {
		// Input data.
		$post_id = 42;

		// Intermediary data.
		$attachment = m::mock( \WP_Post::class );
		$default    = 'My custom default credit';

		// Set up expectations.
		Functions\expect( 'get_post' )->once()->with( $post_id )->andReturn( $attachment );
		$this->sut->shouldReceive( 'get_default_credit' )->once()->with( $attachment )->andReturn( $default );
		$this->core->shouldReceive( 'update_media_credit_json' )->once()->with( $attachment, [ 'freeform' => $default ] );

		$this->assertNull( $this->sut->add_default_media_credit_for_attachment( $post_id ) );
	}

	/**
	 * Tests ::add_default_media_credit_for_attachment.
	 *
	 * @covers ::add_default_media_credit_for_attachment
	 */
	public function test_add_default_media_credit_for_attachment_empty_default() {
		// Input data.
		$post_id = 42;

		// Intermediary data.
		$attachment = m::mock( \WP_Post::class );
		$default    = '';

		// Set up expectations.
		Functions\expect( 'get_post' )->once()->with( $post_id )->andReturn( $attachment );
		$this->sut->shouldReceive( 'get_default_credit' )->once()->with( $attachment )->andReturn( $default );
		$this->core->shouldReceive( 'update_media_credit_json' )->never();

		$this->assertNull( $this->sut->add_default_media_credit_for_attachment( $post_id ) );
	}

	/**
	 * Tests ::add_default_media_credit_for_attachment.
	 *
	 * @covers ::add_default_media_credit_for_attachment
	 */
	public function test_add_default_media_credit_for_attachment_invalid_post_id() {
		// Input data.
		$post_id = 42;

		// Set up expectations.
		Functions\expect( 'get_post' )->once()->with( $post_id )->andReturn( null );
		$this->sut->shouldReceive( 'get_default_credit' )->never();
		$this->core->shouldReceive( 'update_media_credit_json' )->never();

		$this->assertNull( $this->sut->add_default_media_credit_for_attachment( $post_id ) );
	}

	/**
	 * Tests ::get_default_credit.
	 *
	 * @covers ::get_default_credit
	 */
	public function test_get_default_credit() {
		$attachment = m::mock( \WP_Post::class );

		$raw_default      = ' My custom default credit ';
		$trimmed_default  = \trim( $raw_default );
		$filtered_default = 'My filtered custom default credit';

		$this->settings->shouldReceive( 'get' )->once()->with( Settings::CUSTOM_DEFAULT_CREDIT )->andReturn( $raw_default );
		Filters\expectApplied( 'media_credit_new_attachment_default' )->once()->with( $trimmed_default, $attachment )->andReturn( $filtered_default );

		$this->assertSame( $filtered_default, $this->sut->get_default_credit( $attachment ) );
	}

	/**
	 * Tests ::store_cropped_image_parent.
	 *
	 * @covers ::store_cropped_image_parent
	 */
	public function test_store_cropped_image_parent() {
		// Input data.
		$attachment_id = 42;
		$context       = 'customizer-control'; // Ignored.

		$this->assertNull( $this->sut->store_cropped_image_parent( $context, $attachment_id ) );

		$this->assert_attribute_same( $attachment_id, 'cropped_parent_id', $this->sut );
	}

	/**
	 * Tests ::add_credit_to_cropped_header_metadata.
	 *
	 * @covers ::add_credit_to_cropped_header_metadata
	 */
	public function test_add_credit_to_cropped_header_metadata() {
		// Input data.
		$parent_id = 4711;
		$data      = [
			'attachment_parent' => $parent_id,
			'foo'               => 'bar',
		];

		// Expected result.
		$new_data = [
			'fake credit' => 'data',
		];

		$this->sut->shouldReceive( 'add_credit_to_metadata' )->once()->with( $data, $parent_id )->andReturn( $new_data );

		$this->assertSame( $new_data, $this->sut->add_credit_to_cropped_header_metadata( $data ) );
	}

	/**
	 * Tests ::add_credit_to_cropped_attachment_metadata.
	 *
	 * @covers ::add_credit_to_cropped_attachment_metadata
	 */
	public function test_add_credit_to_cropped_attachment_metadata() {
		// Input data.
		$parent_id = 4711;
		$data      = [
			'foo' => 'bar',
		];

		// Expected result.
		$new_data = [
			'fake credit' => 'data',
		];

		$this->set_value( $this->sut, 'cropped_parent_id', $parent_id );

		$this->sut->shouldReceive( 'add_credit_to_metadata' )->once()->with( $data, $parent_id )->andReturn( $new_data );

		$this->assertSame( $new_data, $this->sut->add_credit_to_cropped_attachment_metadata( $data ) );

		$this->assert_attribute_same( null, 'cropped_parent_id', $this->sut );
	}

	/**
	 * Tests ::add_credit_to_metadata.
	 *
	 * @covers ::add_credit_to_metadata
	 */
	public function test_add_credit_to_metadata() {
		// Input data.
		$parent_id = 4711;
		$data      = [
			'foo' => 'bar',
		];

		// Intermediary data.
		$credit = [
			'fake credit' => 'data',
		];
		$json   = [
			'rendered' => 'My rendered credit',
			'raw'      => $credit,
		];
		$parent = m::mock( \WP_Post::class );

		// Expected result.
		$new_data = [
			'foo'          => 'bar',
			'media_credit' => $credit,
		];

		// Set up expectations.
		Functions\expect( 'get_post' )->once()->with( $parent_id )->andReturn( $parent );
		$this->core->shouldReceive( 'get_media_credit_json' )->once()->with( $parent )->andReturn( $json );

		$this->assertSame( $new_data, $this->sut->add_credit_to_metadata( $data, $parent_id ) );
	}

	/**
	 * Tests ::add_credit_to_metadata.
	 *
	 * @covers ::add_credit_to_metadata
	 */
	public function test_add_credit_to_metadata_invalid_json() {
		// Input data.
		$parent_id = 4711;
		$data      = [
			'foo' => 'bar',
		];

		// Intermediary data.
		$credit = [
			'fake credit' => 'data',
		];
		$json   = [
			'rendered' => 'My rendered credit',
		];
		$parent = m::mock( \WP_Post::class );

		// Set up expectations.
		Functions\expect( 'get_post' )->once()->with( $parent_id )->andReturn( $parent );
		$this->core->shouldReceive( 'get_media_credit_json' )->once()->with( $parent )->andReturn( $json );

		$this->assertSame( $data, $this->sut->add_credit_to_metadata( $data, $parent_id ) );
	}

	/**
	 * Tests ::add_credit_to_metadata.
	 *
	 * @covers ::add_credit_to_metadata
	 */
	public function test_add_credit_to_metadata_invalid_parent() {
		// Input data.
		$parent_id = 4711;
		$data      = [
			'foo' => 'bar',
		];

		// Set up expectations.
		Functions\expect( 'get_post' )->once()->with( $parent_id )->andReturn( null );
		$this->core->shouldReceive( 'get_media_credit_json' )->never();

		$this->assertSame( $data, $this->sut->add_credit_to_metadata( $data, $parent_id ) );
	}

	/**
	 * Provides data for testing maybe_add_credit_from_exif_metadata.
	 *
	 * @return array
	 */
	public function provide_maybe_add_credit_from_exif_metadata_data() {
		return [
			// No image_meta data.
			[
				[
					'foo' => 'bar',
				],
				0,
				[
					'foo' => 'bar',
				],
			],

			// No copyright data.
			[
				[
					'image_meta' => [
						'foo' => 'bar',
					],
				],
				0,
				[
					'image_meta' => [
						'foo' => 'bar',
					],
				],
			],

			// 'credit' field, user credit.
			[
				[
					'foo'        => 'bar',
					'image_meta' => [
						'bar'    => 'foo',
						'credit' => 'My Credit',
					],
				],
				42,
				[
					'foo'          => 'bar',
					'image_meta'   => [
						'bar'    => 'foo',
						'credit' => 'My Credit',
					],
					'media_credit' => [
						'user_id' => 42,
					],
				],
			],

			// 'credit' field, freeform credit.
			[
				[
					'foo'        => 'bar',
					'image_meta' => [
						'bar'    => 'foo',
						'credit' => 'My Credit',
					],
				],
				0,
				[
					'foo'          => 'bar',
					'image_meta'   => [
						'bar'    => 'foo',
						'credit' => 'My Credit',
					],
					'media_credit' => [
						'freeform' => 'My Credit',
					],
				],
			],

			// 'copyright' field with extra whitespace, user credit.
			[
				[
					'foo'        => 'bar',
					'image_meta' => [
						'bar'       => 'foo',
						'copyright' => 'My Credit ',
					],
				],
				42,
				[
					'foo'          => 'bar',
					'image_meta'   => [
						'bar'       => 'foo',
						'copyright' => 'My Credit ',
					],
					'media_credit' => [
						'user_id' => 42,
					],
				],
			],

			// 'credit' field with extra whitespace, freeform credit.
			[
				[
					'foo'        => 'bar',
					'image_meta' => [
						'bar'       => 'foo',
						'copyright' => ' My Credit ',
					],
				],
				0,
				[
					'foo'          => 'bar',
					'image_meta'   => [
						'bar'       => 'foo',
						'copyright' => ' My Credit ',
					],
					'media_credit' => [
						'freeform' => 'My Credit',
					],
				],
			],
		];
	}

	/**
	 * Tests ::maybe_add_credit_from_exif_metadata.
	 *
	 * @covers ::maybe_add_credit_from_exif_metadata
	 *
	 * @dataProvider provide_maybe_add_credit_from_exif_metadata_data
	 *
	 * @param array $data    The input $data array.
	 * @param int   $user_id The user ID if the author exists (optional call).
	 * @param array $result  The expected result.
	 */
	public function test_maybe_add_credit_from_exif_metadata( array $data, int $user_id, array $result ) {
		// Additional input data.
		$attachment_id = 4711;     // Ignored.
		$context       = 'create';

		$this->author_query->shouldReceive( 'get_author_by_name' )->atMost()->once()->with( m::type( 'string' ) )->andReturn( $user_id );

		$this->assertSame( $result, $this->sut->maybe_add_credit_from_exif_metadata( $data, $attachment_id, $context ) );
	}

	/**
	 * Tests ::maybe_add_credit_from_exif_metadata.
	 *
	 * @covers ::maybe_add_credit_from_exif_metadata
	 *
	 * @dataProvider provide_maybe_add_credit_from_exif_metadata_data
	 *
	 * @param array $data    The input $data array.
	 * @param int   $user_id The user ID if the author exists (optional call).
	 * @param array $result  The expected result.
	 */
	public function test_maybe_add_credit_from_exif_metadata_invalid_context( array $data, int $user_id, array $result ) {
		// Additional input data.
		$attachment_id = 4711;     // Ignored.
		$context       = 'foobar';

		$this->author_query->shouldReceive( 'get_author_by_name' )->never();

		$this->assertSame( $data, $this->sut->maybe_add_credit_from_exif_metadata( $data, $attachment_id, $context ) );
	}

	/**
	 * Provides data for testing maybe_update_image_credit.
	 *
	 * @return array
	 */
	public function provide_maybe_update_image_credit_data() {
		return [
			// No 'media_credit' key.
			[
				[
					'foo' => 'bar',
				],
				false,
				null,
				false,
				[
					'foo' => 'bar',
				],
			],

			// Invalid 'media_credit' key.
			[
				[
					'foo'          => 'bar',
					'media_credit' => true,
				],
				true,
				m::mock( \WP_Post::class ),
				false,
				[
					'foo' => 'bar',
				],
			],

			// Valid 'media_credit' key, invalid attachment.
			[
				[
					'foo'          => 'bar',
					'media_credit' => [
						'fake' => 'credit data',
					],
				],
				true,
				null,
				false,
				[
					'foo' => 'bar',
				],
			],

			// Valid call.
			[
				[
					'foo'          => 'bar',
					'media_credit' => [
						'fake' => 'credit data',
					],
				],
				true,
				m::mock( \WP_Post::class ),
				true,
				[
					'foo' => 'bar',
				],
			],
		];
	}

	/**
	 * Tests ::maybe_update_image_credit.
	 *
	 * @covers ::maybe_update_image_credit
	 *
	 * @dataProvider provide_maybe_update_image_credit_data
	 *
	 * @param array         $data            The input $data array.
	 * @param bool          $expect_get_post Whether the a call to get_post is expected.
	 * @param \WP_Post|null $attachment      The returend attachment object, or null.
	 * @param bool          $expect_update   Whether the credit is expected to be updated.
	 * @param array         $result          The expected result.
	 */
	public function test_maybe_update_image_credit( array $data, bool $expect_get_post, $attachment, bool $expect_update, array $result ) {
		// Additional input data.
		$attachment_id = 4711;

		if ( $expect_get_post ) {
			Functions\expect( 'get_post' )->once()->with( $attachment_id )->andReturn( $attachment );
		} else {
			Functions\expect( 'get_post' )->never();
		}

		if ( $expect_update ) {
			$this->core->shouldReceive( 'update_media_credit_json' )->once()->with( $attachment, m::type( 'array' ) );
		} else {
			$this->core->shouldReceive( 'update_media_credit_json' )->never();
		}

		$this->assertSame( $result, $this->sut->maybe_update_image_credit( $data, $attachment_id ) );
	}
}
