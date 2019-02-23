/* global ajaxurl, HealthCheck, wp */
jQuery( document ).ready(function( $ ) {
	$( 'body.dashboard_page_health-check' ).on( 'click', '.site-health-view-passed', function( e ) {
		$( this ).hide();

		$( '#health-check-issues-good' ).show();
	});

	function HCAppendIssue( issue ) {
		var htmlOutput = '',
			issueWrapper,
			issueCounter;

		HealthCheck.site_status.issues[ issue.status ]++;

		issueWrapper = $( '#health-check-issues-' + issue.status );

		issueCounter = $( '.issue-count', issueWrapper );

		htmlOutput = '<dt role="heading" aria-level="2">\n' +
			'                <button aria-expanded="false" class="health-check-accordion-trigger" aria-controls="health-check-accordion-block-' + issue.test + '" id="health-check-accordion-heading-' + issue.test + '" type="button">\n' +
			'                    <span class="title">\n' +
			'                        ' + issue.label + '\n' +
			'                    </span>\n' +
			'                    <span class="badge ' + issue.badge.color + '">' + issue.badge.label + '</span>\n' +
			'                    <span class="icon"></span>\n' +
			'                </button>\n' +
			'            </dt>\n' +
			'            <dd id="health-check-accordion-block-' + issue.test + '" aria-labelledby="health-check-accordion-heading-' + issue.test + '" role="region" class="health-check-accordion-panel" hidden="hidden">\n' +
			'                ' + issue.description + '\n' +
			'                <div class="actions"><p>' + issue.actions + '</p></div>' +
			'            </dd>';

		issueCounter.text( HealthCheck.site_status.issues[ issue.status ] );
		$( '.issues', '#health-check-issues-' + issue.status ).append( htmlOutput );
	}

	function HCRecalculateProgression() {
		var $progressBar = $( '#progressbar' );
		var $circle = $( '#progressbar svg #bar' );
		var total_tests = parseInt( HealthCheck.site_status.issues.good ) + parseInt( HealthCheck.site_status.issues.recommended ) + ( parseInt( HealthCheck.site_status.issues.critical ) * 1.5 );
		var failed_tests = parseInt( HealthCheck.site_status.issues.recommended ) + ( parseInt( HealthCheck.site_status.issues.critical ) * 1.5 );
		var val = 100 - Math.ceil( ( failed_tests / total_tests ) * 100 );

		if ( 0 === total_tests ) {
			$progressBar.addClass( 'hidden' );
			return;
		}

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

		$progressBar.attr( 'data-pct', val );

		$.post(
			ajaxurl,
			{
				'action': 'health-check-site-status-result',
				'_wpnonce': HealthCheck.nonce.site_status_result,
				'counts': HealthCheck.site_status.issues
			}
		);

		wp.a11y.speak( HealthCheck.string.site_healt_complete, 'polite' );
	}

	function maybeRunNextAsyncTest() {
		var doCalculation = true;

		if ( HealthCheck.site_status.async.length >= 1 ) {
			$.each( HealthCheck.site_status.async, function() {
				if ( this.completed ) {
					return true;
				}

				doCalculation = false;

				this.completed = true;

				var data = {
					'action': 'health-check-site-status',
					'feature': this.test,
					'_wpnonce': HealthCheck.nonce.site_status
				};

				$.post(
					ajaxurl,
					data,
					function ( response ) {
						HCAppendIssue( response.data );
						maybeRunNextAsyncTest();
					}
				);

				return false;
			} );
		}

		if ( doCalculation ) {
			HCRecalculateProgression();
		}
	}


	if ( 0 === HealthCheck.site_status.direct.length && 0 === HealthCheck.site_status.async.length ) {
		HCRecalculateProgression();
	} else {
		HealthCheck.site_status.issues = {
			'good': 0,
			'recommended': 0,
			'critical': 0
		};
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
