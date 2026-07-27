<?php
/**
 * PHPUnit bootstrap file
 *
 * @package Health_Check
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

// Set up some server variables if they're missing
if ( ! isset( $_SERVER['REMOTE_ADDR'] ) ) {
	$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	// Load in our MU plugin first
	require dirname( __DIR__, 2 ) . '/src/php/assets/mu-plugin/health-check-troubleshooting-mode.php';

	require dirname( __DIR__, 2 ) . '/src/php/health-check.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';
