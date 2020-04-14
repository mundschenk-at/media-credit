/**
 * This file is part of Media Credit.
 *
 * Copyright 2016-2020 Peter Putzer.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License,
 * version 2 as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 *
 * @file   This file extends wp.media.view.Attachment.Details views with media
 *         credit fields to properly handle editing credits in the media modal.
 * @author Peter Putzer <github@mundschenk.at>
 * @since  3.1.0
 */

/* global: wp, _, tinymce, mundschenk */ // Scrutinizer-CI

jQuery( function( $ ) {
	'use strict';

	var mediaCredit = mundschenk.mediaCredit || {};

	/**
	 * Install autoselect on given view.
	 *
	 * @param {Backbone.View} view      A view.
	 * @param {string}        input     A jQuery selector string targetting the input element.
	 * @param {boolean}       saveModel Whether the model should be saved after changes.
	 */
	mediaCredit.autoComplete = function( view, input, saveModel ) {
		var updateFreeformCredit = function( credit ) {
			view.model.set( {
				mediaCreditAuthorID: '',
				mediaCreditAuthorDisplay: credit,
				mediaCreditText: credit,
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
						mediaCreditAuthorID: ui.item.id,
						mediaCreditAuthorDisplay: ui.item.value,
						mediaCreditText: ui.item.value,
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
				},
			} )

		// Select input field value on click.
			.click( function() {
				this.select();
			} )

		// Prevent tab while still loading suggestion.
			.change( function( event ) {
				var $input = $( this ),
					credit = $input.val(),
					noDefaultCredit = mediaCredit.options.noDefaultCredit || false;

				if ( noDefaultCredit && '' === credit && '' === view.model.get( 'mediaCreditAuthorID' ) ) {
					view.model.set( {
						mediaCreditAuthorID: view.model.get( 'author' ),
						mediaCreditAuthorDisplay: view.model.get( 'authorName' ),
						mediaCreditText: view.model.get( 'authorName' ),
					} );

					if ( saveModel ) {
						view.model.save();
					}

					// Re-set placeholder.
					$input.val( '' ).attr( 'placeholder', view.model.get( 'mediaCredit.placeholder' ) );

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
					noDefaultCredit = mediaCredit.options.noDefaultCredit || false;

				wp.media.view.Attachment.prototype.render.apply( this, [] );

				$input = mediaCredit.autoComplete( this, 'label[data-setting="mediaCreditText"] input[type="text"]', true );

				if ( noDefaultCredit ) {
					$input.autocomplete( 'disable' );

					// Re-set placeholder.
					if ( '' !== this.model.get( 'mediaCreditAuthorID' ) ) {
						$input.val( '' ).attr( 'placeholder', this.model.get( 'mediaCredit.placeholder' ) );
					}
				} else {
					$input.autocomplete( 'enable' );
				}
			},

			updateSetting: function( event ) {
				var $input = $( event.target );

				// Handle checkboxes.
				if ( $input.is( 'input[type="checkbox"]' ) ) {
					event.target.value = $input.prop( 'checked' ) ? 1 : 0;
				}

				wp.media.view.Attachment.prototype.updateSetting.apply( this, [ event ] );
			},
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
			},
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
					attachment,
					attachmentId,
					updatedMediaCredit = {};

				// If the attachment does not yet have an `id`, return an instantly
				// rejected promise. Otherwise, all of our requests will fail.
				if ( _.isUndefined( this.id ) ) {
					return $.Deferred().rejectWith( this ).promise();
				}

				// Assign attachment ID.
				attachmentId = this.id;

				if ( 'update' === method && model.hasChanged() ) {
					// Handle placeholders gracefully.
					if ( mediaCredit.options.noDefaultCredit && '' === model.changed.mediaCreditText && '' !== model.get( 'mediaCreditAuthorID' ) ) {
						// FIXME: Issue when default credits are off.
						delete model.changed.mediaCreditText;
					}

					// Gather our changes.
					_.each( model.changed, function( value, key ) {
						if ( 0 === key.indexOf( 'mediaCredit' ) ) {
							// Handle according to field.
							if ( 'mediaCreditAuthorID' === key ) {
								updatedMediaCredit.user_id = this.get( key ) || 0;
							} else if ( 'mediaCreditText' === key ) {
								updatedMediaCredit.freeform = this.get( key );
							} else if ( 'mediaCreditLink' === key ) {
								updatedMediaCredit.url = this.get( key );
							} else if ( 'mediaCreditNoFollow' === key ) {
								updatedMediaCredit.flags = updatedMediaCredit.flags || {};
								updatedMediaCredit.flags.nofollow = this.get( key );
							}

							// Nothing to see here.
							delete model.changed[ key ];
						}
					}, this );

					// Don't trigger AJAX call if we have no relevant media-credit changes.
					if ( 0 < _.size( updatedMediaCredit ) ) {
						attachment = new wp.api.models.Media( { id: this.id } );
						attachment.fetch();
						attachment.set( 'media_credit', { raw: updatedMediaCredit } );

						// Necessary workaround, as post status 'inherited' is not supported by the REST API.
						attachment.save( { status: 'publish' } );

						// Update content currently in editor.
						this.updateMediaCreditInEditorContent( $( 'textarea#content' ).val(), attachmentId, model );
					}
				}

				// Don't trigger AJAX call if there is nothing left to do.
				if ( 'update' !== method || model.hasChanged() ) {
					return this.constructor.__super__.sync.apply( this, [ method, model, options ] );
				} else if ( result ) {
					return result;
				}
				return $.Deferred().rejectWith( this ).promise();
			},

			updateMediaCreditInEditorContent: function( previousContent, attachmentId, model ) {
				if ( previousContent ) {
					wp.apiRequest( {
						namespace: 'media-credit/v1',
						endpoint: 'replace_in_content',
						data: {
							content: previousContent,
							attachment_id: attachmentId || 0, // eslint-disable-line camelcase
							author_id: model.get( 'mediaCreditAuthorID' ) || 0, // eslint-disable-line camelcase
							freeform: model.get( 'mediaCreditText' ),
							url: model.get( 'mediaCreditLink' ),
							nofollow: model.get( 'mediaCreditNoFollow' ),
						},

						success: function( newContent ) {
							var editor;

							if ( previousContent === newContent ) {
								return; // Nothing has changed.
							}

							editor = tinymce.get( 'content' );
							if ( editor && editor instanceof tinymce.Editor && $( '#wp-content-wrap' ).hasClass( 'tmce-active' ) ) {
								editor.setContent( newContent );
								editor.save( { no_events: true } ); // eslint-disable-line camelcase
							} else {
								$( 'textarea#content' ).val( newContent );
							}
						},
					} );
				}
			},
		} );

		// Exchange prototype.
		wp.media.model.Attachment.prototype = mediaCredit.AttachmentModel.prototype;
	}
} );
