<?php

class Health_Check_MU_Plugin_Test extends WP_UnitTestCase {

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

	public function testTroubleshootingModeDisabledWrongCookie() {
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

	public function testNoPluginsWhenTroubleshooting() {
		// Make sure we are not currently troubleshooting.
		$_COOKIE['health-check-disable-plugins'] = '';

		$test_plugin = 'akismet/akismet.php';

		/*
		 * Start by making sure there are other plugins activated,
		 * we will use Akismet, as it comes bundled with core.
		 */
		activate_plugin( $test_plugin, '', false, true );

		// Fetch a list of all active plugins.
		$all_plugins = get_option( 'active_plugins' );

		// Test that the plugin list is what we expect it to be.
		$this->assertEquals( array(
			plugin_basename( trim( $test_plugin ) )
		), $all_plugins );

		// Enable troubleshooting
		$_COOKIE['health-check-disable-plugins'] = 'abc123';

		// Fetch a list of all active plugins while troubleshooting.
		$all_plugins = get_option( 'active_plugins' );

		// Test taht the plugin list is now empty.
		$this->assertEmpty( $all_plugins );
	}
}