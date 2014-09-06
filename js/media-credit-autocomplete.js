function mediaCreditAutocomplete(id, currAuthorId, currAuthor) {
	var PLUGIN_DIR = "../wp-content/plugins/media-credit/"; //TODO: better way to do this?
	var inputField = "input#attachments\\[" + id + "\\]\\[media-credit\\]";
	
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
				jQuery("#attachments\\[" + id + "\\]\\[media-credit\\]").attr("value", ui.item.value).change();
				return false;
			},
		    open: function(){
		        jQuery(this).autocomplete('widget').css('z-index', 2000000);
		        return false;
		    },
		});
}

function addID(id, author_id) {
	jQuery("#attachments\\[" + id + "\\]\\[media-credit-hidden\\]").attr("value", author_id);
}

function removeID(id) {
	jQuery("#attachments\\[" + id + "\\]\\[media-credit-hidden\\]").attr("value", "");
}

function setupMediaCreditAutocomplete() {
	that = jQuery('.media-credit-hidden');
	
	myData = that.data();
	mediaCreditAutocomplete(myData.postId, myData.author, myData.authorDisplay);
}


jQuery(document).ready(function() {
	jQuery(document).on('focusin', '.media-credit-input:not(.ui-autocomplete-input)', null, setupMediaCreditAutocomplete);
});