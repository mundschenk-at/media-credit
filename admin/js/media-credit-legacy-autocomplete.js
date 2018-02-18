/*
 ** Properly handle editing credits in the media modal.
 */

jQuery( function( $ ) {
	'use strict';

	var mediaCredit = window.$mediaCredit || {};

	/**
   * Install autoselect on the given input fields.
   *
   * @param $input  A jQuery object for the input field.
   * @param $hidden A jQuery object for the hidden field.
   */
	mediaCredit.autoCompleteLegacy = function( $input, $hidden ) {

		var updateFreeformCredit = function( credit ) {
			$hidden.attr( 'value', '' );
			$hidden.attr( 'data-author-display', credit );
			$input.attr( 'value', credit );
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
				$hidden.attr( 'value', ui.item.id );
				$hidden.attr( 'data-author-display', ui.item.value );
				$input.attr( 'value', ui.item.value );

				return false;
			},

			response: function( event, ui ) {
				var credit;

				if ( 0 === ui.content.length ) {
					credit = $( this ).val();

					if ( credit !== $hidden.attr( 'data-display-author' ) ) {
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

		// Handle tab while still loading suggestion.
		.change( function( event ) {
			var credit = $input.val(),
				authorID = $hidden.attr( 'data-author-id' );

			if ( mediaCredit.noDefaultCredit && '' === credit && '' === $hidden.val() ) {
				$hidden.val( authorID );
				$hidden.attr( 'data-author-display', mediaCredit.id[ authorID ] );

				// Re-set placeholder.
				$input.val( '' ).attr( 'placeholder', $hidden.attr( 'data-author-display' ) );

				event.stopImmediatePropagation();
				event.preventDefault();
			} else if ( credit !== $hidden.attr( 'data-author-display' ) ) {
				updateFreeformCredit( credit );

				event.stopImmediatePropagation();
				event.preventDefault();
			}
		} );
	};

	mediaCredit.data   = $( '.media-credit-hidden' ).data();
	mediaCredit.input  = $( '#attachments\\[' + mediaCredit.data.postId + '\\]\\[media-credit\\]' );
	mediaCredit.hidden = $( '#attachments\\[' + mediaCredit.data.postId + '\\]\\[media-credit-hidden\\]' );
	mediaCredit.autoCompleteLegacy( mediaCredit.input, mediaCredit.hidden );

} );
