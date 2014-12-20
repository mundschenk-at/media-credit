<?php
/*
Plugin Name: Media Credit
Plugin URI: http://www.scottbressler.com/blog/plugins/media-credit/
Description: This plugin adds a "Credit" field to the media uploading and editing tool and inserts this credit when the images appear on your blog.
Version: 2.4.1
Author: Scott Bressler
Author URI: http://www.scottbressler.com/blog/
License: GPL2
*/

define( 'MEDIA_CREDIT_VERSION', '2.4.1' );
define( 'MEDIA_CREDIT_URL', plugins_url(plugin_basename(dirname(__FILE__)).'/') );
define( 'MEDIA_CREDIT_EMPTY_META_STRING', ' ' );
define( 'MEDIA_CREDIT_POSTMETA_KEY', '_media_credit' );
define( 'MEDIA_CREDIT_OPTION', 'media-credit' );
define( 'MEDIA_CREDIT_DEFAULT_SEPARATOR', ' | ' );
define( 'MEDIA_CREDIT_DEFAULT_ORGANIZATION', get_bloginfo() );
define( 'WP_IMAGE_CLASS_NAME_PREFIX', 'wp-image-' );
define( 'WP_ATTACHMENT_CLASS_NAME_PREFIX', 'attachment_' );

require_once( 'display.php' );

function set_default_media_credit_options() {
	$options = array(
		'version' => MEDIA_CREDIT_VERSION,
		'install_date' => date( 'Y-m-d' ),
		'separator' => MEDIA_CREDIT_DEFAULT_SEPARATOR,
		'organization' => MEDIA_CREDIT_DEFAULT_ORGANIZATION,
		'credit_at_end' => false,
		'no_default_credit' => false
	);
	$installed_options = get_option( MEDIA_CREDIT_OPTION );
	if ( empty( $installed_options ) ) { // Install plugin for the first time
		add_option( MEDIA_CREDIT_OPTION, $options );
		$installed_options = $options;
	} else if ( !isset( $installed_options['version'] ) ) { // Upgrade plugin to 1.0 (0.5.5 didn't have a version number)
		$installed_options['version'] = $options['version'];
		$installed_options['install_date'] = $options['install_date'];
		update_option( MEDIA_CREDIT_OPTION, $installed_options );
	}

	if ( version_compare( $installed_options['version'], '1.0.1', '<' ) ) { // Upgrade plugin to 1.0.1
		// Update all media-credit postmeta keys to _media_credit
		global $wpdb;
		$wpdb->update( $wpdb->postmeta, array( 'meta_key' => MEDIA_CREDIT_POSTMETA_KEY ), array( 'meta_key' => 'media-credit' ) );

		$installed_options['version'] = '1.0.1';
		update_option( MEDIA_CREDIT_OPTION, $installed_options );
	}
	
	if ( version_compare( $installed_options['version'], '2.2.0', '<' ) ) { // Upgrade plugin to 2.2.0
		// Update all media-credit postmeta keys to _media_credit
		$installed_options['version'] = '2.2.0';
		$installed_options['no_default_credit'] = $options['no_default_credit'];
		update_option( MEDIA_CREDIT_OPTION, $installed_options );
	}
}
register_activation_hook(__FILE__, 'set_default_media_credit_options' );

/**
 * Delete options in database
 */
function media_credit_uninstall() {
	delete_option(MEDIA_CREDIT_OPTION);
}
if ( function_exists('register_uninstall_hook') )
	register_uninstall_hook(__FILE__, 'media_credit_uninstall');

/**
 * Template tag to return the media credit as plain text for some media attachment.
 *
 * @param int|object $post Optional post ID or object of attachment. Default is global $post object.
 */
function get_media_credit($post = null) {
	$post = get_post($post);
	$credit_meta = get_freeform_media_credit($post);
	$credit_wp_author = get_wpuser_media_credit($post);
	return ( $credit_meta != '' ) ? $credit_meta : $credit_wp_author;
}
// Filter the_author using this method so that freeform media credit is correctly displayed in Media Library.
if ( is_admin() )
	add_filter( 'the_author', 'get_media_credit' );

