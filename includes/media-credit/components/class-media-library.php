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

namespace Media_Credit\Components;

use Media_Credit\Core;
use Media_Credit\Settings;

/**
 * The component handling the integration with the WordPress Media Library.
 *
 * @since 4.0.0 Renamed to Media_Credit\Components\Media_Library
 */
class Media_Library implements \Media_Credit\Component {

	/**
	 * The parameters for querying the list of authors.
	 *
	 * @var array
	 */
	const AUTHORS_QUERY = [
		'who'    => 'authors',
		'fields' => [
			'ID',
			'display_name',
		],
	];

	/**
	 * The version of this plugin.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	private $version;

	/**
	 * The base URL for loading resources.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	private $url;

	/**
	 * The file suffix for loading ressources.
	 *
	 * @since 3.2.0
	 *
	 * @var string
	 */
	private $suffix;

	/**
	 * The core API.
	 *
	 * @var Core
	 */
	private $core;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    3.0.0
	 * @since    4.0.0 Parameter $options added.
	 *
	 * @param string $version     The plugin version.
	 * @param Core   $core        The core plugin API.
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
		// Set up resource files.
		$this->url    = \plugin_dir_url( MEDIA_CREDIT_PLUGIN_FILE );
		$this->suffix = SCRIPT_DEBUG ? '' : '.min';

		\add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_styles' ] );
		\add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		\add_action( 'print_media_templates', [ $this, 'attachment_details_template' ] );
		\add_action( 'admin_init',            [ $this, 'admin_init' ] );
		\add_action( 'add_attachment',        [ $this, 'add_default_media_credit_for_attachment' ], 10, 1 );

