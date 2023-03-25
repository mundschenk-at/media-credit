/**
 * This file is part of Media Credit.
 *
 * Copyright 2013-2023 Peter Putzer.
 * Copyright 2010-2011 Scott Bressler.
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
 * @file   This file handles autocomplete in the legacy media library.
 * @author Peter Putzer <github@mundschenk.at>
 * @author Scott Bressler
 * @since  0.5.1
 */

jQuery( function( $ ) {
	'use strict';

	var mediaCredit = mundschenk.mediaCredit || {};

	/**
	 * Install autoselect on the given input fields.
	 *
	 * @param {jQuery} $input  A jQuery object for the input field.
	 * @param {jQuery} $hidden A jQuery object for the hidden field.
	 */
	mediaCredit.autoCompleteLegacy = function( $input, $hidden ) {
		var updateFreeformCredit = function( credit ) {
			$hidden.prop( 'value', '' );
			$hidden.prop( 'data-author-display', credit );
			$input.prop( 'value', credit );
		};

		// Target the input element (& return it after for chaining).
		return $input

		// Add autocomplete.
			.autocomplete( {
				autoFocus: true,
				minLength: 2,

				source: mediaCredit.names || ( mediaCredit.names = _.map( mediaCredit.id, function( value, key ) {
					return { id: key, value: value, label: value };
				} ) ),

				select: function( event, ui ) {
					$hidden.prop( 'value', ui.item.id );
					$hidden.prop( 'data-author-display', ui.item.value );
					$input.prop( 'value', ui.item.value );


					return false;
				},

				response: function( event, ui ) {
					var credit;

					if ( 0 === ui.content.length ) {
						credit = $( this ).val();

						if ( credit !== $hidden.prop( 'data-display-author' ) ) {
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

		// Handle tab while still loading suggestion.
			.change( function( event ) {
				var credit = $input.val(),
					authorID = $hidden.prop( 'data-author-id' );

				if ( mediaCredit.options.noDefaultCredit && '' === credit && '' === $hidden.val() ) {
					$hidden.val( authorID );
					$hidden.prop( 'data-author-display', mediaCredit.id[ authorID ] );

					// Re-set placeholder.
					$input.val( '' ).prop( 'placeholder', $hidden.prop( 'data-author-display' ) );

					event.stopImmediatePropagation();
					event.preventDefault();
				} else if ( credit !== $hidden.prop( 'data-author-display' ) ) {
					updateFreeformCredit( credit );

					event.stopImmediatePropagation();
					event.preventDefault();
				}
			} );
	};

	mediaCredit.data = $( '.media-credit-hidden' ).data();
	mediaCredit.input = $( '#attachments\\[' + mediaCredit.data.postId + '\\]\\[media-credit\\]' );
	mediaCredit.hidden = $( '#attachments\\[' + mediaCredit.data.postId + '\\]\\[media-credit-hidden\\]' );
	mediaCredit.autoCompleteLegacy( mediaCredit.input, mediaCredit.hidden );
} );
