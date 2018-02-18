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

/*
 * Remove any user meta entries we made, done with a custom query as core
 * does not provide an option to clear them for all users.
 */
$wpdb->delete(
	$wpdb->usermeta,
	array(
		'meta_key' => 'health-check'
	)
);

// Remove the Must-Use plugin if it was implemented.
if ( file_exists( trailingslashit( WPMU_PLUGIN_DIR ) . 'health-check-disable-plugins.php' ) ) {
	unlink( trailingslashit( WPMU_PLUGIN_DIR ) . 'health-check-disable-plugins.php' );
}
