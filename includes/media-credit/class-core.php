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

use Media_Credit\Tools\Media_Query;
use Media_Credit\Tools\Shortcodes_Filter;

use Media_Credit\Data_Storage\Cache;
use Media_Credit\Data_Storage\Options;

/**
 * The main API for the Media Credit plugin. To allow for static template functions,
 * it is instantiated as a singleton.
 *
 * The class provides access to the plugin settings and utility methods for manipulating
 * the postmeta data making up the credit information for individual attachments.
 *
 * @since 4.0.0
 *
 * @author Peter Putzer <github@mundschenk.at>
 */
class Core {

	/**
	 * The string stored in the database when the credit meta is empty.
	 *
	 * @var string
	 */
	const EMPTY_META_STRING = ' ';

	/**
	 * The key used for storing the media credit in postmeta.
	 *
	 * @var string
	 */
	const POSTMETA_KEY = '_media_credit';

	/**
	 * The key used for storing the optional media credit URL in postmeta.
	 *
	 * @var string
	 */
	const URL_POSTMETA_KEY = '_media_credit_url';

	/**
	 * The key used for storing optional media credit data in postmeta.
	 *
	 * @var string
	 */
	const DATA_POSTMETA_KEY = '_media_credit_data';

	/**
	 * An array of empty media credit fields.
	 *
	 * @var array
	 */
	const INVALID_MEDIA_CREDIT = [
		'rendered'  => '',
		'plaintext' => '',
		'fancy'     => '',
		'raw'       => [
			'user_id'  => 0,
			'freeform' => '',
			'url'      => '',
			'flags'    => [
				'nofollow' => 0,
			],
		],
	];

	/**
	 * The singleton instance.
	 *
	 * @var Core
	 */
	private static $instance;

	/**
	 * The plugin version.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * The object cache handler.
	 *
	 * @var Cache
	 */
	private $cache;

	/**
	 * The options handler.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * The default settings.
	 *
	 * @var Settings
	 */
	private $settings_template;

	/**
	 * The cached plugin settings.
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * The shortcodes filter.
	 *
	 * @var Shortcodes_Filter
	 */
	private $shortcodes_filter;

	/**
	 * The media query handler.
	 *
	 * @var Media_Query
	 */
	private $media_query;

	/**
	 * Creates a new instance.
	 *
	 * @param string            $version           The plugin version string (e.g. "3.0.0-beta.2").
	 * @param Cache             $cache             The object cache handler.
	 * @param Options           $options           The options handler.
	 * @param Settings          $settings_template The default settings template.
	 * @param Shortcodes_Filter $shortcodes_filter The shortcodes filter.
	 * @param Media_Query       $media_query       The media query handler.
	 */
	public function __construct( $version, Cache $cache, Options $options, Settings $settings_template, Shortcodes_Filter $shortcodes_filter, Media_Query $media_query ) {
		$this->version           = $version;
		$this->cache             = $cache;
		$this->options           = $options;
		$this->settings_template = $settings_template;
		$this->media_query       = $media_query;
		$this->shortcodes_filter = $shortcodes_filter;
	}

	/**
	 * Sets this API instance as the plugin singleton. Should not be called outside of plugin set-up.
	 *
	 * @internal
	 *
	 * @throws \BadMethodCallException Thrown when Media_Credit\Core::make_singleton is called after plugin initialization.
	 */
	public function make_singleton() {
		if ( null !== self::$instance ) {
			throw new \BadMethodCallException( __METHOD__ . ' called more than once.' );
		}

		self::$instance = $this;
	}

	/**
	 * Retrieves the plugin API instance.
	 *
	 * @throws \BadMethodCallException Thrown when Media_Credit\Core::get_instance is called before plugin initialization.
	 *
	 * @return Core
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			throw new \BadMethodCallException( __METHOD__ . ' called without prior plugin intialization.' );
		}

		return self::$instance;
	}

	/**
	 * Retrieves the plugin version.
	 *
	 * @return string
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Retrieves the plugin settings.
	 *
	 * @return array
	 */
	public function get_settings() {
		if ( empty( $this->settings ) ) {
			$this->settings = $this->options->get( Options::OPTION, [] );
		}

		return $this->settings;
	}

