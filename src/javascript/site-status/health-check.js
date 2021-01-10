/* global ajaxurl */
jQuery( document ).ready( function( $ ) {
	function healthCheckFailureModal( markup, action, parent ) {
		$( '#dynamic-content' ).html( markup );
		$( '.health-check-modal' ).data( 'modal-action', action ).data( 'parent-field', parent ).show();
	}

	function healthCheckFailureModalClose( modal ) {
		modal.hide();
	}

	$( '.modal-close' ).click( function( e ) {
		e.preventDefault();
		healthCheckFailureModalClose( $( this ).closest( '.health-check-modal' ) );
	} );

	$( '.health-check-modal' ).on( 'submit', 'form', function( e ) {
		const data = $( this ).serializeArray(),
			modal = $( this ).closest( '.health-check-modal' );

		e.preventDefault();

		$.post(
			ajaxurl,
			data,
			function( response ) {
				if ( true === response.success ) {
					$( modal.data( 'parent-field' ) ).append( response.data.message );
				} else {
					healthCheckFailureModal( response.data.message, data.action, modal.data( 'parent-field' ) );
				}
			}
		);

		healthCheckFailureModalClose( modal );
	} );
} );
