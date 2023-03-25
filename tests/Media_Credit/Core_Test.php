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

namespace Media_Credit\Tests\Media_Credit;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use Media_Credit\Core;

use Media_Credit\Settings;
use Media_Credit\Data_Storage\Cache;
use Media_Credit\Tools\Media_Query;
use Media_Credit\Tools\Shortcodes_Filter;
use Media_Credit\Tools\Template;

use Media_Credit\Tests\TestCase;

/**
 * Media_Credit\Core unit tests.
 *
 * @since 4.2.0
 *
 * @coversDefaultClass \Media_Credit\Core
 * @usesDefaultClass \Media_Credit\Core
 *
 * @uses ::__construct
 */
class Core_Test extends TestCase {

	const SANITIZED_FREEFORM_CREDIT = 'sanitized_credit';
	const SANITIZED_CREDIT_URL      = 'https://example.org/sanitized/url';

	/**
	 * The system-under-test.
	 *
	 * @var Core
	 */
	private $sut;

	/**
	 * Helper mock.
	 *
	 * @var Cache
	 */
	private $cache;

	/**
	 * Helper mock.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Helper mock.
	 *
	 * @var Shortcodes_Filter
	 */
	private $shortcodes_filter;

	/**
	 * Helper mock.
	 *
	 * @var Media_Query
	 */
	private $media_query;

	/**
	 * Helper mock.
	 *
	 * @var Template
	 */
	private $template;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function set_up() {
		parent::set_up();

		// Initialize helpers.
		$this->cache             = m::mock( Cache::Class );
		$this->settings          = m::mock( Settings::Class );
		$this->shortcodes_filter = m::mock( Shortcodes_Filter::Class );
		$this->media_query       = m::mock( Media_Query::Class );
		$this->template          = m::mock( Template::Class );

		// Create system-under-test.
		$this->sut = m::mock( Core::class, [ $this->cache, $this->settings, $this->shortcodes_filter, $this->media_query, $this->template ] )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Test ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$cache             = m::mock( Cache::Class );
		$settings          = m::mock( Settings::Class );
		$shortcodes_filter = m::mock( Shortcodes_Filter::Class );
		$media_query       = m::mock( Media_Query::Class );
		$template          = m::mock( Template::Class );

		$mock = m::mock( Core::class )->makePartial();
		$mock->__construct( $cache, $settings, $shortcodes_filter, $media_query, $template );

		$this->assert_attribute_same( $cache, 'cache', $mock );
		$this->assert_attribute_same( $settings, 'settings', $mock );
		$this->assert_attribute_same( $shortcodes_filter, 'shortcodes_filter', $mock );
		$this->assert_attribute_same( $media_query, 'media_query', $mock );
		$this->assert_attribute_same( $template, 'template', $mock );
	}

	/**
	 * Test ::make_singleton.
	 *
	 * @covers ::make_singleton
	 */
	public function test_make_singleton() {
		// No singleton set yet.
		$this->assertSame( null, $this->get_static_value( Core::class, 'instance' ) );

		// Make it so.
		$this->assertNull( $this->sut->make_singleton() );

		// Check that our SUT is the new singleton.
		$this->assertSame( $this->sut, $this->get_static_value( Core::class, 'instance' ) );

		// Try to make another instance the singleton.
		$this->expectException( \BadMethodCallException::class );
		$core = m::mock( Core::class )->makePartial();
		$core->make_singleton();
	}

	/**
	 * Test ::get_instance.
	 *
	 * @covers ::get_instance
	 */
	public function test_get_instance() {
		// Our (previous) SUT is the current singleton.
		$this->assertInstanceOf( Core::class, Core::get_instance() );

		// Reset singleton.
		$this->set_static_value( Core::class, 'instance', null );

		// No singleton set yet.
		$this->expectException( \BadMethodCallException::class );
		$this->assertNull( Core::get_instance() );
	}

	/**
	 * Test ::get_version.
	 *
	 * @covers ::get_version
	 */
	public function test_get_version() {
		$version = '47.1.1';

		$this->settings->shouldReceive( 'get_version' )->once()->andReturn( $version );

		$this->assertSame( $version, $this->sut->get_version() );
	}

	/**
	 * Test ::get_settings.
	 *
	 * @covers ::get_settings
	 */
	public function test_get_settings() {
		$s = [
			'foo' => 'bar',
			'bar' => 'baz',
		];

		$this->settings->shouldReceive( 'get_all_settings' )->once()->andReturn( $s );

		$this->assertSame( $s, $this->sut->get_settings() );
	}

	/**
	 * Test ::update_shortcodes_in_parent_post.
	 *
	 * @covers ::update_shortcodes_in_parent_post
	 */
	public function test_update_shortcodes_in_parent_post() {
		$attachment                = m::mock( \WP_Post::class );
		$attachment_id             = 33;
		$post_parent_id            = 47;
		$attachment->post_parent   = $post_parent_id;
		$attachment->ID            = $attachment_id;
		$post_content              = 'fake post content';
		$modified_post_content     = 'modified post content';
		$user_id                   = 42;
		$freeform                  = 'My Freeform Credit';
		$url                       = 'https://example.org/credit/url';
		$nofollow                  = true;
		$flags                     = [
			'nofollow' => $nofollow,
		];

		Functions\expect( 'get_post_field' )->once()->with( 'post_content', $post_parent_id, 'raw' )->andReturn( $post_content );

		$this->shortcodes_filter->shouldReceive( 'update_changed_media_credits' )->once()->with( $post_content, $attachment_id, $user_id, $freeform, $url, $nofollow )->andReturn( $modified_post_content );

		Functions\expect( 'wp_update_post' )->once()->with(
			[
				'ID'           => $post_parent_id,
				'post_content' => $modified_post_content,
			]
		);

		$this->assertNull( $this->sut->update_shortcodes_in_parent_post( $attachment, $user_id, $freeform, $url, $flags ) );
	}