	/**
	 * Updates the shortcodes in the attachments parent post to match the arguments.
	 *
	 * @param  \WP_Post $attachment The attachment \WP_Post object.
	 * @param  int      $user_id  Optional. The new ID of the media item author. Default 0.
	 * @param  string   $freeform Optional. The new free-fomr credit string (if $user_id is not used). Default ''.
	 * @param  string   $url      Optional. The new URL the credit should link to. Default ''.
	 * @param  array    $flags {
	 *     Optional. An array of flags to modify the rendering of the media credit. Default [].
	 *
	 *     @type bool $nofollow Optional. A flag indicating that `rel=nofollow` should be added to the link. Default false.
	 * }
	 */
	protected function update_shortcodes_in_parent_post( \WP_Post $attachment, $user_id = 0, $freeform = '', $url = '', array $flags = [] ) {

		if ( ! empty( $attachment->post_parent ) ) {
			// Get the parent post of the attachment.
			$post = \get_post( $attachment->post_parent );

			// Extract flags.
			$nofollow = ! empty( $flags['nofollow'] );

			// Filter the post's content.
			$post->post_content = $this->shortcodes_filter->update_changed_media_credits( $post->post_content, $attachment->ID, $user_id, $freeform, $url, $nofollow );

			// Save the filtered content in the database.
			\wp_update_post( $post );
		}
	}

	/**
	 * Checks if the current user is authorized to edit the `media_credit` fields.
	 *
	 * @return bool
	 */
	public function authorized_to_edit_media_credit() {
		return \current_user_can( 'edit_posts' );
	}

	/**
	 * Sanitizes the freeform media credit meta field.
	 *
	 * @param  mixed  $meta_value  Meta value to sanitize.
	 * @param  string $meta_key    Meta key.
	 * @param  string $object_type Object type ('post', 'user' etc.).
	 *
	 * @return mixed
	 */
	public function sanitize_media_credit_meta_field( $meta_value, $meta_key, /* @scrutinizer ignore-unused */ $object_type ) {
		switch ( $meta_key ) {
			case self::POSTMETA_KEY:
				$meta_value = \sanitize_text_field( $meta_value );
				break;

			case self::URL_POSTMETA_KEY:
				$meta_value = \esc_url_raw( $meta_value );
				break;

			case self::DATA_POSTMETA_KEY:
				$meta_value = \is_array( $meta_value ) ? $meta_value : [];
				break;
		}

		return $meta_value;
	}

	/**
	 * Returns the freeform media credit for a given attachment.
	 *
	 * @param int $attachment_id An attachment ID.
	 *
	 * @return string            The freeform credit (or the empty string).
	 */
	protected function get_media_credit_freeform_text( $attachment_id ) {
		return (string) \get_post_meta( $attachment_id, self::POSTMETA_KEY, true );
	}

	/**
	 * Returns the media credit URL as plain text for a given attachment.
	 *
	 * @param int $attachment_id An attachment ID.
	 *
	 * @return string            The credit URL (or the empty string if none is set).
	 */
	protected function get_media_credit_url( $attachment_id ) {
		return (string) \get_post_meta( $attachment_id, self::URL_POSTMETA_KEY, true );
	}

	/**
	 * Returns the optional media credit data array for some media attachment.
	 *
	 * @param int $attachment_id An attachment ID.
	 *
	 * @return array             The data array.
	 */
	protected function get_media_credit_data( $attachment_id ) {

		$result = \get_post_meta( $attachment_id, self::DATA_POSTMETA_KEY, true );

		// Always return an array (it shouldn't be a scalar, but we want to be certain).
		return ( $result ? (array) $result : [] );
	}

	/**
	 * Retrieves the organization suffix (separator and organization name) for
	 * crediting registered WordPress users.
	 *
	 * @return string
	 */
	public function get_organization_suffix() {
		$s = $this->get_settings();

		return "{$s[ Settings::SEPARATOR ]}{$s[ Settings::ORGANIZATION ]}";
	}

