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

namespace Media_Credit\Data_Storage;

/**
 * A plugin-specific options handler.
 *
 * @since 4.0.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class Options extends \Mundschenk\Data_Storage\Options {
	/**
	 * The prefix for the plugin options.
	 *
	 * @var string
	 */
	const PREFIX = 'media_credit_';

	/**
	 * The name used for the plugin option in the database.
	 *
	 * @var string OPTION
	 */
	const OPTION = 'settings';

	/**
	 * Creates a new instance.
	 */
	public function __construct() {
		parent::__construct( self::PREFIX );
	}
}
