<?php
/**
 * This file is part of Media Credit.
 *
 * Copyright 2013-2023 Peter Putzer.
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

namespace Media_Credit\Components;

use Media_Credit\Core;
use Media_Credit\Settings;
use Media_Credit\Tools\Author_Query;

/**
 * The component handling the integration with the WordPress Media Library.
 *
 * @since 4.0.0 Renamed to Media_Credit\Components\Media_Library
 * @since 4.1.0 Constant AUTHORS_QUERY removed, property $author_query added.
 * @since 4.2.0 Property $version removed.
 *
 * @phpstan-type WP_Post_Array array{id:int, ID: int, post_author?: int}
 * @phpstan-type ImageMetaData array{}
 * @phpstan-import-type MediaCreditJSONRaw from Core
 */
class Media_Library implements \Media_Credit\Component {

	/**
	 * The core API.
	 *
	 * @var Core
	 */
	private $core;

	/**
	 * The post ID of the original attachment when cropping images.
	 *
	 * @since 4.1.0
	 *
	 * @var int|null
	 */
	private $cropped_parent_id = null;

	/**
	 * The author query helper.
	 *
	 * @since 4.1.0
	 *
	 * @var Author_Query
	 */
	private $author_query;

	/**
	 * The settings handler.
	 *
	 * @since 4.2.0
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since  3.0.0
	 * @since  4.0.0 Parameter $options added.
	 * @since  4.1.0 Parameter $author_query added.
	 * @since  4.2.0 Parameter $settings added, parameter $version removed.
	 *
	 * @param  Core         $core         The core plugin API.
	 * @param  Settings     $settings     The settings handler.
	 * @param  Author_Query $author_query The author query helper.
	 */
	public function __construct( Core $core, Settings $settings, Author_Query $author_query ) {
		$this->core         = $core;
		$this->settings     = $settings;
		$this->author_query = $author_query;
	}

