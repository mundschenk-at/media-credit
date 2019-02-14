<?php
/**
 * This file is part of Media Credit.
 *
 * Copyright 2013-2019 Peter Putzer.
 * Copyright 2010-2011 Scott Bressler.
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
 * An abstract base class containing some important constants.
 *
 * @since      3.0.0
 * @since      3.3.0 Moved to Media_Credit namespace.
 */
interface Base {

	/**
	 * The string stored in the database when the credit meta is empty.
	 *
	 * @var string EMPTY_META_STRING
	 */
	const EMPTY_META_STRING = ' ';

	/**
	 * The key used for storing the media credit in postmeta.
	 *
	 * @var string POSTMETA_KEY
	 */
	const POSTMETA_KEY = '_media_credit';

	/**
	 * The key used for storing the optional media credit URL in postmeta.
	 *
	 * @var string URL_POSTMETA_KEY
	 */
	const URL_POSTMETA_KEY = '_media_credit_url';

	/**
	 * The key used for storing optional media credit data in postmeta.
	 *
	 * @var string DATA_POSTMETA_KEY
	 */
	const DATA_POSTMETA_KEY = '_media_credit_data';

	/**
	 * The string used to separate the username and the organization
	 * for crediting local WordPress users.
	 *
	 * @var string DEFAULT_SEPARATOR
	 */
	const DEFAULT_SEPARATOR = ' | ';
}
