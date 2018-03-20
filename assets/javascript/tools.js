/* global ajaxurl */
jQuery( document ).ready(function( $ ) {
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
			emailMessage = $( '#health-check-mail-check #email_message' ).val(),
			data;

		e.preventDefault();

		$( '#tools-mail-check-response-holder' ).html( '<span class="spinner"></span>' );
		$( '#tools-mail-check-response-holder .spinner' ).addClass( 'is-active' );

		data = {
			'action': 'health-check-mail-check',
			'email': email,
			'email_message': emailMessage
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

	$( '#health-check-enable-wp-debug' ).submit( function( e ) {
		var data;

		e.preventDefault();

		$( '#tools-enable-wp-debug-response-holder' ).html( '<span class="spinner"></span>' );
		$( '#tools-enable-wp-debug-response-holder .spinner' ).addClass( 'is-active' );

		data = {
			'action': 'health-check-wp-debug'
		};

		$.post(
			ajaxurl,
			data,
			function( response ) {
				if ( response.data.status === 'success' ) {
					location.reload();
				} else if ( response.data.status === 'error' ) {
					$( '#tools-enable-wp-debug-response-holder .spinner' ).removeClass( 'is-active' );
					$( '#tools-enable-wp-debug-response-holder' ).parent().css( 'height', 'auto' );
					$( '#tools-enable-wp-debug-response-holder' ).html( response.data.message );
				}
			});
	});

	$( '#health-check-disable-wp-debug' ).submit( function( e ) {
		var data;

		e.preventDefault();

		$( '#tools-disable-wp-debug-response-holder' ).html( '<span class="spinner"></span>' );
		$( '#tools-disable-wp-debug-response-holder .spinner' ).addClass( 'is-active' );

		data = {
			'action': 'health-check-wp-debug'
		};

		$.post(
			ajaxurl,
			data,
			function( response ) {
				if ( response.data.status === 'success' ) {
					location.reload();
				} else if ( response.data.status === 'error' ) {
					$( '#tools-disable-wp-debug-response-holder .spinner' ).removeClass( 'is-active' );
					$( '#tools-disable-wp-debug-response-holder' ).parent().css( 'height', 'auto' );
					$( '#tools-disable-wp-debug-response-holder' ).html( response.data.message );
				}
			});
	});

	if ( $( '#tools-wp-debug-output' ).length ){
        console.log( 'debug output textarea found' );
    }

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
