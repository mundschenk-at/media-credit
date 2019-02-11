<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://mundschenk.at
 * @since      2.3.0
 *
 * @package    Media_Credit
 * @subpackage Media_Credit/admin/partials
 */

?><script type="text/html" id="tmpl-media-credit-image-properties">
	<label class="setting media-credit-text">
		<span><?php esc_html_e( 'Credit', 'media-credit' ); ?></span>
		<input type="text" data-setting="mediaCreditText" value="{{ data.mediaCreditText }}" />
	</label>
	<label class="setting media-credit-link">
		<span><?php esc_html_e( 'Credit URL', 'media-credit' ); ?></span>
		<input type="url" data-setting="mediaCreditLink" value="{{ data.mediaCreditLink }}" />
	</label>
	<div class="setting media-credit-nofollow">
		<label>
			<input type="checkbox" data-setting="mediaCreditNoFollow" value="1" <# if ( data.mediaCreditNoFollow ) { #>checked="checked"<# } #> />
			<?php
				echo wp_kses( __( 'Add <code>rel="nofollow"</code> to credit link.', 'media-credit' ), [ 'code' => [] ] );
			?>
		</label>
	</div>
</script>
<?php
