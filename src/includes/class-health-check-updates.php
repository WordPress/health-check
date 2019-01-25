<?php
/**
 * Class for testing plugin/theme updates in the WordPress code.
 *
 * @package Health Check
 */

// Make sure the file is not directly accessible.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'We\'re sorry, but you can not directly access this file.' );
}

/**
 * Class Health_Check_Updates
 */
class Health_Check_Updates {
	private $plugins_before;
	private $plugins_after;
	private static $plugins_blocked;
	private $themes_before;
	private $themes_after;
	private static $themes_blocked;

	/**
	 * Health_Check_Updates constructor.
	 *
	 * @uses Health_Check_Updates::init()
	 *
	 * @return void
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Initiate the plugin class.
	 *
	 * @return void
	 */
	public function init() {
		$this->plugins_before  = (array) array();
		$this->plugins_after   = (array) array();
		self::$plugins_blocked = (bool) false;

		$this->themes_before  = (array) array();
		$this->themes_after   = (array) array();
		self::$themes_blocked = (bool) false;
	}

	/**
	 * Run tests to determine if auto-updates can run.
	 *
	 * @uses get_class_methods()
	 * @uses substr()
	 * @uses call_user_func()
	 *
	 * @return array
	 */
	public function run_tests() {
		$tests = array();

		foreach ( get_class_methods( $this ) as $method ) {
			if ( 'test_' !== substr( $method, 0, 5 ) ) {
				continue;
			}

			$result = call_user_func( array( $this, $method ) );

			if ( false === $result || null === $result ) {
				continue;
			}

			$result = (object) $result;

			if ( empty( $result->severity ) ) {
				$result->severity = 'warning';
			}

			$tests[ $method ] = $result;
		}

		return $tests;
	}

	/**
	 * Check if plugin updates have been tampered with.
	 *
	 * @uses Health_Check_Updates::check_plugin_update_hooks()
	 * @uses esc_html__()
	 * @uses Health_Check_Updates::check_plugin_update_pre_request()
	 * @uses Health_Check_Updates::check_plugin_update_request_args()
	 *
	 * @return array
	 */
	function test_plugin_updates() {
		// Check if any update hooks have been removed.
		$hooks = $this->check_plugin_update_hooks();
		if ( ! $hooks ) {
			return array(
				'desc'     => esc_html__( 'Plugin update hooks have been removed.', 'health-check' ),
				'severity' => 'fail',
			);
		}

		// Check if update requests are being blocked.
		$blocked = $this->check_plugin_update_pre_request();
		if ( true === $blocked ) {
			return array(
				'desc'     => esc_html__( 'Plugin update requests have been blocked.', 'health-check' ),
				'severity' => 'fail',
			);
		}

		// Check if plugins have been removed from the update requests.
		$diff = (array) $this->check_plugin_update_request_args();
		if ( 0 !== count( $diff ) ) {
			return array(
				'desc'     => sprintf(
					/* translators: %s: List of plugin names. */
					esc_html__( 'The following Plugins have been removed from update checks: %s.', 'health-check' ),
					implode( ',', $diff )
				),
				'severity' => 'warning',
			);
		}

		return array(
			'desc'     => esc_html__( 'Plugin updates should be working as expected.', 'health-check' ),
			'severity' => 'pass',
		);
	}

	/**
	 * Check if any plugin update hooks have been removed.
	 *
	 * @uses has_filter()
	 * @uses wp_next_scheduled()
	 *
	 * @return array
	 */
	function check_plugin_update_hooks() {
		$test1 = has_filter( 'load-plugins.php', 'wp_update_plugins' );
		$test2 = has_filter( 'load-update.php', 'wp_update_plugins' );
		$test3 = has_filter( 'load-update-core.php', 'wp_update_plugins' );
		$test4 = has_filter( 'wp_update_plugins', 'wp_update_plugins' );
		$test5 = has_filter( 'admin_init', '_maybe_update_plugins' );
		$test6 = wp_next_scheduled( 'wp_update_plugins' );

		return $test1 && $test2 && $test3 && $test4 && $test5 && $test6;
	}

	/**
	 * Check if plugin update request checks are being tampered with at the 'pre_http_request' filter.
	 *
	 * @uses add_action()
	 * @uses Health_Check_Updates::wp_plugin_update_fake_request()
	 * @uses esc_html__()
	 *
	 * @return array
	 */
	function check_plugin_update_pre_request() {
		add_action( 'pre_http_request', array( $this, 'plugin_pre_request_check' ), PHP_INT_MAX, 3 );
		add_action( 'pre_http_request', array( $this, 'block_fake_request' ), PHP_INT_MAX, 3 );

		$this->plugin_update_fake_request();

		remove_action( 'pre_http_request', array( $this, 'plugin_pre_request_check' ), PHP_INT_MAX );
		remove_action( 'pre_http_request', array( $this, 'block_fake_request' ), PHP_INT_MAX );

		return self::$plugins_blocked;
	}

