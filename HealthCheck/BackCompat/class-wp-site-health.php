<?php
/**
 * Primary class for the Site Health component.
 *
 * @package WordPress
 * @subpackage Site_Health
 * @since 5.2.0
 */

class WP_Site_Health {

	public function __construct() {
		$this->init();
	}

	public function init() {
		add_filter( 'cron_schedules', array( $this, 'cron_schedules' ) );

		add_action( 'admin_menu', array( $this, 'action_admin_menu' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueues' ) );

		add_action( 'site_health_tab_content', array( $this, 'site_health_tab' ) );
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
		if ( 'tools_page_site-health' !== $screen->id ) {
			return;
		}

		wp_enqueue_style( 'health-check', trailingslashit( HEALTH_CHECK_PLUGIN_URL ) . 'build/health-check.css', array(), HEALTH_CHECK_PLUGIN_VERSION );

		wp_enqueue_script( 'health-check', trailingslashit( HEALTH_CHECK_PLUGIN_URL ) . 'build/health-check.js', array( 'jquery' ), HEALTH_CHECK_PLUGIN_VERSION );

		wp_localize_script(
			'health-check',
			'HealthCheck',
			array(
				'nonce' => array(
					'rest_api' => wp_create_nonce( 'wp_rest' ),
				),
			)
		);
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

		add_submenu_page(
			'tools.php',
			_x( 'Site Health', 'Page title', 'health-check' ),
			$menu_title,
			'view_site_health_checks',
			'site-health',
			array( $this, 'render_menu_page' )
		);
	}

	public function render_menu_page() {
		require_once HEALTH_CHECK_PLUGIN_DIRECTORY . '/pages/site-health-header.php';

		$tab = ( isset( $_GET['tab'] ) && ! empty( $_GET['tab'] ) ? $_GET['tab'] : '' );

		do_action( 'site_health_tab_content', $tab );
	}

	public function site_health_tab( $tab ) {
		include_once( HEALTH_CHECK_PLUGIN_DIRECTORY . '/pages/site-health-header.php' );

		switch ( Health_Check::current_tab() ) {
			case 'debug':
				include_once( HEALTH_CHECK_PLUGIN_DIRECTORY . '/pages/debug-data.php' );
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
}

new WP_Site_Health();
