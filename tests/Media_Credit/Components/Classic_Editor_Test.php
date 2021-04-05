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

use Media_Credit\Components\Classic_Editor;

use Media_Credit\Core;

/**
 * Media_Credit\Components\Classic_Editor unit test.
 *
 * @coversDefaultClass \Media_Credit\Components\Classic_Editor
 * @usesDefaultClass \Media_Credit\Components\Classic_Editor
 *
 * @uses ::__construct
 */
class Classic_Editor_Test extends TestCase {

	const VERSION = '6.6.6';

	/**
	 * The system-under-test.
	 *
	 * @var Classic_Editor
	 */
	private $sut;

	/**
	 * Required helper object.
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
				'admin'       => [
					'partials' => [
						'media-credit-image-properties-tmpl.php' => 'IMAGE PROPERTY TEMPLATE',
					],
				],
			],
		];
		vfsStream::setup( 'root', null, $filesystem );
		set_include_path( 'vfs://root/' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_set_include_path

		$this->core = m::mock( Core::class );

		$this->sut = m::mock( Classic_Editor::class, [ self::VERSION, $this->core ] )
			->makePartial()
			->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$version = '4.7.11';
		$core    = m::mock( Core::class );

		$sut = m::mock( Classic_Editor::class )->makePartial();
		$sut->__construct( $version, $core );

		$this->assert_attribute_same( $version, 'version', $sut );
		$this->assert_attribute_same( $core, 'core', $sut );
	}

	/**
	 * Tests ::run.
	 *
	 * @covers ::run
	 */
	public function test_run() {
		Actions\expectAdded( 'init' )->once()->with( [ $this->sut, 'initialize_editor_integration' ] );

		$this->assertNull( $this->sut->run() );
	}

	/**
	 * Tests ::initialize_editor_integration.
	 *
	 * @covers ::initialize_editor_integration
	 */
	public function test_initialize_editor_integration_cannot_richedit() {
		Functions\expect( 'user_can_richedit' )->once()->andReturn( false );

		Functions\expect( 'plugin_dir_url' )->never();
		Filters\expectAdded( 'tiny_mce_plugins' )->never();
		Filters\expectAdded( 'mce_external_plugins' )->never();
		Filters\expectAdded( 'mce_css' )->never();
		Actions\expectAdded( 'wp_enqueue_editor' )->never();
		Actions\expectAdded( 'wp_enqueue_editor' )->never();

		Filters\expectAdded( 'image_send_to_editor' )->once()->with( [ $this->sut, 'add_media_credit_to_image' ], m::type( 'int' ), m::type( 'int' ) );

		$this->assertNull( $this->sut->initialize_editor_integration() );
	}

	/**
	 * Tests ::initialize_editor_integration.
	 *
	 * @covers ::initialize_editor_integration
	 */
	public function test_initialize_editor_integration_can_richedit() {
		$plugin_dir_url = 'https://example.org/plugin/dir';

		Functions\expect( 'user_can_richedit' )->once()->andReturn( true );
		Functions\expect( 'plugin_dir_url' )->once()->with( m::type( 'string' ) )->andReturn( $plugin_dir_url );

		Filters\expectAdded( 'tiny_mce_plugins' )->once()->with( [ $this->sut, 'tinymce_internal_plugins' ] );
		Filters\expectAdded( 'mce_external_plugins' )->once()->with( [ $this->sut, 'tinymce_external_plugins' ] );
		Filters\expectAdded( 'mce_css' )->once()->with( [ $this->sut, 'tinymce_css' ] );
		Actions\expectAdded( 'wp_enqueue_editor' )->once()->with( [ $this->sut, 'enqueue_editor' ] );
		Actions\expectAdded( 'print_media_templates' )->once()->with( [ $this->sut, 'image_properties_template' ] );

		Filters\expectAdded( 'image_send_to_editor' )->once()->with( [ $this->sut, 'add_media_credit_to_image' ], m::type( 'int' ), m::type( 'int' ) );

		$this->assertNull( $this->sut->initialize_editor_integration() );

		$this->assert_attribute_same( $plugin_dir_url, 'url', $this->sut );
	}

