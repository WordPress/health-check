/* global HealthCheck, ajaxurl, healthCheckFailureModal */
jQuery( document ).ready(function( $ ) {
	function testDefaultTheme() {
		var $parent = $( '.individual-loopback-test-status', '#test-single-no-theme' ),
			data = {
				action: 'health-check-loopback-default-theme'
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
		var $test_lines = $( '.not-tested', '#loopback-individual-plugins-list' );
		var $parent_field,
			$test_line,
			data;

		if ( $test_lines.length < 1 ) {
			testDefaultTheme();
			return null;
		}

		$test_line = $test_lines.first();
		data = {
			action: 'health-check-loopback-individual-plugins',
			plugin: $test_line.data( 'test-plugin' )
		};

		$parent_field = $( '.individual-loopback-test-status', $test_line )M

		$parent_field.html( HealthCheck.string.running_tests );

		$.post(
			ajaxurl,
			data,
			function( response ) {
				if ( true === response.success ) {
					$test_line.removeClass( 'not-tested' );
					$parent_field.html( response.data.message );
					testSinglePlugin();
				} else {
					healthCheckFailureModal( response.data, data.action, $parent_field );
				}
			},
			'json'
		);
	}

	$( '.dashboard_page_health-check' ).on( 'click', '#loopback-no-plugins', function( e ) {
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
	}).on( 'click', '#loopback-individual-plugins', function( e ) {
		e.preventDefault();

		testSinglePlugin();
	});
});
