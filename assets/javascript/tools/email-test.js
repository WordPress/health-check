/* global ajaxurl */
jQuery( document ).ready(function( $ ) {
	$( '#health-check-mail-check' ).submit( function( e ) {
		var email = $( '#health-check-mail-check #email' ).val(),
			emailMessage = $( '#health-check-mail-check #email_message' ).val(),
			data;

		e.preventDefault();

		$( '#tools-mail-check-response-holder' ).html( '<span class="spinner"></span>' );
		$( '#tools-mail-check-response-holder .spinner' ).addClass( 'is-active' );

		data = {
			'action': 'health-check-mail-check',
			'email': email,
			'email_message': emailMessage
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
	});
});