	/**
	 * Test ::update_shortcodes_in_parent_post.
	 *
	 * @covers ::update_shortcodes_in_parent_post
	 */
	public function test_update_shortcodes_in_parent_post_no_post_parent() {
		$attachment              = m::mock( \WP_Post::class );
		$attachment_id           = 33;
		$attachment->post_parent = 0;
		$attachment->ID          = $attachment_id;
		$user_id                 = 42;
		$freeform                = 'My Freeform Credit';
		$url                     = 'https://example.org/credit/url';
		$nofollow                = true;
		$flags                   = [
			'nofollow' => $nofollow,
		];

		Functions\expect( 'get_post' )->never();

		$this->shortcodes_filter->shouldReceive( 'update_changed_media_credits' )->never();

		Functions\expect( 'wp_update_post' )->never();

		$this->assertNull( $this->sut->update_shortcodes_in_parent_post( $attachment, $user_id, $freeform, $url, $flags ) );
	}

	/**
	 * Test ::update_shortcodes_in_parent_post.
	 *
	 * @covers ::update_shortcodes_in_parent_post
	 */
	public function test_update_shortcodes_in_parent_post_invalid_post_parent() {
		$attachment              = m::mock( \WP_Post::class );
		$attachment_id           = 33;
		$post_parent_id          = 47;
		$attachment->post_parent = $post_parent_id;
		$attachment->ID          = $attachment_id;
		$user_id                 = 42;
		$freeform                = 'My Freeform Credit';
		$url                     = 'https://example.org/credit/url';
		$nofollow                = true;
		$flags                   = [
			'nofollow' => $nofollow,
		];

		Functions\expect( 'get_post_field' )->once()->with( 'post_content', $post_parent_id, 'raw' )->andReturn( '' );

		$this->shortcodes_filter->shouldReceive( 'update_changed_media_credits' )->never();

		Functions\expect( 'wp_update_post' )->never();

		$this->assertNull( $this->sut->update_shortcodes_in_parent_post( $attachment, $user_id, $freeform, $url, $flags ) );
	}

	/**
	 * Test ::authorized_to_edit_media_credit.
	 *
	 * @covers ::authorized_to_edit_media_credit
	 */
	public function test_authorized_to_edit_media_credit() {
		$result = true;

		Functions\expect( 'current_user_can' )->once()->with( 'edit_posts' )->andReturn( $result );

		$this->assertSame( $result, $this->sut->authorized_to_edit_media_credit() );
	}

	/**
	 * Provides data for testing ::sanitize_media_credit_meta_field.
	 *
	 * @return array
	 */
	public function provide_sanitize_media_credit_meta_field_data() {
		return [
			[ 'some value', Core::POSTMETA_KEY, 'post', self::SANITIZED_FREEFORM_CREDIT ],
			[ 'https://exmaple.org/', Core::URL_POSTMETA_KEY, 'post', self::SANITIZED_CREDIT_URL ],
			[ 'some value', Core::DATA_POSTMETA_KEY, 'post', [] ],
			[ [ 'foo' => 'bar' ], Core::DATA_POSTMETA_KEY, 'post', [ 'foo' => 'bar' ] ],
			[ 'some value', Core::POSTMETA_KEY, 'user', 'some value' ],
			[ 'some value', Core::URL_POSTMETA_KEY, 'user', 'some value' ],
			[ 'some value', Core::DATA_POSTMETA_KEY, 'user', 'some value' ],
			[ Core::EMPTY_META_STRING, Core::POSTMETA_KEY, 'post', Core::EMPTY_META_STRING ],
			[ [ 'invalid' ], Core::POSTMETA_KEY, 'post', '' ], // invalid value (array).
			[ null, Core::URL_POSTMETA_KEY, 'post', '' ], // invalid value (null).
		];
	}

	/**
	 * Test ::sanitize_media_credit_meta_field.
	 *
	 * @covers ::sanitize_media_credit_meta_field
	 *
	 * @dataProvider provide_sanitize_media_credit_meta_field_data
	 *
	 * @param  mixed  $meta_value  The meta value to sanitize.
	 * @param  string $meta_key    The meta key.
	 * @param  string $object_type The object type ('post', 'user',  ...).
	 * @param  mixed  $result      The expected result.
	 */
	public function test_sanitize_media_credit_meta_field( $meta_value, $meta_key, $object_type, $result ) {
		Functions\expect( 'sanitize_text_field' )->atMost()->once()->with( $meta_value )->andReturn( self::SANITIZED_FREEFORM_CREDIT );
		Functions\expect( 'esc_url_raw' )->atMost()->once()->with( $meta_value )->andReturn( self::SANITIZED_CREDIT_URL );

		$this->assertSame( $result, $this->sut->sanitize_media_credit_meta_field( $meta_value, $meta_key, $object_type ) );
	}

	/**
	 * Test ::get_media_credit_freeform_text.
	 *
	 * @covers ::get_media_credit_freeform_text
	 */
	public function test_get_media_credit_freeform_text() {
		$attachment_id = 4711;
		$result        = 'My Credit';

		Functions\expect( 'get_post_meta' )->once()->with( $attachment_id, Core::POSTMETA_KEY, true )->andReturn( $result );

		$this->assertSame( $result, $this->sut->get_media_credit_freeform_text( $attachment_id ) );
	}

	/**
	 * Test ::get_media_credit_url.
	 *
	 * @covers ::get_media_credit_url
	 */
	public function test_get_media_credit_url() {
		$attachment_id = 4711;
		$result        = 'https://example.org/url';

		Functions\expect( 'get_post_meta' )->once()->with( $attachment_id, Core::URL_POSTMETA_KEY, true )->andReturn( $result );

		$this->assertSame( $result, $this->sut->get_media_credit_url( $attachment_id ) );
	}

	/**
	 * Test ::get_media_credit_data.
	 *
	 * @covers ::get_media_credit_data
	 */
	public function test_get_media_credit_data_string_result() {
		$attachment_id = 4711;
		$result        = 'nofollow';

		Functions\expect( 'get_post_meta' )->once()->with( $attachment_id, Core::DATA_POSTMETA_KEY, true )->andReturn( $result );

		$this->assertSame( [ $result ], $this->sut->get_media_credit_data( $attachment_id ) );
	}

