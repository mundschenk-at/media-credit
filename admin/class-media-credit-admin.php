<?php
/**
 * This file is part of Media Credit.
 *
 * Copyright 2013-2018 Peter Putzer.
 * Copyright 2010-2011 Scott Bressler.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License,
 * version 2 as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 *
 * @link       https://mundschenk.at
 * @since      3.0.0
 *
 * @package    Media_Credit
 * @subpackage Media_Credit/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Media_Credit
 * @subpackage Media_Credit/admin
 * @author     Peter Putzer <github@mundschenk.at>
 */
class Media_Credit_Admin implements Media_Credit_Base {

	/**
	 * The ID of this plugin.
	 *
	 * @since    3.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    3.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * The base URL for loading ressources.
	 *
	 * @since    3.0.0
	 * @access   private
	 * @var      string    $resource_url    The base URL for admin ressources.
	 */
	private $resource_url;

	/**
	 * The file suffix for loading ressources.
	 *
	 * @since    3.2.0
	 * @access   private
	 * @var      string    $resource_suffix    Empty string or '.min'.
	 */
	private $resource_suffix;

	/**
	 * The allowed HTML tags passed to wp_kses.
	 *
	 * @since    3.1.0
	 * @access   private
	 * @var      array   $kses_tags The allowed HTML tags.
	 */
	private $kses_tags = array(
		'strong' => array(),
		'br'     => array(),
		'code'   => array(),
	);

	/**
	 * Some strings for displaying the preview.
	 *
	 * @since  3.1.5
	 * @access private
	 * @var    array   $preview_data {
	 *         Strings used for generating the preview.
	 *
	 *         @type string $pattern The pattern string for credits with two names.
	 *         @type string $name1   A male example name.
	 *         @type string $name2   A female example name.
	 *         @type string $joiner  The string used to join multiple image credits.
	 * }
	 */
	private $preview_data = array();

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    3.0.0
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name  = $plugin_name;
		$this->version      = $version;
		$this->resource_url = plugin_dir_url( __FILE__ );

