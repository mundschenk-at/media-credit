<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
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

// phpcs:disable WordPress.NamingConventions, Squiz.Commenting.ClassComment.Missing, Generic.Files.OneObjectStructurePerFile.MultipleFound, Squiz.Commenting.FunctionComment.InvalidNoReturn

// PHP 8.0 internal classes.
namespace {
	if ( \version_compare( \PHP_VERSION, '8.0.0', '<' ) ) {
		class GdImage {}
	}
}