	/**
	 * Renders the media credit as HTML (i.e. with a link to the author page or
	 * custom URL).
	 *
	 * @internal
	 *
	 * @param int    $user_id  Optional. The ID of the media item author. Default 0 (invalid).
	 * @param string $freeform Optional. The media credit string (if $user_id is not used). Default ''.
	 * @param string $url      Optional. A URL the credit should link to. Default ''.
	 * @param array  $flags {
	 *     Optional. An array of flags to modify the rendering of the media credit. Default [].
	 *
	 *     @type bool $nofollow Optional. A flag indicating that `rel=nofollow` should be added to the link. Default false.
	 * }
	 *
	 * @return string                             The media credit HTML (or the empty string if no credit is set).
	 */
	public function render_media_credit_html( $user_id = 0, $freeform = '', $url = '', array $flags = [] ) {

		// The plugin settings are needed to render the credit.
		$s = $this->get_settings();

		// Start building the credit markup.
		$credit = '';
		$name   = $freeform;
		$suffix = '';

		if ( '' === $freeform && ! empty( $user_id ) && empty( $s[ Settings::NO_DEFAULT_CREDIT ] ) ) {
			$name   = \get_the_author_meta( 'display_name', $user_id );
			$url    = $url ?: \get_author_posts_url( $user_id );
			$suffix = $this->get_organization_suffix();
		}

		if ( ! empty( $url ) ) {
			$attributes = ! empty( $flags['nofollow'] ) ? ' rel="nofollow"' : '';
			$credit     = '<a href="' . \esc_url( $url ) . '"' . "{$attributes}>" . \esc_html( $name ) . '</a>';
		} else {
			$credit = \esc_html( $name );
		}

		// The suffix should not contain any markup (the credit might).
		return $credit . \esc_html( $suffix );
	}

	/**
	 * Renders the media credit as HTML (i.e. with a link to the author page or
	 * custom URL).
	 *
	 * @param int    $user_id  Optional. The ID of the media item author. Default 0 (invalid).
	 * @param string $freeform Optional. The media credit string (if $user_id is not used). Default ''.
	 *
	 * @return string                             The media credit HTML (or the empty string if no credit is set).
	 */
	protected function render_media_credit_plaintext( $user_id = 0, $freeform = '' ) {

		// The plugin settings are needed to render the credit.
		$s = $this->get_settings();

		// Start building the credit markup.
		$credit = $freeform;

		if ( '' === $freeform && ! empty( $user_id ) && empty( $s[ Settings::NO_DEFAULT_CREDIT ] ) ) {
			$credit = \get_the_author_meta( 'display_name', $user_id );
		}

		// The suffix should not contain any markup (the credit might).
		return $credit;
	}

	/**
	 * Renders the media credit as HTML (i.e. with a link to the author page or
	 * custom URL).
	 *
	 * @param int    $user_id  Optional. The ID of the media item author. Default 0 (invalid).
	 * @param string $freeform Optional. The media credit string (if $user_id is not used). Default ''.
	 *
	 * @return string                             The media credit HTML (or the empty string if no credit is set).
	 */
	protected function render_media_credit_fancy( $user_id = 0, $freeform = '' ) {

		// Start building the credit markup.
		$credit = $this->render_media_credit_plaintext( $user_id, $freeform );

		if ( '' === $freeform && ! empty( $user_id ) && '' !== $credit ) {
			$credit .= $this->get_organization_suffix();
		}

		// The suffix should not contain any markup (the credit might).
		return $credit;
	}

	/**
	 * Prepares the JSON data for the given attachment's media credit.
	 *
	 * @param  \WP_Post $attachment The attachment \WP_Post object.
	 *
	 * @return array {
	 *     An array ready for conversion to JSON.
	 *
	 *     @type string $rendered  The HTML representation of the credit (i.e. including links).
	 *     @type string $plaintext The plain text representation of the credit (i.e. without any markup).
	 *     @type array  $raw {
	 *         The raw data used to store the media credit. On error, an empty array is returned.
	 *
	 *         @type int    $user_id  Optional. The ID of the media item author. Default 0 (invalid).
	 *         @type string $freeform Optional. The media credit string (if $user_id is not used). Default ''.
	 *         @type string $url      Optional. A URL the credit should link to. Default ''.
	 *         @type array  $flags {
	 *             Optional. An array of flags to modify the rendering of the media credit. Default [].
	 *
	 *             @type bool $nofollow Optional. A flag indicating that `rel=nofollow` should be added to the link. Default false.
	 *         }
	 *     }
	 * }
	 */
	public function get_media_credit_json( \WP_Post $attachment ) {

		$key  = "json_{$attachment->ID}";
		$json = $this->cache->get( $key );

		if ( ! \is_array( $json ) ) {
			// Retrieve the fields.
			$user_id  = (int) $attachment->post_author;
			$freeform = $this->get_media_credit_freeform_text( $attachment->ID );
			$url      = $this->get_media_credit_url( $attachment->ID );
			$flags    = $this->get_media_credit_data( $attachment->ID );

			// Build media credit data.
			$json = [
				'rendered'  => $this->render_media_credit_html( $user_id, $freeform, $url, $flags ),
				'plaintext' => $this->render_media_credit_plaintext( $user_id, $freeform ),
				'fancy'     => $this->render_media_credit_fancy( $user_id, $freeform ),
				'raw'       => [
					'user_id'   => $user_id,
					'freeform'  => $freeform,
					'url'       => $url,
					'flags'     => [
						'nofollow' => ! empty( $flags['nofollow'] ),
					],
				],
			];

			// Save our efforts for next time.
			$this->cache->set( $key, $json );
		}

		return $json;
	}

