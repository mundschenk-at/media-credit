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

use Media_Credit\Data_Storage\Options;

/**
 * The main API for the Media Credit plugin. To allow for static template functions,
 * it is instantiated as a singleton.
 *
 * The class provides access to the plugin settings and utility methods for manipulating
 * the postmeta data making up the credit information for individual attachments.
 *
 * @since 3.3.0
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
	 * Creates a new instance.
	 *
	 * @param string   $version           The plugin version string (e.g. "3.0.0-beta.2").
	 * @param Options  $options           The options handler.
	 * @param Settings $settings_template The default settings template.
	 */
	public function __construct( $version, Options $options, Settings $settings_template ) {
		$this->version           = $version;
		$this->options           = $options;
		$this->settings_template = $settings_template;
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
	public function update_shortcodes_in_parent_post( \WP_Post $attachment, $user_id = 0, $freeform = '', $url = '', array $flags = [] ) {

		if ( ! empty( $attachment->post_parent ) ) {
			// Get the parent post of the attachment.
			$post = \get_post( $attachment->post_parent );

			// Extract flags.
			$nofollow = ! empty( $flags['nofollow'] );

			// Filter the post's content.
			$post->post_content = $this->filter_changed_media_credits( $post->post_content, $attachment->ID, $user_id, $freeform, $url, $nofollow );

			// Save the filtered content in the database.
			\wp_update_post( $post );
		}
	}

	/**
	 * Filters post content for changed media credits.
	 *
	 * @param string $content   The current post content.
	 * @param int    $image_id  The attachment ID.
	 * @param int    $author_id The author ID.
	 * @param string $freeform  The freeform credit.
	 * @param string $url       The credit URL.
	 * @param bool   $nofollow  The "rel=nofollow" flag.
	 *
	 * @return string           The filtered post content.
	 */
	public function filter_changed_media_credits( $content, $image_id, $author_id, $freeform, $url, $nofollow ) {

		// Get the image source URL.
		$src = \wp_get_attachment_image_src( $image_id );
		if ( empty( $src[0] ) ) {
			// Invalid image ID.
			return $content;
		}

		// Extract the image basename without the size for use in a regular expression.
		$filename = \preg_quote( $this->get_image_filename_from_full_url( $src[0] ), '/' );

		// Look at every matching shortcode.
		\preg_match_all( '/' . \get_shortcode_regex( [ 'media-credit' ] ) . '/Ss', $content, $matches, PREG_SET_ORDER );

		foreach ( $matches as $shortcode ) {

			// Grab the shortcode attributes ...
			$attr = \shortcode_parse_atts( $shortcode[3] );
			$attr = $attr ?: [];

			// ... and the contained <img> tag.
			$img = $shortcode[5];

			if ( ! \preg_match( "/src=([\"'])(?:(?!\1).)*{$filename}/S", $img ) || ! \preg_match( "/wp-image-{$image_id}/S", $img ) ) {
				// This shortcode is for another image.
				continue;
			}

			// Check for credit type.
			if ( $author_id > 0 ) {
				// The new credit should use the ID.
				$id_or_name = "id={$author_id}";
			} else {
				// No valid ID, so use the freeform credit.
				$id_or_name = "name=\"{$freeform}\"";
			}

			// Drop the old id/name attributes (if any).
			unset( $attr['id'] );
			unset( $attr['name'] );

			// Update link attribute.
			if ( ! empty( $url ) ) {
				$attr['link'] = $url;
			} else {
				unset( $attr['link'] );
			}

			// Update nofollow attribute.
			if ( ! empty( $url ) && ! empty( $nofollow ) ) {
				$attr['nofollow'] = true;
			} else {
				unset( $attr['nofollow'] );
			}

			// Start reconstructing the shortcode.
			$new_shortcode = "[media-credit {$id_or_name}";

			// Add the rest of the attributes.
			foreach ( $attr as $name => $value ) {
				$new_shortcode .= " {$name}=\"{$value}\"";
			}

			// Finish up with the closing bracket and the <img> content.
			$new_shortcode .= ']' . $img . '[/media-credit]';

			// Replace the old shortcode with then new one.
			$content = \str_replace( $shortcode[0], $new_shortcode, $content );
		}

		return $content;
	}

	/**
	 * Returns the filename of an image in the wp_content directory (normally, could be any dir really) given the full URL to the image, ignoring WP sizes.
	 * E.g.:
	 * Given http://localhost/wordpress/wp-content/uploads/2010/08/ParksTrip2010_100706_1487-150x150.jpg, returns ParksTrip2010_100706_1487 (ignores size at end of string)
	 * Given http://localhost/wordpress/wp-content/uploads/2010/08/ParksTrip2010_100706_1487-thumb.jpg, return ParksTrip2010_100706_1487-thumb
	 * Given http://localhost/wordpress/wp-content/uploads/2010/08/ParksTrip2010_100706_1487-1.jpg, return ParksTrip2010_100706_1487-1
	 *
	 * @param  string $image Full URL to an image.
	 * @return string        The filename of the image excluding any size or extension, as given in the example above.
	 */
	protected function get_image_filename_from_full_url( $image ) {
		// Drop "-{$width}x{$height}".
		return \preg_replace( '/(.*?)(\-\d+x\d+)?\.\w+/S', '$1', \wp_basename( $image ) );
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
	 * Returns the freeform media credit for a given attachment.
	 *
	 * @param int $attachment_id An attachment ID.
	 *
	 * @return string            The freeform credit (or the empty string).
	 */
	public function get_media_credit_freeform_text( $attachment_id ) {
		return (string) \get_post_meta( $attachment_id, self::POSTMETA_KEY, true );
	}

	/**
	 * Returns the media credit URL as plain text for a given attachment.
	 *
	 * @param int $attachment_id An attachment ID.
	 *
	 * @return string            The credit URL (or the empty string if none is set).
	 */
	public function get_media_credit_url( $attachment_id ) {
		return (string) \get_post_meta( $attachment_id, self::URL_POSTMETA_KEY, true );
	}

	/**
	 * Returns the optional media credit data array for some media attachment.
	 *
	 * @param int $attachment_id An attachment ID.
	 *
	 * @return array             The data array.
	 */
	public function get_media_credit_data( $attachment_id ) {

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
	protected function render_media_credit_html( $user_id = 0, $freeform = '', $url = '', array $flags = [] ) {

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

		// Retrieve the fields.
		$user_id  = (int) $attachment->post_author;
		$freeform = $this->get_media_credit_freeform_text( $attachment->ID );
		$url      = $this->get_media_credit_url( $attachment->ID );
		$flags    = $this->get_media_credit_data( $attachment->ID );

		// Return media credit data.
		return [
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

		// Update the shortcodes in the parent post of the attachment.
		$this->update_shortcodes_in_parent_post( $attachment, $new['freeform'], $new['url'] );
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
}
