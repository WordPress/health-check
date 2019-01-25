/* global HealthCheck, ajaxurl, healthCheckFailureModal */
jQuery( document ).ready(function( $ ) {
	function testDefaultTheme() {
		var $parent = $( '.individual-loopback-test-status', '#test-single-no-theme' ),
			data = {
				'action': 'health-check-loopback-default-theme',
				'_wpnonce': HealthCheck.nonce.loopback_default_theme
			};

		$.post(
			ajaxurl,
			data,
			function( response ) {
				if ( true === response.success ) {
					$parent.html( response.data.message );
				} else {
					healthCheckFailureModal( response.data, data.action, $parent );
				}
			},
			'json'
		);
	}

	function testSinglePlugin() {
		var $testLines = $( '.not-tested', '#loopback-individual-plugins-list' );
		var $parentField,
			$testLine,
			data;

		if ( $testLines.length < 1 ) {
			testDefaultTheme();
			return null;
		}

		$testLine = $testLines.first();
		data = {
			'action': 'health-check-loopback-individual-plugins',
			'plugin': $testLine.data( 'test-plugin' ),
			'_wpnonce': HealthCheck.nonce.loopback_individual_plugins
		};

		$parentField = $( '.individual-loopback-test-status', $testLine );

		$parentField.html( HealthCheck.string.running_tests );

		$.post(
			ajaxurl,
			data,
			function( response ) {
				if ( true === response.success ) {
					$testLine.removeClass( 'not-tested' );
					$parentField.html( response.data.message );
					testSinglePlugin();
				} else {
					healthCheckFailureModal( response.data, data.action, $parentField );
				}
			},
			'json'
		);
	}

	$( '.dashboard_page_health-check' ).on( 'click', '#loopback-no-plugins', function( e ) {
		var $trigger = $( this ),
			$parent = $( this ).closest( 'td' ),
			data = {
				'action': 'health-check-loopback-no-plugins',
				'_wpnonce': HealthCheck.nonce.loopback_no_plugins
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
	}).on( 'click', '#loopback-individual-plugins', function( e ) {
		e.preventDefault();

		testSinglePlugin();
	});
});
