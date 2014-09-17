function mediaCreditAutocomplete(inputField, hiddenField, currAuthorId, currAuthor) {
	jQuery(inputField)
		.click(function() {
			this.select();
			if (this.value == currAuthor) {
				removeID();
			}

		})
		.blur(function() {
			if (this.value == '') {
				removeID();
			}
		})
		/* --- For jQuery UI autocomplete */
		.autocomplete({
			source: function(request, response) { 
						jQuery.post(ajaxurl, 
									{ action: 'media_credit_author_names',
									  term: request.term,
									  limit: 100 }, 
									function(data) { response(data); }, 'json'); 
					}, 
			minLength: 2,
			select: function(event, ui) {
				addID(ui.item.id);
				jQuery(inputField).attr('value', ui.item.value).change();
				jQuery(hiddenField).change(); // only needed for property dialog
				return false;
			},
		    open: function(){
		        jQuery(this).autocomplete('widget').css('z-index', 2000000);
		        return false;
		    },
		});
	
	function addID(author_id) {
		jQuery(hiddenField).attr('value', author_id);
	}

	function removeID() {
		jQuery(hiddenField).attr('value', '');
	}
}

jQuery(document).ready(function() {
	function setupMediaCreditAutocomplete() {
		var myData = jQuery('.media-credit-hidden').data();
		var inputField = '#attachments\\[' + myData.postId + '\\]\\[media-credit\\]';
		var hiddenField = '#attachments\\[' + myData.postId + '\\]\\[media-credit-hidden\\]';

		mediaCreditAutocomplete(inputField, hiddenField, myData.author, myData.authorDisplay);
	}
	
	jQuery(document).on('focusin', '.media-credit-input:not(.ui-autocomplete-input)', null, setupMediaCreditAutocomplete);
});