	/**
	 * Check plugin update requests to see if they are being blocked.
	 *
	 * @param  bool $pre If not false, request cancelled.
	 * @param  array $r Request parameters.
	 * @param  string $url Request URL.
	 * @return bool
	 */
	function plugin_pre_request_check( $pre, $r, $url ) {
		$check_url = 'api.wordpress.org/plugins/update-check/1.1/';
		if ( 0 !== substr_compare( $url, $check_url, -strlen( $check_url ) ) ) {
			return $pre; // Not a plugin update request.
		}

		// If not false something is blocking update checks
		if ( false !== $pre ) {
			self::$plugins_blocked = (bool) true;
		}

		return $pre;
	}

	/**
	 * Check if plugins are being removed at the 'http_request_args' filter.
	 *
	 * @uses add_action()
	 * @uses Health_Check_Updates::wp_plugin_update_fake_request()
	 * @uses remove_action()
	 *
	 * @return array
	 */
	function check_plugin_update_request_args() {
		add_action( 'http_request_args', array( $this, 'plugin_request_args_before' ), 1, 2 );
		add_action( 'http_request_args', array( $this, 'plugin_request_args_after' ), PHP_INT_MAX, 2 );
		add_action( 'pre_http_request', array( $this, 'block_fake_request' ), PHP_INT_MAX, 3 );

		$this->plugin_update_fake_request();

		remove_action( 'http_request_args', array( $this, 'plugin_request_args_before' ), 1 );
		remove_action( 'http_request_args', array( $this, 'plugin_request_args_after' ), PHP_INT_MAX );
		remove_action( 'pre_http_request', array( $this, 'block_fake_request' ), PHP_INT_MAX );

		$diff = array_diff_key( $this->plugins_before['plugins'], $this->plugins_after['plugins'] );

		$titles = array();
		foreach ( $diff as $item ) {
			$titles[] = $item['Title'];
		}

		return $titles;
	}

	/**
	 * Record the list of plugins from plugin update requests at the start of filtering.
	 *
	 * @param  array $r Request parameters.
	 * @param  string $url Request URL.
	 * @return array
	 */
	function plugin_request_args_before( $r, $url ) {
		$check_url = 'api.wordpress.org/plugins/update-check/1.1/';
		if ( 0 !== substr_compare( $url, $check_url, -strlen( $check_url ) ) ) {
			return $r; // Not a plugin update request.
		}

		$this->plugins_before = (array) json_decode( $r['body']['plugins'], true );

		return $r;
	}

	/**
	 * Record the list of plugins from plugin update requests at the end of filtering.
	 *
	 * @param  array $r Request parameters.
	 * @param  string $url Request URL.
	 * @return array
	 */
	function plugin_request_args_after( $r, $url ) {
		$check_url = 'api.wordpress.org/plugins/update-check/1.1/';
		if ( 0 !== substr_compare( $url, $check_url, -strlen( $check_url ) ) ) {
			return $r; // Not a plugin update request.
		}

		$this->plugins_after = (array) json_decode( $r['body']['plugins'], true );

		return $r;
	}

	/**
	 * Create and trigger a fake plugin update check request.
	 *
	 * @uses get_plugins()
	 * @uses get_option()
	 * @uses wp_get_installed_translations()
	 * @uses apply_filters()
	 * @uses wp_json_encode()
	 * @uses get_bloginfo()
	 * @uses home_url()
	 * @uses wp_http_supports()
	 * @uses set_url_scheme()
	 * @uses wp_remote_post()
	 *
	 * @return void
	 */
	function plugin_update_fake_request() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		// Prepare data for the request.
		$plugins      = get_plugins();
		$active       = get_option( 'active_plugins', array() );
		$to_send      = compact( 'plugins', 'active' );
		$translations = wp_get_installed_translations( 'plugins' );
		$locales      = array_values( get_available_languages() );
		$locales      = (array) apply_filters( 'plugins_update_check_locales', $locales );
		$locales      = array_unique( $locales );
		$timeout      = 3 + (int) ( count( $plugins ) / 10 );

		// Setup the request options.
		if ( function_exists( 'wp_json_encode' ) ) {
			$options = array(
				'timeout'    => $timeout,
				'body'       => array(
					'plugins'      => wp_json_encode( $to_send ),
					'translations' => wp_json_encode( $translations ),
					'locale'       => wp_json_encode( $locales ),
					'all'          => wp_json_encode( true ),
				),
				'user-agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url( '/' ),
			);
		} else {
			$options = array(
				'timeout'    => $timeout,
				'body'       => array(
					'plugins'      => json_encode( $to_send ),
					'translations' => json_encode( $translations ),
					'locale'       => json_encode( $locales ),
					'all'          => json_encode( true ),
				),
				'user-agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url( '/' ),
			);
		}

