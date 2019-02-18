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
use Media_Credit\Template_Tags;
use Media_Credit\Data_Storage\Options;

/**
 * The component handling the integration with the WordPress Media Library.
 *
 * @since 3.3.0 Renamed to Media_Credit\Components\Media_Library
 */
class Media_Library implements \Media_Credit\Component, \Media_Credit\Base {

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
	 * The options handler.
	 *
	 * @var Options
	 */
	private $options;

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
	 * @since    3.3.0 Parameter $options added.
	 *
	 * @param string  $version     The plugin version.
	 * @param Core    $core        The core plugin API.
	 * @param Options $options     The options handler.
	 */
	public function __construct( $version, Core $core, Options $options ) {
		$this->version = $version;
		$this->core    = $core;
		$this->options = $options;
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
			wp_enqueue_script( 'media-credit-legacy-autocomplete', "{$this->url}/admin/js/media-credit-legacy-autocomplete{$this->suffix}.js", [ 'jquery', 'jquery-ui-autocomplete' ], $this->version, true );
		}

		// ... and for when the new JavaScript Media API is used.
		if ( did_action( 'wp_enqueue_media' ) ) {
			wp_enqueue_script( 'media-credit-attachment-details', "{$this->url}/admin/js/media-credit-attachment-details{$this->suffix}.js", [ 'jquery', 'jquery-ui-autocomplete', 'wp-api' ], $this->version, true );
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
		$options = $this->options->get( Options::OPTION, [] );

		$authors = [];
		foreach ( get_users( [ 'who' => 'authors' ] ) as $author ) {
			$authors[ $author->ID ] = $author->display_name;
		}

		$media_credit = [
			'separator'       => $options['separator'],
			'organization'    => $options['organization'],
			'noDefaultCredit' => $options['no_default_credit'],
			'id'              => $authors,
		];

		?>
		<script type='text/javascript'>
			var $mediaCredit = <?php echo /* @scrutinizer ignore-type */ wp_json_encode( $media_credit ); ?>;
		</script>
		<?php
	}

	/**
	 * Initialize settings.
	 */
	public function admin_init() {
		// Don't bother doing this stuff if the current user lacks permissions as they'll never see the pages.
		if ( ( current_user_can( 'edit_posts' ) || current_user_can( 'edit_pages' ) ) ) {
			add_action( 'admin_head', [ $this, 'admin_head' ] );
		}

		// Filter the_author using this method so that freeform media credit is correctly displayed in Media Library.
		add_filter( 'the_author', [ Template_Tags::class, 'get_media_credit' ] );
	}

	/**
	 * Is the current page one where media attachments can be edited using the legacy API?
	 *
	 * @internal 3.1.0
	 * @access private
	 */
	private function is_legacy_media_edit_page() {
		$screen = get_current_screen();

		return ! empty( $screen ) && 'post' === $screen->base && 'attachment' === $screen->id;
	}

	/**
	 * Add media credit information to wp.media.model.Attachment.
	 *
	 * @since 3.1.0
	 * @since 3.3.0 Removed unused parameter $meta.
	 *
	 * @param array      $response   Array of prepared attachment data.
	 * @param int|object $attachment Attachment ID or object.
	 *
	 * @return array Array of prepared attachment data.
	 */
	public function prepare_attachment_media_credit_for_js( $response, $attachment ) {

		$credit    = Template_Tags::get_media_credit( $attachment );
		$url       = Template_Tags::get_media_credit_url( $attachment );
		$data      = Template_Tags::get_media_credit_data( $attachment );
		$author_id = '' === Template_Tags::get_freeform_media_credit( $attachment ) ? $attachment->post_author : '';
		$options   = $this->options->get( Options::OPTION, [] );

		// Set up Media Credit model data (not as an array because data-settings code in View can't deal with it.
		$response['mediaCreditText']          = $credit;
		$response['mediaCreditLink']          = $url;
		$response['mediaCreditAuthorID']      = $author_id;
		$response['mediaCreditAuthorDisplay'] = $author_id ? $credit : '';
		$response['mediaCreditNoFollow']      = ! empty( $data['nofollow'] ) ? '1' : '0';

		// Add some nonces.
		$response['nonces']['mediaCredit']['update']  = wp_create_nonce( "save-attachment-{$response['id']}-media-credit" );
		$response['nonces']['mediaCredit']['content'] = wp_create_nonce( "update-attachment-{$response['id']}-media-credit-in-editor" );

		// And the Media Credit options.
		$response['mediaCreditOptions']['noDefaultCredit']     = $options['no_default_credit'];
		$response['mediaCreditOptions']['creditAtEnd']         = $options['credit_at_end'];
		$response['mediaCreditOptions']['postThumbnailCredit'] = $options['post_thumbnail_credit'];

		return $response;
	}

