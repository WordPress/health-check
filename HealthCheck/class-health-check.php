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

		add_filter( 'plugin_action_links', array( $this, 'troubleshoot_plugin_action' ), 20, 4 );
		add_filter( 'plugin_action_links_' . plugin_basename( HEALTH_CHECK_PLUGIN_FILE ), array( $this, 'page_plugin_action' ) );

		add_action( 'admin_notices', array( $this, 'admin_notices' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueues' ) );

		add_action( 'init', array( $this, 'start_troubleshoot_mode' ) );
		add_action( 'load-plugins.php', array( $this, 'start_troubleshoot_single_plugin_mode' ) );

		add_action( 'wp_ajax_health-check-loopback-no-plugins', array( 'Health_Check_Loopback', 'loopback_no_plugins' ) );
		add_action( 'wp_ajax_health-check-loopback-individual-plugins', array( 'Health_Check_Loopback', 'loopback_test_individual_plugins' ) );
		add_action( 'wp_ajax_health-check-loopback-default-theme', array( 'Health_Check_Loopback', 'loopback_test_default_theme' ) );

		add_filter( 'user_has_cap', array( $this, 'maybe_grant_site_health_caps' ), 1, 4 );

		add_filter( 'site_health_navigation_tabs', array( $this, 'add_site_health_navigation_tabs' ) );
		add_action( 'site_health_tab_content', array( $this, 'add_site_health_tab_content' ) );

		add_action( 'init', array( $this, 'maybe_remove_old_scheduled_events' ) );
	}

	/**
	 * Disable scheduled events previously used by the plugin, but now part of WordPress core.
	 *
	 * @return void
	 */
	public function maybe_remove_old_scheduled_events() {
		if ( wp_next_scheduled( 'health-check-scheduled-site-status-check' ) ) {
			wp_clear_scheduled_hook( 'health-check-scheduled-site-status-check' );
		}
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
		if ( 'tools_page_site-health' !== $screen->id && 'site-health' !== $screen->id ) {
			return;
		}

		wp_enqueue_style( 'health-check', trailingslashit( HEALTH_CHECK_PLUGIN_URL ) . 'build/health-check.css', array(), HEALTH_CHECK_PLUGIN_VERSION );

		// If the WordPress 5.2+ version of Site Health is used, do some extra checks to not mess with core scripts and styles.
		if ( 'site-health' === $screen->id ) {
			$plugin_tabs = array(
				'tools',
				'troubleshoot',
			);

			if ( ! isset( $_GET['tab'] ) || ! in_array( $_GET['tab'], $plugin_tabs, true ) ) {
				return;
			}
		}

		wp_enqueue_script( 'health-check-tools', trailingslashit( HEALTH_CHECK_PLUGIN_URL ) . 'build/health-check-tools.js', array( 'jquery' ), HEALTH_CHECK_PLUGIN_VERSION );

		wp_localize_script(
			'health-check-tools',
			'HealthCheck',
			array(
				'rest_api' => array(
					'tools' => array(
						'plugin_compat' => rest_url( 'health-check/v1/plugin-compat' ),
					),
				),
				'nonce'    => array(
					'rest_api'              => wp_create_nonce( 'wp_rest' ),
					'files_integrity_check' => wp_create_nonce( 'health-check-files-integrity-check' ),
					'view_file_diff'        => wp_create_nonce( 'health-check-view-file-diff' ),
					'mail_check'            => wp_create_nonce( 'health-check-mail-check' ),
				),
			)
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
			admin_url( 'site-health.php' ),
			_x( 'Health Check', 'Menu, Section and Page Title', 'health-check' )
		);
		array_unshift( $actions, $page_link );
		return $actions;
	}

	static function tabs() {
		return array(
			''             => esc_html__( 'Status', 'health-check' ), // The status tab is the front page, and therefore has no tab key relation.
			'debug'        => esc_html__( 'Info', 'health-check' ),
			'troubleshoot' => esc_html__( 'Troubleshooting', 'health-check' ),
			'tools'        => esc_html__( 'Tools', 'health-check' ),
		);
	}

	public function add_site_health_navigation_tabs( $tabs ) {
		return array_merge(
			$tabs,
			array(
				'troubleshoot' => esc_html__( 'Troubleshooting', 'health-check' ),
				'tools'        => esc_html__( 'Tools', 'health-check' ),
			)
		);
	}

	public function add_site_health_tab_content( $tab ) {
		switch ( $tab ) {
			case 'troubleshoot':
				include_once( HEALTH_CHECK_PLUGIN_DIRECTORY . '/pages/troubleshoot.php' );
				break;
			case 'tools':
				include_once( HEALTH_CHECK_PLUGIN_DIRECTORY . '/pages/tools.php' );
				break;
		}
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
}
