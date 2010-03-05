function mediaCreditAutocomplete(id, currAuthorId, currAuthor) {
	var PLUGIN_DIR = "../wp-content/plugins/media-credit/"; //TODO: better way to do this?
	var inputField = "input.[id='attachments[" + id + "][media-credit]']"
	
	jQuery(inputField)
		.click(function() {
			this.select();
			if (this.value == currAuthor) {
				this.value = "";
				removeID(id);
			}
		})
		.blur(function() {
			if (this.value == "") {
				this.value = currAuthor;
				addID(id, currAuthorId);
			}
		})
		.autocomplete({
			source: PLUGIN_DIR + "search.php",
			minLength: 2,
			select: function(event, ui) {
				addID(id, ui.item.id);
			}
		});
}

function addID(id, author) {
	jQuery("#media-credit-" + id).attr("value", author);
}

function removeID(id) {
	jQuery("#media-credit-" + id).attr("value", "");
}
