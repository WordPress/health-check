<?php
/**
 * Plugins primary file, in charge of including all other dependencies.
 *
 * @package Health Check
 *
 * @wordpress-plugin
 * Plugin Name: Health Check & Troubleshooting
 * Plugin URI: https://wordpress.org/plugins/health-check/
 * Description: Checks the health of your WordPress install.
 * Author: The WordPress.org community
 * Version: 1.7.0
 * Author URI: https://wordpress.org/plugins/health-check/
 * Text Domain: health-check
 */

namespace HealthCheck;

// Check that the file is not accessed directly.
use Health_Check;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'We\'re sorry, but you can not directly access this file.' );
}

// Set the plugin file.
define( 'HEALTH_CHECK_PLUGIN_FILE', __FILE__ );

// Set the absolute path for the plugin.
define( 'HEALTH_CHECK_PLUGIN_DIRECTORY', plugin_dir_path( __FILE__ ) );

// Set the plugin URL root.
define( 'HEALTH_CHECK_PLUGIN_URL', plugins_url( '/', __FILE__ ) );

// Always include our compatibility file first.
require_once( dirname( __FILE__ ) . '/compat.php' );

// Backwards compatible pull in of extra resources
if ( ! class_exists( 'WP_Debug_Data' ) ) {
	$original_paths = array(
		'class-wp-site-health.php' => ABSPATH . '/wp-admin/includes/class-wp-site-health.php',
		'class-wp-debug-data.php'  => ABSPATH . '/wp-admin/includes/class-wp-debug-data.php',
	);

	foreach ( $original_paths as $filename => $original_path ) {
		if ( file_exists( $original_path ) ) {
			require_once $original_path;
		} else {
			require_once __DIR__ . '/HealthCheck/BackCompat/' . $filename;

			if ( ! defined( 'HEALTH_CHECK_BACKCOMPAT_LOADED' ) ) {
				define( 'HEALTH_CHECK_BACKCOMPAT_LOADED', true );
			}
		}
	}
}

add_action(
	'plugins_loaded',
	function() {
		// Include class-files used by our plugin.
		require_once( dirname( __FILE__ ) . '/HealthCheck/class-health-check.php' );
		require_once( dirname( __FILE__ ) . '/HealthCheck/class-health-check-loopback.php' );
		require_once( dirname( __FILE__ ) . '/HealthCheck/class-health-check-screenshots.php' );
		require_once( dirname( __FILE__ ) . '/HealthCheck/class-health-check-troubleshoot.php' );

		// Tools section.
		require_once( dirname( __FILE__ ) . '/HealthCheck/Tools/class-health-check-tool.php' );
		require_once( dirname( __FILE__ ) . '/HealthCheck/Tools/class-health-check-files-integrity.php' );
		require_once( dirname( __FILE__ ) . '/HealthCheck/Tools/class-health-check-mail-check.php' );
		require_once( dirname( __FILE__ ) . '/HealthCheck/Tools/class-health-check-debug-log-viewer.php' );
		require_once( dirname( __FILE__ ) . '/HealthCheck/Tools/class-health-check-plugin-compatibility.php' );
		require_once( dirname( __FILE__ ) . '/HealthCheck/Tools/class-health-check-phpinfo.php' );
		require_once( dirname( __FILE__ ) . '/HealthCheck/Tools/class-health-check-htaccess.php' );
		require_once( dirname( __FILE__ ) . '/HealthCheck/Tools/class-health-check-robotstxt.php' );
		require_once( dirname( __FILE__ ) . '/HealthCheck/Tools/class-health-check-beta-features.php' );

		// Initialize our plugin.
		new Health_Check();

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			require_once( dirname( __FILE__ ) . '/HealthCheck/class-cli.php' );
		}
	}
);
