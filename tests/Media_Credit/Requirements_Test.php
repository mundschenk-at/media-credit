<?php
/**
 * This file is part of Media Credit.
 *
 * Copyright 2021-2022 Peter Putzer.
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

use org\bovigo\vfs\vfsStream;

use Mockery as m;

use Media_Credit\Tests\TestCase;

/**
 * Media_Credit\Requirements unit test.
 *
 * @since 4.2.0
 *
 * @coversDefaultClass \Media_Credit\Requirements
 * @usesDefaultClass \Media_Credit\Requirements
 */
class Requirements_Test extends TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var \Media_Credit\Requirements
	 */
	private $sut;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function set_up() {
		parent::set_up();

		$this->sut = m::mock( \Media_Credit\Requirements::class )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Test ::__construct.
	 *
	 * @covers ::__construct
	 * @covers Mundschenk\WP_Requirements::__construct
	 */
	public function test_constructor() {

		Functions\expect( 'wp_parse_args' )->andReturnUsing(
			function( $args, $defaults ) {
				return \array_merge( $defaults, $args );
			}
		);
		$req = m::mock( \Media_Credit\Requirements::class )->makePartial();
		$req->__construct();

		$this->assert_attribute_same( 'Media Credit', 'plugin_name', $req );
		$this->assert_attribute_same( 'media-credit', 'textdomain', $req );
		$this->assert_attribute_same(
			[
				'php'              => '7.2.0',
				'multibyte'        => false,
				'utf-8'            => false,
			],
			'install_requirements',
			$req
		);
	}
}
