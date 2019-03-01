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

namespace Media_Credit;

use Media_Credit\Data_Storage\Options;

use Mundschenk\UI\Controls;

/**
 * Default configuration for Media Credit.
 *
 * @internal
 *
 * @since 4.0.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class Settings {
	const INSTALLED_VERSION     = 'version';
	const INSTALL_DATE          = 'install_date';
	const SEPARATOR             = 'separator';
	const ORGANIZATION          = 'organization';
	const CREDIT_AT_END         = 'credit_at_end';
	const NO_DEFAULT_CREDIT     = 'no_default_credit';
	const CUSTOM_DEFAULT_CREDIT = 'custom_default_credit';
	const FEATURED_IMAGE_CREDIT = 'post_thumbnail_credit';
	const SCHEMA_ORG_MARKUP     = 'schema_org_markup';

	const MEDIA_CREDIT_PREVIEW = 'media-credit-preview';

	const SETTINGS_SECTION = 'media-credit';

	/**
	 * The string used to separate the username and the organization
	 * for crediting local WordPress users.
	 *
	 * @var string
	 */
	const DEFAULT_SEPARATOR = ' | ';

	/**
	 * The fields definition array.
	 *
	 * @var array
	 */
	private $fields;

	/**
	 * Retrieves the settings field definitions.
	 *
	 * @return array
	 */
	public function get_fields() {
		if ( empty( $this->fields ) ) {
			$this->fields = [ // @codeCoverageIgnore
				self::MEDIA_CREDIT_PREVIEW      => [
					'ui'             => Controls\Display_Text::class,
					'tab_id'         => '', // Will be added to the 'discussions' page.
					'section'        => self::SETTINGS_SECTION,
					'elements'       => [], // Will be later.
					'short'          => \__( 'Preview', 'media-credit' ),
					'help_text'      => \__( 'This is what media credits will look like with your current settings.', 'media-credit' ),
				],
				self::SEPARATOR                 => [
					'ui'             => Controls\Text_Input::class,
					'tab_id'         => '', // Will be added to the 'media' page.
					'section'        => self::SETTINGS_SECTION,
					'short'          => \__( 'Separator', 'media-credit' ),
					'help_text'      => \__( 'Text used to separate author names from organization when crediting media to users of this blog.', 'media-credit' ),
					'attributes'     => [ 'class' => 'small-text' ],
					'default'        => self::DEFAULT_SEPARATOR,
				],
				self::ORGANIZATION              => [
					'ui'             => Controls\Text_Input::class,
					'tab_id'         => '', // Will be added to the 'media' page.
					'section'        => self::SETTINGS_SECTION,
					'short'          => \__( 'Organization', 'media-credit' ),
					'help_text'      => \__( 'Organization used when crediting media to users of this blog.', 'media-credit' ),
					'attributes'     => [ 'class' => 'regular-text' ],
					'default'        => \get_bloginfo( 'name', 'display' ),
				],
				self::CREDIT_AT_END             => [
					'ui'               => Controls\Checkbox_Input::class,
					'tab_id'           => '',
					'section'          => self::SETTINGS_SECTION,
					'short'            => \__( 'Credit position', 'media-credit' ),
					/* translators: 1: checkbox HTML */
					'label'            => \__( '%1$s Display credit after posts.', 'media-credit' ),
					'help_text'        => \__(
						'Display media credit for all the images attached to a post after the post content. Style with CSS class <code>media-credit-end</code>.',
						'media-credit'
					) . ' <br><strong>' . \__( 'Warning', 'media-credit' ) . '</strong>: ' . \__(
						'This will cause credit for all images in all posts to display at the bottom of every post on this blog.',
						'media-credit'
					),
					'default'          => 0,
				],
				self::FEATURED_IMAGE_CREDIT     => [
					'ui'               => Controls\Checkbox_Input::class,
					'tab_id'           => '',
					'section'          => self::SETTINGS_SECTION,
					/* translators: 1: checkbox HTML */
					'label'            => \__( '%1$s Display credit for featured images.', 'media-credit' ),
					'help_text'        => \__( 'Try to add media credit to featured images (depends on theme support).', 'media-credit' ),
					'grouped_with'     => self::CREDIT_AT_END,
					'default'          => 0,
				],
				self::NO_DEFAULT_CREDIT         => [
					'ui'               => Controls\Checkbox_Input::class,
					'tab_id'           => '',
					'section'          => self::SETTINGS_SECTION,
					'short'            => \__( 'Default credit', 'media-credit' ),
					/* translators: 1: checkbox HTML */
					'label'            => \__( '%1$s Do not credit images to WordPress users.', 'media-credit' ),
					'help_text'        => \__( 'Do not use the attachment author as the default credit.', 'media-credit' ),
					'default'          => 0,
				],
				self::CUSTOM_DEFAULT_CREDIT     => [
					'ui'               => Controls\Text_Input::class,
					'tab_id'           => '',
					'section'          => self::SETTINGS_SECTION,
					'help_text'        => \__( 'Use this custom default credit for new images.', 'media-credit' ),
					'grouped_with'     => self::NO_DEFAULT_CREDIT,
					'attributes'       => [ 'class' => 'regular-text' ],
					'default'          => '',
				],
				self::SCHEMA_ORG_MARKUP         => [
					'ui'               => Controls\Checkbox_Input::class,
					'tab_id'           => '',
					'section'          => self::SETTINGS_SECTION,
					'short'            => \__( 'Structured data', 'media-credit' ),
					/* translators: 1: checkbox HTML */
					'label'            => \__( '%1$s Include schema.org structured data in HTML5 microdata markup.', 'media-credit' ),
					'help_text'        => \__( 'Microdata is added to the credit itself and the surrounding <code>figure</code> and <code>img</code> (if they don\'t already have other microdata set). The setting has no effect if credits are displayed after posts.', 'media-credit' ),
					'default'          => 0,
				],
			];
		}

		return $this->fields;
	}
}
