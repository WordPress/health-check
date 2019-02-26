/* global HealthCheckTroubleshootingModeL10n */
jQuery( document ).ready(function( $ ) {
	$( '.health-check-toggle-visibility' ).click(function() {
		var which = $( this ).data( 'element' ),
			$icon = $( this ).find( '.icon' ),
			$elements = $( '.toggle-visibility', $( '#' + which ).closest( '.welcome-panel-column' ) ),
			showAllText = HealthCheckTroubleshootingModeL10n.showAllPlugins,
			showFewerText = HealthCheckTroubleshootingModeL10n.showFewerPlugins,
			isExpanded = false;

		if ( 'health-check-themes' === which ) {
			showAllText = HealthCheckTroubleshootingModeL10n.showAllThemes;
			showFewerText = HealthCheckTroubleshootingModeL10n.showFewerThemes;
		}

		$elements.toggleClass( 'hidden' );
		$icon.toggleClass( 'icon-up' );
		isExpanded = $icon.hasClass( 'icon-up' );

		$( this )
			.attr( 'aria-expanded', isExpanded )
			.find( '.health-check-toggle-text' ).text( isExpanded ? showFewerText : showAllText );
	});
});
