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

use Media_Credit\Components\Setup;

use Media_Credit\Core;
use Media_Credit\Settings;

/**
 * Media_Credit\Components\Setup unit test.
 *
 * @coversDefaultClass \Media_Credit\Components\Setup
 * @usesDefaultClass \Media_Credit\Components\Setup
 *
 * @uses ::__construct
 */
class Setup_Test extends \Media_Credit\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Settings
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

		Functions\when( '__' )->returnArg();

		$this->core     = m::mock( Core::class );
		$this->settings = m::mock( Settings::class );

		$this->sut = m::mock( Setup::class, [ $this->core, $this->settings ] )
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

		$sut = m::mock( Setup::class )->makePartial();
		$sut->__construct( $core, $settings );

		$this->assert_attribute_same( $core, 'core', $sut );
		$this->assert_attribute_same( $settings, 'settings', $sut );
	}

	/**
	 * Tests ::run.
	 *
	 * @covers ::run
	 */
	public function test_run() {
		Functions\expect( 'register_deactivation_hook' )->once()->with( \MEDIA_CREDIT_PLUGIN_FILE, [ $this->sut, 'deactivate' ] );

		Actions\expectAdded( 'plugins_loaded' )->once()->with( [ $this->sut, 'update_check' ] );
		Actions\expectAdded( 'init' )->once()->with( [ $this->sut, 'register_meta_fields' ] );

		$this->assertNull( $this->sut->run() );
	}

	/**
	 * Tests ::update_check.
	 *
	 * @covers ::update_check
	 */
	public function test_update_check() {
		$version  = '6.6.6';
		$settings = [
			'foo'                       => 'bar',
			Settings::INSTALLED_VERSION => '1.0.1',
		];

		$this->settings->shouldReceive( 'get_all_settings' )->once()->with( true )->andReturn( $settings );
		$this->settings->shouldReceive( 'get_version' )->once()->andReturn( $version );
		$this->settings->shouldReceive( 'set' )->once()->with( Settings::INSTALLED_VERSION, $version );

		$this->assertNull( $this->sut->update_check() );
	}

	/**
	 * Tests ::deactivate.
	 *
	 * @covers ::deactivate
	 */
	public function test_deactivate() {
		$network_wide = true;

		$this->assertNull( $this->sut->deactivate( $network_wide ) );
	}

	/**
	 * Tests ::register_meta_fields.
	 *
	 * @covers ::register_meta_fields
	 */
	public function test_register_meta_fields() {
		Functions\expect( 'register_meta' )->once()->with( 'post', Core::POSTMETA_KEY, m::type( 'array' ) );
		Functions\expect( 'register_meta' )->once()->with( 'post', Core::URL_POSTMETA_KEY, m::type( 'array' ) );
		Functions\expect( 'register_meta' )->once()->with( 'post', Core::DATA_POSTMETA_KEY, m::type( 'array' ) );

		$this->assertNull( $this->sut->register_meta_fields() );
	}
}
