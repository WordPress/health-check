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
 * Class Health_Check
 */
class Health_Check {

	/**
	 * Notices to show at the head of the admin screen.
	 *
	 * @var array
	 */
	public $admin_notices = array();

	/**
	 * Health_Check constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Plugin initiation.
	 *
	 * @return void
	 */
	public function init() {
		add_filter( 'plugin_action_links', array( $this, 'troubleshoot_plugin_action' ), 20, 4 );
		add_filter(
			'plugin_action_links_' . plugin_basename( HEALTH_CHECK_PLUGIN_FILE ),
			array( $this, 'page_plugin_action' )
		);

		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueues' ) );

		add_action( 'init', array( $this, 'start_troubleshoot_mode' ) );
		add_action( 'load-plugins.php', array( $this, 'start_troubleshoot_single_plugin_mode' ) );

		add_action( 'init', array( $this, 'maybe_remove_old_scheduled_events' ) );
	}

	/**
	 * Disable scheduled events previously used by the plugin.
	 *
	 * @return void
	 */
	public function maybe_remove_old_scheduled_events() {
		if ( wp_next_scheduled( 'health-check-scheduled-site-status-check' ) ) {
			wp_clear_scheduled_hook( 'health-check-scheduled-site-status-check' );
		}
	}

	/**
	 * Filters the user capabilities to grant site health access.
	 *
	 * @return bool[]
	 */
	public function maybe_grant_site_health_caps( $allcaps, $caps, $args, $user ) {
		if ( ! empty( $allcaps['install_plugins'] ) && ( ! is_multisite() || is_super_admin( $user->ID ) ) ) {
			$allcaps['view_site_health_checks'] = true;
		}

		return $allcaps;
	}

	/**
	 * Initiate troubleshooting mode.
	 *
	 * @return void
	 */
	public function start_troubleshoot_mode() {
		if (
			! isset( $_POST['health-check-troubleshoot-mode'] ) ||
			! current_user_can( 'view_site_health_checks' )
		) {
			return;
		}

		if (
			! isset( $_POST['_wpnonce'] ) ||
			! wp_verify_nonce( $_POST['_wpnonce'], 'health-check-enable-troubleshooting' )
		) {
			return;
		}

		Health_Check_Troubleshoot::initiate_troubleshooting_mode();
	}

	/**
	 * Enqueue assets.
	 *
	 * @return void
	 */
	public function enqueues() {
		$screen = get_current_screen();

		if ( 'tools_page_site-health' !== $screen->id && 'site-health' !== $screen->id ) {
			return;
		}

		$health_check = include HEALTH_CHECK_PLUGIN_DIRECTORY . 'build/health-check.asset.php';

		wp_enqueue_style(
			'health-check',
			trailingslashit( HEALTH_CHECK_PLUGIN_URL ) . 'build/health-check.css',
			array(),
			$health_check['version']
		);
	}

	/**
	 * Get the current Site Health tab.
	 *
	 * @return string Current Site Health tab slug.
	 */
	public static function current_tab() {
		if ( empty( $_GET['tab'] ) ) {
			return 'site-status';
		}

		return sanitize_key( wp_unslash( $_GET['tab'] ) );
	}

	/**
	 * Display styled admin notice.
	 *
	 * @param string $message Notice message.
	 * @param string $status  Notice type.
	 *
	 * @return void
	 */
	public static function display_notice( $message, $status = 'success' ) {
		printf(
			'<div class="notice notice-%s inline"><p>%s</p></div>',
			esc_attr( $status ),
			$message
		);
	}

	/**
	 * Display queued admin notices.
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
	 * Get filesystem credentials.
	 *
	 * @param array $args Optional arguments.
	 *
	 * @return bool
	 */
	public static function get_filesystem_credentials( $args = array() ) {
		$args = array_merge(
			array(
				'page' => 'health-check',
				'tab'  => 'troubleshoot',
			),
			$args
		);

		$url   = wp_nonce_url( add_query_arg( $args, admin_url() ) );
		$creds = request_filesystem_credentials(
			$url,
			'',
			false,
			WP_CONTENT_DIR,
			array( 'health-check-troubleshoot-mode', 'action', '_wpnonce' )
		);

		if ( false === $creds ) {
			return false;
		}

		if ( ! WP_Filesystem( $creds ) ) {
			return false;
		}

		return true;
	}
}