	/**
	 * Tests ::tinymce_css.
	 *
	 * @covers ::tinymce_css
	 */
	public function test_tinymce_css() {
		// Input data.
		$css = 'http://example.org/foo.css,https://example.org/bar.css';

		// Prepare internal state.
		$url    = 'https://example.org/my/plugin';
		$suffix = '.my.suffix';
		$this->set_value( $this->sut, 'url', $url );
		$this->set_value( $this->sut, 'suffix', $suffix );

		// Expected result.
		$result = "{$css},{$url}/admin/css/media-credit-tinymce{$suffix}.css";

		$this->assertSame( $result, $this->sut->tinymce_css( $css ) );
	}

	/**
	 * Tests ::tinymce_css.
	 *
	 * @covers ::tinymce_css
	 */
	public function test_tinymce_css_no_other_css() {
		// Input data.
		$css = '';

		// Prepare internal state.
		$url    = 'https://example.org/my/plugin';
		$suffix = '.my.suffix';
		$this->set_value( $this->sut, 'url', $url );
		$this->set_value( $this->sut, 'suffix', $suffix );

		// Expected result.
		$result = "{$url}/admin/css/media-credit-tinymce{$suffix}.css";

		$this->assertSame( $result, $this->sut->tinymce_css( $css ) );
	}

	/**
	 * Tests ::enqueue_editor.
	 *
	 * @covers ::enqueue_editor
	 */
	public function test_enqueue_editor() {
		// Input data.
		$to_load = [
			'foo'     => true,
			'bar'     => false,
			'tinymce' => true,
		];

		Functions\expect( 'wp_enqueue_script' )->once()->with( 'media-credit-image-properties', m::type( 'string' ), m::type( 'array' ), self::VERSION, true );
		Functions\expect( 'wp_enqueue_script' )->once()->with( 'media-credit-tinymce-switch', m::type( 'string' ), m::type( 'array' ), self::VERSION, true );
		Functions\expect( 'wp_enqueue_style' )->once()->with( 'media-credit-image-properties-style', m::type( 'string' ), m::type( 'array' ), self::VERSION, 'screen' );

		$this->assertNull( $this->sut->enqueue_editor( $to_load ) );
	}

	/**
	 * Tests ::enqueue_editor.
	 *
	 * @covers ::enqueue_editor
	 */
	public function test_enqueue_editor_nothing_to_do() {
		// Input data.
		$to_load = [
			'foo'     => true,
			'bar'     => false,
		];

		Functions\expect( 'wp_enqueue_script' )->never();
		Functions\expect( 'wp_enqueue_style' )->never();

		$this->assertNull( $this->sut->enqueue_editor( $to_load ) );
	}

	/**
	 * Tests ::tinymce_internal_plugins.
	 *
	 * @covers ::tinymce_internal_plugins
	 */
	public function test_tinymce_internal_plugins() {
		// Input data.
		$plugins = [
			'foo',
			'wpeditimage',
			'bar',
		];

		$this->assertArrayNotHasKey( 'wpeditimage', $this->sut->tinymce_internal_plugins( $plugins ) );
	}

	/**
	 * Tests ::tinymce_external_plugins.
	 *
	 * @covers ::tinymce_external_plugins
	 */
	public function test_tinymce_external_plugins() {
		// Input data.
		$plugins = [
			'foo' => 'bar',
		];

		$result = $this->sut->tinymce_external_plugins( $plugins );

		$this->assertArrayHasKey( 'mediacredit', $result );
		$this->assertArrayHasKey( 'noneditable', $result );
	}

	/**
	 * Tests ::image_properties_template.
	 *
	 * @covers ::image_properties_template
	 */
	public function test_image_properties_template() {
		$this->expectOutputString( 'IMAGE PROPERTY TEMPLATE' );

		$this->assertNull( $this->sut->image_properties_template() );
	}

