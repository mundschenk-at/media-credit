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

use org\bovigo\vfs\vfsStream;

use Mockery as m;

use Media_Credit\Tests\TestCase;

use Media_Credit_Factory;

/**
 * Media_Credit_Factory unit test.
 *
 * @since 4.2.0
 *
 * @coversDefaultClass \Media_Credit_Factory
 * @usesDefaultClass \Media_Credit_Factory
 */
class Media_Credit_Factory_Test extends TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Media_Credit_Factory
	 */
	private $sut;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function set_up() { // @codingStandardsIgnoreLine
		parent::set_up();

		$filesystem = [
			'wordpress' => [
				'path' => [
					'wp-admin' => [
						'includes' => [
							'plugin.php' => "<?php \\Brain\\Monkey\\Functions\\expect( 'get_plugin_data' )->andReturn( [ 'Version' => '6.6.6' ] ); ?>",
						],
					],
				],
			],
		];

		// Set up virtual filesystem.
		vfsStream::setup( 'root', null, $filesystem );
		\set_include_path( 'vfs://root/' ); // @codingStandardsIgnoreLine

		// Set up the mock.
		$this->sut = m::mock( Media_Credit_Factory::class )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests the constructor.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$this->sut->shouldReceive( 'get_rules' )->never();

		// Manually call constructor.
		$this->sut->__construct();

		$resulting_rules = $this->get_value( $this->sut, 'rules' );
		$this->assert_is_array( $resulting_rules );
		$this->assertCount( 0, $resulting_rules );
	}

	/**
	 * Test ::get_rules.
	 *
	 * @covers ::get_rules
	 */
	public function test_get_rules() {
		$version    = '6.6.6';
		$components = [
			[ Media_Credit_Factory::INSTANCE => \Media_Credit\Components\Setup::class ],
			[ Media_Credit_Factory::INSTANCE => \Media_Credit\Components\Avatar_Handling::class ],
		];

		$this->sut->shouldReceive( 'get_plugin_version' )->once()->withNoArgs()->andReturn( $version );
		$this->sut->shouldReceive( 'get_components' )->once()->andReturn( $components );

		$result = $this->sut->get_rules();

		$this->assert_is_array( $result );
		$this->assertArrayHasKey( \Media_Credit\Core::class, $result );
		$this->assertArrayHasKey( \Media_Credit\Components\Classic_Editor::class, $result );
	}

	/**
	 * Test ::get_plugin_version.
	 *
	 * @covers ::get_plugin_version
	 */
	public function test_get_plugin_version() {
		$version     = '6.6.6';
		$plugin_file = '/the/main/plugin/file.php';

		$this->assertSame( $version, $this->sut->get_plugin_version( $plugin_file ) );
	}

	/**
	 * Test ::get. Should be run after test_get_plugin_version.
	 *
	 * @covers ::get
	 *
	 * @uses Media_Credit_Factory::__construct
	 * @uses Media_Credit_Factory::get_components
	 * @uses Media_Credit_Factory::get_plugin_version
	 */
	public function test_get() {
		Functions\expect( 'get_plugin_data' )->once()->with( m::type( 'string' ), false, false )->andReturn( [ 'Version' => '42' ] );

		$result1 = Media_Credit_Factory::get();

		$this->assertInstanceOf( Media_Credit_Factory::class, $result1 );

		$result2 = Media_Credit_Factory::get();

		$this->assertSame( $result1, $result2 );
	}

	/**
	 * Test ::get_components.
	 *
	 * @covers ::get_components
	 */
	public function test_get_components() {
		$result = $this->sut->get_components();

		$this->assert_is_array( $result );

		// Check some exemplary components.
		$this->assert_contains( [ Media_Credit_Factory::INSTANCE => \Media_Credit\Components\Shortcodes::class ], $result, 'Component missing.' );
		$this->assert_contains( [ Media_Credit_Factory::INSTANCE => \Media_Credit\Components\Block_Editor::class ], $result, 'Component missing.' );
		$this->assert_contains( [ Media_Credit_Factory::INSTANCE => \Media_Credit\Components\Setup::class ], $result, 'Component missing.' );

		// Uninstallation must not (!) be included.
		$this->assert_not_contains( [ Media_Credit_Factory::INSTANCE => \Media_Credit\Components\Uninstallation::class ], $result, 'Uninstallation component should not be included.' );
	}
}
