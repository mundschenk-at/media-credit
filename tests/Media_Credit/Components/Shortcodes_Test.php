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

use Media_Credit\Tests\TestCase;

use Media_Credit\Components\Shortcodes;

use Media_Credit\Core;
use Media_Credit\Settings;
use Media_Credit\Tools\Shortcodes_Filter;
use Media_Credit\Tools\Template;

/**
 * Media_Credit\Components\Shortcodes unit test.
 *
 * @coversDefaultClass \Media_Credit\Components\Shortcodes
 * @usesDefaultClass \Media_Credit\Components\Shortcodes
 *
 * @uses ::__construct
 */
class Shortcodes_Test extends TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Shortcodes
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
	 * Required helper object.
	 *
	 * @var Template
	 */
	private $template;

	/**
	 * Required helper object.
	 *
	 * @var Shortcodes_Filter
	 */
	private $filter;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function set_up() {
		parent::set_up();

		$this->core     = m::mock( Core::class );
		$this->settings = m::mock( Settings::class );
		$this->template = m::mock( Template::class );
		$this->filter   = m::mock( Shortcodes_Filter::class );

		$this->sut = m::mock( Shortcodes::class, [ $this->core, $this->settings, $this->template, $this->filter ] )
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
		$template = m::mock( Template::class );
		$filter   = m::mock( Shortcodes_Filter::class );

		$sut = m::mock( Shortcodes::class )->makePartial();
		$sut->__construct( $core, $settings, $template, $filter );

		$this->assert_attribute_same( $core, 'core', $sut );
		$this->assert_attribute_same( $settings, 'settings', $sut );
		$this->assert_attribute_same( $template, 'template', $sut );
		$this->assert_attribute_same( $filter, 'filter', $sut );
	}

	/**
	 * Tests ::run.
	 *
	 * @covers ::run
	 */
	public function test_run() {
		Actions\expectAdded( 'init' )->once()->with( [ $this->sut, 'add_shortcodes' ] );

		$this->assertNull( $this->sut->run() );
	}

	/**
	 * Tests ::add_shortcodes.
	 *
	 * @covers ::add_shortcodes
	 */
	public function test_add_shortcodes() {
		Functions\expect( 'add_shortcode' )->once()->with( 'wp_caption', [ $this->sut, 'caption_shortcode' ] );
		Functions\expect( 'add_shortcode' )->once()->with( 'caption', [ $this->sut, 'caption_shortcode' ] );
		Functions\expect( 'add_shortcode' )->once()->with( 'media-credit', [ $this->sut, 'media_credit_shortcode' ] );

		$this->assertNull( $this->sut->add_shortcodes() );
	}

	/**
	 * Tests ::caption_shortcode.
	 *
	 * @covers ::caption_shortcode
	 */
	public function test_caption_shortcode_no_html5() {
		// Input data.
		$width   = 300;
		$attr    = [
			'fake'  => 'shortcode-attributes',
			'width' => $width,
		];
		$caption = 'Oh caption, my caption!';
		$content = "[media-credit id=5]<img src=\"foobar.jpg\">[/media-credit]{$caption}";

		// Intermediary data.
		$attr_with_caption            = $attr;
		$attr_with_caption['caption'] = $caption;
		$captionless_content          = '[media-credit standalone=0 id=5]<img src="foobar.jpg">[/media-credit]';

		// Expected result.
		$result = 'fake caption markup';

		$this->settings->shouldReceive( 'get' )->once()->with( Settings::SCHEMA_ORG_MARKUP )->andReturn( false );
		$this->settings->shouldReceive( 'get' )->never()->with( Settings::CREDIT_AT_END );
		$this->sut->shouldReceive( 'sanitize_attributes' )->never();

		Functions\expect( 'current_theme_supports' )->once()->with( 'html5', 'caption' )->andReturn( false );
		Filters\expectApplied( 'media_credit_shortcode_html5_caption' )->never();

		Functions\expect( 'img_caption_shortcode' )->once()->with( $attr_with_caption, $captionless_content )->andReturn( $result );
		$this->core->shouldReceive( 'maybe_add_schema_org_markup_to_figure' )->never();

		$this->assertSame( $result, $this->sut->caption_shortcode( $attr, $content ) );
	}

	/**
	 * Tests ::caption_shortcode.
	 *
	 * @covers ::caption_shortcode
	 */
	public function test_caption_shortcode_html5() {
		// Input data.
		$width   = 300;
		$attr    = [
			'fake'  => 'shortcode-attributes',
			'width' => $width,
		];
		$caption = 'Oh caption, my caption!';
		$content = "[media-credit id=5]<img src=\"foobar.jpg\">[/media-credit]{$caption}";

		// Intermediary data.
		$schema_org                   = false;
		$mc_attr                      = [
			'media-credit' => 'shortcode-attributes',
		];
		$sanitized_mc_attr            = [
			'sanitized' => 'media-credit shortcode-attributes',
			'width'     => $width,
		];
		$filtered_html5_caption       = 'Oh my filtered caption! With credit!';
		$attr_with_caption            = $attr;
		$attr_with_caption['caption'] = $filtered_html5_caption;
		$captionless_content          = '<img src="foobar.jpg">';
		$credit                       = 'My credit';

		// Expected result.
		$result = 'fake caption markup';

		$this->settings->shouldReceive( 'get' )->once()->with( Settings::SCHEMA_ORG_MARKUP )->andReturn( $schema_org );

		Functions\expect( 'current_theme_supports' )->once()->with( 'html5', 'caption' )->andReturn( true );

		$this->settings->shouldReceive( 'get' )->once()->with( Settings::CREDIT_AT_END )->andReturn( false );

		$this->filter->shouldReceive( 'parse_shortcode_attributes' )->once()->with( m::type( 'string' ) )->andReturn( $mc_attr );
		$this->sut->shouldReceive( 'sanitize_attributes' )->once()->with( $mc_attr )->andReturn( $sanitized_mc_attr );
		$this->sut->shouldReceive( 'inline_media_credit' )->once()->with( $sanitized_mc_attr, $schema_org )->andReturn( $credit );

		Filters\expectApplied( 'media_credit_shortcode_html5_caption' )->once()->with( "{$caption} {$credit}", $caption, $credit, $sanitized_mc_attr )->andReturn( $filtered_html5_caption );
		Functions\expect( 'img_caption_shortcode' )->once()->with( $attr_with_caption, $captionless_content )->andReturn( $result );

		$this->core->shouldReceive( 'maybe_add_schema_org_markup_to_figure' )->never();

		$this->assertSame( $result, $this->sut->caption_shortcode( $attr, $content ) );
	}

	/**
	 * Tests ::caption_shortcode.
	 *
	 * @covers ::caption_shortcode
	 */
	public function test_caption_shortcode_html5_credit_at_end() {
		// Input data.
		$width   = 300;
		$attr    = [
			'fake'  => 'shortcode-attributes',
			'width' => $width,
		];
		$caption = 'Oh caption, my caption!';
		$content = "[media-credit id=5]<img src=\"foobar.jpg\">[/media-credit]{$caption}";

		// Intermediary data.
		$schema_org                   = false;
		$attr_with_caption            = $attr;
		$attr_with_caption['caption'] = $caption;
		$content_without_mediacredit  = '<img src="foobar.jpg">';

		// Expected result.
		$result = 'fake caption markup';

		$this->settings->shouldReceive( 'get' )->once()->with( Settings::SCHEMA_ORG_MARKUP )->andReturn( $schema_org );

		Functions\expect( 'current_theme_supports' )->once()->with( 'html5', 'caption' )->andReturn( true );

		$this->settings->shouldReceive( 'get' )->once()->with( Settings::CREDIT_AT_END )->andReturn( true );

		$this->filter->shouldReceive( 'parse_shortcode_attributes' )->never();
		$this->sut->shouldReceive( 'sanitize_attributes' )->never();
		$this->sut->shouldReceive( 'inline_media_credit' )->never();
		Filters\expectApplied( 'media_credit_shortcode_html5_caption' )->never();

		Functions\expect( 'img_caption_shortcode' )->once()->with( $attr_with_caption, $content_without_mediacredit )->andReturn( $result );

		$this->core->shouldReceive( 'maybe_add_schema_org_markup_to_figure' )->never();

		$this->assertSame( $result, $this->sut->caption_shortcode( $attr, $content ) );
	}

	/**
	 * Tests ::caption_shortcode.
	 *
	 * @covers ::caption_shortcode
	 */
	public function test_caption_shortcode_html5_and_schema_org() {
		// Input data.
		$width   = 300;
		$attr    = [
			'fake'  => 'shortcode-attributes',
			'width' => $width,
		];
		$caption = 'Oh caption, my caption!';
		$content = "[media-credit id=5]<img src=\"foobar.jpg\">[/media-credit]{$caption}";

		// Intermediary data.
		$schema_org                   = true;
		$mc_attr                      = [
			'media-credit' => 'shortcode-attributes',
		];
		$sanitized_mc_attr            = [
			'sanitized' => 'media-credit shortcode-attributes',
			'width'     => $width,
		];
		$filtered_html5_caption       = 'Oh my filtered caption! With credit!';
		$attr_with_caption            = $attr;
		$attr_with_caption['caption'] = $filtered_html5_caption;
		$captionless_content          = '<img src="foobar.jpg">';
		$credit                       = 'My credit';
		$caption_markup               = 'fake caption markup';

		// Expected result.
		$result = 'fake caption markup with schema.org attributes';

		$this->settings->shouldReceive( 'get' )->once()->with( Settings::SCHEMA_ORG_MARKUP )->andReturn( $schema_org );

		Functions\expect( 'current_theme_supports' )->once()->with( 'html5', 'caption' )->andReturn( true );

		$this->settings->shouldReceive( 'get' )->once()->with( Settings::CREDIT_AT_END )->andReturn( false );

		$this->filter->shouldReceive( 'parse_shortcode_attributes' )->once()->with( m::type( 'string' ) )->andReturn( $mc_attr );
		$this->sut->shouldReceive( 'sanitize_attributes' )->once()->with( $mc_attr )->andReturn( $sanitized_mc_attr );
		$this->sut->shouldReceive( 'inline_media_credit' )->once()->with( $sanitized_mc_attr, $schema_org )->andReturn( $credit );

		Filters\expectApplied( 'media_credit_shortcode_html5_caption' )->once()->with( "{$caption} {$credit}", $caption, $credit, $sanitized_mc_attr )->andReturn( $filtered_html5_caption );
		Functions\expect( 'img_caption_shortcode' )->once()->with( $attr_with_caption, $captionless_content )->andReturn( $caption_markup );

		$this->core->shouldReceive( 'maybe_add_schema_org_markup_to_figure' )->once()->with( $caption_markup )->andReturn( $result );

		$this->assertSame( $result, $this->sut->caption_shortcode( $attr, $content ) );
	}

	/**
	 * Tests ::caption_shortcode.
	 *
	 * @covers ::caption_shortcode
	 */
	public function test_caption_shortcode_old_style_caption() {
		// Input data.
		$width   = 300;
		$caption = 'Oh caption, my caption!';
		$attr    = [
			'fake'    => 'shortcode-attributes',
			'width'   => $width,
			'caption' => $caption,
		];
		$content = '[media-credit id=5]<img src="foobar.jpg">[/media-credit]';

		// Intermediary data.
		$schema_org     = true;
		$caption_markup = 'fake caption markup';

		// Expected result.
		$result = 'fake caption markup with schema.org attributes';

		$this->settings->shouldReceive( 'get' )->once()->with( Settings::SCHEMA_ORG_MARKUP )->andReturn( $schema_org );

		Functions\expect( 'current_theme_supports' )->never();
		$this->settings->shouldReceive( 'get' )->never();

		$this->filter->shouldReceive( 'parse_shortcode_attributes' )->never();
		$this->sut->shouldReceive( 'sanitize_attributes' )->never();
		$this->sut->shouldReceive( 'inline_media_credit' )->never();
		Filters\expectApplied( 'media_credit_shortcode_html5_caption' )->never();

		Functions\expect( 'img_caption_shortcode' )->once()->with( $attr, $content )->andReturn( $caption_markup );

		$this->core->shouldReceive( 'maybe_add_schema_org_markup_to_figure' )->once()->with( $caption_markup )->andReturn( $result );

		$this->assertSame( $result, $this->sut->caption_shortcode( $attr, $content ) );
	}

	/**
	 * Tests ::caption_shortcode.
	 *
	 * @covers ::caption_shortcode
	 */
	public function test_caption_shortcode_empty_content() {
		// Input data.
		$width   = 300;
		$attr    = [
			'fake'    => 'shortcode-attributes',
			'width'   => $width,
		];
		$content = null;

		// Intermediary data.
		$schema_org     = true;
		$caption_markup = 'fake caption markup';

		// Expected result.
		$result = 'fake caption markup with schema.org attributes';

		$this->settings->shouldReceive( 'get' )->once()->with( Settings::SCHEMA_ORG_MARKUP )->andReturn( $schema_org );

		Functions\expect( 'current_theme_supports' )->never();
		$this->settings->shouldReceive( 'get' )->never();

		$this->filter->shouldReceive( 'parse_shortcode_attributes' )->never();
		$this->sut->shouldReceive( 'sanitize_attributes' )->never();
		$this->sut->shouldReceive( 'inline_media_credit' )->never();
		Filters\expectApplied( 'media_credit_shortcode_html5_caption' )->never();

		Functions\expect( 'img_caption_shortcode' )->once()->with( $attr, $content )->andReturn( $caption_markup );

		$this->core->shouldReceive( 'maybe_add_schema_org_markup_to_figure' )->once()->with( $caption_markup )->andReturn( $result );

		$this->assertSame( $result, $this->sut->caption_shortcode( $attr, $content ) );
	}

	/**
	 * Tests ::caption_shortcode.
	 *
	 * @covers ::caption_shortcode
	 */
	public function test_caption_shortcode_no_mediacredit() {
		// Input data.
		$width   = 300;
		$caption = 'Oh caption, my caption!';
		$attr    = [
			'fake'    => 'shortcode-attributes',
			'width'   => $width,
			'caption' => $caption,
		];
		$content = '<img src="foobar.jpg">';

		// Intermediary data.
		$schema_org     = true;
		$caption_markup = 'fake caption markup';

		// Expected result.
		$result = 'fake caption markup with schema.org attributes';

		$this->settings->shouldReceive( 'get' )->once()->with( Settings::SCHEMA_ORG_MARKUP )->andReturn( $schema_org );

		Functions\expect( 'current_theme_supports' )->never();
		$this->settings->shouldReceive( 'get' )->never();

		$this->filter->shouldReceive( 'parse_shortcode_attributes' )->never();
		$this->sut->shouldReceive( 'sanitize_attributes' )->never();
		$this->sut->shouldReceive( 'inline_media_credit' )->never();
		Filters\expectApplied( 'media_credit_shortcode_html5_caption' )->never();

		Functions\expect( 'img_caption_shortcode' )->once()->with( $attr, $content )->andReturn( $caption_markup );

		$this->core->shouldReceive( 'maybe_add_schema_org_markup_to_figure' )->once()->with( $caption_markup )->andReturn( $result );

		$this->assertSame( $result, $this->sut->caption_shortcode( $attr, $content ) );
	}

	/**
	 * Tests ::media_credit_shortcode.
	 *
	 * @covers ::media_credit_shortcode
	 */
	public function test_media_credit_shortcode() {
		// Input data.
		$width   = 300;
		$atts    = [
			'fake'  => 'shortcode-attributes',
			'width' => $width,
		];
		$content = 'Some fake <img>';

		// Intermediary data.
		$sanitized_atts = [
			'sanitized' => 'shortcode-attributes',
			'width'     => $width,
		];
		$html5          = true;
		$schema_org     = true;
		$filtered_width = 320;

		// Expected result.
		$result = 'MY SHORTCODE MARKUP';

		$this->settings->shouldReceive( 'get' )->once()->with( Settings::CREDIT_AT_END )->andReturn( false );
		$this->sut->shouldReceive( 'sanitize_attributes' )->once()->with( $atts )->andReturn( $sanitized_atts );

		Filters\expectApplied( 'media_credit_shortcode' )->once()->with( '', $sanitized_atts, $content )->andReturn( '' );
		Functions\expect( 'current_theme_supports' )->once()->with( 'html5', 'caption' )->andReturn( $html5 );
		Filters\expectApplied( 'img_caption_shortcode_width' )->once()->with( $width, $sanitized_atts, $content )->andReturn( $filtered_width );

		Functions\expect( 'do_shortcode' )->once()->with( $content )->andReturn( '<img> with expanded shortcodes' );
		$this->settings->shouldReceive( 'get' )->once()->with( Settings::SCHEMA_ORG_MARKUP )->andReturn( $schema_org );

		$this->template->shouldReceive( 'get_partial' )->once()->with( '/public/partials/media-credit-shortcode.php', m::type( 'array' ) )->andReturn( $result );

		$this->assertSame( $result, $this->sut->media_credit_shortcode( $atts, $content ) );
	}

	/**
	 * Tests ::media_credit_shortcode.
	 *
	 * @covers ::media_credit_shortcode
	 */
	public function test_media_credit_shortcode_credit_at_end() {
		// Input data.
		$width   = 300;
		$atts    = [
			'fake'  => 'shortcode-attributes',
			'width' => $width,
		];
		$content = 'Some fake <img>';

		// Expected result.
		$filtered_content = '<img> with expanded shortcodes';

		$this->settings->shouldReceive( 'get' )->once()->with( Settings::CREDIT_AT_END )->andReturn( true );
		$this->sut->shouldReceive( 'sanitize_attributes' )->never();

		Filters\expectApplied( 'media_credit_shortcode' )->never();
		Functions\expect( 'current_theme_supports' )->never();
		Filters\expectApplied( 'img_caption_shortcode_width' )->never();

		Functions\expect( 'do_shortcode' )->once()->with( $content )->andReturn( $filtered_content );
		$this->settings->shouldReceive( 'get' )->never()->with( Settings::SCHEMA_ORG_MARKUP );

		$this->assertSame( $filtered_content, $this->sut->media_credit_shortcode( $atts, $content ) );
	}

	/**
	 * Tests ::media_credit_shortcode.
	 *
	 * @covers ::media_credit_shortcode
	 */
	public function test_media_credit_shortcode_pre_filter() {
		// Input data.
		$width   = 300;
		$atts    = [
			'fake'  => 'shortcode-attributes',
			'width' => $width,
		];
		$content = 'Some fake <img>';

		// Intermediary data.
		$sanitized_atts = [
			'sanitized' => 'shortcode-attributes',
			'width'     => $width,
		];

		// Expected result.
		$pre_filter_shortcode = 'Custom shortcode filter result';

		$this->settings->shouldReceive( 'get' )->once()->with( Settings::CREDIT_AT_END )->andReturn( false );
		$this->sut->shouldReceive( 'sanitize_attributes' )->once()->with( $atts )->andReturn( $sanitized_atts );

		Filters\expectApplied( 'media_credit_shortcode' )->once()->with( '', $sanitized_atts, $content )->andReturn( $pre_filter_shortcode );
		Functions\expect( 'current_theme_supports' )->never();
		Filters\expectApplied( 'img_caption_shortcode_width' )->never();

		Functions\expect( 'do_shortcode' )->never();
		$this->settings->shouldReceive( 'get' )->never()->with( Settings::SCHEMA_ORG_MARKUP );

		$this->assertSame( $pre_filter_shortcode, $this->sut->media_credit_shortcode( $atts, $content ) );
	}

	/**
	 * Provides data for testing ::inline_media_credit.
	 *
	 * @return array
	 */
	public function provide_inline_media_credit_data() {
		return [
			[
				[
					'name'     => 'Marc Anthony',
					'link'     => '',
					'id'       => 0,
					'nofollow' => true,
				],
				true,
				'Marc Anthony',
			],
			[
				[
					'name'     => 'Marc Anthony',
					'link'     => 'https://example.org/',
					'id'       => 0,
					'nofollow' => false,
				],
				true,
				'<a href="https://example.org/">Marc Anthony</a>',
			],
			[
				[
					'name'     => 'Marc Anthony',
					'link'     => 'https://example.org/',
					'id'       => 0,
					'nofollow' => true,
				],
				true,
				'<a href="https://example.org/" rel="nofollow">Marc Anthony</a>',
			],
			[
				[
					'name'     => 'Marc Anthony',
					'link'     => 'https://example.org/',
					'id'       => 42,
					'nofollow' => true,
				],
				true,
				'<a href="https://example.org/" rel="nofollow">Marcus Antonius</a> | SPQR',
				'Marcus Antonius',
				' | SPQR',
			],
			[
				[
					'name'     => 'Marc Anthony',
					'link'     => '',
					'id'       => 42,
					'nofollow' => true,
				],
				true,
				'<a href="https://example.org/marcus-antonius" rel="nofollow">Marcus Antonius</a> | SPQR',
				'Marcus Antonius',
				' | SPQR',
				'https://example.org/marcus-antonius',
			],
		];
	}

	/**
	 * Tests ::inline_media_credit.
	 *
	 * @covers ::inline_media_credit
	 *
	 * @dataProvider provide_inline_media_credit_data
	 *
	 * @param  array       $attr               The shortcode attributes.
	 * @param  bool        $include_schema_org Whether schema.org markup should be injected.
	 * @param  string      $credit             The expected credit markup (without wrapper element).
	 * @param  string|null $display_name       Optional. The result of the get_the_author_meta call, if set. Default null.
	 * @param  string|null $org_suffix         Optional. The result of the $this->core->get_organization_suffix call, if set. Default null.
	 * @param  string|null $post_author_url    Optional. The result of the get_author_posts_url call, if set. Default null.
	 */
	public function test_inline_media_credit( $attr, $include_schema_org, $credit, $display_name = null, $org_suffix = null, $post_author_url = null ) {
		// Expected result.
		$markup = 'wrapped credit';

		Functions\expect( 'esc_html' )->twice()->with( m::type( 'string' ) )->andReturnArg( 0 );
		Functions\expect( 'esc_url' )->atMost()->once()->with( m::type( 'string' ) )->andReturnArg( 0 );

		if ( isset( $display_name ) ) {
			Functions\expect( 'get_the_author_meta' )->once()->with( 'display_name', $attr['id'] )->andReturn( $display_name );
		} else {
			Functions\expect( 'get_the_author_meta' )->never();
		}

		if ( isset( $org_suffix ) ) {
			$this->core->shouldReceive( 'get_organization_suffix' )->once()->withNoArgs()->andReturn( $org_suffix );
		} else {
			$this->core->shouldReceive( 'get_organization_suffix' )->never();
		}

		if ( isset( $post_author_url ) ) {
			Functions\expect( 'get_author_posts_url' )->once()->with( $attr['id'] )->andReturn( $post_author_url );
		} else {
			Functions\expect( 'get_author_posts_url' )->never();
		}

		$this->core->shouldReceive( 'wrap_media_credit_markup' )->once()->with( $credit, $include_schema_org )->andReturn( $markup );
		Filters\expectApplied( 'media_credit_shortcode_inline_markup' )->once()->with( $markup, $attr, $include_schema_org )->andReturn( $markup );

		$this->assertSame( $markup, $this->sut->inline_media_credit( $attr, $include_schema_org ) );
	}

	/**
	 * Provides data for testing ::sanitize_attributes.
	 *
	 * @return array
	 */
	public function provide_sanitize_attributes_data() {
		return [
			[
				[],
				[
					'id'         => 0,
					'name'       => '',
					'link'       => '',
					'standalone' => true,
					'align'      => 'none (SANITIZED HTML CLASS)',
					'width'      => 0,
					'nofollow'   => false,
				],
			],
			[
				[
					'id'         => -42,
					'name'       => 'Foobar',
					'link'       => 'https://example.org',
					'standalone' => false,
					'align'      => 'alignright',
					'width'      => -500,
					'nofollow'   => 1,
				],
				[
					'id'         => 42,
					'name'       => 'Foobar (SANITIZED TEXT)',
					'link'       => 'https://example.org (SANITIZED URL)',
					'standalone' => false,
					'align'      => 'right (SANITIZED HTML CLASS)',
					'width'      => 500,
					'nofollow'   => true,
				],
			],
			[
				[
					'id'         => 4711,
					'name'       => 'Barfoo',
					'link'       => 'https://example.org',
					'align'      => 'left',
					'width'      => 300,
					'nofollow'   => 'false',
				],
				[
					'id'         => 4711,
					'name'       => 'Barfoo (SANITIZED TEXT)',
					'link'       => 'https://example.org (SANITIZED URL)',
					'standalone' => true,
					'align'      => 'left (SANITIZED HTML CLASS)',
					'width'      => 300,
					'nofollow'   => false,
				],
			],
		];
	}

	/**
	 * Tests ::sanitize_attributes.
	 *
	 * @covers ::sanitize_attributes
	 *
	 * @dataProvider provide_sanitize_attributes_data
	 *
	 * @param  array $atts   The shortcode attributes.
	 * @param  array $result The expected result.
	 */
	public function test_sanitize_attributes( $atts, $result ) {
		Functions\expect( 'shortcode_atts' )->once()->with( Shortcodes::MEDIA_CREDIT_DEFAULTS, $atts, 'media-credit' )->andReturnUsing(
			function( $defaults, $atts, $shortcode ) {
				return \array_merge( $defaults, $atts );
			}
		);

		Functions\expect( 'absint' )->twice()->andReturnUsing(
			function( $value ) {
				return \abs( (int) $value );
			}
		);
		Functions\expect( 'sanitize_text_field' )->once()->andReturnUsing(
			function( $value ) {
				return empty( $value ) ? '' : "{$value} (SANITIZED TEXT)";
			}
		);
		Functions\expect( 'esc_url_raw' )->once()->andReturnUsing(
			function( $value ) {
				return empty( $value ) ? '' : "{$value} (SANITIZED URL)";
			}
		);
		Functions\expect( 'sanitize_html_class' )->once()->andReturnUsing(
			function( $value ) {
				return empty( $value ) ? '' : "{$value} (SANITIZED HTML CLASS)";
			}
		);

		$this->assertSame( $result, $this->sut->sanitize_attributes( $atts ) );
	}
}
