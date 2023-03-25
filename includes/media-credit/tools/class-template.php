<?php
/**
 * This file is part of Media Credit.
 *
 * Copyright 2021-2023 Peter Putzer.
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

namespace Media_Credit\Tools;

/**
 * A helper for rendering partial templates.
 *
 * @since 4.2.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class Template {

	/**
	 * The full path to the base directory of the plugin (without a trailing slash).
	 *
	 * @var string
	 */
	private $base_dir;

	/**
	 * Creates a new instance.
	 *
	 * @param string $plugin_base_dir The plugin base directory.
	 */
	public function __construct( $plugin_base_dir ) {
		$this->base_dir = \untrailingslashit( $plugin_base_dir );
	}

	/**
	 * Parses and echoes a partial template.
	 *
	 * @param  string $partial The file path of the partial to include (relative
	 *                         to the plugin directory.
	 * @param  array  $args    Arguments passed to the partial. Only string keys
	 *                         allowed and the keys must be valid variable names.
	 *
	 * @return void
	 *
	 * @phpstan-param array<string,mixed> $args
	 */
	public function print_partial( $partial, array $args = [] ) {
		if ( \extract( $args ) !== \count( $args ) ) { // phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- needed for "natural" partials.
			\_doing_it_wrong( __METHOD__, \esc_html( "Invalid arguments passed to partial {$partial}." ), 'Media Credit 4.2.0' );
		}

		require "{$this->base_dir}/{$partial}";
	}

	/**
	 * Parses a partial template and returns the content as a string.
	 *
	 * @param  string $partial The file path of the partial to include (relative
	 *                         to the plugin directory.
	 * @param  array  $args    Arguments passed to the partial. Only string keys
	 *                         allowed and the keys must be valid variable names.
	 *
	 * @return string
	 *
	 * @phpstan-param array<string,mixed> $args
	 */
	public function get_partial( $partial, array $args = [] ) {
		\ob_start();
		$this->print_partial( $partial, $args );
		return (string) \ob_get_clean();
	}
}
