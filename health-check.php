<?php
/**
 * Plugins primary file, in charge of including all other dependencies.
 *
 * @package Health Check
 *
 * Plugin Name: Health Check
 * Plugin URI: http://wordpress.org/plugins/health-check/
 * Description: Checks the health of your WordPress install.
 * Author: The WordPress.org community
 * Version: 0.8.0
 * Author URI: http://wordpress.org/plugins/health-check/
 * Text Domain: health-check
 */

// Check that the file is nto accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'We\'re sorry, but you can not directly access this file.' );
}

// Set the minimum PHP version WordPress supports.
define( 'HEALTH_CHECK_PHP_MIN_VERSION', '5.2.4' );

// Set the PHP version WordPress recommends.
define( 'HEALTH_CHECK_PHP_REC_VERSION', '7.2' );

// Set the minimum MySQL version WordPress supports.
define( 'HEALTH_CHECK_MYSQL_MIN_VERSION', '5.0' );

// Set the MySQL version WordPress recommends.
define( 'HEALTH_CHECK_MYSQL_REC_VERSION', '5.6' );

// Set the plugin version.
define( 'HEALTH_CHECK_PLUGIN_VERSION', '0.8.0' );

// Set the absolute path for the plugin.
define( 'HEALTH_CHECK_PLUGIN_DIRECTORY', plugin_dir_path( __FILE__ ) );

/**
 * Class HealthCheck
 */
class HealthCheck {