/**
 * Template tag to print the media credit as plain text for some media attachment.
 *
 * @param int|object $post Optional post ID or object of attachment. Default is global $post object.
 */
function the_media_credit($post = null) {
	echo get_media_credit($post);
}

/**
 * Template tag to return the media credit as HTML with a link to the author page if one exists for some media attachment.
 *
 * @param int|object $post Optional post ID or object of attachment. Default is global $post object.
 */
function get_media_credit_html($post = null) {
	$post = get_post($post);
	$credit_meta = get_freeform_media_credit($post);
	if ( $credit_meta != '' )
		return $credit_meta;
	$credit_wp_author = get_wpuser_media_credit($post);
	$options = get_option(MEDIA_CREDIT_OPTION);
	return '<a href="' . get_author_posts_url($post->post_author) . '">' . $credit_wp_author . '</a>'
	 . $options['separator'] . $options['organization'];
}

/**
 * Template tag to print the media credit as HTML with a link to the author page if one exists for some media attachment.
 *
 * @param int|object $post Optional post ID or object of attachment. Default is global $post object.
 */
function the_media_credit_html($post = null) {
	echo get_media_credit_html($post);
}

/**
 * Template tag to return the media credit as HTML with a link to the author page if one exists for a WordPress user.
 *
 * @param int $id User ID of a WordPress user.
 */
function get_media_credit_html_by_user_ID($id) {
	$credit_wp_author = get_the_author_meta( 'display_name', $id );
	$options = get_option(MEDIA_CREDIT_OPTION);
	return '<a href="' . get_author_posts_url($id) . '">' . $credit_wp_author . '</a>'
	 . $options['separator'] . $options['organization'];
}

/**
 * Template tag to print the media credit as HTML with a link to the author page if one exists for a WordPress user.
 *
 * @param int $id User ID of a WordPress user.
 */
function the_media_credit_html_by_user_ID($id) {
	echo get_media_credit_html_by_user_ID($id);
}

/**
 *
 * @param int|object $post Optional post ID or object of attachment. Default is global $post object.
 */
function get_wpuser_media_credit($post = null) {
	$post = get_post($post);
	return get_the_author_meta( 'display_name', $post->post_author );
}

/**
 *
 * @param int|object $post Optional post ID or object of attachment. Default is global $post object.
 */
function get_freeform_media_credit($post = null) {
	$post = get_post($post);
	return get_post_meta( $post->ID, MEDIA_CREDIT_POSTMETA_KEY, true );
}

// Add an autocomplete field with the blog users to $fields
function add_media_credit($fields, $post) {
	$credit = get_media_credit($post);
	// add requirement for jquery ui core, jquery ui widgets, jquery ui position
	$html = "<input id='attachments[$post->ID][media-credit]' class='media-credit-input' size='30' value='$credit' name='attachments[$post->ID][media-credit]'  />";
	$fields['media-credit'] = array(
		'label' => __('Credit:'),
		'input' => 'html',
		'html' => $html,
		'show_in_edit' => true,
		'show_in_modal' => true,
	);
	
	$author = ( get_freeform_media_credit($post) == '' ) ? $post->post_author : '';
	$author_display = get_media_credit($post);
	$author_for_script = ($author == '') ? -1 : $author;
	$html_hidden = "<input name='attachments[$post->ID][media-credit-hidden]' id='attachments[$post->ID][media-credit-hidden]' type='hidden' value='$author' class='media-credit-hidden' data-author='$author_for_script' data-post-id='$post->ID' data-author-display='$author_display' />";
	$fields["media-credit-hidden"] = array(
		'label' => '', /* necessary for HTML type fields */
		'input' => 'html',
		'html' => $html_hidden,
		'show_in_edit' => true,
		'show_in_modal' => true,
	);
	return $fields;
}
add_filter('attachment_fields_to_edit', 'add_media_credit', 10, 2);