		// Filter hooks.
		\add_filter( 'wp_prepare_attachment_for_js',    [ $this, 'prepare_attachment_media_credit_for_js' ], 10, 2 );
		\add_filter( 'attachment_fields_to_edit',       [ $this, 'add_media_credit_fields' ],                10, 2 );
		\add_filter( 'attachment_fields_to_save',       [ $this, 'save_media_credit_fields' ],               10, 2 );

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    3.0.0
	 */
	public function enqueue_styles() {
		// Style placeholders when editing media.
		if ( $this->is_legacy_media_edit_page() || did_action( 'wp_enqueue_media' ) ) {
			wp_enqueue_style( 'media-credit-attachment-details-style', "{$this->url}/admin/css/media-credit-attachment-details{$this->suffix}.css", [], $this->version, 'screen' );
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    3.0.0
	 */
	public function enqueue_scripts() {
		// Autocomplete when editing media via the legacy form...
		if ( $this->is_legacy_media_edit_page() ) {
			\wp_enqueue_script( 'media-credit-legacy-autocomplete', "{$this->url}/admin/js/media-credit-legacy-autocomplete{$this->suffix}.js", [ 'jquery', 'jquery-ui-autocomplete' ], $this->version, true );
		}

		// ... and for when the new JavaScript Media API is used.
		if ( \did_action( 'wp_enqueue_media' ) ) {
			\wp_enqueue_script( 'media-credit-attachment-details', "{$this->url}/admin/js/media-credit-attachment-details{$this->suffix}.js", [ 'jquery', 'jquery-ui-autocomplete', 'wp-api' ], $this->version, true );
		}
	}

	/**
	 * Template for setting Media Credit in attachment details.
	 *
	 * @since 3.1.0
	 */
	public function attachment_details_template() {
		include \dirname( MEDIA_CREDIT_PLUGIN_FILE ) . '/admin/partials/media-credit-attachment-details-tmpl.php';
	}

	/**
	 * Add our global variable for the TinyMCE plugin.
	 */
	public function admin_head() {
		// Retrieve the plugin settings.
		$options = $this->core->get_settings();

		$authors = [];
		foreach ( \get_users( self::AUTHORS_QUERY ) as $author ) {
			$authors[ $author->ID ] = $author->display_name;
		}

		$media_credit = [
			'separator'       => $options[ Settings::SEPARATOR ],
			'organization'    => $options[ Settings::ORGANIZATION ],
			'noDefaultCredit' => $options[ Settings::NO_DEFAULT_CREDIT ],
			'id'              => $authors,
		];

		?>
		<script type='text/javascript'>
			var $mediaCredit = <?php echo /* @scrutinizer ignore-type */ \wp_json_encode( $media_credit ); ?>;
		</script>
		<?php
	}

	/**
	 * Initialize settings.
	 */
	public function admin_init() {
		// Don't bother doing this stuff if the current user lacks permissions as they'll never see the pages.
		if ( ( \current_user_can( 'edit_posts' ) || \current_user_can( 'edit_pages' ) ) ) {
			\add_action( 'admin_head', [ $this, 'admin_head' ] );
		}

		// Filter the_author using this method so that freeform media credit is correctly displayed in Media Library.
		\add_filter( 'the_author', [ $this, 'filter_the_author' ], 10, 1 );
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
		if ( $attachment instanceof \WP_Post && 'attachment' === $attachment->post_type ) {
			$fields       = $this->core->get_media_credit_json( $attachment );
			$display_name = $fields['plaintext'];
		}

		return $display_name;
	}

	/**
	 * Is the current page one where media attachments can be edited using the legacy API?
	 *
	 * @internal 3.1.0
	 * @access private
	 */
	private function is_legacy_media_edit_page() {
		$screen = \get_current_screen();

		return ! empty( $screen ) && 'post' === $screen->base && 'attachment' === $screen->id;
	}

	/**
	 * Add media credit information to wp.media.model.Attachment.
	 *
	 * @since 3.1.0
	 * @since 4.0.0 Removed unused parameter $meta.
	 *
	 * @param array    $response   Array of prepared attachment data.
	 * @param \WP_Post $attachment Attachment object.
	 *
	 * @return array Array of prepared attachment data.
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
	 * @param array    $fields     An array of attachment form fields.
	 * @param \WP_Post $attachment The \WP_Post attachment object.
	 *
	 * @return array               The filtered fields.
	 */
	public function add_media_credit_fields( $fields, \WP_Post $attachment ) {

		$data      = $this->core->get_media_credit_json( $attachment );
		$author_id = '' === $data['raw']['freeform'] ? $data['raw']['user_id'] : '';

		// Use placeholders with `no_default_credit` enabled.
		$placeholder = '';
		if ( ! empty( $this->core->get_settings()[ Settings::NO_DEFAULT_CREDIT ] ) ) {
			$placeholder = \esc_attr( $this->get_placeholder_text( $attachment ) );
			$placeholder = "placeholder='{$placeholder}'";
		}

		// Set up credit input field.
		$fields['media-credit'] = [
			'label'         => __( 'Credit', 'media-credit' ),
			'input'         => 'html',
			'html'          => "<input id='attachments[{$attachment->ID}][media-credit]' class='media-credit-input' size='30' {$placeholder} value='{$data['plaintext']}' name='attachments[{$attachment->ID}][media-credit]' />",
			'show_in_edit'  => true,
			'show_in_modal' => false,
		];

		// Set up credit URL field.
		$fields['media-credit-url'] = [
			'label'         => __( 'Credit URL', 'media-credit' ),
			'input'         => 'html',
			'html'          => "<input id='attachments[{$attachment->ID}][media-credit-url]' class='media-credit-input' type='url' size='30' value='{$data['raw']['url']}' name='attachments[{$attachment->ID}][media-credit-url]' />",
			'show_in_edit'  => true,
			'show_in_modal' => false,
		];

		// Set up nofollow checkbox.
		$html = "<label><input id='attachments[{$attachment->ID}][media-credit-nofollow]' class='media-credit-input' type='checkbox' value='1' name='attachments[{$attachment->ID}][media-credit-nofollow]' " . \checked( ! empty( $data['raw']['flags']['nofollow'] ), true, false ) . '/>' . __( 'Add <code>rel="nofollow"</code>.', 'media-credit' ) . '</label>';

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
			'html'          => "<input name='attachments[{$attachment->ID}][media-credit-hidden]' id='attachments[{$attachment->ID}][media-credit-hidden]' type='hidden' value='$author_id' class='media-credit-hidden' data-author-id='{$attachment->post_author}' data-post-id='{$attachment->ID}' data-author-display='{$data['plaintext']}' />",
			'show_in_edit'  => true,
			'show_in_modal' => false,
		];

		return $fields;
	}

	/**
	 * Saves media credit fields edited in the old attachment view.
	 *
	 * @param array $post       An array of post data.
	 * @param array $attachment An array of attachment metadata (including the custom fields).
	 */
	public function save_media_credit_fields( array $post, array $attachment ) {
		$fields = [
			'user_id'  => $attachment['media-credit-hidden'],
			'freeform' => $attachment['media-credit'],
			'url'      => $attachment['media-credit-url'],
			'nofollow' => ! empty( $attachment['media-credit-nofollow'] ),
		];

		$this->core->update_media_credit_json( \get_post( $post['ID'] ), $fields );

		return $post;
	}

	/**
	 * Retrieves the placeholder text to use for the given attachemnt.
	 *
	 * @since 4.0.0
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
	 * @since 4.0.0
	 *
	 * @param int $post_id Attachment ID.
	 */
	public function add_default_media_credit_for_attachment( $post_id ) {
		// Retrieve the attachemnt object.
		$attachment = \get_post( $post_id );
		if ( ! $attachment instanceof \WP_Post ) {
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
	 * @since 4.0.0
	 *
	 * @param  \WP_Post $attachment The attachment \WP_Post object.
	 *
	 * @return string
	 */
	protected function get_default_credit( \WP_Post $attachment ) {
		$s = $this->core->get_settings();

		$default = ! empty( $s[ Settings::CUSTOM_DEFAULT_CREDIT ] ) ? \trim( $s[ Settings::CUSTOM_DEFAULT_CREDIT ] ) : '';

		/**
		 * Filters the default credit for new attachments. An empty string means
		 * no default credit will be set.
		 *
		 * @param string   $default    The custom default media credit set in the plugin settings.
		 * @param \WP_Post $attachment The attachment.
		 */
		return \apply_filters( 'media_credit_new_attachment_default', $default, $attachment );
	}
}
