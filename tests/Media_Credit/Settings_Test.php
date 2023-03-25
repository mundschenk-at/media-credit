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

use Media_Credit\Settings;

use Media_Credit\Data_Storage\Options;

/**
 * Media_Credit\Settings unit test.
 *
 * @coversDefaultClass \Media_Credit\Settings
 * @usesDefaultClass \Media_Credit\Settings
 *
 * @uses ::__construct
 */
class Settings_Test extends \Media_Credit\Tests\TestCase {

	const VERSION = '1.0.0';

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
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function set_up() {
		parent::set_up();

		Functions\when( '__' )->returnArg();

		$this->options = m::mock( Options::class );

		$this->sut = m::mock( Settings::class, [ self::VERSION, $this->options ] )
			->makePartial()
			->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Test ::__construct.
	 *
	 * @covers ::__construct
	 *
	 * @uses ::get_version
	 */
	public function test_constructor() {
		$version = '6.6.6';
		$options = m::mock( Options::class );

		$sut = m::mock( Settings::class )->makePartial();
		$sut->__construct( $version, $options );

		$this->assert_attribute_same( $version, 'version', $sut );
		$this->assert_attribute_same( $options, 'options', $sut );
	}

	/**
	 * Tests ::get_version.
	 *
	 * @covers ::get_version
	 */
	public function test_get_version() {
		$this->assertSame( self::VERSION, $this->sut->get_version() );
	}

	/**
	 * Tests ::get_all_settings.
	 *
	 * @covers ::get_all_settings
	 */
	public function test_get_all_settings() {
		$settings = [
			'foo'                       => 'bar',
			Settings::INSTALLED_VERSION => self::VERSION,
		];

		$this->sut->shouldReceive( 'load_settings' )->once()->andReturn( $settings );

		$this->assertSame( $settings, $this->sut->get_all_settings() );
	}

	/**
	 * Tests ::get_all_settings.
	 *
	 * @covers ::get_all_settings
	 */
	public function test_get_all_settings_repeated() {
		$original = [
			'foo'                       => 'bar',
			Settings::INSTALLED_VERSION => self::VERSION,
		];

		// Prepare state - settings have alreaddy been loaded.
		$this->set_value( $this->sut, 'settings', $original );

		$this->sut->shouldReceive( 'load_settings' )->never();

		$this->assertSame( $original, $this->sut->get_all_settings() );
	}

	/**
	 * Tests ::get_all_settings.
	 *
	 * @covers ::get_all_settings
	 */
	public function test_get_all_settings_forced() {
		$original = [
			'foo'                       => 'bar',
			Settings::INSTALLED_VERSION => self::VERSION,
		];
		$settings = [
			'foo'                       => 'barfoo',
			Settings::INSTALLED_VERSION => self::VERSION,
		];

		// Prepare state - settings have alreaddy been loaded.
		$this->set_value( $this->sut, 'settings', $original );

		$this->sut->shouldReceive( 'load_settings' )->once()->andReturn( $settings );

		$this->assertSame( $settings, $this->sut->get_all_settings( true ) );
	}

	/**
	 * Tests ::get_all_settings.
	 *
	 * @covers ::get_all_settings
	 */
	public function test_get_all_settings_no_version() {
		$original = [
			'foo' => 'bar',
		];
		$settings = [
			'foo'                       => 'barfoo',
			Settings::INSTALLED_VERSION => self::VERSION,
		];

		// Prepare state - settings have alreaddy been loaded.
		$this->set_value( $this->sut, 'settings', $original );

		$this->sut->shouldReceive( 'load_settings' )->once()->andReturn( $settings );

		$this->assertSame( $settings, $this->sut->get_all_settings() );
	}

	/**
	 * Tests ::get_all_settings.
	 *
	 * @covers ::get_all_settings
	 */
	public function test_get_all_settings_version_mismatch() {
		$original = [
			'foo'                       => 'bar',
			Settings::INSTALLED_VERSION => '1.2.3',
		];
		$settings = [
			'foo'                       => 'barfoo',
			Settings::INSTALLED_VERSION => self::VERSION,
		];

		// Prepare state - settings have alreaddy been loaded.
		$this->set_value( $this->sut, 'settings', $original );

		$this->sut->shouldReceive( 'load_settings' )->once()->andReturn( $settings );

		$this->assertSame( $settings, $this->sut->get_all_settings() );
	}

	/**
	 * Tests ::load_settings.
	 *
	 * @covers ::load_settings
	 */
	public function test_load_settings() {
		$setting1 = 'foo';
		$setting2 = 'baz';
		$settings = [
			$setting1                   => 'barfoo',
			Settings::INSTALLED_VERSION => '1.2.3',
		];
		$defaults = [
			$setting1 => 'bar',
			$setting2 => 'foobar',
		];

		$this->options->shouldReceive( 'get' )
			->once()
			->with( Options::OPTION )
			->andReturn( $settings );
		$this->options->shouldReceive( 'get' )->never()->with( 'media-credit', false, true );

		$this->sut->shouldReceive( 'get_defaults' )
			->once()
			->andReturn( $defaults );

		$this->options->shouldReceive( 'set' )
			->once()
			->with( Options::OPTION, m::type( 'array' ) );

		$result = $this->sut->load_settings();

		$this->assert_is_array( $result );
		$this->assertArrayHasKey( $setting1, $result );
		$this->assertSame( 'barfoo', $result[ $setting1 ] );
		$this->assertArrayHasKey( $setting2, $result );
		$this->assertSame( 'foobar', $result[ $setting2 ] );
		$this->assertArrayHasKey( Settings::INSTALLED_VERSION, $result );
	}

	/**
	 * Tests ::load_settings.
	 *
	 * @covers ::load_settings
	 */
	public function test_load_settings_invalid_result() {
		$defaults = [
			'foo' => 'bar',
			'baz' => 'foobar',
		];

		$this->options->shouldReceive( 'get' )
			->once()
			->with( Options::OPTION )
			->andReturn( false );
		$this->options->shouldReceive( 'get' )
			->once()
			->with( 'media-credit', false, true )
			->andReturn( false );

		$this->sut->shouldReceive( 'get_defaults' )
			->once()
			->andReturn( $defaults );

		$this->options->shouldReceive( 'set' )
			->once()
			->with( Options::OPTION, m::type( 'array' ) );

		$this->assertSame( $defaults, $this->sut->load_settings() );
	}

	/**
	 * Tests ::load_settings.
	 *
	 * @covers ::load_settings
	 */
	public function test_load_settings_everything_in_order() {
		$settings = [
			'foo'                       => 'barfoo',
			'baz'                       => 'foo',
			Settings::INSTALLED_VERSION => '1.2.3',
		];
		$defaults = [
			'foo' => 'bar',
			'baz' => 'foobar',
		];

		$this->options->shouldReceive( 'get' )
			->once()
			->with( Options::OPTION )
			->andReturn( $settings );
		$this->options->shouldReceive( 'get' )->never()->with( 'media-credit', false, true );

		$this->sut->shouldReceive( 'get_defaults' )
			->once()
			->andReturn( $defaults );

		$this->options->shouldReceive( 'set' )->never();

		$this->assertSame( $settings, $this->sut->load_settings() );
	}

	/**
	 * Tests ::load_settings from legacy option name.
	 *
	 * @covers ::load_settings
	 */
	public function test_load_settings_legacy() {
		$setting1      = 'foo';
		$setting2      = 'baz';
		$orig_settings = [
			$setting1 => 'barfoo',
		];
		$defaults      = [
			$setting1 => 'bar',
			$setting2 => 'foobar',
		];

		// Expected result.
		$settings                                = $orig_settings;
		$settings[ Settings::INSTALLED_VERSION ] = '0.5.5-or-earlier';
		$settings[ $setting2 ]                   = $defaults[ $setting2 ];

		$this->options->shouldReceive( 'get' )
			->once()
			->with( Options::OPTION )
			->andReturn( false );
		$this->options->shouldReceive( 'get' )
			->once()
			->with( 'media-credit', false, true )
			->andReturn( $orig_settings );
		$this->options->shouldReceive( 'delete' )
			->once()
			->with( 'media-credit', true );

		$this->sut->shouldReceive( 'get_defaults' )
			->once()
			->andReturn( $defaults );

		$this->options->shouldReceive( 'set' )
			->once()
			->with( Options::OPTION, m::type( 'array' ) );

		$result = $this->sut->load_settings();

		$this->assert_is_array( $result );
		$this->assertArrayHasKey( $setting1, $result );
		$this->assertSame( 'barfoo', $result[ $setting1 ] );
		$this->assertArrayHasKey( $setting2, $result );
		$this->assertSame( 'foobar', $result[ $setting2 ] );
		$this->assertArrayHasKey( Settings::INSTALLED_VERSION, $result );
	}

	/**
	 * Tests ::get.
	 *
	 * @covers ::get
	 */
	public function test_get() {
		$setting_key   = 'foo';
		$setting_value = 'bar';
		$settings      = [
			$setting_key => $setting_value,
			'something'  => 'else',
		];

		$this->sut->shouldReceive( 'get_all_settings' )->once()->andReturn( $settings );

		$this->assertSame( $setting_value, $this->sut->get( $setting_key ) );
	}

	/**
	 * Tests ::get.
	 *
	 * @covers ::get
	 */
	public function test_get_invalid_setting() {
		$setting_key   = 'foo';
		$setting_value = 'bar';
		$settings      = [
			$setting_key => $setting_value,
			'something'  => 'else',
		];

		$this->sut->shouldReceive( 'get_all_settings' )->once()->andReturn( $settings );
		$this->expect_exception( \UnexpectedValueException::class );

		$this->assertNull( $this->sut->get( 'invalid setting' ) );
	}

	/**
	 * Tests ::set.
	 *
	 * @covers ::set
	 */
	public function test_set() {
		$setting_key   = 'my_key';
		$setting_value = 'bar';

		$orig_settings = [
			'a_setting'  => 666,
			$setting_key => 'some other value',
			'something'  => 'else',
		];

		$new_settings                 = $orig_settings;
		$new_settings[ $setting_key ] = $setting_value;

		$this->sut->shouldReceive( 'get_all_settings' )->once()->andReturn( $orig_settings );
		$this->options->shouldReceive( 'set' )->once()->with( Options::OPTION, $new_settings )->andReturn( true );

		$this->assertTrue( $this->sut->set( $setting_key, $setting_value ) );
		$this->assert_attribute_same( $new_settings, 'settings', $this->sut );
	}

	/**
	 * Tests ::set.
	 *
	 * @covers ::set
	 *
	 * @uses ::get_all_settings
	 */
	public function test_set_db_error() {
		$setting_key   = 'my_key';
		$setting_value = 'bar';
		$orig_settings = [
			'a_setting'                 => 666,
			$setting_key                => 'some other value',
			'something'                 => 'else',
			Settings::INSTALLED_VERSION => self::VERSION
		];

		$new_settings                 = $orig_settings;
		$new_settings[ $setting_key ] = $setting_value;

		// Set up state.
		$this->sut->shouldReceive( 'load_settings' )->once()->andReturn( $orig_settings );
		$cached_settings = $this->sut->get_all_settings();

		// Set up expectations.
		$this->options->shouldReceive( 'set' )->once()->with( Options::OPTION, $new_settings )->andReturn( false );

		// Run test and assert results.
		$this->assertFalse( $this->sut->set( $setting_key, $setting_value ) );
		$this->assert_attribute_same( $cached_settings, 'settings', $this->sut );
	}

	/**
	 * Tests ::set.
	 *
	 * @covers ::set
	 *
	 * @uses ::get_all_settings
	 */
	public function test_set_invalid_setting() {
		$setting_key   = 'invalid_key';
		$setting_value = 'bar';
		$orig_settings = [
			'a_setting'                 => 666,
			'my_key'                    => 'some other value',
			'something'                 => 'else',
			Settings::INSTALLED_VERSION => self::VERSION
		];

		// Set up state.
		$this->sut->shouldReceive( 'load_settings' )->once()->andReturn( $orig_settings );
		$cached_settings = $this->sut->get_all_settings();

		// Set up expectations.
		$this->expect_exception( \UnexpectedValueException::class );
		$this->options->shouldReceive( 'set' )->never();

		// Run test and assert results.
		$this->assertNull( $this->sut->set( $setting_key, $setting_value ) );
		$this->assert_attribute_same( $cached_settings, 'settings', $this->sut );
	}

	/**
	 * Tests ::get_fields.
	 *
	 * @covers ::get_fields
	 */
	public function test_get_fields() {
		$site_name = 'My Cool Site';

		Functions\expect( 'get_bloginfo' )->once()->with( 'name', 'display' )->andReturn( $site_name );

		$result = $this->sut->get_fields();

		$this->assert_is_array( $result );
		$this->assertContainsOnly( 'string', \array_keys( $result ) );
		$this->assertContainsOnly( 'array', $result );
	}

	/**
	 * Tests ::get_defaults.
	 *
	 * @covers ::get_defaults
	 *
	 * @uses ::get_fields
	 */
	public function test_get_defaults() {
		$site_name = 'My Cool Site';

		Functions\expect( 'get_bloginfo' )->once()->with( 'name', 'display' )->andReturn( $site_name );

		$result = $this->sut->get_defaults();

		$this->assert_is_array( $result );
		$this->assertNotContainsOnly( 'array', $result );
		$this->assertSame( '', $result[ Settings::INSTALLED_VERSION ] );
	}
}
