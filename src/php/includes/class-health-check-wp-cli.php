<?php
/**
 * WP-CLI Commands for the Health Check plugin
 *
 * @package Health Check
 */

use WP_CLI\Utils;

// Make sure the file is not directly accessible.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'We\'re sorry, but you can not directly access this file.' );
}

/**
 * Class Health_Check_WP_CLI
 */
class Health_Check_WP_CLI {
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
		global $health_check_site_status;

		$all_tests = $health_check_site_status::get_tests();

		$test_result = array();

		foreach ( $all_tests['direct'] as $test ) {
			$test_output = call_user_func( $test['test'] );

			$test_result[] = array(
				'test'   => $test['label'],
				'type'   => wp_kses( $test_output['badge']['label'], array() ),
				'result' => wp_kses( $test_output['status'], array() ),
			);
		}
		foreach ( $all_tests['async'] as $test ) {
			$test_output = call_user_func( array( $health_check_site_status, 'get_test_' . $test['test'] ) );

			$test_result[] = array(
				'test'   => $test['label'],
				'type'   => wp_kses( $test_output['badge']['label'], array() ),
				'result' => wp_kses( $test_output['status'], array() ),
			);
		}

		if ( WP_CLI\Utils\get_flag_value( $assoc_args, 'format' ) === 'json' ) {
			WP_CLI\Utils\format_items( 'json', $test_result, array( 'test', 'type', 'result' ) );
		} elseif ( WP_CLI\Utils\get_flag_value( $assoc_args, 'format' ) === 'csv' ) {
			WP_CLI\Utils\format_items( 'csv', $test_result, array( 'test', 'type', 'result' ) );
		} elseif ( WP_CLI\Utils\get_flag_value( $assoc_args, 'format' ) === 'yaml' ) {
			WP_CLI\Utils\format_items( 'yaml', $test_result, array( 'test', 'type', 'result' ) );
		} else {
			WP_CLI\Utils\format_items( 'table', $test_result, array( 'test', 'type', 'result' ) );
		}
	}
}

WP_CLI::add_command( 'health-check', 'Health_Check_WP_CLI' );
