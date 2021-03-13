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
 * @package mundschenk-at/media-credit
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Media_Credit;

/**
 * A custom requirements class to check for additional PHP packages and other
 * prerequisites.
 *
 * @since 4.2.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class Requirements extends \Mundschenk\WP_Requirements {

	const REQUIREMENTS = [
		'php'       => '7.0.0',
		'multibyte' => false,
		'utf-8'     => false,
	];

	/**
	 * Creates a new requirements instance.
	 */
	public function __construct() {
		parent::__construct( 'Media Credit', \MEDIA_CREDIT_PLUGIN_FILE, 'media-credit', self::REQUIREMENTS );
	}
}
