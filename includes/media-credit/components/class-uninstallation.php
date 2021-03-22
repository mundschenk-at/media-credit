<?php
/**
 * This file is part of Media Credit.
 *
 * Copyright 2019-2021 Peter Putzer.
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
 * @package mundschenk-at/mmedia-credit
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Media_Credit\Components;

use Media_Credit\Data_Storage\Options;

/**
 * Handles plugin uninstallation.
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class Uninstallation implements \Media_Credit\Component {

	/**
	 * The options handler.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Creates a new Setup instance.
	 *
	 * @since 4.2.0 Unused parameter $transients removed.
	 *
	 * @param Options $options The options handler.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
	}

	/**
	 * Sets up the various hooks for the plugin component.
	 *
	 * @return void
	 */
	public function run() {
		$this->options->delete( Options::OPTION );
	}
}
