<?php

use PHPUnit\Framework\TestCase;

class AssertionTest extends TestCase {

	public function testHasDefaultTheme() {
		$mu_plugin = new Health_Check_Troubleshooting_MU();

		// Troubleshooting mode should be disabled by default.
		$this->assertFalse( $mu_plugin->is_troubleshooting() );

		// Set up a Troubleshooting hash to test if it is enabled.
		update_option( 'health-check-disable-plugin-hash', 'abc123' );

		// Set a troubleshooting cookie with invalid data.
		$_COOKIE['health-check-disable-plugins'] = 'abc124';

		// This test should fail, as the hash values do not match.
		$this->assertFalse( $mu_plugin->is_troubleshooting() );

		// Set a troubleshooting cookie with valid data.
		$_COOKIE['health-check-disable-plugins'] = 'abc123';

		// This test should pass, as the hash values does now match.
		$this->assertTrue( $mu_plugin->is_troubleshooting() );
	}

	public function testPushAndPop() {
		$stack = [];
		$this->assertEquals( 0, count( $stack ) );

		array_push( $stack, 'foo' );
		$this->assertEquals( 'foo', $stack[ count( $stack ) - 1 ] );
		$this->assertEquals( 0, count( $stack ) );

		$this->assertEquals( 'foo', array_pop( $stack ) );
		$this->assertEquals( 0, count( $stack ) );
	}
}