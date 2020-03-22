<?php
/**
 * Perform plugin installation routines.
 *
 * @package Health Check
 */

global $wpdb;

// Make sure the uninstall file can't be accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

// Remove options introduced by the plugin.
delete_option( 'health-check-disable-plugin-hash' );
delete_option( 'health-check-default-theme' );
delete_option( 'health-check-current-theme' );
delete_option( 'health-check-dashboard-notices' );

/*
 * Remove any user meta entries we made, done with a custom query as core
 * does not provide an option to clear them for all users.
 */
$wpdb->delete(
	$wpdb->usermeta,
	array(
		'meta_key' => 'health-check',
	)
);

// Remove any transients and similar which the plugin may have left behind.
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE `option_name` LIKE '_transient_%_health-check%'" );

// Remove the old Must-Use plugin if it was implemented.
if ( file_exists( trailingslashit( WPMU_PLUGIN_DIR ) . 'health-check-disable-plugins.php' ) ) {
	wp_delete_file( trailingslashit( WPMU_PLUGIN_DIR ) . 'health-check-disable-plugins.php' );
}

// Remove the renamed Must-Use plugin if it exists.
if ( file_exists( trailingslashit( WPMU_PLUGIN_DIR ) . 'health-check-troubleshooting-mode.php' ) ) {
	wp_delete_file( trailingslashit( WPMU_PLUGIN_DIR ) . 'health-check-troubleshooting-mode.php' );
}
