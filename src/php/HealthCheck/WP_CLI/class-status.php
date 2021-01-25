<?php

namespace HealthCheck\WP_CLI;

class Status {

	private $format;

	public function __construct( $format ) {
		$this->format = $format;
	}

	public function run() {
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

		if ( 'json' === $this->format ) {
			WP_CLI\Utils\format_items( 'json', $test_result, array( 'test', 'type', 'result' ) );
		} elseif ( 'csv' === $this->format ) {
			WP_CLI\Utils\format_items( 'csv', $test_result, array( 'test', 'type', 'result' ) );
		} elseif ( 'yaml' === $this->format ) {
			WP_CLI\Utils\format_items( 'yaml', $test_result, array( 'test', 'type', 'result' ) );
		} else {
			WP_CLI\Utils\format_items( 'table', $test_result, array( 'test', 'type', 'result' ) );
		}
	}

}
