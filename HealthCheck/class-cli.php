<?php
/**
 * WP-CLI Commands for the Health Check plugin
 *
 * @package Health Check
 */

namespace HealthCheck;

use HealthCheck\WP_CLI\Status;
use WP_CLI;
use WP_CLI\Utils;

// Make sure the file is not directly accessible.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'We\'re sorry, but you can not directly access this file.' );
}

/**
 * Class Health_Check_CLI
 */
class CLI {
	/**
	 * See the sites status based on best practices and WordPress recommendations.
	 *
	 * ## EXAMPLES
	 *
	 * wp health-check status
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Render the output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 *   - yaml
	 * ---
	 */
	public function status( $args, $assoc_args ) {
		$runner = new Status( WP_CLI\Utils\get_flag_value( $assoc_args, 'format' ) );

		$runner->run();
	}
}

WP_CLI::add_command( 'health-check', __NAMESPACE__ . '\\CLI' );
