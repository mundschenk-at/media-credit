<?php

/**
 * This file is part of Media Credit.
 *
 * Copyright 2013-2015 Peter Putzer.
 * Copyright 2010-2011 Scott Bressler.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License,
 * version 2 as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 *
 * @link       https://mundschenk.at
 * @since      3.0.0
 *
 * @package    Media_Credit
 * @subpackage Media_Credit/includes
 */

/**
 * An abstract base class containing some important constants.
 *
 * @since      3.0.0
 * @package    Media_Credit
 * @subpackage Media_Credit/includes
 * @author     Peter Putzer <github@mundschenk.at>
 */
interface Media_Credit_Base {

	const OPTION                          = 'media-credit';
	const EMPTY_META_STRING               = ' ';
	const POSTMETA_KEY                    = '_media_credit';
	const URL_POSTMETA_KEY                = '_media_credit_url';
	const DEFAULT_SEPARATOR               = ' | ';
	const WP_IMAGE_CLASS_NAME_PREFIX      = 'wp-image-';
	const WP_ATTACHMENT_CLASS_NAME_PREFIX = 'attachment_';

}