	/**
	 * Test ::get_media_credit_data.
	 *
	 * @covers ::get_media_credit_data
	 */
	public function test_get_media_credit_data_no_data() {
		$attachment_id = 4711;

		Functions\expect( 'get_post_meta' )->once()->with( $attachment_id, Core::DATA_POSTMETA_KEY, true )->andReturn( false );

		$this->assertSame( [], $this->sut->get_media_credit_data( $attachment_id ) );
	}

	/**
	 * Test ::get_organization_suffix.
	 *
	 * @covers ::get_organization_suffix
	 */
	public function test_get_organization_suffix() {
		$s = [
			'foo'                  => 'bar',
			'bar'                  => 'baz',
			Settings::SEPARATOR    => ' | ',
			Settings::ORGANIZATION => 'My Organization',
		];

		$this->sut->shouldReceive( 'get_settings' )->once()->andReturn( $s );

		$this->assert_is_string( $this->sut->get_organization_suffix() );
	}

	/**
	 * Test ::render_media_credit_html.
	 *
	 * @covers ::render_media_credit_html
	 */
	public function test_render_media_credit_html_freeform_with_url() {
		// Input data.
		$user_id  = 0;
		$freeform = 'Susan User';
		$url      = 'https://example.org/credit/url';
		$flags    = [
			'foo' => 'bar',
		];

		// Expected result.
		$result = "<a href=\"{$url}\">{$freeform}</a>";

		$this->settings->shouldReceive( 'get' )->never();

		Functions\expect( 'get_the_author_meta' )->never();
		$this->sut->shouldReceive( 'get_author_credit_url' )->never();
		$this->sut->shouldReceive( 'get_organization_suffix' )->never();

		Functions\expect( 'esc_html' )->once()->with( $freeform )->andReturn( $freeform );
		Functions\expect( 'esc_url' )->once()->with( $url )->andReturn( $url );

		$this->assertSame( $result, $this->sut->render_media_credit_html( $user_id, $freeform, $url, $flags ) );
	}

	/**
	 * Test ::render_media_credit_html.
	 *
	 * @covers ::render_media_credit_html
	 */
	public function test_render_media_credit_html_freeform_no_url() {
		// Input data.
		$user_id  = 0;
		$freeform = 'Susan User';
		$url      = '';
		$flags    = [
			'foo' => 'bar',
		];

		// Expected result.
		$result = $freeform;

		$this->settings->shouldReceive( 'get' )->never();

		Functions\expect( 'get_the_author_meta' )->never();
		$this->sut->shouldReceive( 'get_author_credit_url' )->never();
		$this->sut->shouldReceive( 'get_organization_suffix' )->never();

		Functions\expect( 'esc_html' )->once()->with( $freeform )->andReturn( $freeform );
		Functions\expect( 'esc_url' )->never();

		$this->assertSame( $result, $this->sut->render_media_credit_html( $user_id, $freeform, $url, $flags ) );
	}

	/**
	 * Test ::render_media_credit_html.
	 *
	 * @covers ::render_media_credit_html
	 */
	public function test_render_media_credit_html_user_with_default_credit() {
		// Input data.
		$user_id  = 47;
		$freeform = '';
		$url      = 'https://example.org/credit/url';
		$flags    = [
			'foo' => 'bar',
		];

		// Intermediary data.
		$no_default_credit = false;
		$display_name      = 'Susan User';
		$suffix            = ' | My Organization';

		// Expected result.
		$result = "<a href=\"{$url}\">{$display_name}</a>{$suffix}";

		$this->settings->shouldReceive( 'get' )->once()->with( Settings::NO_DEFAULT_CREDIT )->andReturn( $no_default_credit );

		Functions\expect( 'get_the_author_meta' )->once()->with( 'display_name', $user_id )->andReturn( $display_name );
		Functions\expect( 'esc_html' )->once()->with( $display_name )->andReturn( $display_name );
		$this->sut->shouldReceive( 'get_author_credit_url' )->once()->with( $user_id, $url )->andReturn( $url );
		Functions\expect( 'esc_url' )->once()->with( $url )->andReturn( $url );
		Functions\expect( 'esc_html' )->once()->with( $suffix )->andReturn( $suffix );
		$this->sut->shouldReceive( 'get_organization_suffix' )->once()->andReturn( $suffix );

		$this->assertSame( $result, $this->sut->render_media_credit_html( $user_id, $freeform, $url, $flags ) );
	}

	/**
	 * Test ::render_media_credit_html.
	 *
	 * @covers ::render_media_credit_html
	 */
	public function test_render_media_credit_html_user_with_default_credit_no_url() {
		// Input data.
		$user_id  = 47;
		$freeform = '';
		$url      = '';
		$flags    = [
			'foo' => 'bar',
		];

		// Intermediary data.
		$no_default_credit = false;
		$display_name      = 'Susan User';
		$suffix            = ' | My Organization';
		$default_url       = "https://example.org/author/{$user_id}";

		// Expected result.
		$result = "<a href=\"{$default_url}\">{$display_name}</a>{$suffix}";

		$this->settings->shouldReceive( 'get' )->once()->with( Settings::NO_DEFAULT_CREDIT )->andReturn( $no_default_credit );

		Functions\expect( 'get_the_author_meta' )->once()->with( 'display_name', $user_id )->andReturn( $display_name );
		Functions\expect( 'esc_html' )->once()->with( $display_name )->andReturn( $display_name );
		$this->sut->shouldReceive( 'get_author_credit_url' )->once()->with( $user_id, $url )->andReturn( $default_url );
		Functions\expect( 'esc_url' )->once()->with( $default_url )->andReturn( $default_url );
		Functions\expect( 'esc_html' )->once()->with( $suffix )->andReturn( $suffix );
		$this->sut->shouldReceive( 'get_organization_suffix' )->once()->andReturn( $suffix );

		$this->assertSame( $result, $this->sut->render_media_credit_html( $user_id, $freeform, $url, $flags ) );
	}

