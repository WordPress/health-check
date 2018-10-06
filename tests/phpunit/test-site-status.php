<?php

class Health_Check_Site_Status_Test extends WP_UnitTestCase {

	private $tests_list;

	public function setUp() {
		parent::setUp();

		$this->tests_list = Health_Check_Site_Status::get_tests();
	}

	private function runStatusTest( $func ) {
		global $health_check_site_status;

		$this->assertTrue(
			method_exists( $health_check_site_status, $func ) && is_callable( array( $health_check_site_status, $func ) )
		);

		$start_time = microtime( true );
		ob_start();
		call_user_func( array( $health_check_site_status, $func ) );
		ob_end_clean();

		return round( ( microtime( true ) - $start_time ) * 1000 );
	}

	public function testDirectTiming() {
		$tests = $this->tests_list['direct'];
		foreach ( $tests as $test ) {
			$test_function = sprintf(
				'test_%s',
				$test['test']
			);

			$result = $this->runStatusTest( $test_function );

			$message = sprintf(
				'Function %s exceeded the execution time limit.',
				$test_function
			);

			/**
			 * Result should be <= 100 miliseconds.
			 */
			$this->assertLessThanOrEqual(
				100,
				$result,
				$message
			);
		}
	}
}