	/**
	 * Updates the media credit fields from a JSON response and fixes the shortcodes
	 * in the attachments parent post.
	 *
	 * @param  \WP_Post $attachment The attachment \WP_Post object.
	 * @param  array    $fields {
	 *     The raw data used to store the media credit. May be wrapped in an
	 *     additional outer array with the 'raw' index.
	 *
	 *     @type int|null    $user_id  Optional. The ID of the media item author. Default null.
	 *     @type string|null $freeform Optional. The media credit string (if $user_id is not used). Default null.
	 *     @type string|null $url      Optional. A URL the credit should link to. Default null.
	 *     @type array|null  $flags {
	 *         Optional. An array of flags to modify the rendering of the media credit. Default null.
	 *
	 *         @type bool $nofollow Optional. A flag indicating that `rel=nofollow` should be added to the link. Default false.
	 *     }
	 * }
	 */
	public function update_media_credit_json( \WP_Post $attachment, array $fields ) {

		// Allow direct use of REST API response.
		if ( isset( $fields['raw'] ) && \is_array( $fields['raw'] ) ) {
			$fields = $fields['raw'];
		}

		// Extract values from the fields.
		$user_id  = isset( $fields['user_id'] ) ? $this->validate_user_id( $fields['user_id'] ) : null;
		$freeform = isset( $fields['freeform'] ) ? $fields['freeform'] : null;
		$url      = isset( $fields['url'] ) ? $fields['url'] : null;
		$flags    = isset( $fields['flags'] ) ? $fields['flags'] : null;

		// Modify the media credit fields and retrieve the new values.
		$new = $this->set_media_credit_fields( $attachment, $user_id, $freeform, $url, $flags );

		// Invalidate the cache.
		$this->cache->delete( "json_{$attachment->ID}" );

		// Update the shortcodes in the parent post of the attachment.
		$this->update_shortcodes_in_parent_post( $attachment, $new['user_id'], $new['freeform'], $new['url'], $new['flags'] );
	}

	/**
	 * Updates the media credit fields.
	 *
	 * @internal
	 *
	 * @param  \WP_Post    $attachment The attachment \WP_Post object.
	 * @param  int|null    $user_id  Optional. The ID of the media item author. Default null.
	 * @param  string|null $freeform Optional. The media credit string (if $user_id is not used). Default null.
	 * @param  string|null $url      Optional. A URL the credit should link to. Default null.
	 * @param  array|null  $flags {
	 *     Optional. An array of flags to modify the rendering of the media credit. Default null.
	 *
	 *     @type bool $nofollow Optional. A flag indicating that `rel=nofollow` should be added to the link. Default false.
	 * }
	 *
	 * @return array {
	 *     The new values for the media credit fields.
	 *
	 *     @type int    $user_id  The ID of the media item author.
	 *     @type string $freeform The media credit string (if $user_id is not used).
	 *     @type string $url      A URL the credit should link to.
	 *     @type array  $flags {
	 *         An array of flags to modify the rendering of the media credit.
	 *
	 *         @type bool $nofollow A flag indicating that `rel=nofollow` should be added to the link.
	 *     }
	 * }
	 */
	protected function set_media_credit_fields( \WP_Post $attachment, $user_id = null, $freeform = null, $url = null, $flags = null ) {

		// Retrieve the current media credit fields for the attachemnt.
		$current = $this->get_media_credit_json( $attachment )['raw'];

		// Check if either the free-form credit or the author credit need to be updated.
		if ( isset( $freeform ) || isset( $user_id ) ) {
			if ( ! empty( $user_id ) && ( empty( $freeform ) || \get_the_author_meta( 'display_name', $user_id ) === $freeform ) ) {

				// A valid WordPress user was selected, and the display name matches
				// the free-form credit (or empty). The final conditional is necessary
				// for the case when a valid user is selected, filling in the hidden
				// field, then free-form text is entered after that. if so, the
				// free-form text is what should be used.
				$this->set_post_author_credit( $attachment, $user_id );

				// The freeform string does not exist anymore.
				$freeform = '';
			} else {

				// Free-form text was entered, insert postmeta with credit.
				// if free-form text is blank, insert a single space in postmeta.
				$freeform = $freeform ?: self::EMPTY_META_STRING;
				\update_post_meta( $attachment->ID, self::POSTMETA_KEY, $freeform );
			}
		}

		// Check if we need to update the URL.
		if ( isset( $url ) ) {
			\update_post_meta( $attachment->ID, self::URL_POSTMETA_KEY, $url );
		} else {
			$url = $current['url'];
		}

		// Check if we need to update the flags.
		if ( isset( $flags ) ) {
			// Merge current and updated fflags.
			$flags = \wp_parse_args( $flags, $current['flags'] );

			// Store the new flags array.
			\update_post_meta( $attachment->ID, self::DATA_POSTMETA_KEY, $flags );
		} else {
			$flags = $current['flags'];
		}

		return [
			'user_id'  => $user_id,
			'freeform' => $freeform,
			'url'      => $url,
			'flags'    => $flags,
		];
	}

