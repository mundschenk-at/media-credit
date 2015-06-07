/*
 ** Properly handle editing credits in the media modal.
 */

(function($) {
	// can't extend wp.media.model.Attachment, don't know why
	origAttachmentCompat = wp.media.view.AttachmentCompat;
	wp.media.view.AttachmentCompat = wp.media.view.AttachmentCompat.extend({

	    save: function( event ) {
	    	var author_id, freeform, image_id;
	    	
	    	_.each( this.$el.serializeArray(), function( pair ) {	    		
	    		if (!image_id) {
	    			var rx = /attachments\[(\d+)\]/gm;
	    			var res =  rx.exec(pair.name) || ['', '0'];
 	    			image_id = res[1];
	    		}
	    		
	    		if (!freeform) {
	    			if (pair.name.match(/attachments\[\d+\]\[media-credit\]/gm)) {
	    				freeform = pair.value;
	    			}
	    		}
	    		
	    		if (!author_id) {
	    			if (pair.name.match(/attachments\[\d+\]\[media-credit-hidden\]/gm)) {
	    				author_id = pair.value;
	    			}
	    		}
	    	});
	    	
			var post_id = jQuery('#post_ID').val(), 
		    previous_content = jQuery('#content').val(),
		    new_content = previous_content;
			
			var options = {
				type : 'POST',
				data : {
					'author_id' : author_id,
					'freeform'  : freeform,
					'post_content' : previous_content,
					'image_id' : image_id
				},
				success : function(s) {
					var new_content = s;//.replace(/\\"/gm, '"');
					
					if (previous_content === new_content) {
						return; // nothing has changed
					}
					
					var editor = tinymce.get('content');
					if (editor && editor instanceof tinymce.Editor && jQuery('#wp-content-wrap').hasClass('tmce-active')) {
						editor.setContent(new_content);
						editor.save({ no_events : true });
					} else {
						jQuery('textarea#content').val(new_content);
					}
				}
			};

			wp.media.ajax('media_credit_filter_content', options);
			
	        origAttachmentCompat.prototype.save.apply(this, event);
	    }
	});

	
})(jQuery);