	/**
	 * Add custom media credit fields to Edit Media screens.
	 *
	 * @param array        $fields The custom fields.
	 * @param int|\WP_Post $post   Post object or ID.
	 * @return array               The list of fields.
	 */
	public function add_media_credit_fields( $fields, $post ) {
		$options   = $this->options->get( Options::OPTION, [] );
		$credit    = Template_Tags::get_media_credit( $post );
		$value     = 'value';
		$author_id = '' === Template_Tags::get_freeform_media_credit( $post ) ? $post->post_author : '';

		// Use placeholders instead of value if no freeform credit is set with `no_default_credit` enabled.
		if ( ! empty( $options['no_default_credit'] ) && ! empty( $author_id ) ) {
			$value = 'placeholder';
		}

		// Set up credit input field.
		$fields['media-credit'] = [
			'label'         => __( 'Credit', 'media-credit' ),
			'input'         => 'html',
			'html'          => "<input id='attachments[$post->ID][media-credit]' class='media-credit-input' size='30' $value='$credit' name='attachments[$post->ID][media-credit]' />",
			'show_in_edit'  => true,
			'show_in_modal' => false,
		];

		// Set up credit URL field.
		$url = Template_Tags::get_media_credit_url( $post );

		$fields['media-credit-url'] = [
			'label'         => __( 'Credit URL', 'media-credit' ),
			'input'         => 'html',
			'html'          => "<input id='attachments[$post->ID][media-credit-url]' class='media-credit-input' type='url' size='30' value='$url' name='attachments[$post->ID][media-credit-url]' />",
			'show_in_edit'  => true,
			'show_in_modal' => false,
		];

		// Set up nofollow checkbox.
		$data = Template_Tags::get_media_credit_data( $post );
		$html = "<label><input id='attachments[$post->ID][media-credit-nofollow]' class='media-credit-input' type='checkbox' value='1' name='attachments[$post->ID][media-credit-nofollow]' " . checked( ! empty( $data['nofollow'] ), true, false ) . '/>' . __( 'Add <code>rel="nofollow"</code>.', 'media-credit' ) . '</label>';

		$fields['media-credit-data'] = [
			'label'         => '', // necessary for HTML type fields.
			'input'         => 'html',
			'html'          => $html,
			'show_in_edit'  => true,
			'show_in_modal' => false,
		];

		// Set up hidden field as a container for additional data.
		$author_display = Template_Tags::get_media_credit( $post );
		$nonce          = wp_create_nonce( 'media_credit_author_names' );

		$fields['media-credit-hidden'] = [
			'label'         => '', // necessary for HTML type fields.
			'input'         => 'html',
			'html'          => "<input name='attachments[$post->ID][media-credit-hidden]' id='attachments[$post->ID][media-credit-hidden]' type='hidden' value='$author_id' class='media-credit-hidden' data-author-id='{$post->post_author}' data-post-id='$post->ID' data-author-display='$author_display' data-nonce='$nonce' />",
			'show_in_edit'  => true,
			'show_in_modal' => false,
		];

		return $fields;
	}

	/**
	 * Change the post_author to the entered media credit from add_media_credit() above.
	 *
	 * @param object $post Object of attachment containing all fields from get_post().
	 * @param object $attachment Object of attachment containing few fields, unused in this method.
	 */
	public function save_media_credit_fields( $post, $attachment ) {
		$wp_user_id    = $attachment['media-credit-hidden'];
		$freeform_name = $attachment['media-credit'];
		$url           = $attachment['media-credit-url'];
		$nofollow      = $attachment['media-credit-nofollow'];
		$options       = $this->options->get( Options::OPTION, [] );

		// We need to update the credit URL in any case.
		update_post_meta( $post['ID'], self::URL_POSTMETA_KEY, $url ); // insert '_media_credit_url' metadata field.

		// Update optional data array with nofollow.
		update_post_meta( $post['ID'], self::DATA_POSTMETA_KEY, wp_parse_args( [ 'nofollow' => $nofollow ], Template_Tags::get_media_credit_data( $post ) ) );

		/**
		 * A valid WP user was selected, and the display name matches the free-form. The final conditional is
		 * necessary for the case when a valid user is selected, filling in the hidden field, then free-form
		 * text is entered after that. if so, the free-form text is what should be used.
		 *
		 * @internal 3.1.0 Also check for `no_default_credit` option to prevent unnecessary `EMPTY_META_STRING` uses.
		 */
		if ( ! empty( $wp_user_id ) && ( $options['no_default_credit'] || get_the_author_meta( 'display_name', $wp_user_id ) === $freeform_name ) ) {
			// Update post_author with the chosen user.
			$post['post_author'] = $wp_user_id;

			// Delete any residual metadata from a free-form field.
			delete_post_meta( $post['ID'], self::POSTMETA_KEY );

			// Update media credit shortcodes in the current post.
			$this->core->update_media_credit_in_post( $post, '', $url );
		} else {
			/**
			 * Free-form text was entered, insert postmeta with credit. If free-form text is blank, insert
			 * a single space in postmeta.
			 */
			$freeform = empty( $freeform_name ) ? self::EMPTY_META_STRING : $freeform_name;

			// Insert '_media_credit' metadata field for image with free-form text.
			update_post_meta( $post['ID'], self::POSTMETA_KEY, $freeform );

			// Update media credit shortcodes in the current post.
			$this->core->update_media_credit_in_post( $post, $freeform, $url );
		}

		return $post;
	}
}
