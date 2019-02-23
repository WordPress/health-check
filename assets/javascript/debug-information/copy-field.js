/* global HealthCheck, wp */
jQuery( document ).ready(function( $ ) {
	$( '.health-check-copy-field' ).click(function( e ) {
		var $textarea = $( 'system-information-' + $( this ).data( 'copy-field' ) + '-copy-field' ),
			$wrapper = $( this ).closest( 'div' );

		e.preventDefault();

		$textarea.select();

		if ( document.execCommand( 'copy' ) ) {
			$( 'copy-field-success', $wrapper ).addClass( 'visible' );

			wp.a11y.speak( HealthCheck.string.site_info_copied, 'polite' );
		}
	});
});
