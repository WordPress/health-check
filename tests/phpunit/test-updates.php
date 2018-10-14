<?php

class Health_Check_Updates_Test extends WP_UnitTestCase {

	private $test_updates;

	public function setUp() {
		parent::setUp();

		$this->test_updates = new Health_Check_Updates();
	}

	public function testPluginHooks() {
		// Check if any update hooks have been removed.
		$hooks = $this->test_updates->check_plugin_update_hooks();

		$this->assertTrue( $hooks );
	}

	public function testPluginUpdateRequest() {
		// Check if update requests are being blocked.
		$blocked = $this->test_updates->check_plugin_update_pre_request();

		$this->assertFalse( $blocked );
	}

	public function testPluginUpdateRequestArgs() {
		// Check if plugins have been removed from the update requests.
		$diff = (array) $this->test_updates->check_plugin_update_request_args();

		$this->assertCount( 0, $diff );
	}

	public function testThemeHooks() {
		// Check if any update hooks have been removed.
		$hooks = $this->test_updates->check_theme_update_hooks();

		$this->assertTrue( $hooks );
	}

	public function testThemeUpdateRequest() {
		// Check if update requests are being blocked.
		$blocked = $this->test_updates->check_theme_update_pre_request();

		$this->assertFalse( $blocked );
	}

	public function testThemeUpdateRequestArgs() {
		// Check if themes have been removed from the update requests.
		$diff = (array) $this->test_updates->check_theme_update_request_args();

		$this->assertCount( 0, $diff );
	}
}
