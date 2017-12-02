<?php
/*
	Plugin Name: Health Check Disable Plugins
	Description: Conditionally disabled plugins on your site for a given session, used to rule out plugin interactions during troubleshooting.
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'We\'re sorry, but you can not directly access this file.' );
}

/**
 * Filter the plugins that are activated in WordPress.
 *
 * @param array $plugins An array of plugins marked as active.
 *
 * @return array
 */
function health_check_loopback_test_disable_plugins( $plugins ) {
	// Check if a session cookie to disable plugins has been set.
	if ( isset( $_COOKIE['health-check-disable-plugins'] ) ) {
		$_GET['health-check-disable-plugin-hash'] = $_COOKIE['health-check-disable-plugins'];
	}


	// If the disable hash isn't set, no need to interact with things.
	if ( ! isset( $_GET['health-check-disable-plugin-hash'] ) ) {
		return $plugins;
	}

	// If the plugin hash is not valid, we also break out
	$disable_hash = get_option( 'health-check-disable-plugin-hash', '' );
	if ( $disable_hash !== $_GET['health-check-disable-plugin-hash'] ) {
		return $plugins;
	}

	$allowed_plugins = array();

	// If we've received a comma-separated list of allowed plugins, we'll add them to the array of allowed plugins.
	if ( isset( $_GET['health-check-allowed-plugins'] ) ) {
		$allowed_plugins = explode( ',', $_GET['health-check-allowed-plugins'] );
	}

	foreach( $plugins as $plugin_no => $plugin_path ) {
		// Split up the plugin path, [0] is the slug and [1] holds the primary plugin file.
		$plugin_parts = explode( '/', $plugin_path );

		// We may want to allow individual, or groups of plugins, so introduce a skip-mechanic for those scenarios.
		if ( in_array( $plugin_parts[0], $allowed_plugins ) ) {
			continue;
		}

		// Remove the reference to this plugin.
		unset( $plugins[ $plugin_no ] );
	}

	// Return a possibly modified list of activated plugins.
	return $plugins;
}

add_filter( 'option_active_plugins', 'health_check_loopback_test_disable_plugins' );
add_filter( 'option_active_sitewide_plugins', 'health_check_loopback_test_disable_plugins' );

function health_check_troubleshoot_theme( $theme ) {
	// Check if a session cookie to disable plugins has been set.
	if ( isset( $_COOKIE['health-check-disable-plugins'] ) ) {
		$_GET['health-check-disable-plugin-hash'] = $_COOKIE['health-check-disable-plugins'];
	}


	// If the disable hash isn't set, no need to interact with things.
	if ( ! isset( $_GET['health-check-disable-plugin-hash'] ) ) {
		return $theme;
	}

	// If the plugin hash is not valid, we also break out
	$disable_hash = get_option( 'health-check-disable-plugin-hash', '' );
	if ( $disable_hash !== $_GET['health-check-disable-plugin-hash'] ) {
		return $theme;
	}

	$default_themes = array(
		'twentyseventeen',
		'twentysixteen',
		'twentyfifteen',
		'twentyfourteen',
		'twentythirteen',
		'twentytwelve',
		'twentyeleven',
		'twentyten'
	);

	foreach( $default_themes AS $default_theme ) {
		if ( is_dir( WP_CONTENT_DIR . '/themes/' . $default_theme ) ) {
			return $default_theme;
		}
	}

	return $theme;
}
add_filter( 'stylesheet', 'health_check_troubleshoot_theme' );
add_filter( 'template', 'health_check_troubleshoot_theme' );

function health_check_troubleshooter_mode_logout() {
	if ( isset( $_COOKIE['health-check-disable-plugins'] ) ) {
		unset( $_COOKIE['health-check-disable-plugins'] );
		setcookie( 'health-check-disable-plugins', null, 0, COOKIEPATH, COOKIE_DOMAIN );
	}
}

add_action( 'wp_logout', 'health_check_troubleshooter_mode_logout' );
