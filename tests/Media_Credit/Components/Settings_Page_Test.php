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

use Media_Credit\Components\Settings_Page;

use Media_Credit\Settings;
use Media_Credit\Data_Storage\Options;

use Mundschenk\UI\Control_Factory;
use Mundschenk\UI\Control;
use Mundschenk\UI\Controls;

/**
 * Media_Credit\Components\Settings_Page unit test.
 *
 * @coversDefaultClass \Media_Credit\Components\Settings_Page
 * @usesDefaultClass \Media_Credit\Components\Settings_Page
 *
 * @uses ::__construct
 */
class Settings_Page_Test extends \Media_Credit\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Settings
	 */
	private $sut;

	/**
	 * Required helper object.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Required helper object.
	 *
	 * @var Settings
	 */
	private $settings;


	/**
	 * Required class helper.
	 *
	 * @var Control_Factory
	 */
	private $control_factory;
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
						'settings' => [
							'preview.php' => 'PREVIEW TEMPLATE',
							'section.php' => 'SECTION TEMPLATE',
						],
					],
				],
			],
		];
		vfsStream::setup( 'root', null, $filesystem );
		set_include_path( 'vfs://root/' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_set_include_path

		Functions\when( '__' )->returnArg( 1 );
		Functions\when( '_x' )->returnArg( 1 );
		Functions\when( '_n' )->returnArg( 2 );

		$this->control_factory = m::mock( 'alias:' . Control_Factory::class );

		$this->options  = m::mock( Options::class );
		$this->settings = m::mock( Settings::class );

		$this->sut = m::mock( Settings_Page::class, [ $this->options, $this->settings ] )
			->makePartial()
			->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$options  = m::mock( Options::class );
		$settings = m::mock( Settings::class );

		$sut = m::mock( Settings_Page::class )->makePartial();
		$sut->__construct( $options, $settings );

		$this->assert_attribute_same( $options, 'options', $sut );
		$this->assert_attribute_same( $settings, 'settings', $sut );
	}

	/**
	 * Tests ::run.
	 *
	 * @covers ::run
	 */
	public function test_run() {
		// Intermediary data.
		$basename = 'my_plugin_basename';

		Functions\expect( 'is_admin' )->once()->withNoArgs()->andReturn( true );

		Actions\expectAdded( 'admin_init' )->once()->with( [ $this->sut, 'register_settings' ] );
		Actions\expectAdded( 'admin_enqueue_scripts' )->once()->with( [ $this->sut, 'enqueue_scripts_and_styles' ] );

		Functions\expect( 'plugin_basename' )->once()->with( \MEDIA_CREDIT_PLUGIN_FILE )->andReturn( $basename );
		Filters\expectAdded( "plugin_action_links_{$basename}" )->once()->with( [ $this->sut, 'add_action_links' ] );

		$this->assertNull( $this->sut->run() );
	}

	/**
	 * Tests ::run.
	 *
	 * @covers ::run
	 */
	public function test_run_frontend() {
		// Intermediary data.
		$basename = 'my_plugin_basename';

		Functions\expect( 'is_admin' )->once()->withNoArgs()->andReturn( false );

		Actions\expectAdded( 'admin_init' )->never();
		Actions\expectAdded( 'admin_enqueue_scripts' )->never();

		Functions\expect( 'plugin_basename' )->never();
		Actions\expectAdded( "plugin_action_links_{$basename}" )->never();

		$this->assertNull( $this->sut->run() );
	}

	/**
	 * Tests ::register_settings.
	 *
	 * @covers ::register_settings
	 */
	public function test_register_settings() {
		// Intermediary data.
		$option_name    = 'my_option_name';
		$field_defs     = [
			'foobar' => [
				'fake' => 'field defintion',
			],
		];
		$preview_markup = 'Fake preview HTML';
		$controls       = [
			m::mock( Control::class ),
			m::mock( Control::class ),
			m::mock( Control::class ),
		];

		Functions\expect( 'add_settings_section' )->once()->with( Settings_Page::SETTINGS_SECTION, m::type( 'string' ), [ $this->sut, 'print_settings_section' ], 'media' );

		$this->options->shouldReceive( 'get_name' )->once()->with( Options::OPTION )->andReturn( $option_name );
		Functions\expect( 'register_setting' )->once()->with( 'media', $option_name, [ $this->sut, 'sanitize_settings' ] );

		$this->settings->shouldReceive( 'get_fields' )->once()->withNoArgs()->andReturn( $field_defs );
		$this->sut->shouldReceive( 'get_preview_markup' )->once()->withNoArgs()->andReturn( $preview_markup );

		$this->control_factory->shouldReceive( 'initialize' )->once()->with( m::type( 'array' ), $this->options, Options::OPTION )->andReturn( $controls );
		foreach ( $controls as $control ) {
			$control->shouldReceive( 'register' )->once()->with( 'media' );
		}

		$this->assertNull( $this->sut->register_settings() );

		$preview_data = $this->get_value( $this->sut, 'preview_data' );
		$this->assertArrayHasKey( 'pattern', $preview_data );
		$this->assert_is_string( $preview_data['pattern'] );
	}

	/**
	 * Tests ::get_preview_markup.
	 *
	 * @covers ::get_preview_markup
	 */
	public function test_get_preview_markup() {
		// Intermediary data.
		$settings     = [
			'mocked' => 'settings',
		];
		$preview_data = [
			'mocked' => 'preview data',
		];

		// Expected result.
		$result = 'PREVIEW TEMPLATE';

		$this->settings->shouldReceive( 'get_all_settings' )->once()->withNoArgs()->andReturn( $settings );
		$this->set_value( $this->sut, 'preview_data', $preview_data );

		$this->assertSame( $result, $this->sut->get_preview_markup() );
	}

	/**
	 * Tests ::enqueue_scripts_and_styles.
	 *
	 * @covers ::enqueue_scripts_and_styles
	 */
	public function test_enqueue_scripts_and_styles() {
		// Input data.
		$hook_suffix = 'options-media.php';

		// Intermediary data.
		$url     = 'https://example.org/plugin/';
		$version = '99.99.0';

		// Set up object state.
		$preview_data = [
			'mocked' => 'preview data',
		];
		$this->set_value( $this->sut, 'preview_data', $preview_data );

		Functions\expect( 'plugin_dir_url' )->once()->with( \MEDIA_CREDIT_PLUGIN_FILE )->andReturn( $url );
		$this->settings->shouldReceive( 'get_version' )->once()->withNoArgs()->andReturn( $version );

		Functions\expect( 'wp_enqueue_style' )->once()->with( 'media-credit-preview-style', m::type( 'string' ), [], $version, 'screen' );
		Functions\expect( 'wp_enqueue_script' )->once()->with( 'media-credit-preview', m::type( 'string' ), [ 'jquery' ], $version, true );
		Functions\expect( 'wp_localize_script' )->once()->with( 'media-credit-preview', 'mediaCreditPreviewData', $preview_data );

		$this->assertNull( $this->sut->enqueue_scripts_and_styles( $hook_suffix ) );
	}

	/**
	 * Tests ::enqueue_scripts_and_styles.
	 *
	 * @covers ::enqueue_scripts_and_styles
	 */
	public function test_enqueue_scripts_and_styles_invalid_hook() {
		// Input data.
		$hook_suffix = 'options-foobar.php';

		// Set up object state.
		$preview_data = [
			'mocked' => 'preview data',
		];
		$this->set_value( $this->sut, 'preview_data', $preview_data );

		Functions\expect( 'plugin_dir_url' )->never();
		$this->settings->shouldReceive( 'get_version' )->never();

		Functions\expect( 'wp_enqueue_style' )->once()->never();
		Functions\expect( 'wp_enqueue_script' )->once()->never();
		Functions\expect( 'wp_localize_script' )->once()->never();

		$this->assertNull( $this->sut->enqueue_scripts_and_styles( $hook_suffix ) );
	}

	/**
	 * Provides data for testing ::sanitize_settings.
	 *
	 * @return array
	 */
	public function provide_sanitize_settings_data() {
		return [
			// No checkboxes.
			[
				[
					'foo' => 'bar',
					'bar' => 'baz',
				],
				[
					'foo' => [
						'ui' => Controls\Text_Input::class,
					],
					'bar' => [
						'ui' => Controls\Text_Input::class,
					],
					'baz' => [
						'ui' => Controls\Number_Input::class,
					],
				],
				[
					'foo' => 'oldfoo',
					'bar' => 'oldbar',
					'baz' => 'oldbaz',
				],
				[
					'foo' => 'sanitized_text',
					'bar' => 'sanitized_text',
					'baz' => 'oldbaz',
				],
			],
			// 1 checkboxes, filled.
			[
				[
					'foo' => 'bar',
					'bar' => 'baz',
					'baz' => '1',
				],
				[
					'foo' => [
						'ui' => Controls\Text_Input::class,
					],
					'bar' => [
						'ui' => Controls\Text_Input::class,
					],
					'baz' => [
						'ui' => Controls\Checkbox_Input::class,
					],
				],
				[
					'foo' => 'oldfoo',
					'bar' => 'oldbar',
					'baz' => 'oldbaz',
				],
				[
					'foo' => 'sanitized_text',
					'bar' => 'sanitized_text',
					'baz' => 'sanitized_text',
				],
			],
			// 1 checkboxes, not filled.
			[
				[
					'foo' => 'bar',
					'bar' => 'baz',
				],
				[
					'foo' => [
						'ui' => Controls\Text_Input::class,
					],
					'bar' => [
						'ui' => Controls\Text_Input::class,
					],
					'baz' => [
						'ui' => Controls\Checkbox_Input::class,
					],
				],
				[
					'foo' => 'oldfoo',
					'bar' => 'oldbar',
					'baz' => 'oldbaz',
				],
				[
					'foo' => 'sanitized_text',
					'bar' => 'sanitized_text',
					'baz' => 'sanitized_text',
				],
			],
			// No checkboxes, but with separator field.
			[
				[
					Settings::SEPARATOR => 'foobar',
					'foo'               => 'bar',
					'bar'               => 'baz',
				],
				[
					'foo' => [
						'ui' => Controls\Text_Input::class,
					],
					'bar' => [
						'ui' => Controls\Text_Input::class,
					],
					'baz' => [
						'ui' => Controls\Number_Input::class,
					],
				],
				[
					Settings::SEPARATOR => 'oldseparator',
					'foo'               => 'oldfoo',
					'bar'               => 'oldbar',
					'baz'               => 'oldbaz',
				],
				[
					Settings::SEPARATOR => 'sanitized_separator',
					'foo'               => 'sanitized_text',
					'bar'               => 'sanitized_text',
					'baz'               => 'oldbaz',
				],
			],
		];
	}

	/**
	 * Tests ::sanitize_settings .
	 *
	 * @covers ::sanitize_settings
	 *
	 * @dataProvider provide_sanitize_settings_data
	 *
	 * @param  array $input    The form input data.
	 * @param  array $fields   The settings field definitions.
	 * @param  array $settings The plugin settings.
	 * @param  array $result   The expected result.
	 */
	public function test_sanitize_settings( $input, $fields, $settings, $result ) {
		$this->settings->shouldReceive( 'get_fields' )->once()->withNoArgs()->andReturn( $fields );
		$this->settings->shouldReceive( 'get_all_settings' )->once()->withNoArgs()->andReturn( $settings );

		Functions\expect( 'wp_kses' )->zeroOrMoreTimes()->with( m::type( 'string' ), [] )->andReturn( 'sanitized_separator' );
		Functions\expect( 'sanitize_text_field' )->zeroOrMoreTimes()->with( m::any() )->andReturn( 'sanitized_text' );

		$this->assertSame( $result, $this->sut->sanitize_settings( $input ) );
	}

	/**
	 * Tests ::print_settings_section.
	 *
	 * @covers ::print_settings_section
	 */
	public function test_print_settings_section() {
		// Input data.
		$args = [
			'mocked' => 'arg',
			'second' => 'mocked arg',
		];

		$this->expectOutputString( 'SECTION TEMPLATE' );
		$this->assertNull( $this->sut->print_settings_section( $args ) );
	}

	/**
	 * Tests ::add_action_links.
	 *
	 * @covers ::add_action_links
	 */
	public function test_add_action_links() {
		// Input data.
		$links = [
			'some plugin link',
			'another plugin link',
		];

		$result = $this->sut->add_action_links( $links );

		$this->assert_is_array( $result );
		$this->assertCount( \count( $links ) + 1, $result );
		$this->assert_is_string( $result[0] );
		$this->assert_matches_regular_expression( '|<a href="options-media.php#media-credit">Settings</a>|', $result[0] );
		$this->assertSame( $result[1], $links[0] );
		$this->assertSame( $result[2], $links[1] );
	}
}