	/**
	 * Sets up the various hooks for the plugin component.
	 *
	 * @return void
	 */
	public function run() {
		// Initialize admin-only parts.
		\add_action( 'admin_init',            [ $this, 'show_credit_in_media_list_view' ] );
		\add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts_and_styles' ] );

		// Add default credit to new attachments.
		\add_action( 'add_attachment', [ $this, 'add_default_media_credit_for_attachment' ] );

		// Handle editing attachment details.
		\add_action( 'print_media_templates',        [ $this, 'attachment_details_template' ] );
		\add_filter( 'wp_prepare_attachment_for_js', [ $this, 'prepare_attachment_media_credit_for_js' ], 10, 2 );
		\add_filter( 'attachment_fields_to_edit',    [ $this, 'add_media_credit_fields' ],                10, 2 );
		\add_filter( 'attachment_fields_to_save',    [ $this, 'save_media_credit_fields' ],               10, 2 );

		// Handle image cropping in the customizer.
		\add_action( 'wp_ajax_crop_image_pre_save',         [ $this, 'store_cropped_image_parent' ], 10, 2 );
		\add_filter( 'wp_ajax_cropped_attachment_metadata', [ $this, 'add_credit_to_cropped_attachment_metadata' ] );
		\add_filter( 'wp_header_image_attachment_metadata', [ $this, 'add_credit_to_cropped_header_metadata' ] );

		// Handle credits in EXIF meta data.
		\add_filter( 'wp_generate_attachment_metadata', [ $this, 'maybe_add_credit_from_exif_metadata' ], 10, 3 );

		// Update image credit if necessary.
		\add_filter( 'wp_update_attachment_metadata', [ $this, 'maybe_update_image_credit' ], 10, 2 );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since  3.0.0
	 * @since  4.0.0 Renamed to enqueue_scripts_and_stylees.
	 *
	 * @return void
	 */
	public function enqueue_scripts_and_styles() {
		// Set up resource files.
		$suffix  = ( defined( 'SCRIPT_DEBUG' ) && \SCRIPT_DEBUG ) ? '' : '.min';
		$url     = \plugin_dir_url( \MEDIA_CREDIT_PLUGIN_FILE );
		$version = $this->settings->get_version();

		// Pre-register the media scripts.
		\wp_register_script( 'media-credit-bootstrap',           "{$url}/admin/js/media-credit-bootstrap{$suffix}.js",           [],                                                                         $version, true );
		\wp_register_script( 'media-credit-legacy-autocomplete', "{$url}/admin/js/media-credit-legacy-autocomplete{$suffix}.js", [ 'media-credit-bootstrap', 'jquery', 'jquery-ui-autocomplete' ],           $version, true );
		\wp_register_script( 'media-credit-attachment-details',  "{$url}/admin/js/media-credit-attachment-details{$suffix}.js",  [ 'media-credit-bootstrap', 'jquery', 'jquery-ui-autocomplete', 'wp-api' ], $version, true );

		// And some styles.
		\wp_register_style( 'media-credit-legacy-edit-media-style',  "{$url}/admin/css/media-credit-legacy-edit-media{$suffix}.css",  [], $version, 'screen' );
		\wp_register_style( 'media-credit-attachment-details-style', "{$url}/admin/css/media-credit-attachment-details{$suffix}.css", [], $version, 'screen' );

		// Now add inline script data.
		$this->add_inline_script_data();

		// Autocomplete when editing media via the legacy form...
		if ( $this->is_legacy_media_edit_page() ) {
			\wp_enqueue_script( 'media-credit-legacy-autocomplete' );
			\wp_enqueue_style( 'media-credit-legacy-edit-media-style' );
		}

		// ... and for when the new JavaScript Media API is used.
		if ( \did_action( 'wp_enqueue_media' ) ) {
			\wp_enqueue_script( 'media-credit-attachment-details' );
			\wp_enqueue_style( 'media-credit-attachment-details-style' );
		}
	}

	/**
	 * Template for setting Media Credit in attachment details.
	 *
	 * @since 3.1.0
	 *
	 * @return void
	 */
	public function attachment_details_template() {
		include \MEDIA_CREDIT_PLUGIN_PATH . '/admin/partials/media-credit-attachment-details-tmpl.php';
	}

	/**
	 * Adds our global data for the JavaScript modules.
	 *
	 * @return void
	 */
	public function add_inline_script_data() {
		// Retrieve list of authors.
		$authors = [];
		foreach ( $this->author_query->get_authors() as $author ) {
			$authors[ $author->ID ] = $author->display_name;
		}

		// Retrieve the plugin settings.
		$s       = $this->settings->get_all_settings();
		$options = [
			'separator'       => $s[ Settings::SEPARATOR ],
			'organization'    => $s[ Settings::ORGANIZATION ],
			'noDefaultCredit' => $s[ Settings::NO_DEFAULT_CREDIT ],
		];

		$script  = 'var mundschenk=window.mundschenk||{},mediaCredit=mundschenk.mediaCredit||{};';
		$script .= 'mediaCredit.options=' . \wp_json_encode( $options ) . ';';
		$script .= 'mediaCredit.id=' . \wp_json_encode( $authors ) . ';';

		\wp_add_inline_script( 'media-credit-bootstrap', $script, 'after' );
	}

	/**
	 * Ensures that proper attachment credits are shown on the admin side of WordPress.
	 *
	 * @since 4.2.0 Renamed from `admin_init`.`
	 *
	 * @return void
	 */
	public function show_credit_in_media_list_view() {
		// Filter the_author using this method so that freeform media credit is correctly displayed in Media Library.
		\add_filter( 'the_author', [ $this, 'filter_the_author' ] );
	}

	/**
	 * Filters the author name to properly display custom credits.
	 *
	 * @param  string $display_name The author's display name.
	 *
	 * @return string
	 */
	public function filter_the_author( $display_name ) {
		$attachment = \get_post();
		if ( ! empty( $attachment ) && 'attachment' === $attachment->post_type ) {
			$fields       = $this->core->get_media_credit_json( $attachment );
			$display_name = $fields['plaintext'];
		}

		return $display_name;
	}

	/**
	 * Is the current page one where media attachments can be edited using the legacy API?
	 *
	 * @internal
	 *
	 * @since  4.2.0 Method visibility changed to protected to allow unit testing.
	 *
	 * @return bool
	 */
	protected function is_legacy_media_edit_page() {
		$screen = \get_current_screen();

		return ! empty( $screen ) && 'post' === $screen->base && 'attachment' === $screen->id;
	}

	/**
	 * Add media credit information to wp.media.model.Attachment.
	 *
	 * @since  3.1.0
	 * @since  4.0.0 Removed unused parameter $meta.
	 *
	 * @param  mixed[]  $response   Array of prepared attachment data.
	 * @param  \WP_Post $attachment Attachment object.
	 *
	 * @return mixed[] Array of prepared attachment data.
	 */
	public function prepare_attachment_media_credit_for_js( array $response, \WP_Post $attachment ) {

		// Load data.
		$credit = $this->core->get_media_credit_json( $attachment );

		// Set up Media Credit model data (not as an array because data-settings code in View can't deal with it.
		$response['mediaCreditText']          = $credit['plaintext'];
		$response['mediaCreditLink']          = $credit['raw']['url'];
		$response['mediaCreditAuthorID']      = empty( $credit['raw']['freeform'] ) ? $credit['raw']['user_id'] : '';
		$response['mediaCreditAuthorDisplay'] = $response['mediaCreditAuthorID'] ? \get_the_author_meta( 'display_name',  /* @scrutinizer ignore-type */ $response['mediaCreditAuthorID'] ) : '';
		$response['mediaCreditNoFollow']      = ! empty( $credit['raw']['flags']['nofollow'] ) ? '1' : '0';

		// Additional data that's not directly related to the fields.
		$response['mediaCredit']['placeholder'] = $this->get_placeholder_text( $attachment );

		// We need some nonces as well.
		$response['nonces']['mediaCredit']['update']  = \wp_create_nonce( "save-attachment-{$response['id']}-media-credit" );
		$response['nonces']['mediaCredit']['content'] = \wp_create_nonce( "update-attachment-{$response['id']}-media-credit-in-editor" );

		return $response;
	}

	/**
	 * Adds custom media credit fields to Edit Media screens.
	 *
	 * @param  mixed[]  $fields     An array of attachment form fields.
	 * @param  \WP_Post $attachment The \WP_Post attachment object.
	 *
	 * @return mixed                The filtered fields.
	 */
	public function add_media_credit_fields( $fields, \WP_Post $attachment ) {

		$data       = $this->core->get_media_credit_json( $attachment );
		$author_id  = \esc_attr( '' === $data['raw']['freeform'] ? (string) $data['raw']['user_id'] : '' );
		$credit     = \esc_attr( $data['plaintext'] );
		$credit_url = \esc_url( $data['raw']['url'] );
		$nofollow   = \checked( ! empty( $data['raw']['flags']['nofollow'] ), true, false );

		// Use placeholders with `no_default_credit` enabled.
		$placeholder = '';
		if ( ! empty( $this->settings->get( Settings::NO_DEFAULT_CREDIT ) ) ) {
			$placeholder = \esc_attr( $this->get_placeholder_text( $attachment ) );
			$placeholder = "placeholder='{$placeholder}'";
		}

		// Set up credit input field.
		$fields['media-credit'] = [
			'label'         => __( 'Credit', 'media-credit' ),
			'input'         => 'html',
			'html'          => "<input id='attachments[{$attachment->ID}][media-credit]' class='media-credit-input' size='30' {$placeholder} value='{$credit}' name='attachments[{$attachment->ID}][media-credit]' />",
			'show_in_edit'  => true,
			'show_in_modal' => false,
		];

		// Set up credit URL field.
		$fields['media-credit-url'] = [
			'label'         => __( 'Credit URL', 'media-credit' ),
			'input'         => 'html',
			'html'          => "<input id='attachments[{$attachment->ID}][media-credit-url]' class='media-credit-input' type='url' size='30' value='{$credit_url}' name='attachments[{$attachment->ID}][media-credit-url]' />",
			'show_in_edit'  => true,
			'show_in_modal' => false,
		];

		// Set up nofollow checkbox.
		$html = "<label><input id='attachments[{$attachment->ID}][media-credit-nofollow]' class='media-credit-input' type='checkbox' value='1' name='attachments[{$attachment->ID}][media-credit-nofollow]' {$nofollow}/>" . __( 'Add <code>rel="nofollow"</code>.', 'media-credit' ) . '</label>';

		$fields['media-credit-data'] = [
			'label'         => '', // necessary for HTML type fields.
			'input'         => 'html',
			'html'          => $html,
			'show_in_edit'  => true,
			'show_in_modal' => false,
		];

		// Set up hidden field as a container for additional data.
		$fields['media-credit-hidden'] = [
			'label'         => '', // necessary for HTML type fields.
			'input'         => 'html',
			'html'          => "<input name='attachments[{$attachment->ID}][media-credit-hidden]' id='attachments[{$attachment->ID}][media-credit-hidden]' type='hidden' value='$author_id' class='media-credit-hidden' data-author-id='{$attachment->post_author}' data-post-id='{$attachment->ID}' data-author-display='{$credit}' />",
			'show_in_edit'  => true,
			'show_in_modal' => false,
		];

		return $fields;
	}

	/**
	 * Saves media credit fields edited in the old attachment view.
	 *
	 * @param  array $post       An array of post data.
	 * @param  array $attachment An array of attachment metadata (including the custom fields).
	 *
	 * @return array
	 *
	 * @phpstan-param WP_Post_Array $post
	 * @phpstan-param array{'media-credit-hidden': int, 'media-credit': string, 'media-credit-url': string, 'media-credit-nofollow': bool} $attachment
	 * @phpstan-return WP_Post_Array
	 */
	public function save_media_credit_fields( array $post, array $attachment ) {
		$fields = [
			'user_id'  => $attachment['media-credit-hidden'],
			'freeform' => $attachment['media-credit'],
			'url'      => $attachment['media-credit-url'],
			'flags'    => [
				'nofollow' => ! empty( $attachment['media-credit-nofollow'] ),
			],
		];

		// Only clear the post author if we've been setting a user credit.
		if ( ! empty( $fields['user_id'] ) ) {
			unset( $post['post_author'] );
		}

		$attachment = \get_post( $post['ID'] );
		if ( $attachment instanceof \WP_Post ) {
			$this->core->update_media_credit_json( $attachment, $fields );
		}

		return $post;
	}

	/**
	 * Retrieves the placeholder text to use for the given attachemnt.
	 *
	 * @since  4.0.0
	 *
	 * @param  \WP_Post $attachment The attachment \WP_Post object.
	 *
	 * @return string
	 */
	protected function get_placeholder_text( \WP_Post $attachment ) {

		// The default placeholder for credit input fields.
		$placeholder = \__( 'e.g. Jane Doe', 'media-credit' );

		/**
		 * Filters the placeholder text for the credit input field.
		 *
		 * @param string   $placeholder The placeholder text.
		 * @param \WP_Post $attachment  The attachment \WP_Post object.
		 */
		return \apply_filters( 'media_credit_placeholder_text', $placeholder, $attachment );
	}

	/**
	 * Saves the default media credit for newly uploaded attachments.
	 *
	 * @since  4.0.0
	 *
	 * @param  int $post_id Attachment ID.
	 *
	 * @return void
	 */
	public function add_default_media_credit_for_attachment( $post_id ) {
		// Retrieve the attachemnt object.
		$attachment = \get_post( $post_id );
		if ( empty( $attachment ) ) {
			return;
		}

		// Get the filtered default value.
		$default = $this->get_default_credit( $attachment );

		if ( ! empty( $default ) ) {
			$this->core->update_media_credit_json( $attachment, [ 'freeform' => $default ] );
		}
	}

	/**
	 * Retrieves and filters the custom default credit string for new attachments.
	 *
	 * @since  4.0.0
	 *
	 * @param  \WP_Post $attachment The attachment \WP_Post object.
	 *
	 * @return string
	 */
	protected function get_default_credit( \WP_Post $attachment ) {
		$default = \trim( \strval( $this->settings->get( Settings::CUSTOM_DEFAULT_CREDIT ) ) );

		/**
		 * Filters the default credit for new attachments. An empty string means
		 * no default credit will be set.
		 *
		 * @param string   $default    The custom default media credit set in the plugin settings.
		 * @param \WP_Post $attachment The attachment.
		 */
		return \apply_filters( 'media_credit_new_attachment_default', $default, $attachment );
	}

	/**
	 * Stores the parent attachment ID for a cropped image.
	 *
	 * @since  4.1.0
	 *
	 * @param  string $context       The Customizer control requesting the cropped image.
	 * @param  int    $attachment_id The attachment ID of the original image.
	 *
	 * @return void
	 */
	public function store_cropped_image_parent( $context, $attachment_id ) {
		$this->cropped_parent_id = $attachment_id;
	}

	/**
	 * Adds the media credit information from the original attachment for cropped
	 * header images.
	 *
	 * @since  4.1.0
	 *
	 * @param  mixed[] $data Attachment meta data.
	 *
	 * @return mixed[]       The filtered meta data.
	 *
	 * @phpstan-param array{'attachment_parent'?: int} $data
	 */
	public function add_credit_to_cropped_header_metadata( array $data ) {
		if ( ! empty( $data['attachment_parent'] ) ) {
			$data = $this->add_credit_to_metadata( $data, $data['attachment_parent'] );
		}

		// Nothing to see here.
		return $data;
	}

	/**
	 * Adds the media credit information from the original attachment for cropped
	 * images (other than header images).
	 *
	 * @since  4.1.0
	 *
	 * @param  mixed[] $data Attachment meta data.
	 *
	 * @return mixed[]       The filtered meta data.
	 */
	public function add_credit_to_cropped_attachment_metadata( array $data ) {
		if ( ! empty( $this->cropped_parent_id ) ) {
			$data                    = $this->add_credit_to_metadata( $data, $this->cropped_parent_id );
			$this->cropped_parent_id = null;
		}

		// Nothing to see here.
		return $data;
	}

	/**
	 * Adds the media credit information from the parent to the meta data array.
	 *
	 * @since  4.1.0
	 *
	 * @param  mixed[] $data      Attachment meta data.
	 * @param  int     $parent_id Attachment parent post ID.
	 *
	 * @return mixed[]            The enriched meta data.
	 */
	protected function add_credit_to_metadata( array $data, $parent_id ) {
		$parent = \get_post( $parent_id );

		if ( ! empty( $parent ) ) {
			$credit = $this->core->get_media_credit_json( $parent );

			if ( ! empty( $credit['raw'] ) ) {
				$data['media_credit'] = $credit['raw'];
			}
		}

		return $data;
	}

	/**
	 * Adds a credit for the image if one is set in the EXIF data.
	 *
	 * If the name exactly matches a user (and default credits are not disabled),
	 * the ID of the user will be used, otherwise a freeform credit will be generated.
	 *
	 * @since  4.1.0
	 *
	 * @param  mixed[] $data          An array of attachment meta data.
	 * @param  int     $attachment_id Current attachment ID.
	 * @param  string  $context       Optional (only available on WordPress 5.3+). Additional context. Can be 'create' when metadata was initially created for new attachment
	 *                                or 'update' when the metadata was updated. Default 'create'.
	 *
	 * @return mixed[]
	 *
	 * @phpstan-param array{image_meta?: array{ credit?:string, copyright?:string } } $data
	 */
	public function maybe_add_credit_from_exif_metadata( array $data, $attachment_id, $context = 'create' ) {
		if ( 'create' !== $context ) {
			// We are only doing this on the initial call.
			return $data;
		}

		$credit = '';
		if ( ! empty( $data['image_meta']['credit'] ) ) {
			$credit = \trim( $data['image_meta']['credit'] );
		} elseif ( ! empty( $data['image_meta']['copyright'] ) ) {
			$credit = \trim( $data['image_meta']['copyright'] );
		}

		if ( ! empty( $credit ) ) {
			$user_id = $this->author_query->get_author_by_name( $credit );

			if ( ! empty( $user_id ) ) {
				$data['media_credit']['user_id'] = $user_id;
			} else {
				$data['media_credit']['freeform'] = $credit;
			}
		}

		return $data;
	}

	/**
	 * Updates the media credit information when possible. (The `media_credit` key
	 * is removed from the meta data array afterwards.)
	 *
	 * @since  4.1.0
	 *
	 * @param  mixed[] $data          Attachment meta data.
	 * @param  int     $attachment_id Attachment post ID.
	 *
	 * @return mixed[]                The filtered meta data.
	 *
	 * @phpstan-param array{'media_credit'?: MediaCreditJSONRaw} $data
	 */
	public function maybe_update_image_credit( array $data, $attachment_id ) {
		if ( isset( $data['media_credit'] ) ) {
			$attachment = \get_post( $attachment_id );

			if ( ! empty( $attachment )
				&& ! empty( $data['media_credit'] )
				&& \is_array( $data['media_credit'] )
			) {
				$this->core->update_media_credit_json( $attachment, $data['media_credit'] );
			}

			// Remove media credit data from meta data array.
			unset( $data['media_credit'] );
		}

		// We are done here.
		return $data;
	}
}