/**
 * Change the post_author to the entered media credit from add_media_credit() above.
 *
 * @param object $post Object of attachment containing all fields from get_post().
 * @param object $attachment Object of attachment containing few fields, unused in this method.
 */
function save_media_credit($post, $attachment) {
	$wp_user_id = $attachment['media-credit-hidden'];
	$freeform_name = $attachment['media-credit'];
		
	if ( isset( $wp_user_id ) && $wp_user_id != '' && $freeform_name === get_the_author_meta( 'display_name', $wp_user_id ) ) {
		// a valid WP user was selected, and the display name matches the free-form
		// the final conditional is necessary for the case when a valid user is selected, filling in the hidden field,
		// then free-form text is entered after that. if so, the free-form text is what should be used
		$post['post_author'] = $wp_user_id; // update post_author with the chosen user
		delete_post_meta($post['ID'], MEDIA_CREDIT_POSTMETA_KEY); // delete any residual metadata from a free-form field (as inserted below)
		update_media_credit_in_post($post, true);
	} else { // free-form text was entered, insert postmeta with credit. if free-form text is blank, insert a single space in postmeta.
		$freeform = empty( $freeform_name ) ? MEDIA_CREDIT_EMPTY_META_STRING : $freeform_name;
		update_post_meta($post['ID'], MEDIA_CREDIT_POSTMETA_KEY, $freeform); // insert '_media_credit' metadata field for image with free-form text
		update_media_credit_in_post($post, false, $freeform);
	}
	return $post;
}
add_filter('attachment_fields_to_save', 'save_media_credit', 10, 2);

/**
 * If the given media is attached to a post, edit the media-credit info in the attached (parent) post.
 *
 * @param object $post Object of attachment containing all fields from get_post().
 * @param bool $wp_user True if attachment should be credited to a user of this blog, false otherwise.
 * @param string $freeform Credit for attachment with freeform string. Empty if attachment should be credited to a user of this blog, as indicated by $wp_user above.
 */
function update_media_credit_in_post($post, $wp_user, $freeform = '') {
	if ( isset( $post['post_parent'] ) && $post['post_parent'] !== 0 ) {
		$parent = get_post( $post['post_parent'], ARRAY_A );
		$parent['post_content'] = media_credit_filter_post_content($parent['post_content'], $post['ID'], $post['post_author'], $freeform);
		wp_update_post($parent);
	}
}

/**
 * Returns the filename of an image in the wp_content directory (normally, could be any dir really) given the full URL to the image, ignoring WP sizes.
 * E.g.:
 * Given http://localhost/wordpress/wp-content/uploads/2010/08/ParksTrip2010_100706_1487-150x150.jpg, returns ParksTrip2010_100706_1487 (ignores size at end of string)
 * Given http://localhost/wordpress/wp-content/uploads/2010/08/ParksTrip2010_100706_1487-thumb.jpg, return ParksTrip2010_100706_1487-thumb
 * Given http://localhost/wordpress/wp-content/uploads/2010/08/ParksTrip2010_100706_1487-1.jpg, return ParksTrip2010_100706_1487-1
 * 
 * @param string $image Full URL to an image.
 * @return string The filename of the image excluding any size or extension, as given in the example above.
 */
function get_image_filename_from_full_url( $image ) {
	$image_len = strlen( $image );
	$image_last_slash_pos = strrpos( $image, '/' );
	$image_filename = substr( $image, $image_last_slash_pos + 1, strrpos( $image, '.') - $image_last_slash_pos - 1 );
	$image_filename = preg_replace( '/(.*)-\d+x\d+/', '$1', $image_filename );
	return $image_filename;
}

add_shortcode('wp_caption', 'media_credit_caption_shortcode');
add_shortcode('caption', 'media_credit_caption_shortcode');

/**
 * Modified Caption shortcode.
 *
 * Fixes the new style caption shortcode parsing and then calls the stock
 * shortcode function.
 *
 * @param array $attr Attributes attributed to the shortcode.
 * @param string $content Optional. Shortcode content.
 * @return string
 */