	/**
	 * Test ::render_media_credit_html.
	 *
	 * @covers ::render_media_credit_html
	 */
	public function test_render_media_credit_html_user_with_default_credit_nofollow() {
		// Input data.
		$user_id  = 47;
		$freeform = '';
		$url      = 'https://example.org/credit/url';
		$flags    = [
			'foo'      => 'bar',
			'nofollow' => true,
		];

		// Intermediary data.
		$no_default_credit = false;
		$display_name      = 'Susan User';
		$suffix            = ' | My Organization';

		// Expected result.
		$result = "<a href=\"{$url}\" rel=\"nofollow\">{$display_name}</a>{$suffix}";

		$this->settings->shouldReceive( 'get' )->once()->with( Settings::NO_DEFAULT_CREDIT )->andReturn( $no_default_credit );

		Functions\expect( 'get_the_author_meta' )->once()->with( 'display_name', $user_id )->andReturn( $display_name );
		Functions\expect( 'esc_html' )->once()->with( $display_name )->andReturn( $display_name );
		$this->sut->shouldReceive( 'get_author_credit_url' )->once()->with( $user_id, $url )->andReturn( $url );
		Functions\expect( 'esc_url' )->once()->with( $url )->andReturn( $url );
		Functions\expect( 'esc_html' )->once()->with( $suffix )->andReturn( $suffix );
		$this->sut->shouldReceive( 'get_organization_suffix' )->once()->andReturn( $suffix );

		$this->assertSame( $result, $this->sut->render_media_credit_html( $user_id, $freeform, $url, $flags ) );
	}

	/**
	 * Test ::render_media_credit_html.
	 *
	 * @covers ::render_media_credit_html
	 */
	public function test_render_media_credit_html_user_no_default_credit() {
		// Input data.
		$user_id  = 47;
		$freeform = '';
		$url      = 'https://example.org/credit/url';
		$flags    = [
			'foo' => 'bar',
		];

		// Intermediary data.
		$no_default_credit = true;

		// Expected result.
		$result = '';

		$this->settings->shouldReceive( 'get' )->once()->with( Settings::NO_DEFAULT_CREDIT )->andReturn( $no_default_credit );

		Functions\expect( 'get_the_author_meta' )->never();
		Functions\expect( 'esc_html' )->never();
		$this->sut->shouldReceive( 'get_author_credit_url' )->never();
		Functions\expect( 'esc_url' )->never();
		$this->sut->shouldReceive( 'get_organization_suffix' )->never();

		$this->assertSame( $result, $this->sut->render_media_credit_html( $user_id, $freeform, $url, $flags ) );
	}

	/**
	 * Test ::render_media_credit_html.
	 *
	 * @covers ::render_media_credit_html
	 */
	public function test_render_media_credit_html_user_invalid_arguments() {
		// Input data.
		$user_id  = 0;
		$freeform = '';
		$url      = 'https://example.org/credit/url';
		$flags    = [
			'foo' => 'bar',
		];

		// Expected result.
		$result = '';

		$this->settings->shouldReceive( 'get' )->never();

		Functions\expect( 'get_the_author_meta' )->never();
		Functions\expect( 'esc_html' )->never();
		$this->sut->shouldReceive( 'get_author_credit_url' )->never();
		Functions\expect( 'esc_url' )->never();
		$this->sut->shouldReceive( 'get_organization_suffix' )->never();

		$this->assertSame( $result, $this->sut->render_media_credit_html( $user_id, $freeform, $url, $flags ) );
	}

	/**
	 * Test ::get_author_credit_url.
	 *
	 * @covers ::get_author_credit_url
	 */
	public function test_get_author_credit_url() {
		// Input data.
		$user_id  = 47;
		$url      = 'https://example.org/credit/url';

		// Expected result.
		$result = $url;

		Filters\expectApplied( 'media_credit_disable_author_urls' )->once()->with( false )->andReturn( false );
		Functions\expect( 'get_author_posts_url' )->never();

		$this->assertSame( $result, $this->sut->get_author_credit_url( $user_id, $url ) );
	}

	/**
	 * Test ::get_author_credit_url.
	 *
	 * @covers ::get_author_credit_url
	 */
	public function test_get_author_credit_url_empty_url() {
		// Input data.
		$user_id  = 47;
		$url      = '';

		// Intermediary data.
		$author_url = "https://example.org/authors/{$user_id}";

		// Expected result.
		$result = $author_url;

		Filters\expectApplied( 'media_credit_disable_author_urls' )->once()->with( false )->andReturn( false );
		Functions\expect( 'get_author_posts_url' )->once()->with( $user_id )->andReturn( $author_url );

		$this->assertSame( $result, $this->sut->get_author_credit_url( $user_id, $url ) );
	}

	/**
	 * Test ::get_author_credit_url.
	 *
	 * @covers ::get_author_credit_url
	 */
	public function test_get_author_credit_url_disabled_author_urls() {
		// Input data.
		$user_id  = 47;
		$url      = '';

		// Expected result.
		$result = '';

		Filters\expectApplied( 'media_credit_disable_author_urls' )->once()->with( false )->andReturn( true );
		Functions\expect( 'get_author_posts_url' )->never();

		$this->assertSame( $result, $this->sut->get_author_credit_url( $user_id, $url ) );
	}

	/**
	 * Test ::render_media_credit_plaintext.
	 *
	 * @covers ::render_media_credit_plaintext
	 */
	public function test_render_media_credit_plaintext_freeform() {
		// Input data.
		$user_id  = 0;
		$freeform = 'Susan User';

		// Expected result.
		$result = $freeform;

		$this->settings->shouldReceive( 'get' )->never();

		Functions\expect( 'get_the_author_meta' )->never();

		$this->assertSame( $result, $this->sut->render_media_credit_plaintext( $user_id, $freeform ) );
	}

	/**
	 * Test ::render_media_credit_plaintext.
	 *
	 * @covers ::render_media_credit_plaintext
	 */
	public function test_render_media_credit_plaintext_empty_freeform() {
		// Input data.
		$user_id  = 0;
		$freeform = Core::EMPTY_META_STRING;

		// Expected result.
		$result = '';

		$this->settings->shouldReceive( 'get' )->never();

		Functions\expect( 'get_the_author_meta' )->never();

		$this->assertSame( $result, $this->sut->render_media_credit_plaintext( $user_id, $freeform ) );
	}

