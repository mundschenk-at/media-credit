jQuery( document ).ready( function( $ ) {
	/* jshint unused: vars */ /* globals mediaCreditPreviewData */

	function renderCreditExample() {
		var author       = $( '#media-credit-preview a' ).clone().wrap( '<p>' ).parent().html();
		var separator    = $( 'input[name=\'media-credit[separator]\']' ).val();
		var organization = $( 'input[name=\'media-credit[organization]\']' ).val();

		$( '#media-credit-preview' ).html( author + separator + organization );
	}

	function renderCreditAtEndExample() {
	    var author         = $( '#media-credit-preview a' ).clone().wrap( '<p>' ).parent().html();
	    var separator      = $( 'input[name=\'media-credit[separator]\']' ).val();
	    var organization   = $( 'input[name=\'media-credit[organization]\']' ).val();

		$( '#media-credit-preview' ).html( mediaCreditPreviewData.pattern.replace( '%2$s', author + separator + organization + mediaCreditPreviewData.joiner + mediaCreditPreviewData.name2 ).replace( '%1$s', mediaCreditPreviewData.name1 ) );
	}

	/**
	 * Handle changes to the text fields.
	 */
	$( 'input[name^=\'media-credit\']' ).keyup( function( event ) {
		if ( ! $( 'input[name=\'media-credit[credit_at_end]\']' ).prop( 'checked' ) ) {
			renderCreditExample();
		} else {
			renderCreditAtEndExample();
		}
	} );

	/**
	 * Handle changes to 'Display credits at the end' checkbox.
	 */
	$( 'input[name=\'media-credit[credit_at_end]\']' ).change( function( event ) {
		if ( this.checked ) {
			renderCreditAtEndExample();
		} else {
			renderCreditExample();
	    }
	} );

} );
