jQuery( document ).ready( function( $ ) {
	$( '#health-check-diff-modal' ).on( 'click', 'a[href="#health-check-diff-modal-close"]', function( e ) {
		e.preventDefault();
		$( '#health-check-diff-modal' ).toggle();
		$( '#health-check-diff-modal #health-check-diff-modal-diff' ).html( '' );
		$( '#health-check-diff-modal #health-check-diff-modal-content h3' ).html( '' );
	} );

	$( document ).on( 'keyup', function( e ) {
		if ( 27 === e.which ) {
			$( '#health-check-diff-modal' ).css( 'display', 'none' );
			$( '#health-check-diff-modal #health-check-diff-modal-diff' ).html( '' );
			$( '#health-check-diff-modal #health-check-diff-modal-content h3' ).html( '' );
		}
	} );
} );
