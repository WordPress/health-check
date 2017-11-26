<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

delete_option( 'health-check-disable-plugin-hash' );

if ( file_exists( trailingslashit( WPMU_PLUGIN_DIR ) . 'health-check-disable-plugins.php' ) ) {
	unlink( trailingslashit( WPMU_PLUGIN_DIR ) . 'health-check-disable-plugins.php' );
}