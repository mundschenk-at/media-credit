function mediaCreditAutocomplete(id, currAuthorId, currAuthor) {
	var inputField = "#attachments\\[" + id + "\\]\\[media-credit\\]";
	var hiddenField = "#attachments\\[" + id + "\\]\\[media-credit-hidden\\]";
	
	jQuery(inputField)
		.click(function() {
			this.select();
			if (this.value == currAuthor) {
				removeID(id);
			}

		})
		.blur(function() {
			if (this.value == "") {
				removeID(id);
			}
		})
		/* --- For jQuery UI autocomplete */
		.autocomplete({
			source: function(request, response) { 
						jQuery.post(ajaxurl, 
									{ action: 'media_credit_author_names',
									  term: request.term,
									  limit: 100 }, 
									function(data) { response(data); }, "json"); 
					}, 
			minLength: 2,
			select: function(event, ui) {
				addID(id, ui.item.id);
				jQuery(inputField).attr("value", ui.item.value).change();
				return false;
			},
		    open: function(){
		        jQuery(this).autocomplete('widget').css('z-index', 2000000);
		        return false;
		    },
		});
	
	function addID(id, author_id) {
		jQuery(hiddenField).attr("value", author_id);
	}

	function removeID(id) {
		jQuery(hiddenField).attr("value", "");
	}
}

jQuery(document).ready(function() {
	function setupMediaCreditAutocomplete() {
		myData = jQuery('.media-credit-hidden').data();
		mediaCreditAutocomplete(myData.postId, myData.author, myData.authorDisplay);
	}
	
	jQuery(document).on('focusin', '.media-credit-input:not(.ui-autocomplete-input)', null, setupMediaCreditAutocomplete);
});