		// Set the URL
		$http_url = 'http://api.wordpress.org/plugins/update-check/1.1/';
		$url      = wp_http_supports( array( 'ssl' ) ) ? set_url_scheme( $http_url, 'https' ) : $http_url;

		// Ignore the response. Just need the hooks to fire.
		wp_remote_post( $url, $options );
	}

	/**
	 * Check if theme updates have been tampered with.
	 *
	 * @uses Health_Check_Updates::check_theme_update_hooks()
	 * @uses esc_html__()
	 * @uses Health_Check_Updates::check_theme_update_pre_request()
	 * @uses Health_Check_Updates::check_theme_update_request_args()
	 *
	 * @return array
	 */
	function test_constant_theme_updates() {
		// Check if any update hooks have been removed.
		$hooks = $this->check_theme_update_hooks();
		if ( ! $hooks ) {
			return array(
				'desc'     => esc_html__( 'Theme update hooks have been removed.', 'health-check' ),
				'severity' => 'fail',
			);
		}

		// Check if update requests are being blocked.
		$blocked = $this->check_theme_update_pre_request();
		if ( true === $blocked ) {
			return array(
				'desc'     => esc_html__( 'Theme update requests have been blocked.', 'health-check' ),
				'severity' => 'fail',
			);
		}

		// Check if themes have been removed from the update requests.
		$diff = (array) $this->check_theme_update_request_args();
		if ( 0 !== count( $diff ) ) {
			return array(
				'desc'     => sprintf(
					/* translators: %s: List of theme names. */
					esc_html__( 'The following Themes have been removed from update checks: %s.', 'health-check' ),
					implode( ',', $diff )
				),
				'severity' => 'warning',
			);
		}

		return array(
			'desc'     => esc_html__( 'Theme updates should be working as expected.', 'health-check' ),
			'severity' => 'pass',
		);
	}

	/**
	 * Check if any theme update hooks have been removed.
	 *
	 * @uses has_filter()
	 * @uses wp_next_scheduled()
	 *
	 * @return array
	 */
	function check_theme_update_hooks() {
		$test1 = has_filter( 'load-themes.php', 'wp_update_themes' );
		$test2 = has_filter( 'load-update.php', 'wp_update_themes' );
		$test3 = has_filter( 'load-update-core.php', 'wp_update_themes' );
		$test4 = has_filter( 'wp_update_themes', 'wp_update_themes' );
		$test5 = has_filter( 'admin_init', '_maybe_update_themes' );
		$test6 = wp_next_scheduled( 'wp_update_themes' );

		return $test1 && $test2 && $test3 && $test4 && $test5 && $test6;
	}

	/**
	 * Check if theme update request checks are being tampered with at the 'pre_http_request' filter.
	 *
	 * @uses add_action()
	 * @uses Health_Check_Updates::wp_theme_update_fake_request()
	 * @uses esc_html__()
	 *
	 * @return array
	 */
	function check_theme_update_pre_request() {
		add_action( 'pre_http_request', array( $this, 'theme_pre_request_check' ), PHP_INT_MAX, 3 );
		add_action( 'pre_http_request', array( $this, 'block_fake_request' ), PHP_INT_MAX, 3 );

		$this->theme_update_fake_request();

		remove_action( 'pre_http_request', array( $this, 'theme_pre_request_check' ), PHP_INT_MAX );
		remove_action( 'pre_http_request', array( $this, 'block_fake_request' ), PHP_INT_MAX );

		return self::$themes_blocked;
	}

	/**
	 * Check theme update requests to see if they are being blocked.
	 *
	 * @param  bool $pre If not false, request cancelled.
	 * @param  array $r Request parameters.
	 * @param  string $url Request URL.
	 * @return bool
	 */
	function theme_pre_request_check( $pre, $r, $url ) {
		$check_url = 'api.wordpress.org/themes/update-check/1.1/';
		if ( 0 !== substr_compare( $url, $check_url, -strlen( $check_url ) ) ) {
			return $pre; // Not a theme update request.
		}

		// If not false something is blocking update checks
		if ( false !== $pre ) {
			self::$themes_blocked = (bool) true;
		}

		return $pre;
	}

	/**
	 * Check if themes are being removed at the 'http_request_args' filter.
	 *
	 * @uses add_action()
	 * @uses Health_Check_Updates::wp_theme_update_fake_request()
	 * @uses remove_action()
	 *
	 * @return array
	 */
	function check_theme_update_request_args() {
		add_action( 'http_request_args', array( $this, 'theme_request_args_before' ), 1, 2 );
		add_action( 'http_request_args', array( $this, 'theme_request_args_after' ), PHP_INT_MAX, 2 );
		add_action( 'pre_http_request', array( $this, 'block_fake_request' ), PHP_INT_MAX, 3 );

		$this->theme_update_fake_request();

		remove_action( 'http_request_args', array( $this, 'theme_request_args_before' ), 1 );
		remove_action( 'http_request_args', array( $this, 'theme_request_args_after' ), PHP_INT_MAX );
		remove_action( 'pre_http_request', array( $this, 'block_fake_request' ), PHP_INT_MAX );

		$diff = array_diff_key( $this->themes_before['themes'], $this->themes_after['themes'] );

		$titles = array();
		foreach ( $diff as $item ) {
			$titles[] = $item['Title'];
		}

		return $titles;
	}

	/**
	 * Record the list of themes from theme update requests at the start of filtering.
	 *
	 * @param  array $r Request parameters.
	 * @param  string $url Request URL.
	 * @return array
	 */
	function theme_request_args_before( $r, $url ) {
		$check_url = 'api.wordpress.org/themes/update-check/1.1/';
		if ( 0 !== substr_compare( $url, $check_url, -strlen( $check_url ) ) ) {
			return $r; // Not a theme update request.
		}

		$this->themes_before = (array) json_decode( $r['body']['themes'], true );

		return $r;
	}

	/**
	 * Record the list of themes from theme update requests at the end of filtering.
	 *
	 * @param  array $r Request parameters.
	 * @param  string $url Request URL.
	 * @return array
	 */
	function theme_request_args_after( $r, $url ) {
		$check_url = 'api.wordpress.org/themes/update-check/1.1/';
		if ( 0 !== substr_compare( $url, $check_url, -strlen( $check_url ) ) ) {
			return $r; // Not a theme update request.
		}

		$this->themes_after = (array) json_decode( $r['body']['themes'], true );

		return $r;
	}

	/**
	 * Create and trigger a fake theme update check request.
	 *
	 * @uses wp_get_themes()
	 * @uses wp_get_installed_translations()
	 * @uses get_option()
	 * @uses get_available_languages()
	 * @uses wp_json_encode()
	 * @uses get_bloginfo()
	 * @uses home_url()
	 * @uses wp_http_supports()
	 * @uses set_url_scheme()
	 * @uses wp_remote_post()
	 *
	 * @return void
	 */
	function theme_update_fake_request() {
		$themes            = array();
		$checked           = array();
		$request           = array();
		$installed_themes  = wp_get_themes();
		$translations      = wp_get_installed_translations( 'themes' );
		$request['active'] = get_option( 'stylesheet' );

		foreach ( $installed_themes as $theme ) {
			$checked[ $theme->get_stylesheet() ] = $theme->get( 'Version' );

			$themes[ $theme->get_stylesheet() ] = array(
				'Name'       => $theme->get( 'Name' ),
				'Title'      => $theme->get( 'Name' ),
				'Version'    => $theme->get( 'Version' ),
				'Author'     => $theme->get( 'Author' ),
				'Author URI' => $theme->get( 'AuthorURI' ),
				'Template'   => $theme->get_template(),
				'Stylesheet' => $theme->get_stylesheet(),
			);
		}

		$request['themes'] = $themes;

		$locales = array_values( get_available_languages() );
		$timeout = 3 + (int) ( count( $themes ) / 10 );

		if ( function_exists( 'wp_json_encode' ) ) {
			$options = array(
				'timeout'    => $timeout,
				'body'       => array(
					'themes'       => wp_json_encode( $request ),
					'translations' => wp_json_encode( $translations ),
					'locale'       => wp_json_encode( $locales ),
				),
				'user-agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url( '/' ),
			);
		} else {
			$options = array(
				'timeout'    => $timeout,
				'body'       => array(
					'themes'       => json_encode( $request ),
					'translations' => json_encode( $translations ),
					'locale'       => json_encode( $locales ),
				),
				'user-agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url( '/' ),
			);
		}

		// Set the URL
		$http_url = 'http://api.wordpress.org/themes/update-check/1.1/';
		$url      = wp_http_supports( array( 'ssl' ) ) ? set_url_scheme( $http_url, 'https' ) : $http_url;

		// Ignore the response. Just need the hooks to fire.
		wp_remote_post( $url, $options );
	}

	/**
	 * Blocks the fake update requests, ensuring they do not slow down page loads.
	 *
	 * @param  bool $pre If not false, request cancelled.
	 * @param  array $r Request parameters.
	 * @param  string $url Request URL.
	 * @return bool
	 */
	function block_fake_request( $pre, $r, $url ) {
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
