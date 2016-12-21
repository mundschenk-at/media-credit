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
		<input type="text" class="media-credit-input" <#
		if ( data.mediaCreditAuthorID && data.mediaCreditOptions.noDefaultCredit ) {
			#>placeholder<#
		} else {
			#>value<#
		} #>="{{ data.mediaCreditText }}" />
	</label>
	<label class="setting" data-setting="mediaCreditLink">
		<span class="name"><?php esc_html_e( 'Credit URL', 'media-credit' ); ?></span>
		<input type="url" value="{{ data.mediaCreditLink }}" />
	</label>
	<label class="setting" data-setting="mediaCreditNoFollow">
		<input type="checkbox" value="{{ data.mediaCreditNoFollow }}"
			<# if ( '1' === data.mediaCreditNoFollow ) { #>
				checked="checked"
			<# } #>
		/>
		<?php echo wp_kses( __( 'Add <code>rel="nofollow"</code>.', 'media-credit' ), array( 'code' => array() ) ); ?>
	</label>
</script><?php