	/**
	 * HealthCheck constructor.
	 *
	 * @uses HealthCheck::init()
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
		add_filter( 'plugin_row_meta', array( $this, 'settings_link' ), 10, 2 );

		add_filter( 'plugin_action_links', array( $this, 'troubeshoot_plugin_action' ), 20, 4 );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueues' ) );

		add_action( 'init', array( $this, 'start_troubleshoot_mode' ) );
		add_action( 'init', array( $this, 'start_troubleshoot_single_plugin_mode' ) );

		add_action( 'wp_ajax_health-check-loopback-no-plugins', array( 'Health_Check_Loopback', 'loopback_no_plugins' ) );
		add_action( 'wp_ajax_health-check-loopback-individual-plugins', array( 'Health_Check_Loopback', 'loopback_test_individual_plugins' ) );
	}

	/**
	 * Initiate troubleshooting mode.
	 *
	 * Catch when the troubleshooting form has been submitted, and appropriately set required options and cookies.
	 *
	 * @uses current_user_can()
	 * @uses md5()
	 * @uses rand()
	 * @uses update_option()
	 * @uses setcookie()
	 *
	 * @return void
	 */
	public function start_troubleshoot_mode() {
		if ( ! isset( $_POST['health-check-troubleshoot-mode'] ) || ! isset( $_POST['health-check-troubleshoot-mode-confirmed'] ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$loopback_hash = md5( rand() );
		update_option( 'health-check-disable-plugin-hash', $loopback_hash );

		setcookie( 'health-check-disable-plugins', $loopback_hash, 0, COOKIEPATH, COOKIE_DOMAIN );
	}

	/**
	 * Initiate troubleshooting mode for a specific plugin.
	 *
	 * Catch when the troubleshooting link on an individual plugin has been clicked, and appropriately sets the
	 * required options and cookies.
	 *
	 * @uses current_user_can()
	 * @uses md5()
	 * @uses rand()
	 * @uses update_option()
	 * @uses setcookie()
	 * @uses wp_redirect()
	 * @uses admin_url()
	 *
	 * @return void
	 */
	public function start_troubleshoot_single_plugin_mode() {
		if ( ! isset( $_GET['health-check-troubleshoot-plugin'] ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$loopback_hash = md5( rand() );
		update_option( 'health-check-disable-plugin-hash', $loopback_hash );

		update_option( 'health-check-allowed-plugins', array( $_GET['health-check-troubleshoot-plugin'] ) );

		setcookie( 'health-check-disable-plugins', $loopback_hash, 0, COOKIEPATH, COOKIE_DOMAIN );

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
		load_plugin_textdomain( 'health-check', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
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
		// Don't enqueue anything unless we're on the health check page
		if ( ! isset( $_GET['page'] ) || 'health-check' !== $_GET['page'] ) {
			return;
		}

		wp_enqueue_style( 'health-check', plugins_url( '/assets/css/health-check.css', __FILE__ ), array(), HEALTH_CHECK_PLUGIN_VERSION );

		wp_enqueue_script( 'health-check', plugins_url( '/assets/javascript/health-check.js', __FILE__ ), array( 'jquery' ), HEALTH_CHECK_PLUGIN_VERSION, true );

		wp_localize_script( 'health-check', 'health_check', array(
			'string' => array(
				'please_wait' => esc_html__( 'Please wait...', 'health-check' ),
				'copied'      => esc_html__( 'Copied', 'health-check' )
			)
		) );
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
		add_dashboard_page( __( 'Health Check', 'health-check' ), __( 'Health Check', 'health-check' ), 'manage_options', 'health-check', array( $this, 'dashboard_page' ) );
	}

	/**
	 * Add a quick-access link under our plugin name on the plugins-list.
	 *
	 * @uses plugin_basename()
	 * @uses sprintf()
	 * @uses menu_page_url()
	 *
	 * @param array  $meta An array containing meta links.
	 * @param string $name The plugin slug that these metas relate to.
	 *
	 * @return array
	 */
	public function settings_link( $meta, $name ) {
		if ( plugin_basename( __FILE__ ) === $name ) {
			$meta[] = sprintf( '<a href="%s">' . __( 'Health Check', 'health-check' ) . '</a>', menu_page_url( 'health-check', false ) );
		}

		return $meta;
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
	public function troubeshoot_plugin_action( $actions, $plugin_file, $plugin_data, $context ) {
		// Don't add anything if this is a Must-Use plugin, we can't touch those.
		if ( 'mustuse' === $context ) {
			return $actions;
		}

		// Only add troubleshooting actions to active plugins.
		if ( ! is_plugin_active( $plugin_file ) ) {
			return $actions;
		}

		$actions['troubleshoot'] = sprintf(
			'<a href="%s">%s</a>',
			esc_url( add_query_arg( array(
				'health-check-troubleshoot-plugin' => ( isset( $plugin_data['slug'] ) ? $plugin_data['slug'] : sanitize_title( $plugin_data['Name'] ) )
			), admin_url() ) ),
			esc_html__( 'Troubleshoot', 'health-check' )
		);

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
		?>
		<div class="wrap">
			<h1>
				<?php _e( 'Health Check', 'health-check' ); ?>
			</h1>

			<?php
			$tabs = array(
				'health-check' => esc_html__( 'Health Check', 'health-check' ),
				'debug'        => esc_html__( 'Debug information', 'health-check' ),
				'troubleshoot' => esc_html__( 'Troubleshooting', 'health-check' ),
				'phpinfo'      => esc_html__( 'PHP Information', 'health-check' )
			);

			$current_tab = ( isset( $_GET['tab'] ) ? $_GET['tab'] : 'health-check' );
			?>

			<h2 class="nav-tab-wrapper wp-clearfix">
				<?php
				foreach( $tabs as $tab => $label ) {
					printf(
						'<a href="%s" class="nav-tab %s">%s</a>',
						sprintf(
							'%s&tab=%s',
							menu_page_url( 'health-check', false ),
							$tab
						),
						( $current_tab === $tab ? 'nav-tab-active' : '' ),
						$label
					);
				}
				?>
			</h2>

			<?php
			switch ( $current_tab ) {
				case 'debug':
					include_once( dirname( __FILE__ ) . '/pages/debug-data.php' );
					break;
				case 'phpinfo':
					include_once( dirname( __FILE__ ) . '/pages/phpinfo.php' );
					break;
				case 'troubleshoot':
					include_once( dirname( __FILE__ ) . '/pages/troubleshoot.php' );
					break;
				case 'health-check':
				default:
					include_once( dirname( __FILE__ ) . '/pages/health-check.php' );
			}
			?>
		</div>
		<?php
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
			'<div class="notice notice-%s inline">',
			$status
		);

		printf(
			'<p>%s</p>',
			$message
		);

		echo '</div>';
	}

	/**
	 * Perform a check to see is JSON is enabled.
	 *
	 * @uses extension_loaded()
	 * @uses function_Exists()
	 * @uses son_encode()
	 *
	 * @return bool
	 */
	static function json_check() {
		$extension_loaded = extension_loaded( 'json' );
		$functions_exist = function_exists( 'json_encode' ) && function_exists( 'json_decode' );
		$functions_work = function_exists( 'json_encode' ) && ( '' != json_encode( 'my test string' ) );

		return $extension_loaded && $functions_exist && $functions_work;
	}
}

// Initialize our plugin.
new HealthCheck();

// Include class-files used by our plugin.
require_once( dirname( __FILE__ ) . '/includes/class-health-check-auto-updates.php' );
require_once( dirname( __FILE__ ) . '/includes/class-health-check-wp-cron.php' );
require_once( dirname( __FILE__ ) . '/includes/class-health-check-debug-data.php' );
require_once( dirname( __FILE__ ) . '/includes/class-health-check-loopback.php' );
require_once( dirname( __FILE__ ) . '/includes/class-health-check-troubleshoot.php' );
