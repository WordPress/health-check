<?php

class Health_Check_Updates_Test extends WP_UnitTestCase {

	private $test_updates;

	public function setUp() {
		parent::setUp();

		$this->test_updates = new Health_Check_Updates();
	}

	public function testPluginHooks() {
		// Check update hooks are in place.
		$hooks = $this->test_updates->check_plugin_update_hooks();
		$this->assertTrue( $hooks );

		// Check update hooks have been removed.
		remove_filter( 'load-plugins.php', 'wp_update_plugins' );
		$hooks = $this->test_updates->check_plugin_update_hooks();
		add_filter( 'load-plugins.php', 'wp_update_plugins' );
		$this->assertFalse( $hooks );
	}

	public function testPluginUpdateRequest() {
		// Check if update requests are being blocked.
		$blocked = $this->test_updates->check_plugin_update_pre_request();
		$this->assertFalse( $blocked );

		// Check detection of blocked update requests.
		add_action( 'pre_http_request', array( $this, 'blockUpdates' ), 10, 3 );
		$blocked = $this->test_updates->check_plugin_update_pre_request();
		$this->assertTrue( $blocked );
		remove_action( 'pre_http_request', array( $this, 'blockUpdates' ) );
	}

	public function testPluginUpdateRequestArgs() {
		// Check if plugins have been removed from the update requests.
		$diff = (array) $this->test_updates->check_plugin_update_request_args();
		$this->assertCount( 0, $diff );

		// Check for plugin which has been hidden.
		add_filter( 'http_request_args', array( $this, 'hidePlugin' ), 10, 2 );
		$diff = (array) $this->test_updates->check_plugin_update_request_args();
		remove_filter( 'http_request_args', array( $this, 'hidePlugin' ) );
		$this->assertCount( 1, $diff );
	}

	public function testThemeHooks() {
		// Check if update hooks are in place.
		$hooks = $this->test_updates->check_theme_update_hooks();
		$this->assertTrue( $hooks );

		// Check update hooks have been removed.
		remove_filter( 'load-themes.php', 'wp_update_themes' );
		$hooks = $this->test_updates->check_theme_update_hooks();
		add_filter( 'load-themes.php', 'wp_update_themes' );
		$this->assertFalse( $hooks );
	}

	public function testThemeUpdateRequest() {
		// Check if update requests are being blocked.
		$blocked = $this->test_updates->check_theme_update_pre_request();
		$this->assertFalse( $blocked );

		// Check detection of blocked update requests.
		add_action( 'pre_http_request', array( $this, 'blockUpdates' ), 10, 3 );
		$blocked = $this->test_updates->check_theme_update_pre_request();
		$this->assertTrue( $blocked );
		remove_action( 'pre_http_request', array( $this, 'blockUpdates' ) );
	}

	public function testThemeUpdateRequestArgs() {
		// Check if themes have been removed from the update requests.
		$diff = (array) $this->test_updates->check_theme_update_request_args();
		$this->assertCount( 0, $diff );

		// Check for theme which has been hidden.
		add_filter( 'http_request_args', array( $this, 'hideTheme' ), 10, 2 );
		$diff = (array) $this->test_updates->check_theme_update_request_args();
		remove_filter( 'http_request_args', array( $this, 'hideTheme' ) );
		$this->assertGreaterThanOrEqual( 1, count( $diff ) );
	}

	public function hidePlugin( $r, $url ) {
		$check_url = 'api.wordpress.org/plugins/update-check/1.1/';
		if ( 0 !== substr_compare( $url, $check_url, -strlen( $check_url ) ) ) {
			return $r; // Not a plugin update request.
		}

		$plugins = json_decode( $r['body']['plugins'], true );
		unset( $plugins['plugins']['akismet/akismet.php'] );
		unset( $plugins['active']['akismet/akismet.php'] );
		$r['body']['plugins'] = json_encode( $plugins );
		return $r;
	}

	public function hideTheme( $r, $url ) {
		$check_url = 'api.wordpress.org/themes/update-check/1.1/';
		if ( 0 !== substr_compare( $url, $check_url, -strlen( $check_url ) ) ) {
			return $r; // Not a theme update request.
		}

		$themes = json_decode( $r['body']['themes'], true );
		unset( $themes['themes']['twentyfourteen'] );
		unset( $themes['themes']['twentyseventeen'] );
		$r['body']['themes'] = json_encode( $themes );
		return $r;
	}

	public function blockUpdates( $pre, $r, $url ) {
		switch ( $url ) {
			case 'https://api.wordpress.org/plugins/update-check/1.1/':
				return 'block_request';
			case 'https://api.wordpress.org/themes/update-check/1.1/':
				return 'block_request';
			default:
				return $pre;
		}
	}

}
