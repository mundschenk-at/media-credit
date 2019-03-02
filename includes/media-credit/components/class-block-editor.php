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

namespace Media_Credit\Components;

use Media_Credit\Core;
use Media_Credit\Settings;

/**
 * The component handling the integration with the Block Editor (i.e. Gutenberg).
 *
 * @since 4.0.0
 */
class Block_Editor implements \Media_Credit\Component {

	/**
	 * The version of this plugin.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * The core API.
	 *
	 * @var Core
	 */
	private $core;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $version     The plugin version.
	 * @param Core   $core    The core plugin API.
	 */
	public function __construct( $version, Core $core ) {
		$this->version = $version;
		$this->core    = $core;
	}

	/**
	 * Sets up the various hooks for the plugin component.
	 *
	 * @return void
	 */
	public function run() {
		\add_filter( 'render_block', [ $this, 'add_media_credit_to_image_blocks' ], 10, 2 );
	}

	/**
	 * Adds the images media credit to rendered `core/image` blocks.
	 *
	 * @param string $block_content The block content about to be appended.
	 * @param array  $block         The full block, including name and attributes.
	 */
	public function add_media_credit_to_image_blocks( $block_content, array $block ) {

		// Access the plugin settings.
		$s = $this->core->get_settings();

		// We only target standard images, and only when the credits are not displayed after the post content.
		if ( 'core/image' !== $block['blockName'] || ! empty( $s[ Settings::CREDIT_AT_END ] ) ) {
			return $block_content;
		}

		// Retrieve image.
		$attachment = \get_post( $block['attrs']['id'] );
		if ( ! $attachment instanceof \WP_Post ) {
			return $block_content;
		}

		// Load the media credit for the attachment.
		$credit = $this->core->get_media_credit_json( $attachment );
		$markup = "<span class='media-credit'>{$credit['rendered']}</span>";

		if ( \preg_match( '#(<figcaption[^>]*>)(.*)</figcaption>#S', $block_content, $matches ) ) {
			$block_content = \str_replace( $matches[0], "{$matches[1]}{$markup} {$matches[2]}</figcaption>", $block_content );
		} else {
			$block_content = \str_replace( '</figure>', "<figcaption>{$markup}</figcaption></figure>", $block_content );
		}

		return $block_content;
	}
}
