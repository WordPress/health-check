jQuery( document ).ready(function( $ ) {
	$( '.health-check-accordion' ).on( 'click', '.health-check-accordion-trigger', function() {
		var isExpanded = ( 'true' === $( this ).attr( 'aria-expanded' ) );

		if ( isExpanded ) {
			$( this ).attr( 'aria-expanded', 'false' );
			$( '#' + $( this ).attr( 'aria-controls' ) ).attr( 'hidden', true );
		} else {
			$( this ).attr( 'aria-expanded', 'true' );
			$( '#' + $( this ).attr( 'aria-controls' ) ).attr( 'hidden', false );
		}
	});

	$( '.health-check-accordion' ).on( 'keyup', '.health-check-accordion-trigger', function( e ) {
		if ( '38' === e.keyCode.toString() ) {
			$( '.health-check-accordion-trigger', $( this ).closest( 'dt' ).prevAll( 'dt' ) ).focus();
		} else if ( '40' === e.keyCode.toString() ) {
			$( '.health-check-accordion-trigger', $( this ).closest( 'dt' ).nextAll( 'dt' ) ).focus();
		}
	});
});
