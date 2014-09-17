<script type="text/html" id="tmpl-media-credit-image-properties">
	<div class="image-settings-group media-credit-properties">
		<label class="setting media-credit-text">
			<span><?php esc_html_e( 'Credit', 'media-credit' ); ?></span>
			<input id="attachments[shortcode][media-credit]" type="text" data-setting="mediaCreditName" class="media-credit-input" value="{{ data.mediaCreditName }}" />
			<input id="attachments[shortcode][media-credit-hidden]" type="hidden" data-setting="mediaCreditID" data-post-id="shortcode" data-author="{{ data.mediaCreditID }}" data-author-display="{{ data.mediaCreditName }}" class="media-credit-hidden" value="{{ data.mediaCreditID }}" />
		</label>
	</div>
</script>