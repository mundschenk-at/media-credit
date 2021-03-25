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

namespace Media_Credit\Tests\Media_Credit;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use Media_Credit\Controller;
use Media_Credit\Core;
use Media_Credit\Component;

use Media_Credit\Tests\TestCase;

/**
 * Media_Credit\Controller unit test.
 *
 * @since 4.2.0
 *
 * @coversDefaultClass \Media_Credit\Controller
 * @usesDefaultClass \Media_Credit\Controller
 *
 * @uses ::__construct
 */
class Controller_Test extends TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Controller
	 */
	private $sut;

	/**
	 * Helper mock.
	 *
	 * @var Core
	 */
	private $core;

	/**
	 * Array of mocked components.
	 *
	 * @var Component[]
	 */
	private $components;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function set_up() {
		parent::set_up();

		// Initialize helpers.
		$this->core       = m::mock( Core::Class );
		$this->components = [
			m::mock( Component::class ),
			m::mock( Component::class ),
			m::mock( Component::class ),
		];

		// Create system-under-test.
		$this->sut = m::mock( Controller::class, [ $this->core, $this->components ] )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Test ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$core       = m::mock( Core::class );
		$components = [
			m::mock( Component::class ),
			m::mock( Component::class ),
		];

		$mock = m::mock( Controller::class )->makePartial();
		$mock->__construct( $core, $components );

		$this->assert_attribute_same( $core, 'core', $mock );
		$this->assert_attribute_same( $components, 'components', $mock );
	}

	/**
	 * Test ::run.
	 *
	 * @covers ::run
	 */
	public function test_run() {
		$this->core->shouldReceive( 'make_singleton' )->once();

		foreach ( $this->components as $component ) {
			$component->shouldReceive( 'run' )->once();
		}

		$this->assertNull( $this->sut->run() );
	}
}
