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
		if ( 'core/image' !== $block['blockName'] || ! empty( $s[ Settings::CREDIT_AT_END ] ) || ! isset( $block['attrs']['id'] ) ) {
			return $block_content;
		}

		// Retrieve image.
		$attachment = \get_post( $block['attrs']['id'] );
		if ( ! $attachment instanceof \WP_Post ) {
			return $block_content;
		}

		$include_schema_org = ! empty( $s[ Settings::SCHEMA_ORG_MARKUP ] );

		// Load the media credit for the attachment.
		$credit = $this->core->get_media_credit_json( $attachment );
		$markup = $this->core->wrap_media_credit_markup( $credit['rendered'], $include_schema_org );

		// Inject the (modified) caption markup.
		$block_content = $this->inject_credit_into_caption( $block_content, $markup );

		// Inject additional schema.org markup.
		if ( $include_schema_org ) {
			// <figure> markup.
			if ( ! \preg_match( '/<figure[^>]*\bitemscope\b/S', $block_content ) ) {
				$block_content = \preg_replace( '/<figure\b/S', '<figure itemscope itemtype="http://schema.org/ImageObject"', $block_content );
			}

			// <figcaption> markup.
			if ( ! \preg_match( '/<figcaption[^>]*\bitemprop\s*=\b/S', $block_content ) ) {
				$block_content = \preg_replace( '/<figcaption\b/S', '<figcaption itemprop="caption"', $block_content );
			}
		}

		return $block_content;
	}

	/**
	 * Injects the credit into the caption markup of a `core/image` block.
	 *
	 * @param  string $block_content The block content.
	 * @param  string $credit        The credit markup.
	 *
	 * @return string
	 */
	protected function inject_credit_into_caption( $block_content, $credit ) {

		// Default injection pattern and replacement parts.
		$pattern     = '</figure>';
		$open        = '<figcaption>';
		$old_caption = '';
		$close       = '</figcaption></figure>';

		if ( \preg_match( '#(<figcaption[^>]*>)(.*)</figcaption>#S', $block_content, $matches ) ) {
			$pattern     = $matches[0];
			$open        = $matches[1];
			$old_caption = $matches[2];
			$close       = '</figcaption>';
		}

		// Prepare the default caption value.
		$caption = \trim( "{$credit} {$old_caption}" );

		/**
		 * Filters the Block Editor caption including the credit byline.
		 *
		 * @since 4.0.0
		 *
		 * @param string $caption       The caption including the credit. Default
		 *                              credit followed by captoin text with a
		 *                              seperating space, or just the credit if
		 *                              the caption text is empty.
		 * @param string $old_caption   The caption text.
		 * @param string $credit        The credit byline (including markup).
		 * @param string $block_content The original block content.
		 */
		$caption = \apply_filters( 'media_credit_block_editor_caption', $caption, $old_caption, $credit, $block_content );

		// Inject the (modified) caption markup.
		return \str_replace( $pattern, "{$open}{$caption}{$close}", $block_content );
	}
}
