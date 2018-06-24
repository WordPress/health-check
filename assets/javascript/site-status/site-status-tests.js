/* global ajaxurl */
jQuery( document ).ready(function( $ ) {
	$( '.health-check-site-status-test' ).each( function() {
		var $check = $( this ),
			data = {
				action: 'health-check-site-status',
				feature: $( this ).data( 'site-status' )
			};

		$.post(
			ajaxurl,
			data,
			function( response ) {
				$check.html( response );
			}
		);
	});
});
