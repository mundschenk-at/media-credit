/*
 ** Properly handle editing credits in the media modal.
 */

( function ( $ ) {
	/**
	 * MediaCreditAttachmentModel
	 * 
	 * @class
	 * @augments wp.media.model.Attachment
	 */
	var MediaCreditAttachmentModel = wp.media.model.Attachment.extend({
	
		/**
		 * @param {Object} data The properties to be saved.
		 * @param {Object} options Sync options. e.g. patch, wait, success, error.
		 *
		 * @this Backbone.Model
		 *
		 * @returns {Promise}
		 */
		saveCompat: function( data, options ) {
			var author_id, freeform, image_id, url, nonce, result;
			
			// Save fields.
			result = this.constructor.__super__.saveCompat.apply( this, [ data, options ] );
			
			// Retrieve variables.
			_.each( data, function ( value, key ) {
				
				if ( ! image_id ) {
					var rx = /attachments\[(\d+)\]/gm,
						res = rx.exec( key ) || [	'', '0'	];
					
					image_id = res[1];
				}

				if ( ! freeform && key.match( /attachments\[\d+\]\[media-credit\]/gm ) ) {
					freeform = value;
				}

				if ( ! url && key.match( /attachments\[\d+\]\[media-credit-url\]/gm ) ) {
					url = value;
				}

				if ( ! author_id && key.match( /attachments\[\d+\]\[media-credit-hidden\]/gm ) ) {
					author_id = value;
				}
			} );
			
			// Update content.
			var previous_content = jQuery( '#content' ).val();
			
			if ( previous_content ) {
				var ajax_options = {
					type : 'POST',
					data : {
						'author_id'    : author_id,
						'freeform'     : freeform,
						'post_content' : previous_content,
						'image_id'     : image_id,
						'url'          : url,
						'nonce'        : this.get('nonces').update,
					},
					success : function ( new_content ) {
						var editor;
						
						if ( previous_content === new_content ) {
							return; // nothing has changed
						}

						editor = tinymce.get( 'content' );
						if ( editor && editor instanceof tinymce.Editor && jQuery( '#wp-content-wrap' ).hasClass( 'tmce-active' ) ) {
							editor.setContent( new_content );
							editor.save( { no_events : true } );
						} else {
							jQuery( 'textarea#content' ).val( new_content );
						}
					}
				};
				
				wp.media.ajax( 'media_credit_filter_content', ajax_options );
			}
			
			return result;
		},
	} );

	wp.media.model.Attachment.prototype = MediaCreditAttachmentModel.prototype;
	
} )( jQuery );