	/**
	 * Test ::render_media_credit_plaintext.
	 *
	 * @covers ::render_media_credit_plaintext
	 */
	public function test_render_media_credit_plaintext_user() {
		// Input data.
		$user_id      = 47;
		$freeform     = '';
		$display_name = 'Susan User';

		// Intermediary data.
		$no_default_credit = false;

		// Expected result.
		$result = $display_name;

		$this->settings->shouldReceive( 'get' )->once()->with( Settings::NO_DEFAULT_CREDIT )->andReturn( $no_default_credit );

		Functions\expect( 'get_the_author_meta' )->once()->with( 'display_name', $user_id )->andReturn( $display_name );

		$this->assertSame( $result, $this->sut->render_media_credit_plaintext( $user_id, $freeform ) );
	}


	/**
	 * Test ::render_media_credit_plaintext.
	 *
	 * @covers ::render_media_credit_plaintext
	 */
	public function test_render_media_credit_plaintext_user_no_default_credit() {
		// Input data.
		$user_id  = 47;
		$freeform = '';

		// Intermediary data.
		$no_default_credit = true;

		// Expected result.
		$result = $freeform;

		$this->settings->shouldReceive( 'get' )->once()->with( Settings::NO_DEFAULT_CREDIT )->andReturn( $no_default_credit );

		Functions\expect( 'get_the_author_meta' )->never();

		$this->assertSame( $result, $this->sut->render_media_credit_plaintext( $user_id, $freeform ) );
	}
	/**
	 * Test ::render_media_credit_plaintext.
	 *
	 * @covers ::render_media_credit_plaintext
	 */
	public function test_render_media_credit_plaintext_freeform_invalid() {
		// Input data.
		$user_id  = 0;
		$freeform = '';

		// Expected result.
		$result = '';

		$this->settings->shouldReceive( 'get' )->never();

		Functions\expect( 'get_the_author_meta' )->never();

		$this->assertSame( $result, $this->sut->render_media_credit_plaintext( $user_id, $freeform ) );
	}

	/**
	 * Test ::render_media_credit_fancy.
	 *
	 * @covers ::render_media_credit_fancy
	 */
	public function test_render_media_credit_fancy_freeform() {
		// Input data.
		$user_id  = 0;
		$freeform = 'Susan User';

		// Intermediary data.
		$credit = 'Susan User';
		$suffix = '';

		// Expected result.
		$result = "{$credit}{$suffix}";

		$this->sut->shouldReceive( 'render_media_credit_plaintext' )->once()->with( $user_id, $freeform )->andReturn( $credit );
		$this->sut->shouldReceive( 'get_organization_suffix' )->never();

		$this->assertSame( $result, $this->sut->render_media_credit_fancy( $user_id, $freeform ) );
	}

	/**
	 * Test ::render_media_credit_fancy.
	 *
	 * @covers ::render_media_credit_fancy
	 */
	public function test_render_media_credit_fancy_user() {
		// Input data.
		$user_id  = 47;
		$freeform = '';

		// Intermediary data.
		$credit = 'Susan User';
		$suffix = ' | My Organization';

		// Expected result.
		$result = "{$credit}{$suffix}";

		$this->sut->shouldReceive( 'render_media_credit_plaintext' )->once()->with( $user_id, $freeform )->andReturn( $credit );
		$this->sut->shouldReceive( 'get_organization_suffix' )->once()->andReturn( $suffix );

		$this->assertSame( $result, $this->sut->render_media_credit_fancy( $user_id, $freeform ) );
	}

	/**
	 * Test ::render_media_credit_fancy.
	 *
	 * @covers ::render_media_credit_fancy
	 */
	public function test_render_media_credit_fancy_invalid() {
		// Input data.
		$user_id  = 0;
		$freeform = '';

		// Intermediary data.
		$credit = '';

		// Expected result.
		$result = '';

		$this->sut->shouldReceive( 'render_media_credit_plaintext' )->once()->with( $user_id, $freeform )->andReturn( $credit );
		$this->sut->shouldReceive( 'get_organization_suffix' )->never();

		$this->assertSame( $result, $this->sut->render_media_credit_fancy( $user_id, $freeform ) );
	}

	/**
	 * Test ::get_media_credit_json.
	 *
	 * @covers ::get_media_credit_json
	 */
	public function test_get_media_credit_json() {
		// Input data.
		$attachment = m::mock( \WP_Post::class );

		// Intermediary data.
		$user_id       = 4711;
		$attachment_id = 42;
		$freeform      = 'My freeform credit';
		$url           = 'https://example.org/some/url';
		$nofollow      = false;
		$flags         = [
			'nofollow' => $nofollow,
		];
		$rendered      = 'My rendered credit (HTML)';
		$plaintext     = 'My plaintext credit';
		$fancy         = 'My fancy credit';

		// Set up attachment.
		$attachment->ID          = $attachment_id;
		$attachment->post_author = $user_id;

		// Expected result.
		$result = [
			'rendered'  => $rendered,
			'plaintext' => $plaintext,
			'fancy'     => $fancy,
			'raw'       => [
				'user_id'  => $user_id,
				'freeform' => $freeform,
				'url'      => $url,
				'flags'    => [
					'nofollow' => $nofollow,
				],
			],
		];

		$this->cache->shouldReceive( 'get' )->once()->with( m::type( 'string' ) )->andReturn( false );

		$this->sut->shouldReceive( 'get_media_credit_freeform_text' )->once()->with( $attachment_id )->andReturn( $freeform );
		$this->sut->shouldReceive( 'get_media_credit_url' )->once()->with( $attachment_id )->andReturn( $url );
		$this->sut->shouldReceive( 'get_media_credit_data' )->once()->with( $attachment_id )->andReturn( $flags );
		$this->sut->shouldReceive( 'render_media_credit_html' )->once()->with( $user_id, $freeform, $url, $flags )->andReturn( $rendered );
		$this->sut->shouldReceive( 'render_media_credit_plaintext' )->once()->with( $user_id, $freeform )->andReturn( $plaintext );
		$this->sut->shouldReceive( 'render_media_credit_fancy' )->once()->with( $user_id, $freeform )->andReturn( $fancy );

		$this->cache->shouldReceive( 'set' )->once()->with( m::type( 'string' ), $result )->andReturn( true );

		$this->assertSame( $result, $this->sut->get_media_credit_json( $attachment ) );
	}

