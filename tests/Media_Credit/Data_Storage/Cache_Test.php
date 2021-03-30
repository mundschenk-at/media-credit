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

namespace Media_Credit\Tests\Media_Credit\Data_Storage;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use Mockery as m;

use Media_Credit\Tests\TestCase;

use Media_Credit\Data_Storage\Cache;

/**
 * Media_Credit\Data_Storage\Cache unit test.
 *
 * @since 4.2.0
 *
 * @coversDefaultClass \Media_Credit\Data_Storage\Cache
 * @usesDefaultClass \Media_Credit\Data_Storage\Cache
 */
class Cache_Test extends TestCase {

	/**
	 * The system-under-test.
	 *
	 * @var Cache
	 */
	private $sut;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function set_up() {
		parent::set_up();

		$this->sut = m::mock( Cache::class )->makePartial()->shouldAllowMockingProtectedMethods();
	}

	/**
	 * Test ::__construct.
	 *
	 * @covers ::__construct
	 * @uses Mundschenk\Data_Storage\Cache::__construct
	 * @uses Mundschenk\Data_Storage\Abstract_Cache::__construct
	 */
	public function test_constructor() {
		$cache = m::mock( Cache::class )->makePartial();

		Functions\expect( 'wp_cache_get' )->once()->with( m::type( 'string' ), Cache::GROUP )->andReturn( 42 );

		$cache->__construct();

		$this->assert_attribute_same( Cache::PREFIX, 'prefix', $cache );
		$this->assert_attribute_same( Cache::GROUP, 'group', $cache );
	}
}
