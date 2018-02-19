/**
 *  Properly handle editing credits in the media modal.
 *
 *  global: tinymce
 */

jQuery( function( $ ) {
	'use strict';

	var mediaCredit = window.$mediaCredit || {};

	/**
     * Install autoselect on given view.
     *
     * @param view      A Backbone.View.
     * @param input     A jQuery selector string targetting the input element.
     * @param saveModel Whether the model should be saved after changes.
     */
	mediaCredit.autoComplete = function( view, input, saveModel ) {

		var updateFreeformCredit = function( credit ) {
			view.model.set( {
				mediaCreditAuthorID:      '',
				mediaCreditAuthorDisplay: credit,
				mediaCreditText:          credit
			} );

			if ( saveModel ) {
				view.model.save();
			}
		};

		// Target the input element (& return it after for chaining).
		return view.$el.find( input )

		// Add autocomplete.
		.autocomplete( {
			autoFocus: true,
			minLength: 2,

			source: mediaCredit.names || ( mediaCredit.names = _.map( mediaCredit.id, function( value, key ) {
				return { id: key, value: value, label: value };
			} ) ),

			select: function( event, ui ) {
				$( this ).attr( 'value', ui.item.value );
				view.model.set( {
					mediaCreditAuthorID:      ui.item.id,
					mediaCreditAuthorDisplay: ui.item.value,
					mediaCreditText:          ui.item.value
				} );

				if ( saveModel ) {
					view.model.save();
				}

				return false;
			},

			response: function( event, ui ) {
				var credit;

				if ( 0 === ui.content.length ) {
					credit = $( this ).val();

					if ( credit !== view.model.get( 'mediaCreditAuthorDisplay' ) ) {
						updateFreeformCredit( credit );
					}
				}
			},

			open: function() {
				$( this ).autocomplete( 'widget' ).css( 'z-index', 2000000 );

				return false;
			}
		} )

		// Select input field value on click.
		.click( function() {
			this.select();
		} )

		// Prevent tab while still loading suggestion.
		.change( function( event ) {
			var $input = $( this ),
				credit = $input.val(),
				noDefaultCredit = mediaCredit.noDefaultCredit || false;

			if ( noDefaultCredit && '' === credit && '' === view.model.get( 'mediaCreditAuthorID' ) ) {
				view.model.set( {
					mediaCreditAuthorID:      view.model.get( 'author' ),
					mediaCreditAuthorDisplay: view.model.get( 'authorName' ),
					mediaCreditText:          view.model.get( 'authorName' )
				} );

				if ( saveModel ) {
					view.model.save();
				}

				// Re-set placeholder.
				$input.val( '' ).attr( 'placeholder', view.model.get( 'mediaCreditAuthorDisplay' ) );

				event.stopImmediatePropagation();
				event.preventDefault();
			} else if ( credit !== view.model.get( 'mediaCreditAuthorDisplay' ) ) {
				updateFreeformCredit( credit );

				event.stopImmediatePropagation();
				event.preventDefault();
			}
		} );
	};

	if ( wp.media.view.Attachment.Details ) {
		/**
		 * MediaCredit.AttachmentDetails
		 *
		 * @class
		 * @augments wp.media.view.Attachment.Details
		 * @augments wp.media.view.Attachment
		 */
		mediaCredit.AttachmentDetails = wp.media.view.Attachment.Details.extend( {

			template: function( view ) {
				return wp.media.template( 'attachment-details' )( view ) + wp.media.template( 'media-credit-attachment-details' )( view );
			},

			render: function() {
				var $input,
					noDefaultCredit = mediaCredit.noDefaultCredit || false;

				wp.media.view.Attachment.prototype.render.apply( this, [] );

				$input = mediaCredit.autoComplete( this, 'label[data-setting="mediaCreditText"] input[type="text"]', true );

				if ( noDefaultCredit ) {
					$input.autocomplete( 'disable' );

					// Re-set placeholder.
					if ( '' !== this.model.get( 'mediaCreditAuthorID' ) ) {
						$input.val( '' ).attr( 'placeholder', this.model.get( 'mediaCreditAuthorDisplay' ) );
					}
				} else {
					$input.autocomplete( 'enable' );
				}
			},

			updateSetting: function( event ) {
				var $input = $( event.target );

				// Handle checkboxes.
				if ( $input.is( 'input[type="checkbox"]' ) ) {
					event.target.value = $input.prop( 'checked' );
				}

				wp.media.view.Attachment.prototype.updateSetting.apply( this, [ event ] );
			}
		} );

		// Exchange prototype.
		wp.media.view.Attachment.Details.prototype = mediaCredit.AttachmentDetails.prototype;
	}

	if ( wp.media.view.Attachment.Details.TwoColumn ) {

		/**
		 * MediaCredit.AttachmentDetailsTwoColumn
		 *
		 * @class
		 * @augments wp.media.view.Attachment.Details.TwoColumn
		 * @augments wp.media.view.Attachment.Details
		 * @augments wp.media.view.Attachment
		 */
		mediaCredit.AttachmentDetailsTwoColumn = wp.media.view.Attachment.Details.TwoColumn.extend( {

			template: function( view ) {
				var templateHtml = $( $.parseHTML( wp.media.template( 'attachment-details-two-column' )( view ) ) );
				$( wp.media.template( 'media-credit-attachment-details' )( view ) ).insertAfter( templateHtml.find( '.attachment-compat' ).prevAll( '*[data-setting]' )[0] );

				return templateHtml;
			},

			updateSetting: function( event ) {

				// If we don't override this here, the superclass updateSetting will never be called.
				wp.media.view.Attachment.Details.prototype.updateSetting.apply( this, [ event ] );
			}
		} );

		// Exchange prototype.
		wp.media.view.Attachment.Details.TwoColumn.prototype = mediaCredit.AttachmentDetailsTwoColumn.prototype;
	}

	if ( wp.media.model.Attachment ) {
		/**
		 * MediaCredit.AttachmentModel
		 *
		 * @class
		 * @augments wp.media.model.Attachment
		 */
		mediaCredit.AttachmentModel = wp.media.model.Attachment.extend( {

			sync: function( method, model, options ) {
				var result = null,
					nonces;

				// If the attachment does not yet have an `id`, return an instantly
				// rejected promise. Otherwise, all of our requests will fail.
				if ( _.isUndefined( this.id ) ) {
					return $.Deferred().rejectWith( this ).promise();
				}

				if ( 'update' === method && model.hasChanged() ) {

					// If we do not have the necessary nonce, fall through to superclass.
					nonces = this.get( 'nonces' );
					if ( nonces && nonces.mediaCredit && nonces.mediaCredit.update ) {
						options = options || {};
						options.context = this;

						// Set the action and ID.
						options.data = _.extend( options.data || {}, {
							action:  'save-attachment-media-credit',
							id:      this.id,
							nonce:   nonces.mediaCredit.update,
							post_id: wp.media.model.settings.post.id
						});

						// Record the values of the changed attributes.
						if ( model.hasChanged() ) {
							options.data.changes     = {};
							options.data.mediaCredit = {};

							// Handle placeholders gracefully.
							if ( mediaCredit.noDefaultCredit && '' === model.changed.mediaCreditText && '' !== model.get( 'mediaCreditAuthorID' ) ) {
								delete model.changed.mediaCreditText;
							}

							// Gather our changes.
							_.each( model.changed, function( value, key ) {
								if ( 0 === key.indexOf( 'mediaCredit' ) ) {
									options.data.changes[ key ] = this.get( key );
									delete model.changed[ key ];
								}
							}, this );

							// Set up media credit attributes.
							options.data.mediaCredit.text     = model.get( 'mediaCreditText' );
							options.data.mediaCredit.link     = model.get( 'mediaCreditLink' );
							options.data.mediaCredit.id       = model.get( 'mediaCreditAuthorID' );
							options.data.mediaCredit.nofollow = model.get( 'mediaCreditNoFollow' );
						}

						// Don't trigger AJAX call if we have no media-credit changes.
						if ( _.size( options.data.changes ) > 0 ) {
							result = wp.media.ajax( options );

							// Clean-up, part I.
							delete options.data.changes;

							// Update content currently in editor.
							this.updateMediaCreditInEditorContent( $( 'textarea#content' ).val(), options );

							// Clean-up, part II.
							delete options.data.mediaCredit;
						}
					}
				}

				// Don't trigger AJAX call if there is nothing left to do.
				if ( 'update' !== method || model.hasChanged() ) {
					return this.constructor.__super__.sync.apply( this, [ method, model, options ] );
				} else if ( result ) {
					return result;
				} else {
					return $.Deferred().rejectWith( this ).promise();
				}
			},

			updateMediaCreditInEditorContent: function( previousContent, options ) {
				var nonces = this.get( 'nonces' ),
					ajaxOptions;

				if ( previousContent && nonces && nonces.mediaCredit && nonces.mediaCredit.content ) {
					ajaxOptions = _.extend( options, {
						data: _.extend( options.data, {
							action:      'update-media-credit-in-post-content',
							nonce:       nonces.mediaCredit.content,
							mediaCredit: _.extend( options.data.mediaCredit, {
								content: previousContent
							} )
						} ),

						/* globals tinymce: false */
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
								$( 'textarea#content' ).val( newContent );
							}
						}
					} );

					wp.media.ajax( 'update-media-credit-in-post-content', ajaxOptions );
				}
			}
		} );

		// Exchange prototype.
		wp.media.model.Attachment.prototype = mediaCredit.AttachmentModel.prototype;
	}

} );