	/**
	 * Tests ::add_media_credit_to_image.
	 *
	 * @covers ::add_media_credit_to_image
	 */
	public function test_add_media_credit_to_image_with_freeform() {
		// Input data.
		$html          = '<img class="size-thumbnail wp-image-13163 alignleft" src="https://example.org/wp-content/uploads/2016/10/another-image-150x150.png" alt="Another picture" width="150" height="150" />';
		$attachment_id = 42;
		$caption       = 'my caption'; // ignored.
		$title         = 'My image title.'; // ignored.
		$align         = 'left';

		// Intermediary data.
		$attachment  = m::mock( \WP_Post::class );
		$user_id     = 0;
		$freeform    = 'My freeform credit';
		$url         = 'https://example.org/some/url';
		$nofollow    = false;
		$rendered    = 'My rendered credit (HTML)';
		$plaintext   = 'My plaintext credit';
		$fancy       = 'My fancy credit';
		$credit_json = [
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

		// Expected result.
		$result = "[media-credit name=\"{$freeform}\" link=\"{$url}\" width=150 align=\"{$align}\"]<img class=\"size-thumbnail wp-image-13163\" src=\"https://example.org/wp-content/uploads/2016/10/another-image-150x150.png\" alt=\"Another picture\" width=\"150\" height=\"150\" />[/media-credit]";

		Functions\expect( 'get_post' )->once()->with( $attachment_id )->andReturn( $attachment );

		$this->core->shouldReceive( 'get_media_credit_json' )->once()->with( $attachment )->andReturn( $credit_json );

		Filters\expectApplied( 'media_add_credit_shortcode' )->once()->with( $result, m::type( 'string' ) )->andReturnFirstArg();

		$this->assertSame( $result, $this->sut->add_media_credit_to_image( $html, $attachment_id, $caption, $title, $align ) );
	}

	/**
	 * Tests ::add_media_credit_to_image.
	 *
	 * @covers ::add_media_credit_to_image
	 */
	public function test_add_media_credit_to_image_with_author_id_and_nofollow() {
		// Input data.
		$html          = '<img class="size-thumbnail alignleft wp-image-13163" src="https://example.org/wp-content/uploads/2016/10/another-image-150x150.png" alt="Another picture" width="150" height="150" />';
		$attachment_id = 42;
		$caption       = 'my caption'; // ignored.
		$title         = 'My image title.'; // ignored.
		$align         = 'left';

		// Intermediary data.
		$attachment  = m::mock( \WP_Post::class );
		$user_id     = 42;
		$freeform    = '';
		$url         = 'https://example.org/some/url';
		$nofollow    = true;
		$rendered    = 'My rendered credit (HTML)';
		$plaintext   = 'My plaintext credit';
		$fancy       = 'My fancy credit';
		$credit_json = [
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

		// Expected result.
		$result = "[media-credit id={$user_id} link=\"{$url}\" nofollow=1 width=150 align=\"{$align}\"]<img class=\"size-thumbnail wp-image-13163\" src=\"https://example.org/wp-content/uploads/2016/10/another-image-150x150.png\" alt=\"Another picture\" width=\"150\" height=\"150\" />[/media-credit]";

		Functions\expect( 'get_post' )->once()->with( $attachment_id )->andReturn( $attachment );

		$this->core->shouldReceive( 'get_media_credit_json' )->once()->with( $attachment )->andReturn( $credit_json );

		Filters\expectApplied( 'media_add_credit_shortcode' )->once()->with( $result, m::type( 'string' ) )->andReturnFirstArg();

		$this->assertSame( $result, $this->sut->add_media_credit_to_image( $html, $attachment_id, $caption, $title, $align ) );
	}

	/**
	 * Tests ::add_media_credit_to_image.
	 *
	 * @covers ::add_media_credit_to_image
	 */
	public function test_add_media_credit_to_image_with_author_id_and_nofollow_initial_alignment() {
		// Input data.
		$html          = '<img class="alignleft size-thumbnail wp-image-13163" src="https://example.org/wp-content/uploads/2016/10/another-image-150x150.png" alt="Another picture" width="150" height="150" />';
		$attachment_id = 42;
		$caption       = 'my caption'; // ignored.
		$title         = 'My image title.'; // ignored.
		$align         = 'left';

		// Intermediary data.
		$attachment  = m::mock( \WP_Post::class );
		$user_id     = 42;
		$freeform    = '';
		$url         = 'https://example.org/some/url';
		$nofollow    = true;
		$rendered    = 'My rendered credit (HTML)';
		$plaintext   = 'My plaintext credit';
		$fancy       = 'My fancy credit';
		$credit_json = [
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

		// Expected result.
		$result = "[media-credit id={$user_id} link=\"{$url}\" nofollow=1 width=150 align=\"{$align}\"]<img class=\"size-thumbnail wp-image-13163\" src=\"https://example.org/wp-content/uploads/2016/10/another-image-150x150.png\" alt=\"Another picture\" width=\"150\" height=\"150\" />[/media-credit]";

		Functions\expect( 'get_post' )->once()->with( $attachment_id )->andReturn( $attachment );

		$this->core->shouldReceive( 'get_media_credit_json' )->once()->with( $attachment )->andReturn( $credit_json );

		Filters\expectApplied( 'media_add_credit_shortcode' )->once()->with( $result, m::type( 'string' ) )->andReturnFirstArg();

		$this->assertSame( $result, $this->sut->add_media_credit_to_image( $html, $attachment_id, $caption, $title, $align ) );
	}

	/**
	 * Tests ::add_media_credit_to_image.
	 *
	 * @covers ::add_media_credit_to_image
	 */
	public function test_add_media_credit_to_image_invalid_attachment() {
		// Input data.
		$html          = '<img class="size-thumbnail wp-image-13163 alignleft" src="https://example.org/wp-content/uploads/2016/10/another-image-150x150.png" alt="Another picture" width="150" height="150" />';
		$attachment_id = 42;
		$caption       = 'my caption'; // ignored.
		$title         = 'My image title.'; // ignored.
		$align         = 'left';

		Functions\expect( 'get_post' )->once()->with( $attachment_id )->andReturnNull();

		$this->core->shouldReceive( 'get_media_credit_json' )->never();

		$this->assertSame( $html, $this->sut->add_media_credit_to_image( $html, $attachment_id, $caption, $title, $align ) );
	}

	/**
	 * Tests ::add_media_credit_to_image.
	 *
	 * @covers ::add_media_credit_to_image
	 */
	public function test_add_media_credit_to_image_with_empty_credit() {
		// Input data.
		$html          = '<img class="size-thumbnail wp-image-13163 alignleft" src="https://example.org/wp-content/uploads/2016/10/another-image-150x150.png" alt="Another picture" width="150" height="150" />';
		$attachment_id = 42;
		$caption       = 'my caption'; // ignored.
		$title         = 'My image title.'; // ignored.
		$align         = 'left';

		// Intermediary data.
		$attachment  = m::mock( \WP_Post::class );
		$user_id     = 42;
		$freeform    = Core::EMPTY_META_STRING;
		$url         = 'https://example.org/some/url';
		$nofollow    = true;
		$rendered    = 'My rendered credit (HTML)';
		$plaintext   = 'My plaintext credit';
		$fancy       = 'My fancy credit';
		$credit_json = [
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

		Functions\expect( 'get_post' )->once()->with( $attachment_id )->andReturn( $attachment );

		$this->core->shouldReceive( 'get_media_credit_json' )->once()->with( $attachment )->andReturn( $credit_json );

		$this->assertSame( $html, $this->sut->add_media_credit_to_image( $html, $attachment_id, $caption, $title, $align ) );
	}

	/**
	 * Tests ::add_media_credit_to_image.
	 *
	 * @covers ::add_media_credit_to_image
	 */
	public function test_add_media_credit_to_image_with_empty_plaintext_credit() {
		// Input data.
		$html          = '<img class="size-thumbnail wp-image-13163 alignleft" src="https://example.org/wp-content/uploads/2016/10/another-image-150x150.png" alt="Another picture" width="150" height="150" />';
		$attachment_id = 42;
		$caption       = 'my caption'; // ignored.
		$title         = 'My image title.'; // ignored.
		$align         = 'left';

		// Intermediary data.
		$attachment  = m::mock( \WP_Post::class );
		$user_id     = 42;
		$freeform    = Core::EMPTY_META_STRING;
		$url         = 'https://example.org/some/url';
		$nofollow    = true;
		$rendered    = 'My rendered credit (HTML)';
		$plaintext   = '';
		$fancy       = 'My fancy credit';
		$credit_json = [
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

		Functions\expect( 'get_post' )->once()->with( $attachment_id )->andReturn( $attachment );

		$this->core->shouldReceive( 'get_media_credit_json' )->once()->with( $attachment )->andReturn( $credit_json );

		$this->assertSame( $html, $this->sut->add_media_credit_to_image( $html, $attachment_id, $caption, $title, $align ) );
	}
}