	/**
	 * Test ::get_media_credit_json.
	 *
	 * @covers ::get_media_credit_json
	 */
	public function test_get_media_credit_json_cached() {
		// Input data.
		$attachment = m::mock( \WP_Post::class );

		// Intermediary data.
		$user_id       = 4711;
		$attachment_id = 42;
		$freeform      = 'My freeform credit';
		$url           = 'https://example.org/some/url';
		$nofollow      = false;
		$rendered      = 'My rendered credit (HTML)';
		$plaintext     = 'My plaintext credit';
		$fancy         = 'My fancy credit';

		// Set up attachment.
		$attachment->ID          = $attachment_id;
		$attachment->post_author = $user_id;

		// Expected result.
		$result = [
			'rendered'  => $rendered,
			'plaintext' => $plaintext,
			'fancy'     => $fancy,
			'raw'       => [
				'user_id'  => $user_id,
				'freeform' => $freeform,
				'url'      => $url,
				'flags'    => [
					'nofollow' => $nofollow,
				],
			],
		];

		$this->cache->shouldReceive( 'get' )->once()->with( m::type( 'string' ) )->andReturn( $result );

		$this->sut->shouldReceive( 'get_media_credit_freeform_text' )->never();
		$this->sut->shouldReceive( 'get_media_credit_url' )->never();
		$this->sut->shouldReceive( 'get_media_credit_data' )->never();
		$this->sut->shouldReceive( 'render_media_credit_html' )->never();
		$this->sut->shouldReceive( 'render_media_credit_plaintext' )->never();
		$this->sut->shouldReceive( 'render_media_credit_fancy' )->never();

		$this->cache->shouldReceive( 'set' )->never();

		$this->assertSame( $result, $this->sut->get_media_credit_json( $attachment ) );
	}

	/**
	 * Provides data for testing ::updatE_media_credit_json.
	 *
	 * @return array
	 */
	public function provide_update_media_credit_json_data() {
		// Not really used further on.
		$rendered  = 'My rendered credit (HTML)';
		$plaintext = 'My plaintext credit';
		$fancy     = 'My fancy credit';

		return [
			// Full array, user data.
			'user OK'            => [
				[
					'rendered'  => $rendered,
					'plaintext' => $plaintext,
					'fancy'     => $fancy,
					'raw'       => [
						'user_id'  => 42,
						'freeform' => 'Marc User',
						'url'      => '',
						'flags'    => [
							'nofollow' => true,
						],
					],
				],
				42,
				'Marc User',
				'',
				[ 'nofollow' => true ],
				42,
				'',
				'',
				[
					'nofollow' => true,
					'foo'      => 'bar',
				],
			],
			// Short array, user data.
			'no raw OK'          => [
				[
					'user_id'  => 42,
					'freeform' => 'Marc User',
					'url'      => '',
					'flags'    => [
						'nofollow' => true,
					],
				],
				42,
				'Marc User',
				'',
				[ 'nofollow' => true ],
				42,
				'',
				'',
				[
					'nofollow' => true,
					'foo'      => 'bar',
				],
			],
			// Short array, freeform data with invalid URL, no flags.
			'no raw freeform OK' => [
				[
					'user_id'  => 0,
					'freeform' => 'My Credit',
					'url'      => 'https://example.org$FOO',
				],
				null,
				'My Credit',
				'https://example.org$FOO',
				null,
				0,
				'My Credit',
				'https://example.org',
				[],
			],
		];
	}

	/**
	 * Test ::update_media_credit_json.
	 *
	 * @covers ::update_media_credit_json
	 *
	 * @dataProvider provide_update_media_credit_json_data
	 *
	 * @param  array       $fields        The input fields.
	 * @param  int|null    $user_id       Optional. The extracted user ID. Default null.
	 * @param  string|null $freeform      Optional. The extracted freeform text. Default null.
	 * @param  string|null $url           Optional. The extracted URL. Default null.
	 * @param  array|null  $flags         Optional. The extracted data array. Default null.
	 * @param  int|null    $new_user_id   Optional. The result user ID. Default null.
	 * @param  string|null $new_freeform  Optional. The result freeform text. Default null.
	 * @param  string|null $new_url       Optional. The result URL. Default null.
	 * @param  array|null  $new_flags     Optional. The result data array. Default null.
	 */
	public function test_update_media_credit_json( $fields, $user_id = null, $freeform = null, $url = null, $flags = null, $new_user_id = null, $new_freeform = null, $new_url = null, $new_flags = null ) {
		// Input data.
		$attachment     = m::mock( \WP_Post::class );
		$attachment_id  = 4711;
		$attachment->ID = $attachment_id;

		$initial_user_id = $user_id;
		if ( isset( $fields['raw']['user_id'] ) ) {
			$initial_user_id = $fields['raw']['user_id'];
		} elseif ( isset( $fields['user_id'] ) ) {
			$initial_user_id = $fields['user_id'];
		}

		if ( null !== $initial_user_id ) {
			$this->sut->shouldReceive( 'validate_user_id' )->once()->with( $initial_user_id )->andReturn( $user_id );
		} else {
			$this->sut->shouldReceive( 'validate_user_id' )->never();
		}

		$this->sut->shouldReceive( 'set_media_credit_fields' )->once()->with( $attachment, $user_id, $freeform, $url, $flags )->andReturn(
			[
				'user_id'  => $new_user_id,
				'freeform' => $new_freeform,
				'url'      => $new_url,
				'flags'    => $new_flags,
			]
		);

		$this->cache->shouldReceive( 'delete' )->once()->with( m::type( 'string' ) )->andReturn( true );

		$this->sut->shouldReceive( 'update_shortcodes_in_parent_post' )->once()->with( $attachment, $user_id, $new_freeform, $new_url, $new_flags );

		$this->assertNull( $this->sut->update_media_credit_json( $attachment, $fields ) );
	}

