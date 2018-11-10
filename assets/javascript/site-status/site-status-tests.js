/* global ajaxurl */
jQuery( document ).ready(function( $ ) {
	function runNextSiteStatusTest() {
		var $test = $( '.health-check-site-status-test' ),
			data;

		// If there are no more tests to run, stop processing.
		if ( $test.length < 1 ) {
			return;
		}

		$test = $test.first();

		data = {
			action: 'health-check-site-status',
			feature: $test.data( 'site-status' )
		};

		$test.removeClass( 'health-check-site-status-test' );

		$.post(
			ajaxurl,
			data,
			function( response ) {
				$test.html( response );
                $( document ).trigger( 'health-check:site-status-classification' );
				runNextSiteStatusTest();
			}
		);
	}

	runNextSiteStatusTest();
});
