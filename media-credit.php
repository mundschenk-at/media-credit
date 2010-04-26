<?php
/*
Plugin Name: Media Credit
Plugin URI: http://www.scottbressler.com/wp/
Description: This plugin adds a "Credit" field to the media uploading and editing tool and inserts this credit when the images appear on your blog.
Version: 1.0
Author: Scott Bressler
Author URI: http://www.scottbressler.com/wp/
License: GPL2
*/

define( 'MEDIA_CREDIT_VERSION', '1.0' );
define( 'MEDIA_CREDIT_URL', plugins_url(plugin_basename(dirname(__FILE__)).'/') );
define( 'MEDIA_CREDIT_EMPTY_META_STRING', ' ' );
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
		'credit_at_end' => false
	);
	$installed_options = get_option( MEDIA_CREDIT_OPTION );
	if ( !$installed_options ) // Install plugin
		add_option( MEDIA_CREDIT_OPTION, $options );
	else if ( !array_key_exists( 'version', $installed_options ) ) { // Upgrade plugin to 1.0 (0.5.5 didn't have a version number)
		$installed_options['version'] = $options['version'];
		$installed_options['install_date'] = $options['install_date'];
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
function get_media_credit($post) {
	$post = get_post($post);
	$credit_meta = get_freeform_media_credit($post);
	$credit_wp_author = get_wpuser_media_credit($post);
	return ( $credit_meta != '' ) ? $credit_meta : $credit_wp_author;
}

/**
 * Template tag to print the media credit as plain text for some media attachment.
 *
 * @param int|object $post Optional post ID or object of attachment. Default is global $post object.
 */
function the_media_credit($post) {
	echo get_media_credit($post);
}

/**
 * Template tag to return the media credit as HTML with a link to the author page if one exists for some media attachment.
 *
 * @param int|object $post Optional post ID or object of attachment. Default is global $post object.
 */
function get_media_credit_html($post) {
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
function the_media_credit_html($post) {
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
function get_wpuser_media_credit($post) {
	$post = get_post($post);
	return get_the_author_meta( 'display_name', $post->post_author );
}

/**
 *
 * @param int|object $post Optional post ID or object of attachment. Default is global $post object.
 */
function get_freeform_media_credit($post) {
	$post = get_post($post);
	return get_post_meta( $post->ID, 'media-credit', true );
}

// Add an autocomplete field with the blog users to $fields
function add_media_credit($fields, $post) {
	$credit = get_media_credit($post);
	// add requirement for jquery ui core, jquery ui widgets, jquery ui position
	$html = "<input id='attachments[$post->ID][media-credit]' class='media-credit-input' size='30' value='$credit' name='free-form' />";
	$author = ( get_freeform_media_credit($post) == '' ) ? $post->post_author : '';
	$author_display = get_media_credit($post);
	$html .= "<input name='media-credit-$post->ID' id='media-credit-$post->ID' type='hidden' value='$author' />";
	$html .= "<script type='text/javascript'>jQuery(document).ready(function() {mediaCreditAutocomplete($post->ID, " . (($author == '') ? -1 : $author) . ", '$author_display');});</script>";
	$fields['media-credit'] = array(
		'label' => __('Credit:'),
		'input' => 'html',
		'html' => $html
	);
	return $fields;
}
add_filter('attachment_fields_to_edit', 'add_media_credit', 10, 2);

/**
 * Change the post_author to the entered media credit from add_media_credit() above.
 */
function save_media_credit($post, $attachment) {
	$key = "media-credit-{$post['ID']}";
	if ( isset( $_POST[$key] ) && $_POST[$key] != '' ) { // a valid WP user was selected
		$new_author = $_POST[$key];
		$post['post_author'] = $new_author; // update post_author with the chosen user
		delete_post_meta($post['ID'], 'media-credit'); // delete any residual metadata from a free-form field (as inserted below)
		if ( isset( $post['post_parent'] ) ) { // if media is attached somewhere, edit the media-credit info in the attached (parent) post
			$parent = get_post( $post['post_parent'], ARRAY_A );
			$parent['post_content'] = preg_replace('/(media-credit.*id=)\d+/', '${1}' . $new_author, $parent['post_content']);
			wp_update_post($parent);
		}
	} else { // free-form text was entered, insert postmeta with credit. if free-form text is blank, insert a single space in postmeta.
		$freeform = $_POST['free-form'] == '' ? MEDIA_CREDIT_EMPTY_META_STRING : $_POST['free-form'];
		update_post_meta($post['ID'], MEDIA_CREDIT_OPTION, $freeform); // insert 'media-credit' metadata field for image with free-form text
		if ( isset( $post['post_parent'] ) ) { // if media is attached somewhere, edit the media-credit info in the attached (parent) post
			$parent = get_post( $post['post_parent'], ARRAY_A );
//			echo $parent['post_content'] . "<br /><br /><br />";
			// @todo make this work so that name field of attached post is updated when media credit is updated
//			$parent['post_content'] = preg_replace('/(media-credit.*name=").*"/', '${1}' . $freeform . '"', $parent['post_content']);
//			echo $parent['post_content'];die();
			wp_update_post($parent);
		}
	}
	return $post;
}
add_filter('attachment_fields_to_save', 'save_media_credit', 10, 2);

/**
 * Add media credit information to media using shortcode notation before sending to editor.
 */
function send_media_credit_to_editor_by_shortcode($html, $attachment_id, $caption, $title, $align) {
	$post = get_post($attachment_id);
	$credit_meta = get_freeform_media_credit($post);
	if ( $credit_meta == MEDIA_CREDIT_EMPTY_META_STRING )
		return $html;
	else if ( $credit_meta != '' )
		$credit = 'name="' . $credit_meta . '"';
	else
		$credit = 'id=' . $post->post_author;
	
	if ( ! preg_match( '/width="([0-9]+)/', $html, $matches ) )
		return $html;

	$width = $matches[1];

	$html = preg_replace( '/(class=["\'][^\'"]*)align(none|left|right|center)\s?/', '$1', $html );
	if ( empty($align) )
		$align = 'none';
	
	$shcode = '[media-credit ' . $credit . ' align="align' . $align . '" width="' . $width . '"]' . $html . '[/media-credit]';
	
	return apply_filters( 'media_add_credit_shortcode', $shcode, $html );
}
add_filter('image_send_to_editor', 'send_media_credit_to_editor_by_shortcode', 10, 5);

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
	if ( array_key_exists( 'credit_at_end', $options ) && $options['credit_at_end'] )
		return do_shortcode( $content );

	extract(shortcode_atts(array(
		'id' => -1,
		'name' => '',
		'align'	=> 'alignnone',
		'width'	=> '',
	), $atts));
	
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
if ( array_key_exists( 'credit_at_end', $options ) && $options['credit_at_end'] )
	add_filter( 'the_content', 'add_media_credits_to_end', 10, 1 );

function media_credit_stylesheet() {
	$options = get_option( MEDIA_CREDIT_OPTION );
	if ( array_key_exists( 'credit_end_end', $options ) && $options['credit_at_end'] ) // Do not display inline media credit if media credit is displayed at end of posts.
		wp_enqueue_style( 'media-credit-end', MEDIA_CREDIT_URL . 'css/media-credit-end.css', array(), 1.0, 'all');
	else
		wp_enqueue_style( 'media-credit', MEDIA_CREDIT_URL . 'css/media-credit.css', array(), 1.0, 'all');
}
add_action('wp_print_styles', 'media_credit_stylesheet');


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

	// Call register settings function
	add_action( 'admin_init', 'media_credit_init' );
}

function media_credit_init() { // whitelist options
	register_setting( 'media', MEDIA_CREDIT_OPTION, 'media_credit_options_validate' );
	if ( is_media_settings_page( ) )
		wp_enqueue_script( 'media-credit', MEDIA_CREDIT_URL . 'js/media-credit-preview.js', array('jquery'), 1.0, true);

	if ( is_media_edit_page( ) ) {
		wp_enqueue_script('jquery-autocomplete', MEDIA_CREDIT_URL . 'js/jquery.autocomplete.pack.js', array('jquery'), '1.1');
		wp_enqueue_script('media-credit-autocomplete', MEDIA_CREDIT_URL . 'js/media-credit-autocomplete.js', array('jquery', 'jquery-autocomplete'), '1.0', true);
	}

//	wp_enqueue_style('jquery-ui-autocomplete', MEDIA_CREDIT_URL . 'css/jquery-ui-1.8rc2.custom.css');
//	wp_enqueue_style('jquery-autocomplete', MEDIA_CREDIT_URL . 'css/jquery.autocomplete.css');
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

// TODO: eventually figure out if autocomplete from jQuery UI can be used instead using some of the code below...
function media_credit_scripts() {
	//TODO: only load all this nonsense on the Media edit page, not all of the admin!!
//	wp_deregister_script('jquery-ui-core'); // TODO: decide what to do here, uncommenting this breaks a lot of the AJAX functionality in admin
//	wp_deregister_script('jquery');
//	wp_register_script('jquery-1.4.2', MEDIA_CREDIT_URL . 'js/jquery-ui/jquery-1.4.2.min.js', array('jquery-ui-core'), '1.4.2');
//	wp_enqueue_script('jquery-ui-core-1.8', MEDIA_CREDIT_URL . 'js/jquery-ui/jquery.ui.core.js', array('jquery'), '1.8rc2');
//	wp_enqueue_script('jquery-ui-widget-1.8', MEDIA_CREDIT_URL . 'js/jquery-ui/jquery.ui.widget.js', array('jquery', 'jquery-ui-core-1.8'), '1.8rc2');
//	wp_enqueue_script('jquery-ui-position-1.8', MEDIA_CREDIT_URL . 'js/jquery-ui/jquery.ui.position.js', array('jquery', 'jquery-ui-core-1.8'), '1.8rc2');
//	wp_enqueue_script('jquery-ui-autocomplete-1.8', MEDIA_CREDIT_URL . 'js/jquery-ui/jquery.ui.autocomplete.js', array('jquery', 'jquery-ui-core-1.8', 'jquery-ui-widget-1.8', 'jquery-ui-position-1.8'), '1.8rc2');
//	wp_enqueue_script('jquery-ui-autocomplete-1.8', MEDIA_CREDIT_URL . 'js/jquery-ui/jquery.ui.autocomplete.js', array('jquery', 'jquery-ui-core-1.8', 'jquery-ui-widget-1.8', 'jquery-ui-position-1.8'), '1.8rc2');

//	wp_deregister_script('jquery-ui-core');
//	wp_deregister_script('jquery-ui-widget');
//	wp_deregister_script('jquery-ui-position');

//	wp_enqueue_script('jquery-ui-core', MEDIA_CREDIT_URL . 'js/jquery-ui/jquery.ui.core.min.js', array('jquery-1.4.2'), '1.8rc3');
//	wp_enqueue_script('jquery-ui-widget', MEDIA_CREDIT_URL . 'js/jquery-ui/jquery.ui.widget.min.js', array('jquery-1.4.2', 'jquery-ui-core'), '1.8rc3');
//	wp_enqueue_script('jquery-ui-position', MEDIA_CREDIT_URL . 'js/jquery-ui/jquery.ui.position.min.js', array('jquery-1.4.2', 'jquery-ui-core'), '1.8rc3');
//	wp_enqueue_script('jquery-ui-mouse', MEDIA_CREDIT_URL . 'js/jquery-ui/jquery.ui.mouse.min.js', array('jquery-1.4.2', 'jquery-ui-core, jquery-ui-widget'), '1.8rc3');
//	wp_enqueue_script('jquery-ui-autocomplete-1.8', MEDIA_CREDIT_URL . 'js/jquery-ui/jquery.ui.autocomplete.min.js', array('jquery-1.4.2', 'jquery-ui-core-1.8', 'jquery-ui-widget-1.8', 'jquery-ui-position-1.8'), '1.8rc3');

	// Or more simply, used the packaged version downloaded from jqueryui.com
	//wp_enqueue_script('jquery-ui-autocomplete-1.8', MEDIA_CREDIT_URL . 'js/jquery-ui/jquery-ui-1.8rc3.custom.min.js', array('jquery-1.4.2', 'jquery-ui-core'), '1.8rc3');
	//wp_enqueue_script('media-credit-autocomplete', MEDIA_CREDIT_URL . 'js/media-credit-autocomplete.js', array('jquery', 'jquery-autocomplete'), '1.0', true);
}
add_action('wp_print_scripts', 'media_credit_scripts');

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

function media_credit_options_validate($input) {
	foreach ($input as $key => $value) {
		$input[$key] = htmlspecialchars( $value, ENT_QUOTES );
	}
	return $input;
}


function is_media_edit_page( ) {
	global $pagenow;
	
	$media_edit_pages = array('post-new.php', 'post.php', 'page.php', 'page-new.php', 'media-upload.php', 'media.php', 'media-new.php');
	return in_array($pagenow, $media_edit_pages);
}

function is_media_settings_page( ) {
	global $pagenow;
	
	return $pagenow == 'options-media.php';
}

?>
