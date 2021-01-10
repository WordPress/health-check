<?php

class Health_Check_Site_Status_Test extends WP_UnitTestCase {

	private $tests_list;

	public function setUp() {
		parent::setUp();

		$this->tests_list = Health_Check_Site_Status::get_tests();
	}

	private function runStatusTest( $func ) {
		global $health_check_site_status;

		$callable = $func;

		if ( is_array( $func ) ) {
			$message = sprintf(
				'Site status test %s could not be accessed.',
				$func[1]
			);

			$this->assertTrue(
				is_callable( $callable ),
				$message
			);
		} else {
			$callable = array( $health_check_site_status, $func );

			$message = sprintf(
				'Site status test %s could not be accessed.',
				$func
			);

			$this->assertTrue(
				method_exists( $health_check_site_status, $func ) && is_callable( $callable ),
				$message
			);
		}

		$start_time = microtime( true );
		ob_start();
		call_user_func( $callable );
		ob_end_clean();

		return round( ( microtime( true ) - $start_time ) * 1000 );
	}

	public function testDirectTiming() {
		$tests = $this->tests_list['direct'];

		// Certain tests may only appear slow in certain scenarios, although may appear long in testing
		$skip_testing = array(
			'get_test_rest_availability', // Runs slow on PHP 5.2, but in 5-10ms on other builds.
			'get_test_php_version', // Slow on first run, but is ran by core, so will "always" be cached.
		);

		foreach ( $tests as $test ) {
			if ( is_array( $test['test'] ) ) {
				if ( in_array( $test['test'][1], $skip_testing, true ) ) {
					continue;
				}
			} else {
				if ( in_array( $test['test'], $skip_testing, true ) ) {
					continue;
				}
			}

			$result = $this->runStatusTest( $test['test'] );

			$message = sprintf(
				'Function %s exceeded the execution time limit.',
				$test['test'][1]
			);

			/**
			 * Result should be <= 100 milliseconds.
			 */
			$this->assertLessThanOrEqual(
				100,
				$result,
				$message
			);
		}
	}

	public function testAsyncTiming() {
		$tests = $this->tests_list['async'];

		// Certain tests are known to be prolonged, but will appear short in testing
		$skip_testing = array(
			'loopback_requests', // fail early, as there's no loopback to hit on a unit test.
			'dotorg_communication', // Time needed to run this test heavily depends on host loads.
		);

		foreach ( $tests as $test ) {
			if ( is_array( $test['test'] ) ) {
				if ( in_array( $test['test'][1], $skip_testing, true ) ) {
					continue;
				}
			} else {
				if ( in_array( $test['test'], $skip_testing, true ) ) {
					continue;
				}
			}

			// async tests are passed without a prefix when not given a method object parent, so we add that here.
			if ( ! is_array( $test['test'] ) ) {
				$test['test'] = 'get_test_' . $test['test'];
			}

			$result = $this->runStatusTest( $test['test'] );

			$message = sprintf(
				'Function %s executed in %dms and should be run directly.',
				$test['test'][1],
				$result
			);

			/**
			 * Result should be > 100 miliseconds.
			 */
			$this->assertGreaterThan(
				100,
				$result,
				$message
			);
		}
	}
}
