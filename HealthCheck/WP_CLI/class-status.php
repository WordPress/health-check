<?php

namespace HealthCheck\WP_CLI;

class Status {

	private $format;

	public function __construct( $format ) {
		$this->format = $format;
	}

	public function run() {
		$health_check_site_status = \WP_Site_Health::get_instance();

		$tests = $health_check_site_status::get_tests();

		$test_result = array();

		foreach ( $tests['direct'] as $test ) {
			if ( is_string( $test['test'] ) ) {
				$test_function = sprintf(
					'get_test_%s',
					$test['test']
				);

				if ( method_exists( $health_check_site_status, $test_function ) && is_callable( array( $health_check_site_status, $test_function ) ) ) {
					$test_output = apply_filters( 'site_status_test_result', call_user_func( array( $health_check_site_status, $test_function ) ) );

					$test_result[] = array(
						'test'   => $test['label'],
						'type'   => wp_kses( $test_output['badge']['label'], array() ),
						'result' => wp_kses( $test_output['status'], array() ),
					);

					continue;
				}
			}

			if ( is_callable( $test['test'] ) ) {
				$test_output = apply_filters( 'site_status_test_result', call_user_func( $test['test'] ) );

				$test_result[] = array(
					'test'   => $test['label'],
					'type'   => wp_kses( $test_output['badge']['label'], array() ),
					'result' => wp_kses( $test_output['status'], array() ),
				);
			}
		}

		foreach ( $tests['async'] as $test ) {
			if ( isset( $test['async_direct_test'] ) && is_callable( $test['async_direct_test'] ) ) {
				$test_output = apply_filters( 'site_status_test_result', call_user_func( $test['async_direct_test'] ) );

				$test_result[] = array(
					'test'   => $test['label'],
					'type'   => wp_kses( $test_output['badge']['label'], array() ),
					'result' => wp_kses( $test_output['status'], array() ),
				);
			}
		}

		if ( 'json' === $this->format ) {
			\WP_CLI\Utils\format_items( 'json', $test_result, array( 'test', 'type', 'result' ) );
		} elseif ( 'csv' === $this->format ) {
			\WP_CLI\Utils\format_items( 'csv', $test_result, array( 'test', 'type', 'result' ) );
		} elseif ( 'yaml' === $this->format ) {
			\WP_CLI\Utils\format_items( 'yaml', $test_result, array( 'test', 'type', 'result' ) );
		} else {
			\WP_CLI\Utils\format_items( 'table', $test_result, array( 'test', 'type', 'result' ) );
		}
	}

}
