/* global HealthCheck, wp */
jQuery( document ).ready(function( $ ) {
	$( '.health-check-copy-field' ).click(function( e ) {
		var $textarea = $( '#system-information-' + $( this ).data( 'copy-field' ) + '-copy-field' ),
			$wrapper = $( this ).closest( 'div' );

		e.preventDefault();

		$textarea.select();

		if ( document.execCommand( 'copy' ) ) {
			$( '.copy-field-success', $wrapper ).addClass( 'visible' );
			$( this ).focus();

			wp.a11y.speak( HealthCheck.string.site_info_copied, 'polite' );
		}
	});

	$( '.health-check-toggle-copy-section' ).click(function( e ) {
		var $copySection = $( '.system-information-copy-wrapper' );

		e.preventDefault();

		if ( $copySection.hasClass( 'hidden' ) ) {
			$copySection.removeClass( 'hidden' );

			$( this ).text( HealthCheck.string.site_info_hide_copy );
		} else {
			$copySection.addClass( 'hidden' );

			$( this ).text( HealthCheck.string.site_info_show_copy );
		}
	});
});
