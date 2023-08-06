/* global HealthCheckTools, ClipboardJS, setTimeout, clearTimeout */
import html2canvas from 'html2canvas';

jQuery( document ).ready( function( $ ) {
	const __ = wp.i18n.__,
		clipboard = new ClipboardJS( '.health-check-screenshot-embed' );

	let successTimeout;

	$( document ).on( 'click', '.health-check-take-screenshot', function( event ) {
		event.preventDefault();

		html2canvas( document.body ).then( function( canvas ) {
			// Store image to clipboard.
			/*
			canvas.toBlob( function( blob ) {
				navigator.clipboard.write( [
					new ClipboardItem(
						Object.defineProperty( {}, blob.type, {
							value: blob,
							enumerable: true
						} )
					)
				] );
			} );
			*/

			// Push the screenshot to the screenshot storage.
			$.ajax( {
				url: HealthCheckTools.rest.screenshot,
				method: 'POST',
				beforeSend: ( xhr ) => {
					xhr.setRequestHeader( 'X-WP-Nonce', HealthCheckTools.nonce.rest );
				},
				data: {
					screenshot: canvas.toDataURL( 'image/jpeg' ),
					label: document.title,
					nonce: HealthCheckTools.nonce.screenshot,
				},
			} );
		} );
	} );

	clipboard.on( 'success', function( event ) {
		const triggerElement = $( event.trigger ),
			promptElement = $( '.prompt', triggerElement.closest( 'div' ) ),
			successElement = $( '.success', triggerElement.closest( 'div' ) );

		// Clear the selection and move focus back to the trigger.
		event.clearSelection();
		// Handle ClipboardJS focus bug, see https://github.com/zenorocha/clipboard.js/issues/680
		triggerElement.trigger( 'focus' );

		// Show success visual feedback.
		clearTimeout( successTimeout );
		promptElement.addClass( 'hidden' );
		successElement.removeClass( 'hidden' );

		// Hide success visual feedback after 3 seconds since last success.
		successTimeout = setTimeout( function() {
			successElement.addClass( 'hidden' );
			promptElement.removeClass( 'hidden' );
		}, 2000 );

		// Handle success audible feedback.
		wp.a11y.speak( __( 'Site information has been copied to your clipboard.', 'health-check' ) );
	} );
} );
