/*
 ** Properly handle editing credits in the media modal.
 */

/* globals tinymce: false */

( function( $ ) {
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
			var authorId, freeform, imageId, url, result, ajaxOptions, previousContent, rx, res;

			// Save fields.
			result = this.constructor.__super__.saveCompat.apply( this, [ data, options ] );

			// Retrieve variables.
			_.each( data, function( value, key ) {

				if ( ! imageId ) {
					rx = /attachments\[(\d+)\]/gm;
					res = rx.exec( key ) || [ '', '0' ];
					imageId = res[1];
				}

				if ( ! freeform && key.match( /attachments\[\d+\]\[media-credit\]/gm ) ) {
					freeform = value;
				}

				if ( ! url && key.match( /attachments\[\d+\]\[media-credit-url\]/gm ) ) {
					url = value;
				}

				if ( ! authorId && key.match( /attachments\[\d+\]\[media-credit-hidden\]/gm ) ) {
					authorId = value;
				}
			} );

			// Update content.
			previousContent = $( '#content' ).val();

			if ( previousContent ) {
				ajaxOptions = {
					type: 'POST',
					data: {
						'author_id':    authorId,
						'freeform':     freeform,
						'post_content': previousContent,
						'image_id':     imageId,
						'url':          url,
						'nonce':        this.get( 'nonces' ).update
					},
					success: function( newContent ) {
						var editor;

						if ( previousContent === newContent ) {
							return; // Nothing has changed.
						}

						editor = tinymce.get( 'content' );
						if ( editor && editor instanceof tinymce.Editor && $( '#wp-content-wrap' ).hasClass( 'tmce-active' ) ) {
							editor.setContent( newContent );
							editor.save( { no_events: true } );
						} else {
							jQuery( 'textarea#content' ).val( newContent );
						}
					}
				};

				wp.media.ajax( 'media_credit_filter_content', ajaxOptions );
			}

			return result;
		}
	} );

	wp.media.model.Attachment.prototype = MediaCreditAttachmentModel.prototype;

} )( jQuery );