	/**
	 * Provides data for testing ::set_media_credit_fields.
	 *
	 * @return array
	 */
	public function provide_set_media_credit_fields_data() {
		return [
			'Update URL and flags'
			=>
			[
				[
					'user_id'  => 47,
					'freeform' => '',
					'url'      => 'https://old.example.org/url',
					'flags'    => [ 'foo' => 'bar' ],
				],
				'invalid',
				null,
				null,
				'https://new.example.org/',
				[ 'nofollow' => true ],
				[
					'user_id'  => 47,
					'freeform' => '',
					'url'      => 'https://new.example.org/',
					'flags'    => [
						'foo'      => 'bar',
						'nofollow' => true,
					],
				],
			],

			'New user ID, freeform same as user name'
			=>
			[
				[
					'user_id'  => 47,
					'freeform' => '',
					'url'      => 'https://old.example.org/url',
					'flags'    => [ 'foo' => 'bar' ],
				],
				'Marc Anthony',
				666,
				'Marc Anthony',
				null,
				[],
				[
					'user_id'  => 666,
					'freeform' => '',
					'url'      => 'https://old.example.org/url',
					'flags'    => [
						'foo'      => 'bar',
					],
				],
			],

			'New user ID, freeform empty'
			=>
			[
				[
					'user_id'  => 47,
					'freeform' => '',
					'url'      => 'https://old.example.org/url',
					'flags'    => [ 'foo' => 'bar' ],
				],
				'Marc Anthony',
				666,
				'',
				null,
				null,
				[
					'user_id'  => 666,
					'freeform' => '',
					'url'      => 'https://old.example.org/url',
					'flags'    => [
						'foo'      => 'bar',
					],
				],
			],

			'New user ID, freeform non-empty (taking precedence)'
			=>
			[
				[
					'user_id'  => 47,
					'freeform' => '',
					'url'      => 'https://old.example.org/url',
					'flags'    => [ 'foo' => 'bar' ],
				],
				'Marc Anthony',
				666,
				'Larifari',
				null,
				null,
				[
					'user_id'  => 47,
					'freeform' => 'Larifari',
					'url'      => 'https://old.example.org/url',
					'flags'    => [
						'foo'      => 'bar',
					],
				],
			],
		];
	}

	/**
	 * Test ::set_media_credit_fields.
	 *
	 * @covers ::set_media_credit_fields
	 *
	 * @dataProvider provide_set_media_credit_fields_data
	 *
	 * @param array       $current   The current fields.
	 * @param string      $user_name The display name of the user specified by $user_id.
	 * @param int|null    $user_id   New user ID.
	 * @param string|null $freeform  New freeform credit.
	 * @param string|null $url       New credit URL.
	 * @param array|null  $flags     New flags array.
	 * @param array       $result    The expected result.
	 */
	public function test_set_media_credit_fields( array $current, $user_name, $user_id, $freeform, $url, $flags, array $result ) {
		// Input data.
		$attachment     = m::mock( \WP_Post::class );
		$attachment_id  = 4711;
		$attachment->ID = $attachment_id;

		$this->sut->shouldReceive( 'get_media_credit_json' )->once()->with( $attachment )->andReturn( [ 'raw' => $current ] );

		// This is an imperfect approximation of the underlyling logic. Needs to be refactored together with the code itself.
		if ( ! empty( $user_id ) ) {
			Functions\expect( 'get_the_author_meta' )->atMost()->once()->with( 'display_name', $user_id )->andReturn( $user_name );
			$this->sut->shouldReceive( 'set_post_author_credit' )->atMost()->once()->with( $attachment, $user_id );
		} else {
			Functions\expect( 'get_the_author_meta' )->never();
			$this->sut->shouldReceive( 'set_post_author_credit' )->never();
		}

		Functions\expect( 'update_post_meta' )->atMost()->once()->with( $attachment_id, Core::POSTMETA_KEY, m::type( 'string' ) );
		Functions\expect( 'update_post_meta' )->atMost()->once()->with( $attachment_id, Core::URL_POSTMETA_KEY, $url );
		Functions\expect( 'wp_parse_args' )->atMost()->once()->with( $flags, $current['flags'] )->andReturn( isset( $flags ) ? \array_merge( $current['flags'], $flags ) : $current['flags'] );
		Functions\expect( 'update_post_meta' )->atMost()->once()->with( $attachment_id, Core::DATA_POSTMETA_KEY, m::type( 'array' ) );

		$this->assertSame( $result, $this->sut->set_media_credit_fields( $attachment, $user_id, $freeform, $url, $flags ) );
	}

	/**
	 * Test ::set_post_author_credit.
	 *
	 * @covers ::set_post_author_credit
	 */
	public function test_set_post_author_credit() {
		// Input data.
		$attachment     = m::mock( \WP_Post::class );
		$attachment_id  = 4711;
		$attachment->ID = $attachment_id;
		$user_id        = 47;

		// Intermediary data.
		$fields = [
			'ID'          => $attachment_id,
			'post_author' => $user_id,
		];

		Functions\expect( 'wp_update_post' )->once()->with( $fields );
		Functions\expect( 'delete_post_meta' )->once()->with( $attachment_id, Core::POSTMETA_KEY );

		$this->assertNull( $this->sut->set_post_author_credit( $attachment, $user_id ) );
	}

	/**
	 * Test ::validate_user_id.
	 *
	 * @covers ::validate_user_id
	 */
	public function test_validate_user_id() {
		// Input data.
		$user_id = 47;

		Functions\expect( 'get_user_by' )->once()->with( 'id', $user_id )->andReturn( m::mock( \WP_User::class ) );

		$this->assertSame( $user_id, $this->sut->validate_user_id( $user_id ) );
	}

