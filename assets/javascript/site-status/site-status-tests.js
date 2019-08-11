/* global ajaxurl, SiteHealth */
jQuery( document ).ready( function( $ ) {
	let data;
	const isDebugTab = $( '.health-check-debug-tab.active' ).length;

	$( '.site-health-view-passed' ).on( 'click', function() {
		const goodIssuesWrapper = $( '#health-check-issues-good' );

		goodIssuesWrapper.toggleClass( 'hidden' );
		$( this ).attr( 'aria-expanded', ! goodIssuesWrapper.hasClass( 'hidden' ) );
	} );

	function AppendIssue( issue ) {
		const template = wp.template( 'health-check-issue' ),
			issueWrapper = $( '#health-check-issues-' + issue.status );

		let heading;

		SiteHealth.site_status.issues[ issue.status ]++;

		const count = SiteHealth.site_status.issues[ issue.status ];

		if ( 'critical' === issue.status ) {
			if ( count <= 1 ) {
				heading = SiteHealth.string.site_info_heading_critical_single.replace( '%s', '<span class="issue-count">' + count + '</span>' );
			} else {
				heading = SiteHealth.string.site_info_heading_critical_plural.replace( '%s', '<span class="issue-count">' + count + '</span>' );
			}
		} else if ( 'recommended' === issue.status ) {
			if ( count <= 1 ) {
				heading = SiteHealth.string.site_info_heading_recommended_single.replace( '%s', '<span class="issue-count">' + count + '</span>' );
			} else {
				heading = SiteHealth.string.site_info_heading_recommended_plural.replace( '%s', '<span class="issue-count">' + count + '</span>' );
			}
		} else if ( 'good' === issue.status ) {
			if ( count <= 1 ) {
				heading = SiteHealth.string.site_info_heading_good_single.replace( '%s', '<span class="issue-count">' + count + '</span>' );
			} else {
				heading = SiteHealth.string.site_info_heading_good_plural.replace( '%s', '<span class="issue-count">' + count + '</span>' );
			}
		}

		if ( heading ) {
			$( '.site-health-issue-count-title', issueWrapper ).html( heading );
		}

		$( '.issues', '#health-check-issues-' + issue.status ).append( template( issue ) );
	}

	function RecalculateProgression() {
		const $progress = $( '.site-health-progress' );
		const $wrapper = $progress.closest( '.site-health-progress-wrapper' );
		const $progressLabel = $( '.site-health-progress-label', $wrapper );
		const $circle = $( '.site-health-progress svg #bar' );
		const totalTests = parseInt( SiteHealth.site_status.issues.good, 0 ) + parseInt( SiteHealth.site_status.issues.recommended, 0 ) + ( parseInt( SiteHealth.site_status.issues.critical, 0 ) * 1.5 );
		const failedTests = ( parseInt( SiteHealth.site_status.issues.recommended, 0 ) * 0.5 ) + ( parseInt( SiteHealth.site_status.issues.critical, 0 ) * 1.5 );
		let val = 100 - Math.ceil( ( failedTests / totalTests ) * 100 );

		if ( 0 === totalTests ) {
			$progress.addClass( 'hidden' );
			return;
		}

		$wrapper.removeClass( 'loading' );

		const r = $circle.attr( 'r' );
		const c = Math.PI * ( r * 2 );

		if ( 0 > val ) {
			val = 0;
		}
		if ( 100 < val ) {
			val = 100;
		}

		const pct = ( ( 100 - val ) / 100 ) * c;

		$circle.css( { strokeDashoffset: pct } );

		if ( 1 > parseInt( SiteHealth.site_status.issues.critical, 0 ) ) {
			$( '#health-check-issues-critical' ).addClass( 'hidden' );
		}

		if ( 1 > parseInt( SiteHealth.site_status.issues.recommended, 0 ) ) {
			$( '#health-check-issues-recommended' ).addClass( 'hidden' );
		}

		if ( ! isDebugTab ) {
			$.post(
				ajaxurl,
				{
					action: 'health-check-site-status-result',
					_wpnonce: SiteHealth.nonce.site_status_result,
					counts: SiteHealth.site_status.issues,
				}
			);
		}

		if ( 80 <= val && 0 === parseInt( SiteHealth.site_status.issues.critical, 0 ) ) {
			$wrapper.addClass( 'green' ).removeClass( 'orange' );

			$progressLabel.text( SiteHealth.string.site_health_complete_pass );
			wp.a11y.speak( SiteHealth.string.site_health_complete_pass_sr );
		} else {
			$wrapper.addClass( 'orange' ).removeClass( 'green' );

			$progressLabel.text( SiteHealth.string.site_health_complete_fail );
			wp.a11y.speak( SiteHealth.string.site_health_complete_fail_sr );
		}

		if ( 100 === val ) {
			$( '.site-status-all-clear' ).removeClass( 'hide' );
			$( '.site-status-has-issues' ).addClass( 'hide' );
		}
	}

	function maybeRunNextAsyncTest() {
		let doCalculation = true;

		if ( 1 <= SiteHealth.site_status.async.length ) {
			$.each( SiteHealth.site_status.async, function() {
				data = {
					action: 'health-check-site-status',
					feature: this.test,
					_wpnonce: SiteHealth.nonce.site_status,
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
						if ( typeof wp.hooks !== 'undefined' ) {
							AppendIssue( wp.hooks.applyFilters( 'site_status_test_result', response.data ) );
						} else {
							AppendIssue( response.data );
						}
						maybeRunNextAsyncTest();
					}
				);

				return false;
			} );
		}

		if ( doCalculation ) {
			RecalculateProgression();
		}
	}

	if ( 'undefined' !== typeof SiteHealth ) {
		if ( 0 === SiteHealth.site_status.direct.length && 0 === SiteHealth.site_status.async.length ) {
			RecalculateProgression();
		} else {
			SiteHealth.site_status.issues = {
				good: 0,
				recommended: 0,
				critical: 0,
			};
		}

		if ( 0 < SiteHealth.site_status.direct.length ) {
			$.each( SiteHealth.site_status.direct, function() {
				AppendIssue( this );
			} );
		}

		if ( 0 < SiteHealth.site_status.async.length ) {
			data = {
				action: 'health-check-site-status',
				feature: SiteHealth.site_status.async[ 0 ].test,
				_wpnonce: SiteHealth.nonce.site_status,
			};

			SiteHealth.site_status.async[ 0 ].completed = true;

			$.post(
				ajaxurl,
				data,
				function( response ) {
					AppendIssue( response.data );
					maybeRunNextAsyncTest();
				}
			);
		} else {
			RecalculateProgression();
		}
	}
} );
