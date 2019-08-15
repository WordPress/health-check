/* global ajaxurl, SiteHealth */
jQuery( document ).ready( function( $ ) {
	$( '#health-check-tool-plugin-compat' ).click( function() {
		$( 'tr', '#health-check-tool-plugin-compat-list' ).data( 'plugin-checked', false );
		$( '.spinner', '#health-check-tool-plugin-compat-list' ).addClass( 'is-active' );

		$( this ).attr( 'disabled', true );

		HealthCheckToolsPluginCompatTest();
	} );

	function HealthCheckToolsPluginCompatTest() {
		const $plugins = $( '[data-plugin-checked="false"]', '#health-check-tool-plugin-compat-list' );

		if ( $plugins.length <= 0 ) {
			return;
		}

		const $nextPlugin = $( $plugins[ 0 ] );

		$nextPlugin.attr( 'data-plugin-checked', 'true' );

		const data = {
			action: 'health-check-tools-plugin-compat',
			slug: $nextPlugin.data( 'plugin-slug' ),
			version: $nextPlugin.data( 'plugin-version' ),
			_wpnonce: SiteHealth.nonce.tools_plugin_compat,
		};

		$.post(
			ajaxurl,
			data,
			function( response ) {
				$( '.spinner', $nextPlugin ).removeClass( 'is-active' );
				$( '.supported-version', $nextPlugin ).append( response.data.version );

				HealthCheckToolsPluginCompatTest();
			}
		);
	}
} );
