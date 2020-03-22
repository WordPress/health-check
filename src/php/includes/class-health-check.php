<?php
/**
 * Primary class file for the Health Check plugin.
 *
 * @package Health Check
 */

// Make sure the file is not directly accessible.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'We\'re sorry, but you can not directly access this file.' );
}

/**
 * Class HealthCheck
 */
class Health_Check {

	/**
	 * Notices to show at the head of the admin screen.
	 *
	 * @access public
	 *
	 * @var array
	 */
	public $admin_notices = array();

	/**
	 * HealthCheck constructor.
	 *
	 * @uses Health_Check::init()
	 *
	 * @return void
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Plugin initiation.
	 *
	 * A helper function, called by `HealthCheck::__construct()` to initiate actions, hooks and other features needed.
	 *
	 * @uses add_action()
	 * @uses add_filter()
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'plugins_loaded', array( $this, 'load_i18n' ) );

		add_action( 'admin_menu', array( $this, 'action_admin_menu' ) );

		add_filter( 'plugin_action_links', array( $this, 'troubleshoot_plugin_action' ), 20, 4 );
		add_filter( 'plugin_action_links_' . plugin_basename( HEALTH_CHECK_PLUGIN_FILE ), array( $this, 'page_plugin_action' ) );

		add_action( 'admin_notices', array( $this, 'admin_notices' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueues' ) );

		add_action( 'init', array( $this, 'start_troubleshoot_mode' ) );
		add_action( 'load-plugins.php', array( $this, 'start_troubleshoot_single_plugin_mode' ) );

		add_action( 'wp_ajax_health-check-loopback-no-plugins', array( 'Health_Check_Loopback', 'loopback_no_plugins' ) );
		add_action( 'wp_ajax_health-check-loopback-individual-plugins', array( 'Health_Check_Loopback', 'loopback_test_individual_plugins' ) );
		add_action( 'wp_ajax_health-check-loopback-default-theme', array( 'Health_Check_Loopback', 'loopback_test_default_theme' ) );
		add_action( 'wp_ajax_health-check-get-sizes', array( 'Health_Check_Debug_Data', 'ajax_get_sizes' ) );

		add_filter( 'cron_schedules', array( $this, 'cron_schedules' ) );

		add_filter( 'user_has_cap', array( $this, 'maybe_grant_site_health_caps' ), 1, 4 );
	}

	/**
	 * Filters the user capabilities to grant the 'view_site_health_checks' capabilities as necessary.
	 *
	 * @since 5.2.2
	 *
	 * @param bool[]   $allcaps An array of all the user's capabilities.
	 * @param string[] $caps    Required primitive capabilities for the requested capability.
	 * @param array    $args {
	 *     Arguments that accompany the requested capability check.
	 *
	 *     @type string    $0 Requested capability.
	 *     @type int       $1 Concerned user ID.
	 *     @type mixed  ...$2 Optional second and further parameters, typically object ID.
	 * }
	 * @param WP_User  $user    The user object.
	 * @return bool[] Filtered array of the user's capabilities.
	 */
	function maybe_grant_site_health_caps( $allcaps, $caps, $args, $user ) {
		if ( ! empty( $allcaps['install_plugins'] ) && ( ! is_multisite() || is_super_admin( $user->ID ) ) ) {
			$allcaps['view_site_health_checks'] = true;
		}

		return $allcaps;
	}

