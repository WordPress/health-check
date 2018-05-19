jQuery( document ).ready(function( $ ) {
	$( '.health-check-toc' ).click(function( e ) {

		// Remove the height of the admin bar, and an extra 10px for better positioning.
		var offset = $( $( this ).attr( 'href' ) ).offset().top - $( '#wpadminbar' ).height() - 10;

		e.preventDefault();

		$( 'html, body' ).animate({
			scrollTop: offset
		}, 1200 );
	});
});
