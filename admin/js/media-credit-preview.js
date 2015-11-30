jQuery( document ).ready( function ( $ ) {
	
	$( "input[name^='media-credit']" ).keyup( function ( event ) {
		
		var author = $( "span#preview a" ).clone().wrap( '<p>' ).parent().html();
		var separator = $( "input[name='media-credit[separator]']" ).val();
		var organization = $( "input[name='media-credit[organization]']" ).val();
		
		$( "span#preview" ).html( author + separator + organization );
	} );
	
} );
