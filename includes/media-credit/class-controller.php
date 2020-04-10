<?php
/**
 * This file is part of Media Credit.
 *
 * Copyright 2019-2020 Peter Putzer.
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
 * @package mundschenk-at/media-credit
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Media_Credit;

use Media_Credit\Component;

/**
 * Initializes Media Credit plugin.
 *
 * @since 4.1.0 Renamed to `Media_Credit\Controller`
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class Controller {

	/**
	 * The settings page handler.
	 *
	 * @var Component[]
	 */
	private $components = [];

	/**
	 * The core plugin API.
	 *
	 * @var Core
	 */
	private $core;

	/**
	 * Creates an instance of the plugin controller.
	 *
	 * @since 4.1.0 Parameter `$core` added and `Component` parameters replaced
	 *              with factory-configured array.
	 *
	 * @param Core        $core       The core API.
	 * @param Component[] $components An array of plugin components.
	 */
	public function __construct( Core $core, array $components ) {
		$this->core       = $core;
		$this->components = $components;
	}

	/**
	 * Starts the plugin for real.
	 */
	public function run() {
		// Set up API singleton.
		$this->core->make_singleton();

		foreach ( $this->components as $component ) {
			$component->run();
		}
	}
}
