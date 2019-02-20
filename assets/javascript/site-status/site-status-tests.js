/* global ajaxurl, HealthCheck */
jQuery( document ).ready(function( $ ) {
	var htmlOutput = '',
		issueWrapper,
		issueCounter,
		issueCount;

	$( 'body.dashboard_page_health-check' ).on( 'click', '.toggle-more-info', function( e ) {
		e.preventDefault();

		$( this ).closest( '.issue' ).toggleClass( 'open' );
	});

	function HCAppendIssue( issue ) {
		issueWrapper = $( '#health-check-issues-' + issue.status );

		issueCounter = $( '.issue-count', issueWrapper );
		issueCount = parseInt( issueCounter.text(), 10 );

		issueCount++;

		htmlOutput = '<div class="issue">\n' +
			'                <h3>' + issue.label + '</h3>' +
			'                <span class="badge ' + issue.badge.color + '">' + issue.badge.label + '</span>' +
			'                <button type="button" class="button-link toggle-more-info">\n' +
			'                    More info\n' +
			'                    <span class="icon"></span>\n' +
			'                </button>\n' +
			'                <div class="more-info">\n' +
			'                    ' + issue.description + '\n' +
			'                    <div class="actions">' + issue.actions + '</div>' +
			'                </div>\n' +
			'            </div>';

		issueCounter.text( issueCount );
		$( '.issues', '#health-check-issues-' + issue.status ).append( htmlOutput );
	}

	function HCRecalculateProgression() {
		var $progressBar = $( '#progressbar' );
		var $circle = $( '#progressbar svg #bar' );
		var total_tests = parseInt( HealthCheck.site_status.direct.length + HealthCheck.site_status.async.length );
		var passed_tests = parseInt( $( '.issue-count', '#health-check-issues-good' ).text() );
		var val = Math.ceil( ( passed_tests / total_tests ) * 100 );

		$progressBar.removeClass( 'loading' );

		if ( isNaN( val ) ) {
			val = 100;
		}

		var r = $circle.attr( 'r' );
		var c = Math.PI * ( r * 2 );

		if ( val < 0 ) { val = 0;}
		if ( val > 100 ) { val = 100;}

		var pct = ( ( 100 - val ) / 100 ) * c;

		$circle.css( { strokeDashoffset: pct } );

		if ( val >= 50 ) {
			$circle.addClass( 'orange' ).removeClass( 'red' );
		}

		if ( val >= 90 ) {
			$circle.addClass( 'green' ).removeClass( 'orange' );
		}

		$( '#progressbar' ).attr( 'data-pct', val );
	}

	function maybeRunNextAsyncTest() {
		if ( HealthCheck.site_status.async.length >= 1 ) {
			var check = HealthCheck.site_status.async[0];
			HealthCheck.site_status.async.shift();

			var data = {
				'action': 'health-check-site-status',
				'feature': check.test,
				'_wpnonce': HealthCheck.nonce.site_status
			};

			$.post(
				ajaxurl,
				data,
				function( response ) {
					HCAppendIssue( response.data );
					maybeRunNextAsyncTest();
				}
			);

			return true;
		}

		HCRecalculateProgression();

		return false;
	}

	if ( HealthCheck.site_status.direct.length > 0 ) {
		$.each( HealthCheck.site_status.direct, function() {
			HCAppendIssue( this );
		} );
	}

	if ( HealthCheck.site_status.async.length > 0 ) {
		HealthCheck.site_status.async[0].completed = true;

		var data = {
			'action': 'health-check-site-status',
			'feature': HealthCheck.site_status.async[0].test,
			'_wpnonce': HealthCheck.nonce.site_status
		};

		$.post(
			ajaxurl,
			data,
			function( response ) {
				HCAppendIssue( response.data );
				maybeRunNextAsyncTest();
			}
		);
	}
});
