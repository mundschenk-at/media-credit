<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://mundschenk.at
 * @since      3.0.0
 *
 * @package    Media_Credit
 * @subpackage Media_Credit/admin/partials
 */

?><script type="text/html" id="tmpl-media-credit-attachment-details">
	<label class="setting" data-setting="mediaCreditText">
		<span class="name"><?php esc_html_e( 'Credit', 'media-credit' ); ?></span>
		<input type="text" class="media-credit-input" value="{{ data.mediaCreditText }}" />
	</label>
	<label class="setting" data-setting="mediaCreditLink">
		<span class="name"><?php esc_html_e( 'Credit URL', 'media-credit' ); ?></span>
		<input type="url" value="{{ data.mediaCreditLink }}" />
	</label>
</script><?php