	/**
	 * Sets a new post author media credit and deletes any existing free-form credit.
	 *
	 * @param \WP_Post $attachment The attachment \WP_Post object.
	 * @param int      $user_id    A valid user ID.
	 */
	protected function set_post_author_credit( \WP_Post $attachment, $user_id ) {
		$fields = [
			'ID'          => $attachment->ID,
			'post_author' => $user_id,
		];
		\wp_update_post( $fields );

		// Delete any residual metadata from a free-form field (as inserted below).
		\delete_post_meta( $attachment->ID, self::POSTMETA_KEY );
	}

	/**
	 * Validates a putative user ID against the database.
	 *
	 * @param  mixed $user_id A supposed user ID.
	 *
	 * @return int|null
	 */
	protected function validate_user_id( $user_id ) {
		$user_id = \absint( $user_id );

		return false !== \get_user_by( 'id', $user_id ) ? $user_id : null;
	}

	/**
	 * Returns the recently added media attachments and posts for a given author.
	 *
	 * @param array $query {
	 *    Optional. The query variables.
	 *
	 *    @type int    $author_id          A user ID. Default current user.
	 *    @type int    $offset             Number of attachment/posts to offset
	 *                                     in retrieved results. Can be used in
	 *                                     conjunction with pagination. Default 0.
	 *    @type int    $number             Number of users to limit the query for.
	 *                                     Can be used in conjunction with pagination.
	 *                                     Value -1 (all) is supported, but should
	 *                                     be used with caution on larger sites.
	 *                                     Default empty (all attachments/posts).
	 *    @type int    $paged              When used with number, defines the page
	 *                                     of results to return. Default 1.
	 *    @type bool   $include_posts      A flag indicating whether posts (as well
	 *                                     as attachments) should be included in the
	 *                                     results. Default false.
	 *    @type bool   $exclude_unattached A flag indicating whether media items
	 *                                     not currently attached to a parent post
	 *                                     should be excluded from the results.
	 *                                     Default true.
	 * }
	 *
	 * @return array
	 */
	public function get_author_media_and_posts( array $query = [] ) {

		// Limit query to attachments/posts published since the first plugin activation.
		$settings = $this->get_settings();
		if ( ! empty( $settings[ Settings::INSTALL_DATE ] ) ) {
			$query['since'] = $settings[ Settings::INSTALL_DATE ];
		}

		return $this->media_query->get_author_media_and_posts( $query );
	}

	/**
	 * Wraps the credit in HTML tags and allows the result to be filtered.
	 *
	 * @internal
	 *
	 * @param  string $credit             The credit (including optional link).
	 * @param  bool   $include_schema_org Optional. A flag indicating whether schema.org
	 *                                    markup should be included. Default false.
	 * @param  string $extra_attributes   Optional. Additional HTML attributes to
	 *                                    add to the wrapping tag. Default ''.
	 *
	 * @return string
	 */
	public function wrap_media_credit_markup( $credit, $include_schema_org = false, $extra_attributes = '' ) {
			$extra_attributes = \rtrim( ( $include_schema_org ? ' itemprop="copyrightHolder" ' : ' ' ) . $extra_attributes );
			$markup           = "<span class=\"media-credit\"{$extra_attributes}>{$credit}</span>";

			/**
			 * Filters the wrapped media credit markup.
			 *
			 * @param string $markup             The credit wrapped in additional markup.
			 * @param string $credit             The credit (including optional link).
			 * @param bool   $include_schema_org A flag indicating whether schema.org markup should be included.
			 */
			return \apply_filters( 'media_credit_wrapper', $markup, $credit, $include_schema_org );
	}
}