function media_credit_caption_shortcode($attr, $content = null) {
        // New-style shortcode with the caption inside the shortcode with the link and image tags.
        if ( ! isset( $attr['caption'] ) ) {
                if ( preg_match( '#((?:\[media-credit[^\]]+\]\s*)(?:<a [^>]+>\s*)?<img [^>]+>(?:\s*</a>)?(?:\s*\[/media-credit\])?)(.*)#is', $content, $matches ) ) {
                        $content = $matches[1];
                        $attr['caption'] = trim( $matches[2] );
                }
        }
	
        return img_caption_shortcode($attr, $content);
}

/**
 * Add media credit information to media using shortcode notation before sending to editor.
 */
function send_media_credit_to_editor_by_shortcode($html, $attachment_id, $caption, $title, $align, $url, $size, $alt = '' ) {
	$post = get_post($attachment_id);
	$credit_meta = get_freeform_media_credit($post);
	$options = get_option( MEDIA_CREDIT_OPTION );
	
	if ( $credit_meta == MEDIA_CREDIT_EMPTY_META_STRING )
		return $html;
	else if ( $credit_meta != '' )
		$credit = 'name="' . $credit_meta . '"';
	else if ( $options['no_default_credit'] == false ) {
		$credit = 'id=' . $post->post_author;
	} else {
		return $html;
	}
	
	if ( ! preg_match( '/width="([0-9]+)/', $html, $matches ) )
		return $html;

	$width = $matches[1];

	$html = preg_replace( '/(class=["\'][^\'"]*)align(none|left|right|center)\s?/', '$1', $html );
	if ( empty($align) )
		$align = 'none';
	
	$shcode = '[media-credit ' . $credit . ' align="align' . $align . '" width="' . $width . '"]' . $html . '[/media-credit]';
	
	return apply_filters( 'media_add_credit_shortcode', $shcode, $html );
}
add_filter('image_send_to_editor', 'send_media_credit_to_editor_by_shortcode', 10, 8);

/**
 * Add shortcode for media credit. Allows for credit to be specified for media attached to a post
 * by either specifying the ID of a WordPress user or with a raw string for the name assigned credit.
 * If an ID is present, it will take precedence over a name.
 * 
 * Usage: [media-credit id=1 align="aligncenter" width="300"] or [media-credit name="Another User" align="aligncenter" width="300"]
 */
function media_credit_shortcode($atts, $content = null) {
	// Allow plugins/themes to override the default media credit template.
	$output = apply_filters('media_credit_shortcode', '', $atts, $content);
	if ( $output != '' )
		return $output;

	$options = get_option( MEDIA_CREDIT_OPTION );
	if ( !empty( $options['credit_at_end'] ) )
		return do_shortcode( $content );

	extract(shortcode_atts(array(
		'id' => -1,
		'name' => '',
		'align'	=> 'alignnone',
		'width'	=> '',
	), $atts, 'media-credit'));
	
	if ($id !== -1)
		$author_link = get_media_credit_html_by_user_ID($id);
	else
		$author_link = $name;
		
	return '<div class="media-credit-container ' . esc_attr($align) . '" style="width: ' . (10 + (int) $width) . 'px">'
	. do_shortcode( $content ) . '<span class="media-credit">' . $author_link . '</span></div>';
}
add_shortcode('media-credit', 'media_credit_shortcode');

function add_media_credits_to_end( $content ) {
	// Find the attachment_IDs of all media used in $content
	preg_match_all( '/' . WP_IMAGE_CLASS_NAME_PREFIX . '(\d+)/', $content, $matches );
	$images = $matches[1];
	
	if ( count($images) == 0 )
		return $content;
	
	$credit_unique = array();
	foreach ($images as $image)
		$credit_unique[] = get_media_credit_html($image);
	$credit_unique = array_unique($credit_unique);
	
	$image_credit = (count($images) > 1 ? 'Images' : 'Image') . ' courtesy of ';
	
	$count = 0;	
	foreach ($credit_unique as $credit) {
		if ( $count > 0 ) {
			if ( $count < count($credit_unique) - 1 )
				$image_credit .= ', ';
			else
				$image_credit .= __(' and ');
		}
		$image_credit .= $credit;
		$count++;
	}
	
	return $content . '<div class="media-credit-end">' . $image_credit . '</div>';
}
$options = get_option( MEDIA_CREDIT_OPTION );
if ( !empty( $options['credit_at_end'] ) )
	add_filter( 'the_content', 'add_media_credits_to_end', 10, 1 );