		// Set up resource file suffix.
		$this->resource_suffix = SCRIPT_DEBUG ? '' : '.min';
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    3.0.0
	 */
	public function enqueue_styles() {
		// Style the preview area for the settings page.
		if ( $this->is_media_settings_page() ) {
			wp_enqueue_style( 'media-credit-preview-style', $this->resource_url . "css/media-credit-preview{$this->resource_suffix}.css", array(), $this->version, 'screen' );
		}

		// Style placeholders when editing media.
		if ( $this->is_legacy_media_edit_page() || did_action( 'wp_enqueue_media' ) ) {
			wp_enqueue_style( 'media-credit-attachment-details-style', $this->resource_url . "css/media-credit-attachment-details{$this->resource_suffix}.css", array(), $this->version, 'screen' );
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    3.0.0
	 */
	public function enqueue_scripts() {

		// Preview script for the settings page.
		if ( $this->is_media_settings_page() ) {
			wp_enqueue_script( 'media-credit-preview', $this->resource_url . "js/media-credit-preview{$this->resource_suffix}.js", array( 'jquery' ), $this->version, true );
			wp_localize_script( 'media-credit-preview', 'mediaCreditPreviewData', $this->preview_data );
		}

		// Autocomplete when editing media via the legacy form...
		if ( $this->is_legacy_media_edit_page() ) {
			wp_enqueue_script( 'media-credit-legacy-autocomplete', $this->resource_url . "js/media-credit-legacy-autocomplete{$this->resource_suffix}.js", array( 'jquery', 'jquery-ui-autocomplete' ), $this->version, true );
		}

		// ... and for when the new JavaScript Media API is used.
		if ( did_action( 'wp_enqueue_media' ) ) {
			wp_enqueue_script( 'media-credit-attachment-details', $this->resource_url . "js/media-credit-attachment-details{$this->resource_suffix}.js", array( 'jquery', 'jquery-ui-autocomplete' ), $this->version, true );
		}
	}

	/**
	 * Template for setting Media Credit in image properties.
	 */
	public function image_properties_template() {
		include dirname( __FILE__ ) . '/partials/media-credit-image-properties-tmpl.php';
	}

	/**
	 * Template for setting Media Credit in attachment details.
	 *
	 * @since 3.1.0
	 */
	public function attachment_details_template() {
		include dirname( __FILE__ ) . '/partials/media-credit-attachment-details-tmpl.php';
	}


	/**
	 * Removes the default wpeditimage plugin.
	 *
	 * @param array $plugins An array of plugins to load.
	 * @return array The array of plugins to load.
	 */
	public function tinymce_internal_plugins( $plugins ) {
		$key = array_search( 'wpeditimage', $plugins, true );

		if ( false !== $key ) {
			unset( $plugins[ $key ] );
		}

		return $plugins;
	}

	/**
	 * Add our own version of the wpeditimage plugin.
	 * The plugins depend on the global variable echoed in admin_head().
	 *
	 * @param array $plugins An array of plugins to load.
	 *
	 * @return array The array of plugins to load.
	 */
	public function tinymce_external_plugins( $plugins ) {
		$plugins['mediacredit'] = $this->resource_url . "js/tinymce4/media-credit-tinymce{$this->resource_suffix}.js";
		$plugins['noneditable'] = $this->resource_url . "js/tinymce4/tinymce-noneditable{$this->resource_suffix}.js";

		return $plugins;
	}

	/**
	 * Add our global variable for the TinyMCE plugin.
	 */
	public function admin_head() {
		$options = get_option( self::OPTION );

		$authors = array();
		foreach ( get_users( array(
			'who' => 'authors',
		) ) as $author ) {
			$authors[ $author->ID ] = $author->display_name;
		}

		$media_credit = array(
			'separator'       => $options['separator'],
			'organization'    => $options['organization'],
			'noDefaultCredit' => $options['no_default_credit'],
			'id'              => $authors,
		);

		?>
		<script type='text/javascript'>
			var $mediaCredit = <?php echo /* @scrutinizer ignore-type */ wp_json_encode( $media_credit ); ?>;
		</script>
		<?php
	}

	/**
	 * Add styling for media credits in the rich editor.
	 *
	 * @param string $css A comma separated list of CSS files.
	 *
	 * @return string A comma separated list of CSS files.
	 */
	public function tinymce_css( $css ) {
		return $css . ( ! empty( $css ) ? ',' : '' ) . $this->resource_url . "css/media-credit-tinymce{$this->resource_suffix}.css";
	}

	/**
	 * Initialize settings.
	 */
	public function admin_init() {
		// Initialize preview strings with translations.
		$this->preview_data = array(
			/* translators: 1: last credit 2: concatenated other credits (empty in singular) */
			'pattern' => _n( 'Image courtesy of %2$s%1$s', 'Images courtesy of %2$s and %1$s', 3, 'media-credit' ),
			'name1'   => _x( 'John Smith', 'Male example name for preview', 'media-credit' ),
			'name2'   => _x( 'Jane Doe', 'Female example name for preview', 'media-credit' ),
			'joiner'  => _x( ', ', 'String used to join multiple image credits for "Display credit after post"', 'media-credit' ),
		);

		register_setting( 'media', self::OPTION, array( $this, 'sanitize_option_values' ) );

		// Don't bother doing this stuff if the current user lacks permissions as they'll never see the pages.
		if ( ( current_user_can( 'edit_posts' ) || current_user_can( 'edit_pages' ) ) ) {
			add_action( 'admin_head', array( $this, 'admin_head' ) );

			if ( user_can_richedit() ) {
				add_filter( 'mce_external_plugins', array( $this, 'tinymce_external_plugins' ) );
				add_filter( 'tiny_mce_plugins',     array( $this, 'tinymce_internal_plugins' ) );
				add_filter( 'mce_css',              array( $this, 'tinymce_css' ) );
			}
		}

		// Filter the_author using this method so that freeform media credit is correctly displayed in Media Library.
		add_filter( 'the_author', 'Media_Credit_Template_Tags::get_media_credit' );
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
	 * Is the current page the media settings page?
	 *
	 * @access private
	 */
	private function is_media_settings_page() {
		global $pagenow;

		return ( 'options-media.php' === $pagenow );
	}

	/**
	 * AJAX hook for filtering post content after editing media files
	 */
	public function ajax_filter_content() {
		if ( ! isset( $_REQUEST['id'] ) || ! $attachment_id = absint( $_REQUEST['id'] ) ) { // Input var okay. // @codingStandardsIgnoreLine
			wp_send_json_error();
		}

		check_ajax_referer( "update-attachment-{$attachment_id}-media-credit-in-editor", 'nonce' );

		if ( ! isset( $_REQUEST['mediaCredit'] ) ) { // Input var okay. // @codingStandardsIgnoreLine
			wp_send_json_error();
		}

		if ( ! isset( $_REQUEST['mediaCredit']['content'] ) || ! ( $content   = filter_var( wp_unslash( $_REQUEST['mediaCredit']['content'] ) ) )       || // Input var okay. Only uses for comparison. // @codingStandardsIgnoreLine
			 ! isset( $_REQUEST['mediaCredit']['id'] )      || ! ( $author_id = absint( $_REQUEST['mediaCredit']['id'] ) )                              || // Input var okay. // @codingStandardsIgnoreLine
			 ! isset( $_REQUEST['mediaCredit']['text'] )    || ! ( $freeform  = sanitize_text_field( wp_unslash( $_REQUEST['mediaCredit']['text'] ) ) ) || // Input var okay. // @codingStandardsIgnoreLine
			 ! isset( $_REQUEST['mediaCredit']['link'] )    || ! ( $url       = esc_url_raw( wp_unslash( $_REQUEST['mediaCredit']['link'] ) ) ) ) {        // Input var okay. // @codingStandardsIgnoreLine
			wp_send_json_error();
		}

		wp_send_json_success( $this->filter_post_content( $content, $attachment_id, $author_id, $freeform, $url ) );
	}

	/**
	 * Display settings for plugin on the built-in Media options page.
	 */
	public function display_settings() {
		$options = get_option( self::OPTION );

		add_settings_section( $this->plugin_name, __( 'Media Credit', 'media-credit' ), array( $this, 'print_settings_section' ), 'media' );

		$this->add_settings_field( array(
			'id'          => 'media-credit-preview',
			'label'       => __( 'Preview', 'media-credit' ),
			'input_type'  => 'preview',
			'with_label'  => false,
			'css_class'   => '',
			'description' => __( 'This is what media credits will look like with your current settings.', 'media-credit' ),
			'options'     => $options,
		) );

		$this->add_settings_field( array(
			'id'          => 'separator',
			'label'       => __( 'Separator', 'media-credit' ),
			'value'       => $options['separator'],
			'with_label'  => true,
			'css_class'   => 'small-text',
			'description' => __( 'Text used to separate author names from organization when crediting media to users of this blog.', 'media-credit' ),
		) );

		$this->add_settings_field( array(
			'id'          => 'organization',
			'label'       => __( 'Organization', 'media-credit' ),
			'value'       => $options['organization'],
			'with_label'  => true,
			'css_class'   => 'regular-text',
			'description' => __( 'Organization used when crediting media to users of this blog.', 'media-credit' ),
		) );

		$this->add_settings_field( array(
			'id'          => 'credit_at_end',
			'label'       => __( 'Credit position', 'media-credit' ),
			'input_type'  => 'multi',
			'fields'      => array(
				array(
					'id'          => 'credit_at_end',
					'check_label' => __( 'Display credit after posts.', 'media-credit' ),
					'input_type'  => 'checkbox',
					'value'       => ! empty( $options['credit_at_end'] ),
					'css_class'   => '',
					'description' => __( "Display media credit for all the images attached to a post after the post content. Style with CSS class 'media-credit-end'.", 'media-credit' ) .
									'<br><strong>' . __( 'Warning', 'media-credit' ) . '</strong>: ' . __( 'This will cause credit for all images in all posts to display at the bottom of every post on this blog', 'media-credit' ),
				),
				array(
					'id'          => 'post_thumbnail_credit',
					'check_label' => __( 'Display credit for featured images.', 'media-credit' ),
					'input_type'  => 'checkbox',
					'value'       => ! empty( $options['post_thumbnail_credit'] ),
					'css_class'   => '',
					'description' => __( 'Try to add media credit to featured images (depends on theme support).', 'media-credit' ),
				),
			),
		) );

		$this->add_settings_field( array(
			'id'          => 'no_default_credit',
			'label'       => __( 'Default credit', 'media-credit' ),
			'check_label' => __( 'Do not display default credit.', 'media-credit' ),
			'input_type'  => 'checkbox',
			'value'       => ! empty( $options['no_default_credit'] ),
			'with_label'  => false,
			'css_class'   => '',
			'description' => __( 'Do not display the attachment author as default credit if it has not been set explicitly (= freeform credits only).', 'media-credit' ),
		) );

		$this->add_settings_field( array(
			'id'          => 'schema_org_markup',
			'label'       => __( 'Structured data', 'media-credit' ),
			'check_label' => __( 'Include schema.org structured data in HTML5 microdata markup.', 'media-credit' ),
			'input_type'  => 'checkbox',
			'value'       => ! empty( $options['schema_org_markup'] ),
			'with_label'  => false,
			'css_class'   => '',
			'description' => __( 'Microdata is added to the credit itself and the surrounding <code>figure</code> and <code>img</code> (if they don\'t already have other microdata set). The setting has no effect if credits are displayed after posts.', 'media-credit' ),
		) );
	}

	// @codingStandardsIgnoreStart
	/**
	 * Add a settings field.
	 *
	 * @since 3.1.0
	 *
	 * @param array $args {
	 *		Arguments array.
	 *
	 *		@type string $id          Field ID.
	 *		@type string $label       Field label (translated).
	 *		@type string $check_label Checkbox label. Optional. Default null.
	 *		@type string $input_type  The input type. Optional. Default 'text'.
	 *		@type string $value       The default value. Optiona. Default ''.
	 *		@type string $description Description for the field. Optional. Default null.
	 *		@type string $css_class   CSS class for input field. Optional. Default 'regular-text'.
	 * }
	 */
	private function add_settings_field( array $args ) {
		// @codingStandardsIgnoreEnd
		$args = wp_parse_args( $args, array(
			'id'          => 'invalid',
			'label'       => 'invalid',
			'check_label' => null,
			'input_type'  => 'text',
			'value'       => '',
			'css_class'   => 'regular-text',
			'description' => null,
		) );

		// Set up standard callback.
		$callback      = array( $this, 'print_input_field' );
		$callback_args = array(
			'label_for'   => ! empty( $args['with_label'] ) ? "media-credit[{$args['id']}]" : '',
			'id'          => $args['id'],
			'check_label' => $args['check_label'],
			'type'        => $args['input_type'],
			'value'       => $args['value'],
			'class'       => $args['css_class'],
			'description' => $args['description'],
		);

		switch ( $args['input_type'] ) {
			case 'checkbox':
				$callback = array( $this, 'print_checkbox_field' );
				break;

			case 'multi':
				if ( isset( $args['fields'] ) && is_array( $args['fields'] ) ) {
					$callback      = array( $this, 'print_multiple_fields' );
					$callback_args = $args['fields'];
				} else {
					return; // invalid parameters, abort.
				}
				break;

			case 'preview':
				$callback      = array( $this, 'print_preview_field' );
				$callback_args = array(
					'id'          => $args['id'],
					'class'       => 'media-credit-preview-row',
					'options'     => $args['options'],
					'description' => $args['description'],
				);
				break;
		}

		add_settings_field( $args['id'], $args['label'], $callback, 'media', $this->plugin_name, $callback_args );
	}

	/**
	 * Print HTML for multiple input fields.
	 *
	 * @since 3.1.0
	 *
	 * @param array $args An array of arrays suitable for `print_input_field` and `print_checkbox_field`.
	 */
	public function print_multiple_fields( array $args ) {
		?>
		<fieldset>
			<?php
			foreach ( $args as $field ) {
				if ( isset( $field['input_type'] ) ) {
					switch ( $field['input_type'] ) {
						case 'checkbox':
							$this->print_checkbox_field( $field );
							break;

						default:
							$this->print_input_field( $field );
					}
					?>
					<br>
					<?php
				}
			}
			?>
		</fieldset>
		<?php
	}

	/**
	 * Print HTML for input field.
	 *
	 * @since 3.1.0
	 *
	 * @param array $args Arguments array.
	 */
	public function print_input_field( array $args ) {
		$args = wp_parse_args( $args, array(
			'value' => '',
			'type'  => 'text',
			'class' => 'regular-text',
			'id'    => 'invalid',
		) );

		$field_name = "media-credit[{$args['id']}]";
		?>
			<input
				type="<?php echo esc_attr( $args['type'] ); ?>"
				id="<?php echo esc_attr( $field_name ); ?>"
				name="<?php echo esc_attr( $field_name ); ?>"
			<?php if ( ! empty( $args['description'] ) ) : ?>
				aria-describedby="<?php echo esc_attr( $field_name ); ?>-description"
			<?php endif; ?>
			<?php if ( ! empty( $args['class'] ) ) : ?>
				class="<?php echo esc_attr( $args['class'] ); ?>"
				value="<?php echo esc_attr( $args['value'] ); ?>"
			<?php endif; ?>
				autocomplete="off" />
			<?php if ( ! empty( $args['description'] ) ) : ?>
				<p id="<?php echo esc_attr( $field_name ); ?>-description" class="description"><?php echo wp_kses( $args['description'], $this->kses_tags ); ?></p>
			<?php endif; ?>
		<?php
	}

	/**
	 * Print HTML for checkbox field.
	 *
	 * @since 3.1.0
	 *
	 * @param array $args Arguments array.
	 */
	public function print_checkbox_field( array $args ) {
		$args = wp_parse_args( $args, array(
			'value' => '',
			'id'    => 'invalid',
		) );

		$field_name = "media-credit[{$args['id']}]";

		?>
		<?php if ( ! empty( $args['check_label'] ) ) : ?>
		<label for="<?php echo esc_attr( $field_name ); ?>">
		<?php endif; ?>
			<input
				type="checkbox"
				id="<?php echo esc_attr( $field_name ); ?>"
				name="<?php echo esc_attr( $field_name ); ?>"
			<?php if ( ! empty( $args['description'] ) ) : ?>
				aria-describedby="<?php echo esc_attr( $field_name ); ?>-description"
			<?php endif; ?>
			<?php if ( ! empty( $args['class'] ) ) : ?>
				class="<?php echo esc_attr( $args['class'] ); ?>"
				value="<?php echo esc_attr( $args['value'] ); ?>"
			<?php endif; ?>
				<?php checked( 1, $args['value'], true ); ?>
				autocomplete="off" />
		<?php if ( ! empty( $args['check_label'] ) ) : ?>
			<?php echo esc_html( $args['check_label'] ); ?>
		</label>
		<?php endif; ?>

		<?php if ( ! empty( $args['description'] ) ) : ?>
			<p id="<?php echo esc_attr( $field_name ); ?>-description" class="description"><?php echo wp_kses( $args['description'], $this->kses_tags ); ?></p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Print HTML for preview area.
	 *
	 * @since 3.1.0
	 *
	 * @param array $args The argument array.
	 */
	public function print_preview_field( $args ) {
		$args = wp_parse_args( $args, array(
			'id'    => 'preview',
		) );

		$field_name   = $args['id'];
		$current_user = wp_get_current_user();
		$user_credit  = '<a href="' . esc_url_raw( get_author_posts_url( $current_user->ID ) ) . '">' . esc_html( $current_user->display_name ) . '</a>' . esc_html( $args['options']['separator'] . $args['options']['organization'] );

		if ( ! empty( $args['options']['credit_at_end'] ) ) {
			$credit_html = sprintf( $this->preview_data['pattern'],
									$this->preview_data['name1'],
									$user_credit . $this->preview_data['joiner'] . $this->preview_data['name2']
			);
		} else {
			$credit_html = $user_credit;
		}

		?>
		<p
			id="<?php echo esc_attr( $args['id'] ); ?>"
			class="notice notice-info"
		<?php if ( ! empty( $args['description'] ) ) : ?>
			aria-describedby="<?php echo esc_attr( $field_name ); ?>-description"
		<?php endif; ?>>
			<?php echo wp_kses( $credit_html, array( 'a' => array( 'href' ) ) ); ?>
		</p>
		<?php if ( ! empty( $args['description'] ) ) : ?>
			<p id="<?php echo esc_attr( $field_name ); ?>-description" class="description"><?php echo wp_kses( $args['description'], $this->kses_tags ); ?></p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Enqueue scripts & styles for displaying media credits in the rich-text editor.
	 *
	 * @param array $options An array of options. Used ot check if TinyMCE is enabled.
	 */
	public function enqueue_editor( $options ) {
		if ( $options['tinymce'] ) {
			// Note: An additional dependency "media-views" is not listed below
			// because in some cases such as /wp-admin/press-this.php the media
			// library isn't enqueued and shouldn't be. The script includes
			// safeguards to avoid errors in this situation.
			wp_enqueue_script( 'media-credit-image-properties', $this->resource_url . "js/tinymce4/media-credit-image-properties{$this->resource_suffix}.js", array( 'jquery', 'media-credit-attachment-details' ), $this->version, true );
			wp_enqueue_script( 'media-credit-tinymce-switch',   $this->resource_url . "js/tinymce4/media-credit-tinymce-switch{$this->resource_suffix}.js",   array( 'jquery' ), $this->version, true );

			// Edit in style.
			wp_enqueue_style( 'media-credit-image-properties-style', $this->resource_url . "css/tinymce4/media-credit-image-properties{$this->resource_suffix}.css", array(), $this->version, 'screen' );
		}
	}

	/**
	 * Handle saving requests from the Attachment Details dialogs.
	 *
	 * @since 3.1.0
	 */
	public function ajax_save_attachment_media_credit() {
		if ( ! isset( $_REQUEST['id'] ) || ! $attachment_id = absint( $_REQUEST['id'] ) ) { // Input var okay. // @codingStandardsIgnoreLine
			wp_send_json_error(); // Standard response for failure.
		}

		check_ajax_referer( "save-attachment-{$attachment_id}-media-credit", 'nonce' );

		if ( ! isset( $_REQUEST['changes'] ) || ! $changes = wp_unslash( $_REQUEST['changes'] ) ) { // Input var okay. WPCS: sanitization ok. // @codingStandardsIgnoreLine
			wp_send_json_error(); // Standard response for failure.
		}

		if ( ! isset( $_REQUEST['mediaCredit'] ) || ! $media_credit = wp_unslash( $_REQUEST['mediaCredit'] ) ) { // Input var okay. WPCS: sanitization ok. // @codingStandardsIgnoreLine
			wp_send_json_error(); // Standard response for failure.
		}

		if ( isset( $changes['mediaCreditText'] ) ) {
			$freeform = wp_kses( $changes['mediaCreditText'], array(
				'a' => array( 'href', 'rel' ),
			) );
		} elseif ( isset( $media_credit ) ) {
			$freeform = wp_kses( $media_credit['text'], array(
				'a' => array( 'href', 'rel' ),
			) );
		} else {
			wp_send_json_error( 'freeform credit not found' );
		}

		if ( isset( $changes['mediaCreditLink'] ) ) {
			$url = sanitize_text_field( $changes['mediaCreditLink'] );
		} elseif ( isset( $media_credit ) ) {
			$url = sanitize_text_field( $media_credit['link'] );
		} else {
			wp_send_json_error( 'link not found' );
		}

		if ( isset( $changes['mediaCreditAuthorID'] ) ) {
			$wp_user_id = intval( $changes['mediaCreditAuthorID'] );
		} elseif ( isset( $media_credit ) ) {
			$wp_user_id = intval( $media_credit['id'] );
		} else {
			wp_send_json_error( 'author_id not found' );
		}

		if ( isset( $changes['mediaCreditNoFollow'] ) ) {
			$nofollow = filter_var( $changes['mediaCreditNoFollow'], FILTER_VALIDATE_BOOLEAN );
		} elseif ( isset( $media_credit ) ) {
			$nofollow = filter_var( $media_credit['nofollow'], FILTER_VALIDATE_BOOLEAN );
		} else {
			wp_send_json_error( 'nofollow not found' );
		}

		if ( isset( $changes['mediaCreditLink'] ) ) {
			// We need to update the credit URL.
			update_post_meta( $attachment_id, self::URL_POSTMETA_KEY, $url ); // insert '_media_credit_url' metadata field.
		}

		if ( isset( $changes['mediaCreditNoFollow'] ) ) {
			$data = wp_parse_args( array(
				'nofollow' => $nofollow,
			), Media_Credit_Template_Tags::get_media_credit_data( $attachment_id ) );
			update_post_meta( $attachment_id, self::DATA_POSTMETA_KEY, $data ); // insert '_media_credit_data' metadata field.
		}

		if ( isset( $changes['mediaCreditText'] ) || isset( $changes['mediaCreditAuthorID'] ) ) {
			if ( ! empty( $wp_user_id ) && get_the_author_meta( 'display_name', $wp_user_id ) === $freeform ) {
				// A valid WP user was selected, and the display name matches the free-form
				// the final conditional is necessary for the case when a valid user is selected, filling in the hidden
				// field, then free-form text is entered after that. if so, the free-form text is what should be used.
				if ( ! wp_update_post( array(
					'ID'          => $attachment_id,
					'post_author' => $wp_user_id,
				) ) ) { // update post_author with the chosen user.
					wp_send_json_error( 'Failed to update post author' );
				}

				delete_post_meta( $attachment_id, self::POSTMETA_KEY ); // delete any residual metadata from a free-form field (as inserted below).
				$this->update_media_credit_in_post( $attachment_id, '', $url );
			} elseif ( isset( $freeform ) ) {
				// Free-form text was entered, insert postmeta with credit.
				// if free-form text is blank, insert a single space in postmeta.
				$freeform = empty( $freeform ) ? self::EMPTY_META_STRING : $freeform;
				update_post_meta( $attachment_id, self::POSTMETA_KEY, $freeform ); // insert '_media_credit' metadata field for image with free-form text.
				$this->update_media_credit_in_post( $attachment_id, $freeform, $url );
			}
		}

		wp_send_json_success();
	}

	/**
	 * Add media credit information to wp.media.model.Attachment.
	 *
	 * @since 3.1.0
	 *
	 * @param array      $response   Array of prepared attachment data.
	 * @param int|object $attachment Attachment ID or object.
	 * @param array      $meta       Array of attachment meta data.
	 *
	 * @return array Array of prepared attachment data.
	 */
	public function prepare_attachment_media_credit_for_js( $response, $attachment, $meta ) {

		$credit    = Media_Credit_Template_Tags::get_media_credit( $attachment );
		$url       = Media_Credit_Template_Tags::get_media_credit_url( $attachment );
		$data      = Media_Credit_Template_Tags::get_media_credit_data( $attachment );
		$author_id = '' === Media_Credit_Template_Tags::get_freeform_media_credit( $attachment ) ? $attachment->post_author : '';
		$options   = get_option( self::OPTION );

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
	 * @param array       $fields The custom fields.
	 * @param int|WP_Post $post   Post object or ID.
	 * @return array              The list of fields.
	 */
	public function add_media_credit_fields( $fields, $post ) {
		$options   = get_option( self::OPTION );
		$credit    = Media_Credit_Template_Tags::get_media_credit( $post );
		$value     = 'value';
		$author_id = '' === Media_Credit_Template_Tags::get_freeform_media_credit( $post ) ? $post->post_author : '';

		// Use placeholders instead of value if no freeform credit is set with `no_default_credit` enabled.
		if ( ! empty( $options['no_default_credit'] ) && ! empty( $author_id ) ) {
			$value = 'placeholder';
		}

		// Set up credit input field.
		$fields['media-credit'] = array(
			'label'         => __( 'Credit', 'media-credit' ),
			'input'         => 'html',
			'html'          => "<input id='attachments[$post->ID][media-credit]' class='media-credit-input' size='30' $value='$credit' name='attachments[$post->ID][media-credit]' />",
			'show_in_edit'  => true,
			'show_in_modal' => false,
		);

		// Set up credit URL field.
		$url = Media_Credit_Template_Tags::get_media_credit_url( $post );

		$fields['media-credit-url'] = array(
			'label'         => __( 'Credit URL', 'media-credit' ),
			'input'         => 'html',
			'html'          => "<input id='attachments[$post->ID][media-credit-url]' class='media-credit-input' type='url' size='30' value='$url' name='attachments[$post->ID][media-credit-url]' />",
			'show_in_edit'  => true,
			'show_in_modal' => false,
		);

		// Set up nofollow checkbox.
		$data = Media_Credit_Template_Tags::get_media_credit_data( $post );
		$html = "<label><input id='attachments[$post->ID][media-credit-nofollow]' class='media-credit-input' type='checkbox' value='1' name='attachments[$post->ID][media-credit-nofollow]' " . checked( ! empty( $data['nofollow'] ), true, false ) . '/>' . __( 'Add <code>rel="nofollow"</code>.', 'media-credit' ) . '</label>';

		$fields['media-credit-data'] = array(
			'label'         => '', // necessary for HTML type fields.
			'input'         => 'html',
			'html'          => $html,
			'show_in_edit'  => true,
			'show_in_modal' => false,
		);

		// Set up hidden field as a container for additional data.
		$author_display = Media_Credit_Template_Tags::get_media_credit( $post );
		$nonce          = wp_create_nonce( 'media_credit_author_names' );

		$fields['media-credit-hidden'] = array(
			'label'         => '', // necessary for HTML type fields.
			'input'         => 'html',
			'html'          => "<input name='attachments[$post->ID][media-credit-hidden]' id='attachments[$post->ID][media-credit-hidden]' type='hidden' value='$author_id' class='media-credit-hidden' data-author-id='{$post->post_author}' data-post-id='$post->ID' data-author-display='$author_display' data-nonce='$nonce' />",
			'show_in_edit'  => true,
			'show_in_modal' => false,
		);

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
		$options       = get_option( self::OPTION );

		// We need to update the credit URL in any case.
		update_post_meta( $post['ID'], self::URL_POSTMETA_KEY, $url ); // insert '_media_credit_url' metadata field.

		// Update optional data array with nofollow.
		update_post_meta( $post['ID'], self::DATA_POSTMETA_KEY, wp_parse_args( array(
			'nofollow' => $nofollow,
		), Media_Credit_Template_Tags::get_media_credit_data( $post ) ) );

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
			$this->update_media_credit_in_post( $post, '', $url );
		} else {
			/**
			 * Free-form text was entered, insert postmeta with credit. If free-form text is blank, insert
			 * a single space in postmeta.
			 */
			$freeform = empty( $freeform_name ) ? self::EMPTY_META_STRING : $freeform_name;

			// Insert '_media_credit' metadata field for image with free-form text.
			update_post_meta( $post['ID'], self::POSTMETA_KEY, $freeform );

			// Update media credit shortcodes in the current post.
			$this->update_media_credit_in_post( $post, $freeform, $url );
		}

		return $post;
	}

	/**
	 * If the given media is attached to a post, edit the media-credit info in the attached (parent) post.
	 *
	 * @since 3.2.0 Unused parameter $wp_user removed.
	 *
	 * @param int|WP_Post $post     Object of attachment containing all fields from get_post().
	 * @param string      $freeform Credit for attachment with freeform string. Empty if attachment should be credited to a user of this blog, as indicated by $wp_user above.
	 * @param string      $url      Credit URL for linking. Empty means default link for user of this blog, no link for freeform credit.
	 */
	private function update_media_credit_in_post( $post, $freeform = '', $url = '' ) {
		if ( is_int( $post ) ) {
			$post = get_post( $post, ARRAY_A );
		}

		if ( ! empty( $post['post_parent'] ) ) {
			$parent                 = get_post( $post['post_parent'], ARRAY_A );
			$parent['post_content'] = $this->filter_post_content( $parent['post_content'], $post['ID'], $post['post_author'], $freeform, $url );

			wp_update_post( $parent );
		}
	}

	/**
	 * Add media credit information to media using shortcode notation before sending to editor.
	 *
	 * @param string $html          The image HTML markup to send.
	 * @param int    $attachment_id The attachment id.
	 * @param string $caption       The image caption.
	 * @param string $title         The image title.
	 * @param string $align         The image alignment.
	 *
	 * @return string
	 */
	public function image_send_to_editor( $html, $attachment_id, $caption, $title, $align ) {
		$attachment  = get_post( $attachment_id );
		$credit_meta = Media_Credit_Template_Tags::get_freeform_media_credit( $attachment );
		$credit_url  = Media_Credit_Template_Tags::get_media_credit_url( $attachment );
		$credit_data = Media_Credit_Template_Tags::get_media_credit_data( $attachment );
		$options     = get_option( self::OPTION );

		// Set freeform or blog user credit.
		if ( self::EMPTY_META_STRING === $credit_meta ) {
			return $html;
		} elseif ( ! empty( $credit_meta ) ) {
			$credit = 'name="' . $credit_meta . '"';
		} elseif ( empty( $options['no_default_credit'] ) ) {
			$credit = 'id=' . $attachment->post_author;
		} else {
			return $html;
		}

		// Add link URL.
		if ( ! empty( $credit_url ) ) {
			$credit .= ' link="' . $credit_url . '"';

			// Optionally add nofollow parameter.
			if ( ! empty( $credit_data['nofollow'] ) ) {
				$credit .= ' nofollow=' . $credit_data['nofollow'] . '';
			}
		}

		// Extract image width.
		if ( ! preg_match( '/width="([0-9]+)/', $html, $width ) ) {
			return $html;
		}
		$width = $width[1];

		// Extract alignment.
		$html = preg_replace( '/(class=["\'][^\'"]*)align(none|left|right|center)\s?/', '$1', $html );
		if ( empty( $align ) ) {
			$align = 'none';
		}

		// Put it all together.
		$shcode = '[media-credit ' . $credit . ' align="align' . $align . '" width="' . $width . '"]' . $html . '[/media-credit]';

		// @todo Document filter.
		return apply_filters( 'media_add_credit_shortcode', $shcode, $html );
	}

	/**
	 * Filter post content for changed media credits.
	 *
	 * @param string $content   The current post content.
	 * @param int    $image_id  The attachment ID.
	 * @param int    $author_id The author ID.
	 * @param string $freeform  The freeform credit.
	 * @param string $url       The credit URL. Optional. Default ''.
	 *
	 * @return string           The filtered post content.
	 */
	private function filter_post_content( $content, $image_id, $author_id, $freeform, $url = '' ) {
		preg_match_all( '/' . get_shortcode_regex() . '/s', $content, $matches, PREG_SET_ORDER );

		if ( ! empty( $matches ) ) {
			foreach ( $matches as $shortcode ) {
				if ( 'media-credit' === $shortcode[2] ) {
					$img              = $shortcode[5];
					$image_attributes = wp_get_attachment_image_src( $image_id );
					$image_filename   = $this->get_image_filename_from_full_url( $image_attributes[0] );

					// Ensure that $attr is an array.
					$attr = shortcode_parse_atts( $shortcode[3] );
					$attr = '' === $attr ? array() : $attr;

					if ( preg_match( '/src=".*' . $image_filename . '/', $img ) && preg_match( '/wp-image-' . $image_id . '/', $img ) ) {
						if ( $author_id > 0 ) {
							$attr['id'] = $author_id;
							unset( $attr['name'] );
						} else {
							$attr['name'] = $freeform;
							unset( $attr['id'] );
						}

						if ( ! empty( $url ) ) {
							$attr['link'] = $url;
						} else {
							unset( $attr['link'] );
						}

						$new_shortcode = '[media-credit';
						if ( isset( $attr['id'] ) ) {
							$new_shortcode .= ' id=' . $attr['id'];
							unset( $attr['id'] );
						} elseif ( isset( $attr['name'] ) ) {
							$new_shortcode .= ' name="' . $attr['name'] . '"';
							unset( $attr['name'] );
						}
						foreach ( $attr as $name => $value ) {
							$new_shortcode .= ' ' . $name . '="' . $value . '"';
						}
						$new_shortcode .= ']' . $img . '[/media-credit]';

						$content = str_replace( $shortcode[0], $new_shortcode, $content );
					}
				} elseif ( ! empty( $shortcode[5] ) && has_shortcode( $shortcode[5], 'media-credit' ) ) {
					$content = str_replace( $shortcode[5], $this->filter_post_content( $shortcode[5], $image_id, $author_id, $freeform, $url ), $content );
				}
			}
		}

		return $content;
	}

	/**
	 * Add a Settings link for the plugin.
	 *
	 * @param array $links A list of action links.
	 * @return array The modified list of action links.
	 */
	public function add_action_links( $links ) {
		$settings_link = '<a href="options-media.php#media-credit">' . __( 'Settings', 'media-credit' ) . '</a>';
		array_unshift( $links, $settings_link );

		return $links;
	}

	/**
	 * Print HTML for settings section.
	 *
	 * @param array $args The argument array.
	 */
	public function print_settings_section( $args ) {
		?>
		<a id="media-credit"></a><p>
			<?php esc_html_e( 'Choose how to display media credit on your blog:', 'media-credit' ); ?>
		</p>
		<?php
	}

	/**
	 * Sanitize our option values.
	 *
	 * @since 3.1.0
	 *
	 * @param  array $input An array of ( $key => $value ).
	 * @return array        The sanitized array.
	 */
	public function sanitize_option_values( $input ) {
		// Retrieve currently set options.
		$valid_options = get_option( self::OPTION );
		$valid_options = empty( $valid_options ) ? array() : $valid_options;

		// Blank out checkboxes because unset checkbox don't get sent by the browser.
		$valid_options['credit_at_end']         = false;
		$valid_options['no_default_credit']     = false;
		$valid_options['post_thumbnail_credit'] = false;
		$valid_options['schema_org_markup']     = false;

		// Sanitize the actual input values.
		foreach ( $input as $key => $value ) {
			switch ( $key ) {
				case 'separator':
					// We can't use sanitize_text_field because we want to keep enclosing whitespace.
					$valid_options[ $key ] = wp_kses( $value, array() );
					break;

				default:
					$valid_options[ $key ] = sanitize_text_field( $value );
			}
		}

		// Return updated options array for storage.
		return $valid_options;
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
	private function get_image_filename_from_full_url( $image ) {
		$last_slash_pos = strrpos( $image, '/' );
		$image_filename = substr( $image, $last_slash_pos + 1, strrpos( $image, '.' ) - $last_slash_pos - 1 );
		$image_filename = preg_replace( '/(.*)-\d+x\d+/', '$1', $image_filename ); // drop "-{$width}x{$height}".

		return $image_filename;
	}
}