	/**
	 * Initiate troubleshooting mode.
	 *
	 * Catch when the troubleshooting form has been submitted, and appropriately set required options and cookies.
	 *
	 * @uses current_user_can()
	 * @uses Health_Check_Troubleshoot::initiate_troubleshooting_mode()
	 *
	 * @return void
	 */
	public function start_troubleshoot_mode() {
		if ( ! isset( $_POST['health-check-troubleshoot-mode'] ) || ! current_user_can( 'view_site_health_checks' ) ) {
			return;
		}

		// Don't enable troubleshooting if nonces are missing or do not match.
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'health-check-enable-troubleshooting' ) ) {
			return;
		}

		Health_Check_Troubleshoot::initiate_troubleshooting_mode();
	}

	/**
	 * Initiate troubleshooting mode for a specific plugin.
	 *
	 * Catch when the troubleshooting link on an individual plugin has been clicked, and appropriately sets the
	 * required options and cookies.
	 *
	 * @uses current_user_can()
	 * @uses ob_start()
	 * @uses Health_Check_Troubleshoot::mu_plugin_exists()
	 * @uses Health_Check::get_filesystem_credentials()
	 * @uses Health_Check_Troubleshoot::setup_must_use_plugin()
	 * @uses Health_Check_Troubleshoot::maybe_update_must_use_plugin()
	 * @uses ob_get_clean()
	 * @uses Health_Check_Troubleshoot::initiate_troubleshooting_mode()
	 * @uses wp_redirect()
	 * @uses admin_url()
	 *
	 * @return void
	 */
	public function start_troubleshoot_single_plugin_mode() {
		if ( ! isset( $_GET['health-check-troubleshoot-plugin'] ) || ! current_user_can( 'view_site_health_checks' ) ) {
			return;
		}

		// Don't enable troubleshooting for an individual plugin if the nonce is missing or invalid.
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'health-check-troubleshoot-plugin-' . $_GET['health-check-troubleshoot-plugin'] ) ) {
			return;
		}

		ob_start();

		$needs_credentials = false;

		if ( ! Health_Check_Troubleshoot::mu_plugin_exists() ) {
			if ( ! Health_Check::get_filesystem_credentials() ) {
				$needs_credentials = true;
			} else {
				$check_output = Health_Check_Troubleshoot::setup_must_use_plugin( false );
				if ( false === $check_output ) {
					$needs_credentials = true;
				}
			}
		} else {
			if ( ! Health_Check_Troubleshoot::maybe_update_must_use_plugin() ) {
				$needs_credentials = true;
			}
		}

		$result = ob_get_clean();

		if ( $needs_credentials ) {
			$this->admin_notices[] = (object) array(
				'message' => $result,
				'type'    => 'warning',
			);
			return;
		}

		Health_Check_Troubleshoot::initiate_troubleshooting_mode(
			array(
				$_GET['health-check-troubleshoot-plugin'] => $_GET['health-check-troubleshoot-plugin'],
			)
		);

		wp_redirect( admin_url( 'plugins.php' ) );
	}

	/**
	 * Load translations.
	 *
	 * Loads the textdomain needed to get translations for our plugin.
	 *
	 * @uses load_plugin_textdomain()
	 * @uses basename()
	 * @uses dirname()
	 *
	 * @return void
	 */
	public function load_i18n() {
		load_plugin_textdomain( 'health-check', false, basename( dirname( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Enqueue assets.
	 *
	 * Conditionally enqueue our CSS and JavaScript when viewing plugin related pages in wp-admin.
	 *
	 * @uses wp_enqueue_style()
	 * @uses plugins_url()
	 * @uses wp_enqueue_script()
	 * @uses wp_localize_script()
	 * @uses esc_html__()
	 *
	 * @return void
	 */
	public function enqueues() {
		$screen = get_current_screen();

		// Don't enqueue anything unless we're on the health check page.
		if ( ( ! isset( $_GET['page'] ) || 'health-check' !== $_GET['page'] ) && 'dashboard' !== $screen->base ) {
			return;
		}

		$health_check_js_variables = array(
			'string'      => array(
				'please_wait'                          => esc_html__( 'Please wait...', 'health-check' ),
				'copied'                               => esc_html__( 'Copied', 'health-check' ),
				'running_tests'                        => esc_html__( 'Currently being tested...', 'health-check' ),
				'site_health_complete'                 => esc_html__( 'All site health tests have finished running.', 'health-check' ),
				'site_health_complete_pass_sr'         => esc_html__( 'All site health tests have finished running. Your site is looking good, and the results are now available on the page.', 'health-check' ),
				'site_health_complete_fail_sr'         => esc_html__( 'All site health tests have finished running. There are items that should be addressed, and the results are now available on the page.', 'health-check' ),
				'site_health_complete_pass'            => esc_html__( 'Good', 'health-check' ),
				'site_health_complete_fail'            => esc_html__( 'Should be improved', 'health-check' ),
				'site_info_copied'                     => esc_html__( 'Site information has been added to your clipboard.', 'health-check' ),
				// translators: %s: Amount of critical issues.
				'site_info_heading_critical_single'    => esc_html__( '%s Critical issue', 'health-check' ),
				// translators: %s: Amount of critical issues.
				'site_info_heading_critical_plural'    => esc_html__( '%s Critical issues', 'health-check' ),
				// translators: %s: Amount of recommended issues.
				'site_info_heading_recommended_single' => esc_html__( '%s Recommended improvement', 'health-check' ),
				// translators: %s: Amount of recommended issues.
				'site_info_heading_recommended_plural' => esc_html__( '%s Recommended improvements', 'health-check' ),
				// translators: %s: Amount of passed tests.
				'site_info_heading_good_single'        => esc_html__( '%s Item with no issues detected', 'health-check' ),
				// translators: %s: Amount of passed tests.
				'site_info_heading_good_plural'        => esc_html__( '%s Items with no issues detected', 'health-check' ),
			),
			'nonce'       => array(
				'loopback_no_plugins'         => wp_create_nonce( 'health-check-loopback-no-plugins' ),
				'loopback_individual_plugins' => wp_create_nonce( 'health-check-loopback-individual-plugins' ),
				'loopback_default_theme'      => wp_create_nonce( 'health-check-loopback-default-theme' ),
				'files_integrity_check'       => wp_create_nonce( 'health-check-files-integrity-check' ),
				'view_file_diff'              => wp_create_nonce( 'health-check-view-file-diff' ),
				'mail_check'                  => wp_create_nonce( 'health-check-mail-check' ),
				'site_status'                 => wp_create_nonce( 'health-check-site-status' ),
				'site_status_result'          => wp_create_nonce( 'health-check-site-status-result' ),
				'tools_plugin_compat'         => wp_create_nonce( 'health-check-tools-plugin-compat' ),
			),
			'site_status' => array(
				'direct' => array(),
				'async'  => array(),
				'issues' => array(
					'good'        => 0,
					'recommended' => 0,
					'critical'    => 0,
				),
			),
		);

		$issue_counts = get_transient( 'health-check-site-status-result' );

		if ( false !== $issue_counts ) {
			$issue_counts = json_decode( $issue_counts );

			$health_check_js_variables['site_status']['issues'] = $issue_counts;
		}

		if ( 'dashboard' !== $screen->base && ( ! isset( $_GET['tab'] ) || ( isset( $_GET['tab'] ) && 'site-status' === $_GET['tab'] ) ) ) {
			$tests = Health_Check_Site_Status::get_tests();

			// Don't run https test on localhost
			if ( 'localhost' === preg_replace( '|https?://|', '', get_site_url() ) ) {
				unset( $tests['direct']['https_status'] );
			}

			foreach ( $tests['direct'] as $test ) {
				if ( is_string( $test['test'] ) ) {
					$test_function = sprintf(
						'get_test_%s',
						$test['test']
					);

					if ( method_exists( $this, $test_function ) && is_callable( array( $this, $test_function ) ) ) {
						/**
						 * Filter the output of a finished Site Health test.
						 *
						 * @since 5.3.0
						 *
						 * @param array $test_result {
						 *     An associated array of test result data.
						 *
						 *     @param string $label  A label describing the test, and is used as a header in the output.
						 *     @param string $status The status of the test, which can be a value of `good`, `recommended` or `critical`.
						 *     @param array  $badge {
						 *         Tests are put into categories which have an associated badge shown, these can be modified and assigned here.
						 *
						 *         @param string $label The test label, for example `Performance`.
						 *         @param string $color Default `blue`. A string representing a color to use for the label.
						 *     }
						 *     @param string $description A more descriptive explanation of what the test looks for, and why it is important for the end user.
						 *     @param string $actions     An action to direct the user to where they can resolve the issue, if one exists.
						 *     @param string $test        The name of the test being ran, used as a reference point.
						 * }
						 */
						$health_check_js_variables['site_status']['direct'][] = apply_filters( 'site_status_test_result', call_user_func( array( $this, $test_function ) ) );
						continue;
					}
				}

				if ( is_callable( $test['test'] ) ) {
					$health_check_js_variables['site_status']['direct'][] = apply_filters( 'site_status_test_result', call_user_func( $test['test'] ) );
				}
			}

			foreach ( $tests['async'] as $test ) {
				if ( is_string( $test['test'] ) ) {
					$health_check_js_variables['site_status']['async'][] = array(
						'test'      => $test['test'],
						'completed' => false,
					);
				}
			}
		}

		if ( ! wp_script_is( 'clipboard', 'registered' ) ) {
			wp_register_script( 'clipboard', trailingslashit( HEALTH_CHECK_PLUGIN_URL ) . 'assets/javascript/clipboard.min.js', array(), '2.0.4' );
		}

		wp_enqueue_style( 'health-check', trailingslashit( HEALTH_CHECK_PLUGIN_URL ) . 'assets/css/health-check.css', array(), HEALTH_CHECK_PLUGIN_VERSION );

		wp_enqueue_script( 'health-check', trailingslashit( HEALTH_CHECK_PLUGIN_URL ) . 'assets/javascript/health-check.js', array( 'jquery', 'wp-a11y', 'clipboard', 'wp-util' ), HEALTH_CHECK_PLUGIN_VERSION, true );

		wp_localize_script( 'health-check', 'SiteHealth', $health_check_js_variables );
	}

	/**
	 * Add item to the admin menu.
	 *
	 * @uses add_dashboard_page()
	 * @uses __()
	 *
	 * @return void
	 */
	public function action_admin_menu() {
		$critical_issues = 0;
		$issue_counts    = get_transient( 'health-check-site-status-result' );

		if ( false !== $issue_counts ) {
			$issue_counts = json_decode( $issue_counts );

			$critical_issues = absint( $issue_counts->critical );
		}

		$critical_count = sprintf(
			'<span class="update-plugins count-%d"><span class="update-count">%s</span></span>',
			esc_attr( $critical_issues ),
			sprintf(
				'%d<span class="screen-reader-text"> %s</span>',
				esc_html( $critical_issues ),
				esc_html_x( 'Critical issues', 'Issue counter label for the admin menu', 'health-check' )
			)
		);

		$menu_title =
			sprintf(
				// translators: %s: Critical issue counter, if any.
				_x( 'Site Health %s', 'Menu Title', 'health-check' ),
				( ! $issue_counts || $critical_issues < 1 ? '' : $critical_count )
			);

		remove_submenu_page( 'tools.php', 'site-health.php' );

		add_submenu_page(
			'tools.php',
			_x( 'Site Health', 'Page Title', 'health-check' ),
			$menu_title,
			'view_site_health_checks',
			'health-check',
			array( $this, 'dashboard_page' )
		);
	}

	/**
	 * Add a troubleshooting action link to plugins.
	 *
	 * @param $actions
	 * @param $plugin_file
	 * @param $plugin_data
	 * @param $context
	 *
	 * @return array
	 */
	public function troubleshoot_plugin_action( $actions, $plugin_file, $plugin_data, $context ) {
		// Don't add anything if this is a Must-Use plugin, we can't touch those.
		if ( 'mustuse' === $context ) {
			return $actions;
		}

		// Only add troubleshooting actions to active plugins.
		if ( ! is_plugin_active( $plugin_file ) ) {
			return $actions;
		}

		// Set a slug if the plugin lives in the plugins directory root.
		if ( ! stristr( $plugin_file, '/' ) ) {
			$plugin_slug = $plugin_file;
		} else { // Set the slug for plugin inside a folder.
			$plugin_slug = explode( '/', $plugin_file );
			$plugin_slug = $plugin_slug[0];
		}

		$actions['troubleshoot'] = sprintf(
			'<a href="%s">%s</a>',
			esc_url(
				add_query_arg(
					array(
						'health-check-troubleshoot-plugin' => $plugin_slug,
						'_wpnonce'                         => wp_create_nonce( 'health-check-troubleshoot-plugin-' . $plugin_slug ),
					),
					admin_url( 'plugins.php' )
				)
			),
			esc_html__( 'Troubleshoot', 'health-check' )
		);

		return $actions;
	}

	/**
	 * Add a quick-access action link to the Heath Check page.
	 *
	 * @param $actions
	 *
	 * @return array
	 */
	public function page_plugin_action( $actions ) {

		$page_link = sprintf(
			'<a href="%s">%s</a>',
			menu_page_url( 'health-check', false ),
			_x( 'Health Check', 'Menu, Section and Page Title', 'health-check' )
		);
		array_unshift( $actions, $page_link );
		return $actions;
	}

	/**
	 * Render our admin page.
	 *
	 * @uses _e()
	 * @uses esc_html__()
	 * @uses printf()
	 * @uses sprintf()
	 * @uses menu_page_url()
	 * @uses dirname()
	 *
	 * @return void
	 */
	public function dashboard_page() {
		include_once( HEALTH_CHECK_PLUGIN_DIRECTORY . '/pages/site-health-header.php' );

		switch ( Health_Check::current_tab() ) {
			case 'debug':
				include_once( HEALTH_CHECK_PLUGIN_DIRECTORY . '/pages/debug-data.php' );
				break;
			case 'phpinfo':
				include_once( HEALTH_CHECK_PLUGIN_DIRECTORY . '/pages/phpinfo.php' );
				break;
			case 'troubleshoot':
				include_once( HEALTH_CHECK_PLUGIN_DIRECTORY . '/pages/troubleshoot.php' );
				break;
			case 'tools':
				include_once( HEALTH_CHECK_PLUGIN_DIRECTORY . '/pages/tools.php' );
				break;
			case 'site-status':
			default:
				include_once( HEALTH_CHECK_PLUGIN_DIRECTORY . '/pages/site-status.php' );
		}

		// Close out the div tag opened as a wrapper in the header.
		echo '</div>';
	}

	static function tabs() {
		return array(
			'site-status'  => esc_html__( 'Status', 'health-check' ),
			'debug'        => esc_html__( 'Info', 'health-check' ),
			'troubleshoot' => esc_html__( 'Troubleshooting', 'health-check' ),
			'tools'        => esc_html__( 'Tools', 'health-check' ),
		);
	}

	static function current_tab() {
		return ( isset( $_GET['tab'] ) ? $_GET['tab'] : 'site-status' );
	}

	/**
	 * Display styled admin notices.
	 *
	 * @uses printf()
	 *
	 * @param string $message A sanitized string containing our notice message.
	 * @param string $status  A string representing the status type.
	 *
	 * @return void
	 */
	static function display_notice( $message, $status = 'success' ) {
		printf(
			'<div class="notice notice-%s inline"><p>%s</p></div>',
			esc_attr( $status ),
			$message
		);
	}

	/**
	 * Display admin notices if we have any queued.
	 *
	 * @return void
	 */
	public function admin_notices() {
		foreach ( $this->admin_notices as $admin_notice ) {
			printf(
				'<div class="notice notice-%s"><p>%s</p></div>',
				esc_attr( $admin_notice->type ),
				$admin_notice->message
			);
		}
	}

	public function cron_schedules( $schedules ) {
		if ( ! isset( $schedules['weekly'] ) ) {
			$schedules['weekly'] = array(
				'interval' => 7 * DAY_IN_SECONDS,
				'display'  => __( 'Once weekly', 'health-check' ),
			);
		}

		return $schedules;
	}


	/**
	 * Conditionally show a form for providing filesystem credentials when introducing our troubleshooting mode plugin.
	 *
	 * @uses wp_nonce_url()
	 * @uses add_query_arg()
	 * @uses admin_url()
	 * @uses request_filesystem_credentials()
	 * @uses WP_Filesystem
	 *
	 * @param array $args Any WP_Filesystem arguments you wish to pass.
	 *
	 * @return bool
	 */
	static function get_filesystem_credentials( $args = array() ) {
		$args = array_merge(
			array(
				'page' => 'health-check',
				'tab'  => 'troubleshoot',
			),
			$args
		);

		$url   = wp_nonce_url( add_query_arg( $args, admin_url() ) );
		$creds = request_filesystem_credentials( $url, '', false, WP_CONTENT_DIR, array( 'health-check-troubleshoot-mode', 'action', '_wpnonce' ) );
		if ( false === $creds ) {
			return false;
		}

		if ( ! WP_Filesystem( $creds ) ) {
			request_filesystem_credentials( $url, '', true, WPMU_PLUGIN_DIR, array( 'health-check-troubleshoot-mode', 'action', '_wpnonce' ) );
			return false;
		}

		return true;
	}

	public static function plugin_activation() {
		if ( ! wp_next_scheduled( 'health-check-scheduled-site-status-check' ) ) {
			wp_schedule_event( time(), 'weekly', 'health-check-scheduled-site-status-check' );
		}
	}

	public static function plugin_deactivation() {
		wp_clear_scheduled_hook( 'health-check-scheduled-site-status-check' );
	}
}