function media_credit_stylesheet() {
	$options = get_option( MEDIA_CREDIT_OPTION );
	if ( !empty( $options['credit_at_end'] ) ) // Do not display inline media credit if media credit is displayed at end of posts.
		wp_enqueue_style( 'media-credit-end', MEDIA_CREDIT_URL . 'css/media-credit-end.css', array(), MEDIA_CREDIT_VERSION, 'all');
	else
		wp_enqueue_style( 'media-credit', MEDIA_CREDIT_URL . 'css/media-credit.css', array(), MEDIA_CREDIT_VERSION, 'all');
}
add_action('wp_print_styles', 'media_credit_stylesheet');


//----- Add AJAX hook for Media Credit autocomplete box ----//

// hit ajaxurl with action=media_credit_author_names and term= your search.
add_action( 'wp_ajax_media_credit_author_names', 'media_credit_author_names_ajax' );
function media_credit_author_names_ajax() {
	if ( ! isset( $_POST['term'] ) ) {
		die('0'); // standard response for failure
	}

	if ( ! current_user_can( 'edit_posts' ) ) {
		die('-1'); // standard response for permissions
	}

	if ( isset( $_POST['term'] ) ) {
		if ($authors = get_editable_authors_by_name( wp_get_current_user()->ID, $_POST['term'], $_POST['limit'] ) ) {
			foreach ( $authors as $author )
				$results[] = (object) array("id"=>$author->ID, "label"=>$author->display_name, "value"=>$author->display_name);
			echo json_encode($results);
		}
		echo '';
	}

	die(0);
}

/*
 * AJAX hook for filtering post content after editing media files
*/
add_action( 'wp_ajax_media_credit_filter_content', 'media_credit_filter_content_ajax' );
function media_credit_filter_content_ajax() {
	if ( ! isset( $_POST['post_content'] ) || !isset( $_POST['image_id'] ) || ! isset($_POST['author_id']) || ! isset($_POST['freeform']) ) {
		wp_send_json_error();
	}

	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error();
	}

	// unescape single & double quotes
	$results = preg_replace(array('/\\\"/', "/\\\'/"), array('"', "'"), $_POST['post_content']); 
		
	if ($_POST['image_id'] > 0) {
		$results = media_credit_filter_post_content($results, $_POST['image_id'], $_POST['author_id'], $_POST['freeform']);
	}
	
	wp_send_json_success($results);
}

function media_credit_filter_post_content($content, $image_id, $author_id, $freeform) {
	preg_match_all( '/' . get_shortcode_regex() . '/s', $content, $matches, PREG_SET_ORDER );
	
	if (! empty( $matches ) ) {
		foreach ( $matches as $shortcode ) {	
			if ( 'media-credit' === $shortcode[2] ) {
				$attr = shortcode_parse_atts( $shortcode[3] );
				$img = $shortcode[5];
					
				$image_filename = wp_get_attachment_image_src($_POST['image_id']);
				$image_filename = get_image_filename_from_full_url($image_filename[0]);
				
				
				if (preg_match('/src=".*' . $image_filename . '/', $img) && preg_match('/wp-image-' . $image_id . '/', $img)) {
	
					if ($author_id > 0) {
						$attr['id'] = $author_id;
						unset($attr['name']);
					} else {
						$attr['name'] = $freeform;
						unset($attr['id']);
					}
		
					$new_shortcode = '[media-credit';
	
					if (isset($attr['id'])) {
						$new_shortcode .= ' id=' . $attr['id'];
						unset ($attr['id']);
					}
					if (isset($attr['name'])) {
						$new_shortcode .= ' name="' . $attr['name'] . '"';
						unset ($attr['name']);
					}
	
					foreach ($attr as $name => $value) {
						$new_shortcode .= ' ' . $name . '="' . $value . '"';
					}
					$new_shortcode .= ']' . $img . '[/media-credit]';
		
					$content = str_replace($shortcode[0], $new_shortcode, $content);
				}
					
			} elseif ( ! empty( $shortcode[5] ) && has_shortcode( $shortcode[5], 'media-credit' ) ) {
				$content = str_replace($shortcode[5], media_credit_filter_post_content($shortcode[5], $image_id, $author_id, $freeform), $content);
			}
		}
	}
		
	return $content;
}

