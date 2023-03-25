/**
 * This file is part of Media Credit.
 *
 * Copyright 2014-2023 Peter Putzer.
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
 * @file   This file defines MediaCreditImagePropertiesView Backbone view and
 *         associated event handlers.
 * @author Peter Putzer <github@mundschenk.at>
 * @since  2.3.0
 */

( function( $, wp, mundschenk ) {
	'use strict';

	var MediaCreditImagePropertiesView, frame,
		mediaCredit = mundschenk.mediaCredit || {};

	// Sanity check.
	if ( ! wp.media.events ) {
		return;
	}

	/**
	 * MediaCreditImagePropertiesView
	 *
	 * @class
	 * @augments wp.Backbone.View
	 */
	MediaCreditImagePropertiesView = wp.Backbone.View.extend( {
		className: 'advanced-media-credit',
		template: wp.media.template( 'media-credit-image-properties' ),

		initialize: function() {
			wp.Backbone.View.prototype.initialize.apply( this, arguments );
		},

		prepare: function() {
			return this.model.toJSON();
		},

		render: function() {
			wp.Backbone.View.prototype.render.apply( this, arguments );
			return this;
		},
	} );

	/**
	 * Initialize a view instance when the editor frame is created.
	 */
	wp.media.events.on( 'editor:frame-create', function( options ) {
		frame = options.frame;
		frame.on( 'content:render:image-details', function( view ) {
			var mediaCreditView = new MediaCreditImagePropertiesView( { model: view.model } );

			view.on( 'post-render', function() {
				view.views.insert( view.$el.find( '.advanced-settings' ), mediaCreditView.render().el, { at: 1 } );
				mediaCredit.autoComplete( mediaCreditView, 'input[data-setting="mediaCreditText"]', false );
			} );
		} );
	} );

	/**
	 * Set media credit to author's display name if the ID attribute is used.
	 */
	wp.media.events.on( 'editor:image-edit', function( options ) {
		if ( '' === options.metadata.mediaCreditText && '' !== options.metadata.mediaCreditAuthorID ) {
			options.metadata.mediaCreditText = mediaCredit.id[ options.metadata.mediaCreditAuthorID ];
		}
	} );

	/**
	 * Update TinyMCE HTML representation of media credit.
	 */
	wp.media.events.on( 'editor:image-update', function( options ) {
		var editor = options.editor,
			dom = editor.dom,
			image = options.image,
			model = frame.content.get().model,
			align = model.get( 'align' ),
			width = parseInt( model.get( 'width' ), 10 ),
			credit,
			mediaCreditText = model.get( 'mediaCreditText' ),
			mediaCreditAuthorID = model.get( 'mediaCreditAuthorID' ),
			mediaCreditLink = model.get( 'mediaCreditLink' ),
			mediaCreditNoFollow = model.get( 'mediaCreditNoFollow' ),
			mediaCreditBlock,
			mediaCreditWrapper;

		// Extract mediaCreditBlock in visual editor.
		if ( image.parentNode && 'A' === image.parentNode.nodeName ) {
			mediaCreditBlock = dom.getNext( image.parentNode, '.mceMediaCreditTemp' );
		} else {
			mediaCreditBlock = dom.getNext( image, '.mceMediaCreditTemp' );
		}

		if ( mediaCredit.id[ mediaCreditAuthorID ] !== mediaCreditText ) {
			mediaCreditAuthorID = '';
		}

		// We need to support custom sizes as well.
		if ( model.get( 'size' ) === 'custom' ) {
			width = parseInt( model.get( 'customWidth' ) );
		}

		credit = mediaCreditAuthorID ? ( mediaCredit.id[ mediaCreditAuthorID ] + mediaCredit.options.separator + mediaCredit.options.organization ) : mediaCreditText;
		credit = credit.replace( /<[^>]+>(.*)<\/[^>]+>/g, '$1' ); // Basic sanitation.
		align = 'align' + ( align || 'none' );

		// No current media credit block.
		if ( null === mediaCreditBlock && ( mediaCreditText || mediaCreditAuthorID ) ) {
			// Create new representation for media-credit.
			mediaCreditBlock = dom.create( 'span', {
				class: 'mceMediaCreditTemp mceNonEditable',
				'data-media-credit-author-id': mediaCreditAuthorID,
				'data-media-credit-text': mediaCreditText,
				'data-media-credit-align': align,
				'data-media-credit-link': mediaCreditLink,
				'data-media-credit-nofollow': mediaCreditNoFollow,
			}, credit );

			if ( image.parentNode && 'A' === image.parentNode.nodeName ) {
				dom.insertAfter( mediaCreditBlock, image.parentNode );
			} else {
				dom.insertAfter( mediaCreditBlock, image );
			}
		}

		if ( mediaCreditBlock ) {
			// Check for media-credit nested inside caption.
			if ( ! dom.getParent( mediaCreditBlock, 'dl.wp-caption' ) ) {
				// Maybe add extra width.
				if ( ! editor.getParam( 'wpeditimage_html5_captions' ) ) {
					width += 10;
				}

				// Standalone [media-credit].
				mediaCreditWrapper = dom.create( 'div', {
					class: 'mceMediaCreditOuterTemp ' + align,
					style: 'width: ' + width + 'px',
				} );

				// Swap existing parent with our new wrapper.
				dom.insertAfter( mediaCreditWrapper, mediaCreditBlock.parentNode );
				dom.add( mediaCreditWrapper, mediaCreditBlock.parentNode );
				dom.remove( mediaCreditBlock.parentNode, true );
			}

			dom.setAttrib( mediaCreditBlock, 'data-media-credit-text', mediaCreditText );
			dom.setAttrib( mediaCreditBlock, 'data-media-credit-author-id', mediaCreditAuthorID );
			dom.setAttrib( mediaCreditBlock, 'data-media-credit-link', mediaCreditLink );
			dom.setAttrib( mediaCreditBlock, 'data-media-credit-nofollow', mediaCreditNoFollow );
			dom.setHTML( mediaCreditBlock, credit );
		}
	} );
}( jQuery, wp, mundschenk ) );
