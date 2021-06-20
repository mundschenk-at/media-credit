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

use Media_Credit\Components\Uninstallation;

use Media_Credit\Data_Storage\Options;

/**
 * Media_Credit\Components\Uninstallation unit test.
 *
 * @coversDefaultClass \Media_Credit\Components\Uninstallation
 * @usesDefaultClass \Media_Credit\Components\Uninstallation
 *
 * @uses ::__construct
 */
class Uninstallation_Test extends \Media_Credit\Tests\TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Uninstallation
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

		$this->options = m::mock( Options::class );

		$this->sut = m::mock( Uninstallation::class, [ $this->options ] )
			->makePartial()
			->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Tests ::__construct.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		$options = m::mock( Options::class );

		$sut = m::mock( Uninstallation::class )->makePartial();
		$sut->__construct( $options );

		$this->assert_attribute_same( $options, 'options', $sut );
	}

	/**
	 * Tests ::run.
	 *
	 * @covers ::run
	 */
	public function test_run() {

		$this->options->shouldReceive( 'delete' )->once()->with( Options::OPTION );

		$this->assertNull( $this->sut->run() );
	}
}