/**
 * Returns the users that are editable by $user_id (normally the current user) and that contain $name within their
 * display name. Important to use this function rather than just selected all users for WPMU bloggers.
 *
 * Basis for this function is proudly stolen from wp-{admin/}includes/user.php :)
 */
function get_editable_authors_by_name( $user_id, $name, $limit ) {
	global $wpdb;
	
	// get_editable_user_ids was deprecated in WordPress 3.1, so let's avoid it unless we're running on a site with
	// WordPress < 3.1.
	if ( !function_exists ( 'get_users' ) ) {
		$editable = get_editable_user_ids( $user_id );
	} else {
		// use a similar call that's used in post_author_meta_box() to get a list of eligible users
		$editable = get_users( array(
			'who' => 'authors',
			'fields' => 'id',
			'include_selected' => true
		) );
	}

	if ( !$editable ) {
		return false;
	} else {
		$editable = join(',', $editable);
		// Prepare autocomplete term for query: add wildcard after, and replace all spaces with wildcards
		// 'Scott Bressler' becomes 'Scott%Bressler%', and literal _ and %'s are escaped.
		$name = str_replace( ' ', '%', $wpdb->esc_like( $name ) ) . '%';
		$authors = $wpdb->get_results( $wpdb->prepare( "
			SELECT ID, display_name
			FROM $wpdb->users
			WHERE ID IN ($editable)
				AND upper(display_name) LIKE %s
			ORDER BY display_name
			LIMIT 0, $limit",
			strtoupper($name) ));
	}

	return apply_filters('get_editable_authors_by_name', $authors, $name);
}


//----- Add menus and settings for plugin -----//

if ( is_admin() )
	add_action( 'admin_menu', 'add_media_credit_menu' );

function add_media_credit_menu() {
	// Display settings for plugin on the built-in Media options page
	add_settings_section(MEDIA_CREDIT_OPTION, 'Media Credit', 'media_credit_settings_section', 'media');
	add_settings_field('preview', '<em>Preview</em>', 'media_credit_preview', 'media', MEDIA_CREDIT_OPTION);
	add_settings_field('separator', 'Separator', 'media_credit_separator', 'media', MEDIA_CREDIT_OPTION);
	add_settings_field('organization', 'Organization', 'media_credit_organization', 'media', MEDIA_CREDIT_OPTION);
	add_settings_field('credit_at_end', 'Display credit after posts', 'media_credit_end_of_post', 'media', MEDIA_CREDIT_OPTION);
	add_settings_field('no_default_credit', 'Do not display default credit', 'media_credit_no_default_credit', 'media', MEDIA_CREDIT_OPTION);
	
	// Call register settings function
	add_action( 'admin_init', 'media_credit_init' );
}

