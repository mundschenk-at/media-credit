<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://mundschenk.at
 * @since      1.0.0
 *
 * @package    Media_Credit
 * @subpackage Media_Credit/admin/partials
 */
?><script type="text/html" id="tmpl-media-credit-image-properties">
	<div class="image-settings-group media-credit-properties">
		<label class="setting media-credit-text">
			<span><?php esc_html_e( 'Credit', 'media-credit' ); ?></span>
			<input id="attachments[shortcode][media-credit]" type="text" data-setting="mediaCreditName" class="media-credit-input" value="{{ data.mediaCreditName }}" />
			<input id="attachments[shortcode][media-credit-hidden]" type="hidden" data-setting="mediaCreditID" data-post-id="shortcode" data-author="{{ data.mediaCreditID }}" data-author-display="{{ data.mediaCreditName }}" data-nonce="<?php echo wp_create_nonce( 'media_credit_author_names' ); ?>" class="media-credit-hidden" value="{{ data.mediaCreditID }}" />
		</label>
		<label class="setting media-credit-link">
			<span><?php esc_html_e( 'Credit URL', 'media-credit' ); ?></span>
			<input id="attachments[shortcode][media-credit-link]" type="url" data-setting="mediaCreditLink" class="media-credit-input" value="{{ data.mediaCreditLink }}" />
		</label>
	</div>
</script>