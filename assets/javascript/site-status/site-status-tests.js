/* global ajaxurl, HealthCheck, wp */
jQuery( document ).ready(function( $ ) {
	var data;

	$( '.site-health-view-passed' ).on( 'click', function() {
		var goodIssuesWrapper = $( '#health-check-issues-good' );

		goodIssuesWrapper.toggleClass( 'hidden' );
		$( this ).attr( 'aria-expanded', ! goodIssuesWrapper.hasClass( 'hidden' ) );
	});

	function HCAppendIssue( issue ) {
		var htmlOutput,
			issueWrapper,
			issueCounter;

		HealthCheck.site_status.issues[ issue.status ]++;

		issueWrapper = $( '#health-check-issues-' + issue.status );

		issueCounter = $( '.issue-count', issueWrapper );

		htmlOutput = '<dt role="heading" aria-level="4">\n' +
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
		var r, c, pct;
		var $progressBar = $( '#progressbar' );
		var $circle = $( '#progressbar svg #bar' );
		var totalTests = parseInt( HealthCheck.site_status.issues.good, 0 ) + parseInt( HealthCheck.site_status.issues.recommended, 0 ) + ( parseInt( HealthCheck.site_status.issues.critical, 0 ) * 1.5 );
		var failedTests = parseInt( HealthCheck.site_status.issues.recommended, 0 ) + ( parseInt( HealthCheck.site_status.issues.critical, 0 ) * 1.5 );
		var val = 100 - Math.ceil( ( failedTests / totalTests ) * 100 );

		if ( 0 === totalTests ) {
			$progressBar.addClass( 'hidden' );
			return;
		}

		$progressBar.removeClass( 'loading' );

		if ( isNaN( val ) ) {
			val = 100;
		}

		r = $circle.attr( 'r' );
		c = Math.PI * ( r * 2 );

		if ( val < 0 ) {
			val = 0;
		}
		if ( val > 100 ) {
			val = 100;
		}

		pct = ( ( 100 - val ) / 100 ) * c;

		$circle.css( { strokeDashoffset: pct } );

		if ( parseInt( HealthCheck.site_status.issues.critical, 0 ) < 1 ) {
			$( '#health-check-issues-critical' ).addClass( 'hidden' );
		}

		if ( parseInt( HealthCheck.site_status.issues.recommended, 0 ) < 1 ) {
			$( '#health-check-issues-recommended' ).addClass( 'hidden' );
		}

		if ( val >= 50 ) {
			$circle.addClass( 'orange' ).removeClass( 'red' );
		}

		if ( val >= 90 ) {
			$circle.addClass( 'green' ).removeClass( 'orange' );
		}

		$progressBar.attr( 'data-pct', val );
		$progressBar.attr( 'aria-valuenow', val );

		$( '.health-check-body' ).attr( 'aria-hidden', false );

		$.post(
			ajaxurl,
			{
				'action': 'health-check-site-status-result',
				'_wpnonce': HealthCheck.nonce.site_status_result,
				'counts': HealthCheck.site_status.issues
			}
		);

		wp.a11y.speak( HealthCheck.string.site_health_complete_screen_reader.replace( '%s', val + '%' ), 'polite' );
	}

	function maybeRunNextAsyncTest() {
		var doCalculation = true;

		if ( HealthCheck.site_status.async.length >= 1 ) {
			$.each( HealthCheck.site_status.async, function() {
				var data = {
					'action': 'health-check-site-status',
					'feature': this.test,
					'_wpnonce': HealthCheck.nonce.site_status
				};

				if ( this.completed ) {
					return true;
				}

				doCalculation = false;

				this.completed = true;

				$.post(
					ajaxurl,
					data,
					function( response ) {
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

	if ( 'undefined' !== typeof HealthCheck ) {
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
			});
		}

		if ( HealthCheck.site_status.async.length > 0 ) {
			data = {
				'action': 'health-check-site-status',
				'feature': HealthCheck.site_status.async[0].test,
				'_wpnonce': HealthCheck.nonce.site_status
			};

			HealthCheck.site_status.async[0].completed = true;

			$.post(
				ajaxurl,
				data,
				function( response ) {
					HCAppendIssue( response.data );
					maybeRunNextAsyncTest();
				}
			);
		}
	}
});