function media_credit_init() { // whitelist options
	register_setting( 'media', MEDIA_CREDIT_OPTION, 'media_credit_options_validate' );
	if ( is_media_settings_page( ) )
		wp_enqueue_script( 'media-credit', MEDIA_CREDIT_URL . 'js/media-credit-preview.js', array('jquery'), MEDIA_CREDIT_VERSION, true);

	if ( is_media_edit_page( ) ) {
		wp_enqueue_script('media-credit-autocomplete', MEDIA_CREDIT_URL . 'js/media-credit-autocomplete.js', array('jquery', 'jquery-ui-autocomplete'), MEDIA_CREDIT_VERSION, true);
		wp_enqueue_script('media-credit-media-handling', MEDIA_CREDIT_URL . 'js/media-credit-media-handling.js', array('jquery'), MEDIA_CREDIT_VERSION, true);
	}

	// Don't bother doing this stuff if the current user lacks permissions as they'll never see the pages
	if ( !current_user_can('edit_posts') && !current_user_can('edit_pages') )
		return;

	if ( 'true' == get_user_option('rich_editing') ) {
		add_filter( 'mce_external_plugins', 'media_credit_mce_external_plugins' );
		add_filter( 'tiny_mce_plugins', 'media_credit_tiny_mce_plugins' );
		add_filter( 'mce_css', 'media_credit_mce_css' );
	}
	
	add_action( 'wp_enqueue_editor', 'media_credit_enqueue_editor', 10, 1 );
	add_action( 'print_media_templates', 'media_credit_image_properties_template' );
}

/*
 * Add MCE views
 */
function media_credit_enqueue_editor( $options ) {
	if ( $options['tinymce'] ) {
		// Note: An additional dependency "media-views" is not listed below
		// because in some cases such as /wp-admin/press-this.php the media
		// library isn't enqueued and shouldn't be. The script includes
		// safeguards to avoid errors in this situation
		
		wp_enqueue_script( 'media-credit-image-properties', plugins_url( 'js/tinymce4/media-credit-image-properties.js', __FILE__ ), array( 'jquery' ), MEDIA_CREDIT_VERSION, true );
		wp_enqueue_script( 'media-credit-tinymce-switch', plugins_url( 'js/tinymce4/media-credit-tinymce-switch.js', __FILE__ ), array( 'jquery' ), MEDIA_CREDIT_VERSION, true );
	}
}

/*
 * Template for setting Media Credit in image properties
 */
function media_credit_image_properties_template() {	
	include dirname( __FILE__ ) . '/templates/media-credit-image-properties-tmpl.php';
}

/*
 * Remove default editimage plugin
 */
function media_credit_tiny_mce_plugins( $plugins ) {
	if ( false !== ( $key = array_search('wpeditimage', $plugins ) ) )
		unset( $plugins[ $key ] );
	return $plugins;
}

// TinyMCE integration hooks
function media_credit_mce_external_plugins( $plugins ) {
	$options = get_option( MEDIA_CREDIT_OPTION );
	$authors = get_users(); //get_media_credit_authors_for_post();
	
	$json_separator = json_encode($options['separator']);
	$json_organization = json_encode($options['organization']);
	
	echo "
	<script type='text/javascript'>
	var \$mediaCredit = {
		'separator': {$json_separator},
		'organization': {$json_organization},
		'id': 
		{
		";
		foreach ($authors as $author) {
			$json_author = json_encode($author->display_name);
			echo "'{$author->ID}': {$json_author},";
		}
		echo "
		}
	};
	</script>
	";
	$plugins['mediacredit'] = MEDIA_CREDIT_URL . 'js/tinymce4/media-credit-tinymce.js';
	$plugins['noneditable'] = MEDIA_CREDIT_URL . 'js/tinymce4/tinymce-noneditable.js';
			
	return $plugins;
}
/*
 * @param int|object $post Optional post ID or object of attachment. Default is global $post object.
 */
function get_media_credit_authors_for_post($post = null) {
	global $post;
	
	// Find the user IDs of all media used in post_content credited to WP users
	preg_match_all( '/\[media-credit id=(\d+)/', $post->post_content, $matches );
	$users = array_unique( $matches[1] );
	
	if ( empty($users) )
		return array();
	
	$users_data = array();
	foreach ($users as $user)
		$users_data[] = get_userdata($user);

	return $users_data;
}

