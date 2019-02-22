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
 * @package mundschenk-at/mmedia-credit
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Media_Credit\Components;

use Media_Credit\Data_Storage\Options;

use Mundschenk\Data_Storage\Network_Options;
use Mundschenk\Data_Storage\Site_Transients;
use Mundschenk\Data_Storage\Transients;

/**
 * Handles plugin uninstallation.
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class Uninstallation implements \Media_Credit\Component {

	/**
	 * The full path to the main plugin file.
	 *
	 * @var string
	 */
	private $plugin_file;

	/**
	 * The options handler.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * The transients handler.
	 *
	 * @var Transients
	 */
	private $transients;

	/**
	 * Creates a new Setup instance.
	 *
	 * @param string     $plugin_file   The full path to the base plugin file.
	 * @param Options    $options       The options handler.
	 * @param Transients $transients    The transients handler.
	 */
	public function __construct( $plugin_file, Options $options, Transients $transients ) {
		$this->plugin_file = $plugin_file;
		$this->options     = $options;
		$this->transients  = $transients;
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
