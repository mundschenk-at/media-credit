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

namespace Media_Credit\Tests\Media_Credit\Tools;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use org\bovigo\vfs\vfsStream;

use Media_Credit\Tools\Template;

/**
 * Media_Credit\Tools\Template unit test.
 *
 * @coversDefaultClass \Media_Credit\Tools\Template
 * @usesDefaultClass \Media_Credit\Tools\Template
 *
 * @uses ::__construct
 */
class Template_Test extends \Media_Credit\Tests\TestCase {

	/**
	 * The system under test.
	 *
	 * @var Template
	 */
	private $sut;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function set_up() {
		parent::set_up();

		$filesystem = [
			'plugin' => [
				'public' => [
					'partials' => [
						'foobar' => [
							'partial.php'           => 'MY_PARTIAL',
							'partial-with-args.php' => '<?php echo "MY_PARTIAL_WITH_{$foo}_{$bar}";',
						],
					],
				],
			],
		];

		// Set up virtual filesystem.
		vfsStream::setup( 'root', null, $filesystem );
		set_include_path( 'vfs://root/' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_set_include_path

		// Partially mock system under test.
		$this->sut = m::mock( Template::class, [ vfsStream::url( 'root/plugin' ) ] )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Test ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		// Input data.
		$base_dir = '/my/plugin/base/dir/';

		// Intermediary data.
		$unslashed_base_dir = '/my/unslashed/base/dir';

		// Prepare mock.
		$mock = m::mock( Template::class )->makePartial();

		// Set up expectations.
		Functions\expect( 'untrailingslashit' )->once()->with( $base_dir )->andReturn( $unslashed_base_dir );

		// Validate.
		$mock->__construct( $base_dir );
		$this->assert_attribute_same( $unslashed_base_dir, 'base_dir', $mock );
	}

	/**
	 * Tests ::print_partial.
	 *
	 * @covers ::print_partial
	 */
	public function test_print_partial() {
		$this->expectOutputString( 'MY_PARTIAL' );

		$this->assertNull( $this->sut->print_partial( 'public/partials/foobar/partial.php' ) );
	}

	/**
	 * Tests ::print_partial.
	 *
	 * @covers ::print_partial
	 */
	public function test_print_partial_with_variables() {
		$args = [
			'foo' => 'A',
			'bar' => 'VARIABLE',
		];

		$this->expectOutputString( 'MY_PARTIAL_WITH_A_VARIABLE' );

		$this->assertNull( $this->sut->print_partial( 'public/partials/foobar/partial-with-args.php', $args ) );
	}

	/**
	 * Tests ::print_partial.
	 *
	 * @covers ::print_partial
	 */
	public function test_print_partial_doing_it_wrong() {
		$args = [
			'foo' => 'A',
			'bar' => 'VARIABLE',
			666   => 'invalid',
		];

		Functions\expect( 'esc_html' )->once()->with( m::type( 'string' ) )->andReturn( 'error message' );
		Functions\expect( '_doing_it_wrong' )->once()->with( m::type( 'string' ), m::type( 'string' ), 'Media Credit 4.2.0' );

		$this->expectOutputString( 'MY_PARTIAL_WITH_A_VARIABLE' );

		$this->assertNull( $this->sut->print_partial( 'public/partials/foobar/partial-with-args.php', $args ) );
	}

	/**
	 * Tests ::get_partial.
	 *
	 * @covers ::get_partial
	 */
	public function test_get_partial() {
		$partial = 'public/partials/foobar/partial.php';
		$args    = [
			'foo' => 'bar',
		];

		$result = 'Some template output';

		$this->sut->shouldReceive( 'print_partial' )->once()->with( $partial, $args )->andReturnUsing(
			function() use ( $result ) {
				echo $result; // phpcs:ignore WordPress.Security.EscapeOutput
			}
		);

		$this->assertSame( $result, $this->sut->get_partial( $partial, $args ) );
	}
}
