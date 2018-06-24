/* global HealthCheck */
jQuery( document ).ready(function( $ ) {
	$( '.health-check-copy-field' ).click(function( e ) {
		var $textarea = $( 'textarea', $( this ).closest( 'div' ) ),
			$button   = $( this ),
			copied    = false;

		e.preventDefault();

		$textarea.select();

		copied = document.execCommand( 'copy' );
		if ( copied ) {
			$button.text( HealthCheck.string.copied );
		}
	});
});
