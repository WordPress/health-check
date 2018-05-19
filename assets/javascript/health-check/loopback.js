/* global HealthCheck, ajaxurl, healthCheckFailureModal */
jQuery( document ).ready(function( $ ) {
	$( '#loopback-no-plugins' ).click(function( e ) {
		var $trigger = $( this ),
			$parent = $( this ).closest( 'td' ),
			data = {
				action: 'health-check-loopback-no-plugins'
			};

		e.preventDefault();

		$( this ).html( '<span class="spinner" style="visibility: visible;"></span> ' + HealthCheck.string.please_wait );

		$.post(
			ajaxurl,
			data,
			function( response ) {
				$trigger.remove();
				if ( true === response.success ) {
					$parent.append( response.data.message );
				} else {
					healthCheckFailureModal( response.data, data.action, $parent );
				}
			},
			'json'
		);
	});

	$( '.dashboard_page_health-check' ).on( 'click', '#loopback-individual-plugins', function( e ) {
		var $trigger = $( this ),
			$parent = $( this ).closest( 'td' ),
			data = {
				action: 'health-check-loopback-individual-plugins'
			};

		e.preventDefault();

		$( this ).html( '<span class="spinner" style="visibility: visible;"></span> ' + HealthCheck.string.please_wait );

		$.post(
			ajaxurl,
			data,
			function( response ) {
				$trigger.remove();
				if ( true === response.success ) {
					$parent.append( response.data.message );
				} else {
					healthCheckFailureModal( response.data, data.action, $parent );
				}
			},
			'json'
		);
	});
});