function media_credit_mce_css($css) {
	return $css . "," . MEDIA_CREDIT_URL . 'css/media-credit-tinymce.css';
}


function media_credit_action_links($links, $file) {
	$plugin_file = basename(__FILE__);
	if (basename($file) == $plugin_file) {
		$settings_link = '<a href="options-media.php#media-credit">'.__('Settings', 'media-credit').'</a>';
		array_unshift($links, $settings_link);
	}
	return $links;
}
add_filter('plugin_action_links', 'media_credit_action_links', 10, 2);

function media_credit_settings_section() {
	echo "<a name='media-credit'></a>";
	echo "<p>Choose how to display media credit on your blog:</p>";
}

function media_credit_separator() {
	$options = get_option( MEDIA_CREDIT_OPTION );
	$explanation = "Text used to separate author names from organization when crediting media to users of this blog";
	echo "<input type='text' id='media-credit[separator]' name='media-credit[separator]' value='{$options['separator']}' autocomplete='off' />";
	echo "<label for='media-credit[separator]' style='margin-left:5px'>$explanation</label>";
}

function media_credit_organization() {
	$options = get_option( MEDIA_CREDIT_OPTION );
	$explanation = "Organization used when crediting media to users of this blog";
	echo "<input type='text' name='media-credit[organization]' value='{$options['organization']}' autocomplete='off' />";
	echo "<label for='media-credit[separator]' style='margin-left:5px'>$explanation</label>";
}

function media_credit_preview() {
	$curr_user = wp_get_current_user();
	$options = get_option( MEDIA_CREDIT_OPTION );
	echo "<span id='preview'><a href='" . get_author_posts_url($curr_user->ID) . "'>$curr_user->display_name</a>${options['separator']}${options['organization']}</span>";
}

function media_credit_end_of_post() {
	$options = get_option( MEDIA_CREDIT_OPTION );
	$credit_at_end = array_key_exists( 'credit_at_end', $options ) ? $options['credit_at_end'] : false;
	$explanation = "Display media credit for all the images attached to a post after the post content. Style with CSS class 'media-credit-end'";
	echo "<input type='checkbox' id='media-credit[credit_at_end]' name='media-credit[credit_at_end]' value='1' " . checked(1, $credit_at_end, false) . " />";
	echo "<label for='media-credit[credit_at_end]' style='margin-left:5px'>$explanation</label>";
	
	$curr_user = wp_get_current_user();
	echo "<br /><em>Preview</em>: Images courtesy of <span id='preview'><a href='" . get_author_posts_url($curr_user->ID) . "'>$curr_user->display_name</a>${options['separator']}${options['organization']}</span>, Jane Doe and John Smith";
	echo "<br /><strong>Warning</strong>: This will cause credit for all images in all posts to display at the bottom of every post on this blog";
}

function media_credit_no_default_credit() {
	$options = get_option( MEDIA_CREDIT_OPTION );
	$no_default_credit = array_key_exists( 'no_default_credit', $options ) ? $options['no_default_credit'] : false;
	$explanation = "Do not display the attachment author as default credit if it has not been set explicitly (= freeform credits only).";
	echo "<input type='checkbox' id='media-credit[no_default_credit]' name='media-credit[no_default_credit]' value='1' " . checked(1, $no_default_credit, false) . " />";
	echo "<label for='media-credit[credit_at_end]' style='margin-left:5px'>$explanation</label>";
}

function media_credit_options_validate($input) {
	foreach ($input as $key => $value) {
		$input[$key] = htmlspecialchars( $value, ENT_QUOTES );
	}
	return $input;
}


function is_media_edit_page( ) {
	global $pagenow;
	
	$media_edit_pages = array('post-new.php', 'post.php', 'page.php', 'page-new.php', 'media-upload.php', 'media.php', 'media-new.php', 'ajax-actions.php', 'upload.php');
			
	return in_array($pagenow, $media_edit_pages);
}

function is_media_settings_page( ) {
	global $pagenow;
	
	return $pagenow == 'options-media.php';
}

?>