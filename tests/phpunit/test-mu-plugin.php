<?php

if ( class_exists( 'PHPUnit\Framework\TestCase' ) ) {
	class_alias( 'PHPUnit\Framework\TestCase', 'PHPUnit_Framework_TestCase' );
}

class AssertionTest extends PHPUnit_Framework_TestCase {

	public function setUp() {
		parent::setUp();

		$this->class_instance = new Health_Check_Troubleshooting_MU();

		// Set up a Troubleshooting hash to test if it is enabled.
		update_option( 'health-check-disable-plugin-hash', 'abc123' );
	}

	public function testTroubleshootingModeDisabledNoCookie() {
		// Troubleshooting mode should be disabled by default, with no cookie declared.
		$this->assertFalse( $this->class_instance->is_troubleshooting() );
	}

	public function testTroubleshootingModeDisabledWrongCokie() {
		// Set a troubleshooting cookie with invalid data.
		$_COOKIE['health-check-disable-plugins'] = 'abc124';

		// This test should fail, as the hash values do not match.
		$this->assertFalse( $this->class_instance->is_troubleshooting() );
	}

	public function testTroubleshootingModeEnabledRightCookie() {
		// Set a troubleshooting cookie with valid data.
		$_COOKIE['health-check-disable-plugins'] = 'abc123';

		// This test should pass, as the hash values does now match.
		$this->assertTrue( $this->class_instance->is_troubleshooting() );
	}
}