jQuery( function( $ ) {
	'use strict';

	/**
	 * Render HTML for standard media credit preview.
	 */
	function renderCreditExample() {
		var author       = $( '#media-credit-preview a' ).clone().wrap( '<p>' ).parent().html();
		var separator    = $( 'input[name=\'media-credit[separator]\']' ).val();
		var organization = $( 'input[name=\'media-credit[organization]\']' ).val();

		$( '#media-credit-preview' ).html( author + separator + organization );
	}

	/**
	 * Render HTML for the combined credits at the end a post.
	 */
	function renderCreditAtEndExample() {
	    var author         = $( '#media-credit-preview a' ).clone().wrap( '<p>' ).parent().html();
	    var separator      = $( 'input[name=\'media-credit[separator]\']' ).val();
	    var organization   = $( 'input[name=\'media-credit[organization]\']' ).val();
			var previewData    = window.mediaCreditPreviewData || {

				// Default object if translated version is missing.
				pattern: 'Images courtesy of %2$s and %1$s',
				name1:   'Joe Smith',
				name2:   'Jane Doe',
				joiner:  ', '
			};

		$( '#media-credit-preview' ).html( previewData.pattern.replace( '%2$s', author + separator + organization + previewData.joiner + previewData.name2 ).replace( '%1$s', previewData.name1 ) );
	}

	/**
	 * Handle changes to the text fields.
	 */
	$( 'input[name^=\'media-credit\']' ).keyup( function() {
		if ( ! $( 'input[name=\'media-credit[credit_at_end]\']' ).prop( 'checked' ) ) {
			renderCreditExample();
		} else {
			renderCreditAtEndExample();
		}
	} );

	/**
	 * Handle changes to 'Display credits at the end' checkbox.
	 */
	$( 'input[name=\'media-credit[credit_at_end]\']' ).change( function() {
		if ( this.checked ) {
			renderCreditAtEndExample();
		} else {
			renderCreditExample();
	    }
	} );

} );
