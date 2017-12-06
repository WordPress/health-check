<?php
/**
 * Perform plugin installation routines.
 *
 * @package Health Check
 */

// Make sure the uninstall file can't be accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

// Remove options introduced by the plugin.
delete_option( 'health-check-disable-plugin-hash' );

// Remove the Must-Use plugin if it was implemented.
if ( file_exists( trailingslashit( WPMU_PLUGIN_DIR ) . 'health-check-disable-plugins.php' ) ) {
	unlink( trailingslashit( WPMU_PLUGIN_DIR ) . 'health-check-disable-plugins.php' );
}
