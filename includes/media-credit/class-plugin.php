<?php
/**
 * This file is part of Media Credit.
 *
 * Copyright 2019 Peter Putzer.
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

use Media_Credit\Components\Admin;
use Media_Credit\Components\Setup;

/**
 * Initializes the Media Credit plugin.
 *
 * @since 3.3.0
 */
class Plugin {

	/**
	 * The settings page handler.
	 *
	 * @var Media_Credit\Component[]
	 */
	private $components = [];

	/**
	 * Creates an instance of the plugin controller.
	 *
	 * @param Setup $setup    The (de-)activation handling.
	 * @param Admin $admin    The backend.
	 */
	public function __construct( Setup $setup, Admin $admin ) {
		$this->components[] = $setup;
		$this->components[] = $admin;
	}

	/**
	 * Starts the plugin for real.
	 */
	public function run() {
		foreach ( $this->components as $component ) {
			$component->run();
		}
	}
}
