<?php
/**
 * The main Health Check class
 *
 * @package Health_Check
 */

namespace HealthCheck;

if ( ! class_exists( '\HealthCheck\Health_Check' ) ) {

	class Health_Check {

		/**
		 * Class constructor.
		 */
		public function __construct() {
			$this->init();
		}

		/**
		 * Initialize plugin functionality.
		 */
		public function init() {
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
			add_filter( 'plugin_action_links_' . plugin_basename( HEALTH_CHECK_PLUGIN_FILE ), array( $this, 'plugin_actions' ) );

			// ✅ Changed from 'plugins_loaded' to 'init' to prevent textdomain load issues in WP 6.7+
			add_action( 'init', array( $this, 'load_i18n' ) );

			// Admin-specific functionality.
			if ( is_admin() ) {
				require_once( dirname( __FILE__ ) . '/class-health-check-admin.php' );
			}

			// AJAX endpoints.
			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				require_once( dirname( __FILE__ ) . '/class-health-check-ajax.php' );
			}
		}

		/**
		 * Add plugin options to the admin menu.
		 */
		public function admin_menu() {
			add_management_page(
				__( 'Site Health', 'health-check' ),
				__( 'Site Health', 'health-check' ),
				'manage_options',
				'health-check',
				array( $this, 'render' )
			);
		}

		/**
		 * Render the plugin page.
		 */
		public function render() {
			require_once( dirname( __FILE__ ) . '/views/tab-dashboard.php' );
		}

		/**
		 * Load the plugin textdomain.
		 */
		public function load_i18n() {
			load_plugin_textdomain( 'health-check', false, dirname( plugin_basename( HEALTH_CHECK_PLUGIN_FILE ) ) . '/languages' );
		}

		/**
		 * Add plugin links to the plugin list table.
		 *
		 * @param array $links Existing plugin action links.
		 * @return array
		 */
		public function plugin_actions( $links ) {
			$links[] = sprintf(
				'<a href="%s">%s</a>',
				admin_url( 'tools.php?page=health-check' ),
				esc_html__( 'Site Health', 'health-check' )
			);

			return $links;
		}

		return true;
	}
}
