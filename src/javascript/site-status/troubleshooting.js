jQuery( document ).ready( function( $ ) {
	$( '.show-remaining' ).click( function() {
		$( '.hidden', $( this ).closest( 'ul' ) ).removeClass( 'hidden' );
	} );
} );
