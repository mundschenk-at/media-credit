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

use Media_Credit\Components\Frontend;

use Media_Credit\Core;
use Media_Credit\Settings;

/**
 * Media_Credit\Components\Frontend unit test.
 *
 * @coversDefaultClass \Media_Credit\Components\Frontend
 * @usesDefaultClass \Media_Credit\Components\Frontend
 *
 * @uses ::__construct
 */
class Frontend_Test extends TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Frontend
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
						'media-credit-image-properties-tmpl.php' => 'IMAGE PROPERTY TEMPLATE',
					],
				],
			],
		];
		vfsStream::setup( 'root', null, $filesystem );
		set_include_path( 'vfs://root/' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_set_include_path

		$this->core     = m::mock( Core::class );
		$this->settings = m::mock( Settings::class );

		$this->sut = m::mock( Frontend::class, [ $this->core, $this->settings ] )
			->makePartial()
			->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$core     = m::mock( Core::class );
		$settings = m::mock( Settings::class );

		$sut = m::mock( Frontend::class )->makePartial();
		$sut->__construct( $core, $settings );

		$this->assert_attribute_same( $core, 'core', $sut );
		$this->assert_attribute_same( $settings, 'settings', $sut );
	}

	/**
	 * Provides data for testing ::run.
	 *
	 * @return array
	 */
	public function provide_run_data() {
		return [
			[ false, false ],
			[ true, false ],
			[ false, true ],
			[ true, true ],
		];
	}

	/**
	 * Tests ::run.
	 *
	 * @covers ::run
	 *
	 * @dataProvider provide_run_data
	 *
	 * @param  bool $credit_at_end         Whether the "credit at end" feature should be enabled.
	 * @param  bool $featured_image_credit Whether featured image credits should be enabled.
	 */
	public function test_run( $credit_at_end, $featured_image_credit ) {
		Actions\expectAdded( 'wp_enqueue_scripts' )->once()->with( [ $this->sut, 'enqueue_styles' ] );

		$this->settings->shouldReceive( 'get' )->once()->with( Settings::CREDIT_AT_END )->andReturn( $credit_at_end );
		if ( $credit_at_end ) {
			Filters\expectAdded( 'the_content' )->once()->with( [ $this->sut, 'add_media_credits_to_end' ], 10, 1 );
			$this->settings->shouldReceive( 'get' )->never()->with( Settings::FEATURED_IMAGE_CREDIT );
			Filters\expectAdded( 'post_thumbnail_html' )->never()->with( [ $this->sut, 'add_media_credit_to_post_thumbnail' ], 10, 3 );
		} else {
			Filters\expectAdded( 'the_content' )->never()->with( [ $this->sut, 'add_media_credits_to_end' ], 10, 1 );
			$this->settings->shouldReceive( 'get' )->once()->with( Settings::FEATURED_IMAGE_CREDIT )->andReturn( $featured_image_credit );
			Filters\expectAdded( 'post_thumbnail_html' )->times( (int) $featured_image_credit )->with( [ $this->sut, 'add_media_credit_to_post_thumbnail' ], 10, 3 );
		}

		$this->assertNull( $this->sut->run() );
	}

	/**
	 * Provides data for testing ::enqueue_styles.
	 *
	 * @return array
	 */
	public function provide_enqueue_styles_data() {
		return [
			[ false ],
			[ true ],
		];
	}

	/**
	 * Tests ::enqueue_styles.
	 *
	 * @covers ::enqueue_styles
	 *
	 * @dataProvider provide_enqueue_styles_data
	 *
	 * @param  bool $credit_at_end The CREDIT_AT_END setting.
	 */
	public function test_enqueue_styles( $credit_at_end ) {
		// Intermediary data.
		$version = '47.1.1';

		Functions\expect( 'plugin_dir_url' )->once()->with( \MEDIA_CREDIT_PLUGIN_FILE )->andReturn( '//some/url' );

		$this->settings->shouldReceive( 'get_version' )->once()->withNoArgs()->andReturn( $version );
		$this->settings->shouldReceive( 'get' )->once()->with( Settings::CREDIT_AT_END )->andReturn( $credit_at_end );

		Functions\expect( 'wp_enqueue_style' )->once()->with( m::pattern( '/media-credit(-end)?/' ), m::type( 'string' ), [], $version, 'all' );

		$this->assertNull( $this->sut->enqueue_styles() );
	}

	/**
	 * Tests ::add_media_credits_to_end.
	 *
	 * @covers ::add_media_credits_to_end
	 */
	public function test_add_media_credits_to_end() {
		// Input data.
		$content = "Fake content\n";

		// Intermediary data.
		$credits      = [ 'Some', 'fake', 'credits' ];
		$credit_count = \count( $credits );

		// Expected result.
		$result = $content . '<div class="media-credit-end">Images courtesy of Some, fake and credits</div>';

		Functions\expect( 'is_singular' )->once()->withNoArgs()->andReturn( true );
		Functions\expect( 'in_the_loop' )->once()->withNoArgs()->andReturn( true );
		Functions\expect( 'is_main_query' )->once()->withNoArgs()->andReturn( true );

		$this->sut->shouldReceive( 'get_unique_image_credits' )->once()->with( $content )->andReturn( $credits );

		Filters\expectApplied( 'media_credit_at_end_use_short_label' )->once()->with( m::type( 'bool' ) )->andReturn( false );

		Functions\expect( '_n' )->once()->with( 'Image courtesy of %2$s%1$s', 'Images courtesy of %2$s and %1$s', $credit_count, 'media-credit' )->andReturnArg( (int) ( $credit_count > 1 ) );
		Functions\expect( '_x' )->once()->with( m::type( 'string' ), m::type( 'string' ), 'media-credit' )->andReturnArg( 0 );

		Filters\expectApplied( 'media_credit_at_end' )->once()->with( m::type( 'string' ), $content, $credits )->andReturnArg( 0 );

		$this->assertSame( $result, $this->sut->add_media_credits_to_end( $content ) );
	}

	/**
	 * Tests ::add_media_credits_to_end.
	 *
	 * @covers ::add_media_credits_to_end
	 */
	public function test_add_media_credits_to_end_short_label() {
		// Input data.
		$content = "Fake content\n";

		// Intermediary data.
		$credits      = [ 'Some', 'fake', 'credits' ];
		$credit_count = \count( $credits );

		// Expected result.
		$result = $content . '<div class="media-credit-end">Images: Some, fake and credits</div>';

		Functions\expect( 'is_singular' )->once()->withNoArgs()->andReturn( true );
		Functions\expect( 'in_the_loop' )->once()->withNoArgs()->andReturn( true );
		Functions\expect( 'is_main_query' )->once()->withNoArgs()->andReturn( true );

		$this->sut->shouldReceive( 'get_unique_image_credits' )->once()->with( $content )->andReturn( $credits );

		Filters\expectApplied( 'media_credit_at_end_use_short_label' )->once()->with( m::type( 'bool' ) )->andReturn( true );

		Functions\expect( '_n' )->once()->with( 'Image: %2$s%1$s', 'Images: %2$s and %1$s', $credit_count, 'media-credit' )->andReturnArg( (int) ( $credit_count > 1 ) );
		Functions\expect( '_x' )->once()->with( m::type( 'string' ), m::type( 'string' ), 'media-credit' )->andReturnArg( 0 );

		Filters\expectApplied( 'media_credit_at_end' )->once()->with( m::type( 'string' ), $content, $credits )->andReturnArg( 0 );

		$this->assertSame( $result, $this->sut->add_media_credits_to_end( $content ) );
	}

	/**
	 * Tests ::add_media_credits_to_end.
	 *
	 * @covers ::add_media_credits_to_end
	 */
	public function test_add_media_credits_to_end_single_image() {
		// Input data.
		$content = "Fake content\n";

		// Intermediary data.
		$credits      = [ 'Some' ];
		$credit_count = \count( $credits );

		// Expected result.
		$result = $content . '<div class="media-credit-end">Image courtesy of Some</div>';

		Functions\expect( 'is_singular' )->once()->withNoArgs()->andReturn( true );
		Functions\expect( 'in_the_loop' )->once()->withNoArgs()->andReturn( true );
		Functions\expect( 'is_main_query' )->once()->withNoArgs()->andReturn( true );

		$this->sut->shouldReceive( 'get_unique_image_credits' )->once()->with( $content )->andReturn( $credits );

		Filters\expectApplied( 'media_credit_at_end_use_short_label' )->once()->with( m::type( 'bool' ) )->andReturnArg( 0 );

		Functions\expect( '_n' )->once()->with( m::type( 'string' ), m::type( 'string' ), $credit_count, 'media-credit' )->andReturnArg( (int) ( $credit_count > 1 ) );
		Functions\expect( '_x' )->once()->with( m::type( 'string' ), m::type( 'string' ), 'media-credit' )->andReturnArg( 0 );

		Filters\expectApplied( 'media_credit_at_end' )->once()->with( m::type( 'string' ), $content, $credits )->andReturnArg( 0 );

		$this->assertSame( $result, $this->sut->add_media_credits_to_end( $content ) );
	}

	/**
	 * Tests ::add_media_credits_to_end.
	 *
	 * @covers ::add_media_credits_to_end
	 */
	public function test_add_media_credits_to_end_not_singular() {
		// Input data.
		$content = "Fake content\n";

		Functions\expect( 'is_singular' )->once()->withNoArgs()->andReturn( false );
		Functions\expect( 'in_the_loop' )->never();
		Functions\expect( 'is_main_query' )->never();

		$this->sut->shouldReceive( 'get_unique_image_credits' )->never();

		Filters\expectApplied( 'media_credit_at_end_use_short_label' )->never();

		Functions\expect( '_n' )->never();
		Functions\expect( '_x' )->never();

		Filters\expectApplied( 'media_credit_at_end' )->never();

		$this->assertSame( $content, $this->sut->add_media_credits_to_end( $content ) );
	}

	/**
	 * Tests ::add_media_credits_to_end.
	 *
	 * @covers ::add_media_credits_to_end
	 */
	public function test_add_media_credits_to_end_not_in_the_loop() {
		// Input data.
		$content = "Fake content\n";

		Functions\expect( 'is_singular' )->once()->withNoArgs()->andReturn( true );
		Functions\expect( 'in_the_loop' )->once()->withNoArgs()->andReturn( false );
		Functions\expect( 'is_main_query' )->never();

		$this->sut->shouldReceive( 'get_unique_image_credits' )->never();

		Filters\expectApplied( 'media_credit_at_end_use_short_label' )->never();

		Functions\expect( '_n' )->never();
		Functions\expect( '_x' )->never();

		Filters\expectApplied( 'media_credit_at_end' )->never();

		$this->assertSame( $content, $this->sut->add_media_credits_to_end( $content ) );
	}

	/**
	 * Tests ::add_media_credits_to_end.
	 *
	 * @covers ::add_media_credits_to_end
	 */
	public function test_add_media_credits_to_end_not_main_query() {
		// Input data.
		$content = "Fake content\n";

		Functions\expect( 'is_singular' )->once()->withNoArgs()->andReturn( true );
		Functions\expect( 'in_the_loop' )->once()->withNoArgs()->andReturn( true );
		Functions\expect( 'is_main_query' )->once()->withNoArgs()->andReturn( false );

		$this->sut->shouldReceive( 'get_unique_image_credits' )->never();

		Filters\expectApplied( 'media_credit_at_end_use_short_label' )->never();

		Functions\expect( '_n' )->never();
		Functions\expect( '_x' )->never();

		Filters\expectApplied( 'media_credit_at_end' )->never();

		$this->assertSame( $content, $this->sut->add_media_credits_to_end( $content ) );
	}

	/**
	 * Tests ::add_media_credits_to_end.
	 *
	 * @covers ::add_media_credits_to_end
	 */
	public function test_add_media_credits_to_end_no_image() {
		// Input data.
		$content = "Fake content\n";

		Functions\expect( 'is_singular' )->once()->withNoArgs()->andReturn( true );
		Functions\expect( 'in_the_loop' )->once()->withNoArgs()->andReturn( true );
		Functions\expect( 'is_main_query' )->once()->withNoArgs()->andReturn( true );

		$this->sut->shouldReceive( 'get_unique_image_credits' )->once()->with( $content )->andReturn( [] );

		Filters\expectApplied( 'media_credit_at_end_use_short_label' )->never();

		Functions\expect( '_n' )->never();
		Functions\expect( '_x' )->never();

		Filters\expectApplied( 'media_credit_at_end' )->never();

		$this->assertSame( $content, $this->sut->add_media_credits_to_end( $content ) );
	}

	/**
	 * Provides data for testing ::get_unique_image_credits.
	 *
	 * @return array
	 */
	public function provide_get_unique_image_credits_data() {
		return [
			// No images.
			[ [], [] ],
			// Unique set of images.
			[
				[
					4711 => [
						'foo'      => 'bar',
						'rendered' => 'Foo',
					],
					42   => [
						'foo'      => 'bar',
						'rendered' => 'Bar',
					],
					815  => [
						'foo'      => 'bar',
						'rendered' => 'Baz',
					],
				],
				[ 'Foo', 'Bar', 'Baz' ],
			],
			// Non-unique set of imags.
			[
				[
					4711 => [
						'foo'      => 'bar',
						'rendered' => 'Foo',
					],
					42   => [
						'foo'      => 'bar',
						'rendered' => 'Bar',
					],
					4711 => [
						'foo'      => 'bar',
						'rendered' => 'Foo',
					],
					815  => [
						'foo'      => 'bar',
						'rendered' => 'Baz',
					],
				],
				[ 'Foo', 'Bar', 'Baz' ],
			],
			// Non-unique credits for unique images.
			[
				[
					4711 => [
						'foo'      => 'bar',
						'rendered' => 'Foo',
					],
					42   => [
						'foo'      => 'bar',
						'rendered' => 'Foo',
					],
					815  => [
						'foo'      => 'bar',
						'rendered' => 'Baz',
					],
				],
				[ 'Foo', 'Baz' ],
			],
			// Invalid JSON for one image.
			[
				[
					4711 => [
						'foo'      => 'bar',
						'rendered' => 'Foo',
					],
					42   => [
						'foo'      => 'bar',
						'rendered' => 'Bar',
					],
					815  => [
						'invalid'  => 'json',
					],
				],
				[ 'Foo', 'Bar' ],
			],
			// Invalid attachment ID for one image.
			[
				[
					4711 => [
						'foo'      => 'bar',
						'rendered' => 'Foo',
					],
					42   => false,
					815  => [
						'foo'      => 'bar',
						'rendered' => 'Baz',
					],
				],
				[ 'Foo', 'Baz' ],
			],
		];
	}

	/**
	 * Tests ::get_unique_image_credits.
	 *
	 * @covers ::get_unique_image_credits
	 *
	 * @dataProvider provide_get_unique_image_credits_data
	 *
	 * @param  array    $json   An array of media credit JSON results indexed by image IDs.
	 * @param  string[] $result The expected result (an array of unique credits).
	 */
	public function test_get_unique_image_credits( $json, $result ) {
		// Input data.
		$content = 'Some fake post content.';

		// Intermediary data.
		$image_ids = \array_keys( $json );

		$this->sut->shouldReceive( 'get_image_ids' )->once()->with( $content )->andReturn( $image_ids );

		foreach ( $image_ids as $image_id ) {
			if ( \is_array( $json[ $image_id ] ) ) {
				$attachment = m::mock( \WP_Post::class );

				Functions\expect( 'get_post' )->once()->with( $image_id )->andReturn( $attachment );
				$this->core->shouldReceive( 'get_media_credit_json' )->once()->with( $attachment )->andReturn( $json[ $image_id ] );
			} else {
				Functions\expect( 'get_post' )->once()->with( $image_id )->andReturn( false );
			}
		}

		$this->assertSame( $result, $this->sut->get_unique_image_credits( $content ) );
	}

	/**
	 * Provides data for testing ::get_image_ids.
	 *
	 * @return array
	 */
	public function provide_get_image_ids_data() {
		return [
			[ '', false, 4711, [] ],
			[ 'Foobar', true, 4711, [ 4711 ] ],
			[ "<p>Foobar</p>\n<img class=\"size-thumbnail wp-image-42 alignleft\" src=\"//foobar/barfoo.jpg\">\n<p>Some more text</p><img class=\"size-full wp-image-4711 alignright\" src=\"//foobar/bar.jpg\">", true, 4711, [ 4711, 42, 4711 ] ],
		];
	}

	/**
	 * Tests ::get_image_ids.
	 *
	 * @covers ::get_image_ids
	 *
	 * @dataProvider provide_get_image_ids_data
	 *
	 * @param  string $content               The post content.
	 * @param  bool   $featured_image_credit Whether featured image credits should be included.
	 * @param  int    $featured_image_id     The featured image ID (or 0).
	 * @param  int[]  $result                The expected result.
	 */
	public function test_get_image_ids( $content, $featured_image_credit, $featured_image_id, $result ) {
		// Input data.
		$post = m::mock( \WP_Post::class );

		$this->settings->shouldReceive( 'get' )->once()->with( Settings::FEATURED_IMAGE_CREDIT )->andReturn( $featured_image_credit );
		Functions\expect( 'get_post_thumbnail_id' )->times( (int) $featured_image_credit )->with( $post )->andReturn( $featured_image_id );

		$this->assertSame( $result, $this->sut->get_image_ids( $content, $post ) );
	}

	/**
	 * Tests ::add_media_credit_to_post_thumbnail.
	 *
	 * @covers ::add_media_credit_to_post_thumbnail
	 */
	public function test_add_media_credit_to_post_thumbnail() {
		// Input data.
		$html              = 'Fake content';
		$post_id           = 4711;
		$post_thumbnail_id = 42;

		// Intermediary data.
		$credit         = 'Fake credit';
		$credit_style   = 'Fake credit style attribute';
		$wrapped_credit = 'Fake wrapped credit';

		// Expected result.
		$result = $html . $wrapped_credit;

		Functions\expect( 'in_the_loop' )->once()->withNoArgs()->andReturn( true );

		Filters\expectApplied( 'media_credit_post_thumbnail' )->once()->with( '', $html, $post_id, $post_thumbnail_id )->andReturn( '' );

		$this->sut->shouldReceive( 'get_featured_image_credit' )->once()->with( $post_id, $post_thumbnail_id )->andReturn( $credit );
		$this->sut->shouldReceive( 'get_featured_image_credit_style' )->once()->with( $html )->andReturn( $credit_style );
		$this->core->shouldReceive( 'wrap_media_credit_markup' )->once()->with( $credit, false, $credit_style )->andReturn( $wrapped_credit );

		$this->assertSame( $result, $this->sut->add_media_credit_to_post_thumbnail( $html, $post_id, $post_thumbnail_id ) );
	}

	/**
	 * Tests ::add_media_credit_to_post_thumbnail.
	 *
	 * @covers ::add_media_credit_to_post_thumbnail
	 */
	public function test_add_media_credit_to_post_thumbnail_empty_credit() {
		// Input data.
		$html              = 'Fake content';
		$post_id           = 4711;
		$post_thumbnail_id = 42;

		// Intermediary data.
		$credit = '';

		// Expected result.
		$result = $html;

		Functions\expect( 'in_the_loop' )->once()->withNoArgs()->andReturn( true );
		Filters\expectApplied( 'media_credit_post_thumbnail' )->once()->with( '', $html, $post_id, $post_thumbnail_id )->andReturn( '' );

		$this->sut->shouldReceive( 'get_featured_image_credit' )->once()->with( $post_id, $post_thumbnail_id )->andReturn( $credit );
		$this->sut->shouldReceive( 'get_featured_image_credit_style' )->never();
		$this->core->shouldReceive( 'wrap_media_credit_markup' )->never();

		$this->assertSame( $result, $this->sut->add_media_credit_to_post_thumbnail( $html, $post_id, $post_thumbnail_id ) );
	}

	/**
	 * Tests ::add_media_credit_to_post_thumbnail.
	 *
	 * @covers ::add_media_credit_to_post_thumbnail
	 */
	public function test_add_media_credit_to_post_thumbnail_non_empty_filter_result() {
		// Input data.
		$html              = 'Fake content';
		$post_id           = 4711;
		$post_thumbnail_id = 42;

		// Intermediary data.
		$filtered_html = 'Filtered content';

		// Expected result.
		$result = $filtered_html;

		Functions\expect( 'in_the_loop' )->once()->withNoArgs()->andReturn( true );
		Filters\expectApplied( 'media_credit_post_thumbnail' )->once()->with( '', $html, $post_id, $post_thumbnail_id )->andReturn( $filtered_html );

		$this->sut->shouldReceive( 'get_featured_image_credit' )->never();
		$this->sut->shouldReceive( 'get_featured_image_credit_style' )->never();
		$this->core->shouldReceive( 'wrap_media_credit_markup' )->never();

		$this->assertSame( $result, $this->sut->add_media_credit_to_post_thumbnail( $html, $post_id, $post_thumbnail_id ) );
	}

	/**
	 * Tests ::add_media_credit_to_post_thumbnail.
	 *
	 * @covers ::add_media_credit_to_post_thumbnail
	 */
	public function test_add_media_credit_to_post_thumbnail_not_in_the_loop() {
		// Input data.
		$html              = 'Fake content';
		$post_id           = 4711;
		$post_thumbnail_id = 42;

		// Expected result.
		$result = $html;

		Functions\expect( 'in_the_loop' )->once()->withNoArgs()->andReturn( false );
		Filters\expectApplied( 'media_credit_post_thumbnail' )->never();

		$this->sut->shouldReceive( 'get_featured_image_credit' )->never();
		$this->sut->shouldReceive( 'get_featured_image_credit_style' )->never();
		$this->core->shouldReceive( 'wrap_media_credit_markup' )->never();

		$this->assertSame( $result, $this->sut->add_media_credit_to_post_thumbnail( $html, $post_id, $post_thumbnail_id ) );
	}

	/**
	 * Tests ::get_featured_image_credit.
	 *
	 * @covers ::get_featured_image_credit
	 */
	public function test_get_featured_image_credit() {
		// Input data.
		$post_id           = 4711;
		$post_thumbnail_id = 42;

		// Intermediary data.
		$attachment      = m::mock( \WP_Post::class );
		$with_links      = false;
		$rendered_credit = '<a href="https://example.org">My Credit | My Organization</a>';
		$fancy_credit    = 'My Credit | My Organization';
		$json            = [
			'foo'      => 'bar',
			'rendered' => $rendered_credit,
			'fancy'    => $fancy_credit,
		];

		// Expected result.
		$result = $fancy_credit;

		Functions\expect( 'get_post' )->once()->with( $post_thumbnail_id )->andReturn( $attachment );

		$this->core->shouldReceive( 'get_media_credit_json' )->once()->with( $attachment )->andReturn( $json );

		Filters\expectApplied( 'media_credit_post_thumbnail_include_links' )->once()->with( false, $post_id, $post_thumbnail_id )->andReturn( $with_links );
		Functions\expect( 'esc_html' )->once()->with( $fancy_credit )->andReturn( $fancy_credit );

		$this->assertSame( $result, $this->sut->get_featured_image_credit( $post_id, $post_thumbnail_id ) );
	}

	/**
	 * Tests ::get_featured_image_credit.
	 *
	 * @covers ::get_featured_image_credit
	 */
	public function test_get_featured_image_credit_with_links() {
		// Input data.
		$post_id           = 4711;
		$post_thumbnail_id = 42;

		// Intermediary data.
		$attachment      = m::mock( \WP_Post::class );
		$with_links      = true;
		$rendered_credit = '<a href="https://example.org">My Credit | My Organization</a>';
		$fancy_credit    = 'My Credit | My Organization';
		$json            = [
			'foo'      => 'bar',
			'rendered' => $rendered_credit,
			'fancy'    => $fancy_credit,
		];

		// Expected result.
		$result = $rendered_credit;

		Functions\expect( 'get_post' )->once()->with( $post_thumbnail_id )->andReturn( $attachment );

		$this->core->shouldReceive( 'get_media_credit_json' )->once()->with( $attachment )->andReturn( $json );

		Filters\expectApplied( 'media_credit_post_thumbnail_include_links' )->once()->with( false, $post_id, $post_thumbnail_id )->andReturn( $with_links );
		Functions\expect( 'esc_html' )->never();

		$this->assertSame( $result, $this->sut->get_featured_image_credit( $post_id, $post_thumbnail_id ) );
	}

	/**
	 * Tests ::get_featured_image_credit.
	 *
	 * @covers ::get_featured_image_credit
	 */
	public function test_get_featured_image_credit_invalid_json() {
		// Input data.
		$post_id           = 4711;
		$post_thumbnail_id = 42;

		// Intermediary data.
		$attachment   = m::mock( \WP_Post::class );
		$fancy_credit = 'My Credit | My Organization';
		$json         = [
			'foo'   => 'bar',
			'fancy' => $fancy_credit,
		];

		Functions\expect( 'get_post' )->once()->with( $post_thumbnail_id )->andReturn( $attachment );

		$this->core->shouldReceive( 'get_media_credit_json' )->once()->with( $attachment )->andReturn( $json );

		Filters\expectApplied( 'media_credit_post_thumbnail_include_links' )->never();
		Functions\expect( 'esc_html' )->never();

		$this->assertSame( '', $this->sut->get_featured_image_credit( $post_id, $post_thumbnail_id ) );
	}

	/**
	 * Tests ::get_featured_image_credit.
	 *
	 * @covers ::get_featured_image_credit
	 */
	public function test_get_featured_image_credit_invalid_attachment() {
		// Input data.
		$post_id           = 4711;
		$post_thumbnail_id = 42;

		Functions\expect( 'get_post' )->once()->with( $post_thumbnail_id )->andReturn( null );

		$this->core->shouldReceive( 'get_media_credit_json' )->never();

		Filters\expectApplied( 'media_credit_post_thumbnail_include_links' )->never();
		Functions\expect( 'esc_html' )->never();

		$this->assertSame( '', $this->sut->get_featured_image_credit( $post_id, $post_thumbnail_id ) );
	}

	/**
	 * Tests ::get_featured_image_credit_style.
	 *
	 * @covers ::get_featured_image_credit_style
	 */
	public function test_get_featured_image_credit_style() {
		// Input data.
		$width = 4711;
		$html  = "Foo <img foo=\"bar\" width=\"{$width}\" /> Bar";

		// Expected result.
		$result = " style=\"max-width: {$width}px\"";

		$this->assertSame( $result, $this->sut->get_featured_image_credit_style( $html ) );
	}

	/**
	 * Tests ::get_featured_image_credit_style.
	 *
	 * @covers ::get_featured_image_credit_style
	 */
	public function test_get_featured_image_credit_style_no_width() {
		// Input data.
		$html = 'Foo <img foo="bar" /> Bar';

		$this->assertSame( '', $this->sut->get_featured_image_credit_style( $html ) );
	}
}
