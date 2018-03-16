/* global HealthCheck, ajaxurl */
jQuery( document ).ready(function( $ ) {
	function healthCheckFailureModal( markup, action, parent ) {
		$( '#dynamic-content' ).html( markup );
		$( '.health-check-modal' ).data( 'modal-action', action ).data( 'parent-field', parent ).show();
	}

	function healthCheckFailureModalClose( modal ) {
		modal.hide();
	}

	$( '.modal-close' ).click(function( e ) {
		e.preventDefault();
		healthCheckFailureModalClose( $( this ).closest( '.health-check-modal' ) );
	});

	$( '.health-check-toc' ).click(function( e ) {

		// Remove the height of the admin bar, and an extra 10px for better positioning.
		var offset = $( $( this ).attr( 'href' ) ).offset().top - $( '#wpadminbar' ).height() - 10;

		e.preventDefault();

		$( 'html, body' ).animate({
			scrollTop: offset
		}, 1200 );
	});

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

	$( '.health-check-modal' ).on( 'submit', 'form', function( e ) {
		var data = $( this ).serializeArray(),
			modal = $( this ).closest( '.health-check-modal' );

		e.preventDefault();

		$.post(
			ajaxurl,
			data,
			function( response ) {
				if ( true === response.success ) {
					$( modal.data( 'parent-field' ) ).append( response.data.message );
				} else {
					healthCheckFailureModal( response.data.message, data.action, modal.data( 'parent-field' ) );
				}
			}
		);

		healthCheckFailureModalClose( modal );
	});

	$( '.health-check-copy-field' ).click(function( e ) {
		var $textarea = $( 'textarea', $( this ).closest( 'div' ) ),
			$button   = $( this ),
			copied    = false;

		e.preventDefault();

		$textarea.select();

		copied = document.execCommand( 'copy' );
		if ( copied ) {
			$button.text( HealthCheck.string.copied );
		}
	});

	$( '#health-check-file-integrity' ).submit( function( e ) {
		var data = {
			'action': 'health-check-files-integrity-check'
		};

		e.preventDefault();

		$( '#tools-file-integrity-response-holder' ).html( '<span class="spinner"></span>' );
		$( '#tools-file-integrity-response-holder .spinner' ).addClass( 'is-active' );

		$.post(
			ajaxurl,
			data,
			function( response ) {
				$( '#tools-file-integrity-response-holder .spinner' ).removeClass( 'is-active' );
				$( '#tools-file-integrity-response-holder' ).parent().css( 'height', 'auto' );
				$( '#tools-file-integrity-response-holder' ).html( response.data.message );
		});
	});

	$( '#health-check-mail-check' ).submit( function( e ) {
		var email = $( '#health-check-mail-check #email' ).val(),
			data;

		e.preventDefault();

		$( '#tools-mail-check-response-holder' ).html( '<span class="spinner"></span>' );
		$( '#tools-mail-check-response-holder .spinner' ).addClass( 'is-active' );

		data = {
			'action': 'health-check-mail-check',
			'email': email
		};

		$.post(
			ajaxurl,
			data,
			function( response ) {
				$( '#tools-mail-check-response-holder .spinner' ).removeClass( 'is-active' );
				$( '#tools-mail-check-response-holder' ).parent().css( 'height', 'auto' );
				$( '#tools-mail-check-response-holder' ).html( response.data.message );
		});
	});

	$( '#tools-file-integrity-response-holder' ).on( 'click', 'a[href="#health-check-diff"]', function( e ) {
		var file = $( this ).data( 'file' ),
			data;

		e.preventDefault();

		$( '#health-check-diff-modal' ).toggle();
		$( '#health-check-diff-modal #health-check-diff-modal-content .spinner' ).addClass( 'is-active' );

		data = {
			'action': 'health-check-view-file-diff',
			'file': file
		};

		$.post(
			ajaxurl,
			data,
			function( response ) {
				$( '#health-check-diff-modal #health-check-diff-modal-diff' ).html( response.data.message );
				$( '#health-check-diff-modal #health-check-diff-modal-content h3' ).html( file );
				$( '#health-check-diff-modal #health-check-diff-modal-content .spinner' ).removeClass( 'is-active' );
		});
	});

	$( '#health-check-diff-modal' ).on( 'click', 'a[href="#health-check-diff-modal-close"]', function( e ) {
		e.preventDefault();
		$( '#health-check-diff-modal' ).toggle();
        $( '#health-check-diff-modal #health-check-diff-modal-diff' ).html( '' );
        $( '#health-check-diff-modal #health-check-diff-modal-content h3' ).html( '' );
	});

	$( document ).keyup(function( e ) {
		if ( 27 === e.which  ) {
			$( '#health-check-diff-modal' ).css( 'display', 'none' );
			$( '#health-check-diff-modal #health-check-diff-modal-diff' ).html( '' );
			$( '#health-check-diff-modal #health-check-diff-modal-content h3' ).html( '' );
		}
	});

});
