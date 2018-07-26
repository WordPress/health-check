<?php
/**
 * Plugins primary file, in charge of including all other dependencies.
 *
 * @package Health Check
 *
 * @wordpress-plugin
 * Plugin Name: Health Check & Troubleshooting
 * Plugin URI: http://wordpress.org/plugins/health-check/
 * Description: Checks the health of your WordPress install.
 * Author: The WordPress.org community
 * Version: 1.2.1
 * Author URI: http://wordpress.org/plugins/health-check/
 * Text Domain: health-check
 */

// Check that the file is nto accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'We\'re sorry, but you can not directly access this file.' );
}

// Set the minimum PHP version WordPress supports.
define( 'HEALTH_CHECK_PHP_MIN_VERSION', '5.2.4' );

// Set the lowest PHP version still receiving security updates.
define( 'HEALTH_CHECK_PHP_SUPPORTED_VERSION', '5.6' );

// Set the PHP version WordPress recommends.
define( 'HEALTH_CHECK_PHP_REC_VERSION', '7.2' );

// Set the minimum MySQL version WordPress supports.
define( 'HEALTH_CHECK_MYSQL_MIN_VERSION', '5.0' );

// Set the MySQL version WordPress recommends.
define( 'HEALTH_CHECK_MYSQL_REC_VERSION', '5.6' );

// Set the plugin version.
define( 'HEALTH_CHECK_PLUGIN_VERSION', '1.2.1' );

// Set the absolute path for the plugin.
define( 'HEALTH_CHECK_PLUGIN_DIRECTORY', plugin_dir_path( __FILE__ ) );

// Set the plugin URL root.
define( 'HEALTH_CHECK_PLUGIN_URL', plugins_url( '/', __FILE__ ) );

// Set the current cURL version.
define( 'HEALTH_CHECK_CURL_VERSION', '7.58' );

// Set the minimum cURL version that we've tested that core works with.
define( 'HEALTH_CHECK_CURL_MIN_VERSION', '7.38' );

// Include class-files used by our plugin.
require_once( dirname( __FILE__ ) . '/includes/class-health-check.php' );
require_once( dirname( __FILE__ ) . '/includes/class-health-check-auto-updates.php' );
require_once( dirname( __FILE__ ) . '/includes/class-health-check-wp-cron.php' );
require_once( dirname( __FILE__ ) . '/includes/class-health-check-debug-data.php' );
require_once( dirname( __FILE__ ) . '/includes/class-health-check-loopback.php' );
require_once( dirname( __FILE__ ) . '/includes/class-health-check-troubleshoot.php' );
require_once( dirname( __FILE__ ) . '/includes/class-health-check-files-integrity.php' );
require_once( dirname( __FILE__ ) . '/includes/class-health-check-mail-check.php' );
require_once( dirname( __FILE__ ) . '/includes/class-health-check-site-status.php' );

// Initialize our plugin.
new Health_Check();
