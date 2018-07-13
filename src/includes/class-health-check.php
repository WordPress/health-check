<?php
/**
 * Primary class file for the Health Check plugin.
 *
 * @package Health Check
 */

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

		add_filter( 'plugin_row_meta', array( $this, 'settings_link' ), 10, 2 );

		add_filter( 'plugin_action_links', array( $this, 'troubeshoot_plugin_action' ), 20, 4 );

		add_action( 'admin_footer', array( $this, 'show_backup_warning' ) );

		add_action( 'admin_notices', array( $this, 'admin_notices' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueues' ) );

		add_action( 'init', array( $this, 'start_troubleshoot_mode' ) );
		add_action( 'load-plugins.php', array( $this, 'start_troubleshoot_single_plugin_mode' ) );

		add_action( 'wp_ajax_health-check-loopback-no-plugins', array( 'Health_Check_Loopback', 'loopback_no_plugins' ) );
		add_action( 'wp_ajax_health-check-loopback-individual-plugins', array( 'Health_Check_Loopback', 'loopback_test_individual_plugins' ) );
		add_action( 'wp_ajax_health-check-loopback-default-theme', array( 'Health_Check_Loopback', 'loopback_test_default_theme' ) );
		add_action( 'wp_ajax_health-check-files-integrity-check', array( 'Health_Check_Files_Integrity', 'run_files_integrity_check' ) );
		add_action( 'wp_ajax_health-check-view-file-diff', array( 'Health_Check_Files_Integrity', 'view_file_diff' ) );
		add_action( 'wp_ajax_health-check-mail-check', array( 'Health_Check_Mail_Check', 'run_mail_check' ) );
		add_action( 'wp_ajax_health-check-confirm-warning', array( 'Health_Check_Troubleshoot', 'confirm_warning' ) );

		add_filter( 'health_check_tools_tab', array( 'Health_Check_Files_Integrity', 'tools_tab' ) );
		add_filter( 'health_check_tools_tab', array( 'Health_Check_Mail_Check', 'tools_tab' ) );
	}

	/**
	 * Show a warning modal about keeping backups.
	 *
	 * @uses Health_Check_Troubleshoot::has_seen_warning()
	 *
	 * @return void
	 */
	public function show_backup_warning() {
		if ( Health_Check_Troubleshoot::has_seen_warning() ) {
			return;
		}

		include_once( HEALTH_CHECK_PLUGIN_DIRECTORY . '/modals/backup-warning.php' );
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
		if ( ! isset( $_POST['health-check-troubleshoot-mode'] ) || ! current_user_can( 'manage_options' ) ) {
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
		if ( ! isset( $_GET['health-check-troubleshoot-plugin'] ) || ! current_user_can( 'manage_options' ) ) {
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

		Health_Check_Troubleshoot::initiate_troubleshooting_mode( array(
			$_GET['health-check-troubleshoot-plugin'] => $_GET['health-check-troubleshoot-plugin'],
		) );

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
		// Don't enqueue anything unless we're on the health check page
		if ( ! isset( $_GET['page'] ) || 'health-check' !== $_GET['page'] ) {

			/*
			 * Special consideration, if warnings are not dismissed we need to display
			 * our modal, and thus require our styles, in other locations, before bailing.
			 */
			if ( ! Health_Check_Troubleshoot::has_seen_warning() ) {
				wp_enqueue_style( 'health-check', HEALTH_CHECK_PLUGIN_URL . '/assets/css/health-check.css', array(), HEALTH_CHECK_PLUGIN_VERSION );
			}
			return;
		}

		wp_enqueue_style( 'health-check', HEALTH_CHECK_PLUGIN_URL . '/assets/css/health-check.css', array(), HEALTH_CHECK_PLUGIN_VERSION );

		wp_enqueue_script( 'health-check', HEALTH_CHECK_PLUGIN_URL . '/assets/javascript/health-check.js', array( 'jquery' ), HEALTH_CHECK_PLUGIN_VERSION, true );

		wp_localize_script( 'health-check', 'HealthCheck', array(
			'string'  => array(
				'please_wait'   => esc_html__( 'Please wait...', 'health-check' ),
				'copied'        => esc_html__( 'Copied', 'health-check' ),
				'running_tests' => esc_html__( 'Currently being tested...', 'health-check' ),
			),
			'warning' => array(
				'seen_backup' => Health_Check_Troubleshoot::has_seen_warning(),
			),
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
		add_dashboard_page( _x( 'Health Check', 'Menu, Section and Page Title', 'health-check' ), _x( 'Health Check', 'Menu, Section and Page Title', 'health-check' ), 'manage_options', 'health-check', array( $this, 'dashboard_page' ) );
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
			$meta[] = sprintf( '<a href="%s">' . _x( 'Health Check', 'Menu, Section and Page Title', 'health-check' ) . '</a>', menu_page_url( 'health-check', false ) );
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

		// Set a slug if the plugin lives in the plugins directory root.
		if ( ! stristr( $plugin_file, '/' ) ) {
			$plugin_data['slug'] = $plugin_file;
		}

		$actions['troubleshoot'] = sprintf(
			'<a href="%s">%s</a>',
			esc_url( add_query_arg( array(
				'health-check-troubleshoot-plugin' => ( isset( $plugin_data['slug'] ) ? $plugin_data['slug'] : sanitize_title( $plugin_data['Name'] ) ),
			), admin_url( 'plugins.php' ) ) ),
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
				<?php _ex( 'Health Check', 'Menu, Section and Page Title', 'health-check' ); ?>
			</h1>

			<?php
			$tabs = array(
				'site-status'  => esc_html__( 'Site Status', 'health-check' ),
				'debug'        => esc_html__( 'Debug Information', 'health-check' ),
				'troubleshoot' => esc_html__( 'Troubleshooting', 'health-check' ),
				'phpinfo'      => esc_html__( 'PHP Information', 'health-check' ),
				'tools'        => esc_html__( 'Tools', 'health-check' ),
			);

			$current_tab = ( isset( $_GET['tab'] ) ? $_GET['tab'] : 'site-status' );
			?>

			<h2 class="nav-tab-wrapper wp-clearfix">
				<?php
				foreach ( $tabs as $tab => $label ) {
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
		$creds = request_filesystem_credentials( $url, '', false, WP_CONTENT_DIR, array( 'health-check-troubleshoot-mode', 'action' ) );
		if ( false === $creds ) {
			return false;
		}

		if ( ! WP_Filesystem( $creds ) ) {
			request_filesystem_credentials( $url, '', true, WPMU_PLUGIN_DIR, array( 'health-check-troubleshoot-mode', 'action' ) );
			return false;
		}

		return true;
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
		$functions_exist  = function_exists( 'json_encode' ) && function_exists( 'json_decode' );
		$functions_work   = function_exists( 'json_encode' ) && ( '' != json_encode( 'my test string' ) );

		return $extension_loaded && $functions_exist && $functions_work;
	}
}
