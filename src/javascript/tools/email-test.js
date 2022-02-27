/* global ajaxurl, HealthCheck */
jQuery( document ).ready( function( $ ) {
	$( '#health-check-mail-check' ).on( 'submit', function( e ) {
		const email = $( '#health-check-mail-check #email' ).val(),
			emailMessage = $( '#health-check-mail-check #email_message' ).val();

		e.preventDefault();

		$( '#tools-mail-check-response-holder' ).html( '<span class="spinner"></span>' );
		$( '#tools-mail-check-response-holder .spinner' ).addClass( 'is-active' );

		const data = {
			action: 'health-check-mail-check',
			email,
			email_message: emailMessage,
			_wpnonce: HealthCheck.nonce.mail_check,
		};

		$.post(
			ajaxurl,
			data,
			function( response ) {
				$( '#tools-mail-check-response-holder .spinner' ).removeClass( 'is-active' );
				$( '#tools-mail-check-response-holder' ).parent().css( 'height', 'auto' );
				$( '#tools-mail-check-response-holder' ).html( response.data.message );
			}
		);
	} );
} );