	/**
	 * Test ::validate_user_id.
	 *
	 * @covers ::validate_user_id
	 */
	public function test_validate_user_id_invalid() {
		// Input data.
		$user_id = 47;

		Functions\expect( 'get_user_by' )->once()->with( 'id', $user_id )->andReturn( m::mock( \WP_User::class ) );

		$this->assertSame( $user_id, $this->sut->validate_user_id( $user_id ) );
	}

	/**
	 * Test ::get_author_media_and_posts.
	 *
	 * @covers ::get_author_media_and_posts
	 */
	public function test_get_author_media_and_posts() {
		// Input data.
		$query = [
			'foo' => 'bar',
		];

		// Intermediary data.
		$install_date = '2001-01-01';

		// Expected result.
		$result = [
			1  => (object) [ 'foo' => 'bar' ],
			55 => (object) [ 'foo' => 'baz' ],
		];

		$this->settings->shouldReceive( 'get' )->once()->with( Settings::INSTALL_DATE )->andReturn( $install_date );
		$this->media_query->shouldReceive( 'get_author_media_and_posts' )->once()->with( m::type( 'array' ) )->andReturn( $result );

		$this->assertSame( $result, $this->sut->get_author_media_and_posts( $query ) );
	}

	/**
	 * Provides data for testing ::wrap_media_credit_markup.
	 *
	 * @return array
	 */
	public function provide_wrap_media_credit_markup_data() {
		return [
			'Normal credit with link'
			=>
			[
				'<span class="media-credit"><a href="https://example.org">My Credit</a></span>',
				'<a href="https://example.org">My Credit</a>',
			],

			'Credit with link and extra attributes'
			=>
			[
				'<span class="media-credit" foo="bar" bar="baz"><a href="https://example.org">My Credit</a></span>',
				'<a href="https://example.org">My Credit</a>',
				false,
				'foo="bar" bar="baz"',
			],

			'Credit with extra attributes & schema.org'
			=>
			[
				'<span class="media-credit" itemprop="copyrightHolder" foo="bar" bar="baz"><a href="https://example.org">My Credit</a></span>',
				'<a href="https://example.org">My Credit</a>',
				true,
				'foo="bar" bar="baz"',
			],
		];
	}

	/**
	 * Test ::wrap_media_credit_markup.
	 *
	 * @covers ::wrap_media_credit_markup
	 *
	 * @dataProvider provide_wrap_media_credit_markup_data
	 *
	 * @param  string $result             The expected result.
	 * @param  string $credit             The credit markup.
	 * @param  bool   $include_schema_org Optional. Whether schema.org markup should be injected. Default false.
	 * @param  string $extra_attributes   Optional. Any extra attributes (inserted verbatim). Default ''.
	 */
	public function test_wrap_media_credit_markup( $result, $credit, $include_schema_org = false, $extra_attributes = '' ) {
		Filters\expectApplied( 'media_credit_wrapper' )->once()->with( $result, $credit, $include_schema_org )->andReturn( $result );

		$this->assertSame( $result, $this->sut->wrap_media_credit_markup( $credit, $include_schema_org, $extra_attributes ) );
	}

	/**
	 * Tests ::print_partial.
	 *
	 * @covers ::print_partial
	 */
	public function test_print_partial() {
		// Input data.
		$partial = '/foo/bar.php';
		$args    = [
			'foo' => 'bar',
			'bar' => 'baz',
		];

		$this->template->shouldReceive( 'print_partial' )->once()->with( $partial, $args );

		$this->assertNull( $this->sut->print_partial( $partial, $args ) );
	}

	/**
	 * Provides data for testing ::maybe_add_schema_org_markup_to_figure.
	 *
	 * @return array
	 */
	public function provide_maybe_add_schema_org_markup_to_figure_data() {
		return [
			// Noop.
			[ '', '' ],
			// <figure> only.
			[
				'<figure foo="bar">FOOBAR</figure>',
				'<figure itemscope itemtype="http://schema.org/ImageObject" foo="bar">FOOBAR</figure>',
			],
			// <figure> with <figcaption>.
			[
				'<figure foo="bar">FOOBAR <figcaption class="some-class">Oh caption, my caption!</figcaption></figure>',
				'<figure itemscope itemtype="http://schema.org/ImageObject" foo="bar">FOOBAR <figcaption itemprop="caption" class="some-class">Oh caption, my caption!</figcaption></figure>',
			],
			// <figure> with <figcaption>, pre-existing 'itemscope'.
			[
				'<figure foo="bar" itemscope itemtype="some://thing">FOOBAR <figcaption class="some-class">Oh caption, my caption!</figcaption></figure>',
				'<figure foo="bar" itemscope itemtype="some://thing">FOOBAR <figcaption itemprop="caption" class="some-class">Oh caption, my caption!</figcaption></figure>',
			],
			// <figure> with <figcaption>, pre-existing 'itemprop' on <figcaption>.
			[
				'<figure foo="bar">FOOBAR <figcaption class="some-class" itemprop="foo">Oh caption, my caption!</figcaption></figure>',
				'<figure itemscope itemtype="http://schema.org/ImageObject" foo="bar">FOOBAR <figcaption class="some-class" itemprop="foo">Oh caption, my caption!</figcaption></figure>',
			],
			// <figure> with <figcaption>, pre-existing 'itemscope' and 'itemprop' (on <figcaption>).
			[
				'<figure foo="bar"itemscope itemtype="some://thing">FOOBAR <figcaption class="some-class" itemprop="foo">Oh caption, my caption!</figcaption></figure>',
				'<figure foo="bar"itemscope itemtype="some://thing">FOOBAR <figcaption class="some-class" itemprop="foo">Oh caption, my caption!</figcaption></figure>',
			],
		];
	}

	/**
	 * Tests ::maybe_add_schema_org_markup_to_figure.
	 *
	 * @covers ::maybe_add_schema_org_markup_to_figure
	 *
	 * @dataProvider provide_maybe_add_schema_org_markup_to_figure_data
	 *
	 * @param string $caption The caption.
	 * @param string $result  The expected result.
	 */
	public function test_maybe_add_schema_org_markup_to_figure( $caption, $result ) {
		$this->assertSame( $result, $this->sut->maybe_add_schema_org_markup_to_figure( $caption ) );
	}
}
