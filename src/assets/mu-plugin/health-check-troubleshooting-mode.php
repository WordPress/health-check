<?php
/*
	Plugin Name: Health Check Troubleshooting Mode
	Description: Conditionally disabled themes or plugins on your site for a given session, used to rule out conflicts during troubleshooting.
	Version: 1.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'We\'re sorry, but you can not directly access this file.' );
}

class Health_Check_Troubleshooting_MU {
	private $disable_hash    = null;
	private $override_active = true;
	private $default_theme   = true;
	private $active_plugins  = array();
	private $allowed_plugins = array();
	private $current_theme;
	private $current_theme_details;
	private $self_fetching_theme = false;

	private $available_query_args = array(
		'health-check-disable-plugins',
		'health-check-disable-plugins-hash',
		'health-check-disable-troubleshooting',
		'health-check-change-active-theme',
		'health-check-troubleshoot-enable-plugin',
		'health-check-troubleshoot-disable-plugin',
	);

	private $default_themes = array(
		'twentyseventeen',
		'twentysixteen',
		'twentyfifteen',
		'twentyfourteen',
		'twentythirteen',
		'twentytwelve',
		'twentyeleven',
		'twentyten',
	);

	/**
	 * Health_Check_Troubleshooting_MU constructor.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Actually initiation of the plugin.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'admin_bar_menu', array( $this, 'health_check_troubleshoot_menu_bar' ), 999 );

		add_filter( 'option_active_plugins', array( $this, 'health_check_loopback_test_disable_plugins' ) );
		add_filter( 'option_active_sitewide_plugins', array( $this, 'health_check_loopback_test_disable_plugins' ) );

		add_filter( 'pre_option_template', array( $this, 'health_check_troubleshoot_theme_template' ) );
		add_filter( 'pre_option_stylesheet', array( $this, 'health_check_troubleshoot_theme_stylesheet' ) );

		add_action( 'admin_notices', array( $this, 'prompt_install_default_theme' ) );
		add_filter( 'user_has_cap', array( $this, 'remove_plugin_theme_install' ) );

		add_action( 'plugin_action_links', array( $this, 'plugin_actions' ), 50, 4 );

		add_action( 'admin_notices', array( $this, 'display_dashboard_widget' ) );
		add_action( 'admin_head', array( $this, 'dashboard_widget_styles' ) );
		add_action( 'admin_footer', array( $this, 'dashboard_widget_scripts' ) );

		add_action( 'wp_logout', array( $this, 'health_check_troubleshooter_mode_logout' ) );
		add_action( 'init', array( $this, 'health_check_troubleshoot_get_captures' ) );

		/*
		 * Plugin activations can be forced by other tools in things like themes, so let's
		 * attempt to work around that by forcing plugin lists back and forth.
		 *
		 * This is not an ideal scenario, but one we must accept as reality.
		 */
		add_action( 'activated_plugin', array( $this, 'plugin_activated' ) );

		$this->load_options();
	}

	/**
	 * Set up the class variables based on option table entries.
	 *
	 * @return void
	 */
	public function load_options() {
		$this->disable_hash    = get_option( 'health-check-disable-plugin-hash', null );
		$this->allowed_plugins = get_option( 'health-check-allowed-plugins', array() );
		$this->default_theme   = ( 'yes' === get_option( 'health-check-default-theme', 'yes' ) ? true : false );
		$this->active_plugins  = $this->get_unfiltered_plugin_list();
		$this->current_theme   = get_option( 'health-check-current-theme', false );
	}

	/**
	 * Add a prompt to install a default theme.
	 *
	 * If no default theme exists, we can't reliably assert if an issue is
	 * caused by the theme. In these cases we should provide an easy step
	 * to get to, and install, one of the default themes.
	 *
	 * @return void
	 */
	public function prompt_install_default_theme() {
		if ( ! $this->is_troubleshooting() || $this->has_default_theme() ) {
			return;
		}

		printf(
			'<div class="notice notice-warning dismissable"><p>%s</p><p><a href="%s" class="button button-primary">%s</a></p></div>',
			esc_html__( 'You don\'t have any of the default themes installed. A default theme helps you determine if your current theme is causing conflicts.', 'health-check' ),
			esc_url( admin_url( sprintf(
				'theme-install.php?theme=%s',
				$this->default_themes[0]
			) ) ),
			esc_html__( 'Install a default theme', 'health-check' )
		);
	}

	/**
	 * Remove the `Add` option for plugins and themes.
	 *
	 * When troubleshooting, adding or changing themes and plugins can
	 * lead to unexpected results. Remove these menu items to make it less
	 * likely that a user breaks their site through these.
	 *
	 * @param  array $caps Array containing the current users capabilities.
	 *
	 * @return array
	 */
	public function remove_plugin_theme_install( $caps ) {
		if ( ! $this->is_troubleshooting() ) {
			return $caps;
		}

		$caps['switch_themes'] = false;

		/*
		 * This is to early for `get_current_screen()`, so we have to do it the
		 * old fashioned way with `$_SERVER`.
		 */
		if ( 'plugin-install.php' === substr( $_SERVER['REQUEST_URI'], -18 ) ) {
			$caps['activate_plugins'] = false;
		}

		return $caps;
	}

	/**
	 * Fire on plugin activation.
	 *
	 * When in Troubleshooting Mode, plugin activations
	 * will clear out the DB entry for `active_plugins`, this is bad.
	 *
	 * We fix this by re-setting the DB entry if anything tries
	 * to modify it during troubleshooting.
	 *
	 * @return void
	 */
	public function plugin_activated() {
		if ( ! $this->is_troubleshooting() ) {
			return;
		}

		// Force the database entry for active plugins if someone tried changing plugins while in Troubleshooting Mode.
		update_option( 'active_plugins', $this->active_plugins );
	}

	/**
	 * Modify plugin actions.
	 *
	 * While in Troubleshooting Mode, weird things will happen if you start
	 * modifying your plugin list. Prevent this, but also add in the ability
	 * to enable or disable a plugin during troubleshooting from this screen.
	 *
	 * @param $actions
	 * @param $plugin_file
	 * @param $plugin_data
	 * @param $context
	 *
	 * @return array
	 */
	public function plugin_actions( $actions, $plugin_file, $plugin_data, $context ) {
		if ( ! $this->is_troubleshooting() ) {
			return $actions;
		}

		if ( 'mustuse' === $context ) {
			return $actions;
		}

		/*
		 * Disable all plugin actions when in Troubleshooting Mode.
		 *
		 * We intentionally remove all plugin actions to avoid accidental clicking, activating or deactivating plugins
		 * while our plugin is altering plugin data may lead to unexpected behaviors, so to keep things sane we do
		 * not allow users to perform any actions during this time.
		 */
		$actions = array();

		// This isn't an active plugin, so does not apply to our troubleshooting scenarios.
		if ( ! in_array( $plugin_file, $this->active_plugins ) ) {
			return $actions;
		}

		// Set a slug if the plugin lives in the plugins directory root.
		if ( ! stristr( $plugin_file, '/' ) ) {
			$plugin_data['slug'] = $plugin_file;
		}

		$plugin_slug = ( isset( $plugin_data['slug'] ) ? $plugin_data['slug'] : sanitize_title( $plugin_data['Name'] ) );

		if ( in_array( $plugin_slug, $this->allowed_plugins ) ) {
			$actions['troubleshoot-disable'] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( add_query_arg( array(
					'health-check-troubleshoot-disable-plugin' => $plugin_slug,
				), admin_url( 'plugins.php' ) ) ),
				esc_html__( 'Disable while troubleshooting', 'health-check' )
			);
		} else {
			$actions['troubleshoot-disable'] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( add_query_arg( array(
					'health-check-troubleshoot-enable-plugin' => $plugin_slug,
				), admin_url( 'plugins.php' ) ) ),
				esc_html__( 'Enable while troubleshooting', 'health-check' )
			);
		}

		return $actions;
	}

	/**
	 * Get the actual list of active plugins.
	 *
	 * When in Troubleshooting Mode we override the list of plugins,
	 * this function lets us grab the active plugins list without
	 * any interference.
	 *
	 * @return array Array of active plugins.
	 */
	public function get_unfiltered_plugin_list() {
		$this->override_active = false;
		$all_plugins           = get_option( 'active_plugins' );
		$this->override_active = true;

		return $all_plugins;
	}

	/**
	 * Check if the user is currently in Troubleshooting Mode or not.
	 *
	 * @return bool
	 */
	public function is_troubleshooting() {
		// Check if a session cookie to disable plugins has been set.
		if ( isset( $_COOKIE['health-check-disable-plugins'] ) ) {
			$_GET['health-check-disable-plugin-hash'] = $_COOKIE['health-check-disable-plugins'];
		}

		// If the disable hash isn't set, no need to interact with things.
		if ( ! isset( $_GET['health-check-disable-plugin-hash'] ) ) {
			return false;
		}

		if ( empty( $this->disable_hash ) ) {
			return false;
		}

		// If the plugin hash is not valid, we also break out
		if ( $this->disable_hash !== $_GET['health-check-disable-plugin-hash'] ) {
			return false;
		}

		return true;
	}

	/**
	 * Filter the plugins that are activated in WordPress.
	 *
	 * @param array $plugins An array of plugins marked as active.
	 *
	 * @return array
	 */
	function health_check_loopback_test_disable_plugins( $plugins ) {
		if ( ! $this->is_troubleshooting() || ! $this->override_active ) {
			return $plugins;
		}

		// If we've received a comma-separated list of allowed plugins, we'll add them to the array of allowed plugins.
		if ( isset( $_GET['health-check-allowed-plugins'] ) ) {
			$this->allowed_plugins = explode( ',', $_GET['health-check-allowed-plugins'] );
		}

		foreach ( $plugins as $plugin_no => $plugin_path ) {
			// Split up the plugin path, [0] is the slug and [1] holds the primary plugin file.
			$plugin_parts = explode( '/', $plugin_path );

			// We may want to allow individual, or groups of plugins, so introduce a skip-mechanic for those scenarios.
			if ( in_array( $plugin_parts[0], $this->allowed_plugins ) ) {
				continue;
			}

			// Remove the reference to this plugin.
			unset( $plugins[ $plugin_no ] );
		}

		// Return a possibly modified list of activated plugins.
		return $plugins;
	}

	/**
	 * Check if a default theme exists.
	 *
	 * If a default theme exists, return the most recent one, if not return `false`.
	 *
	 * @return bool|string
	 */
	function has_default_theme() {
		foreach ( $this->default_themes as $default_theme ) {
			if ( $this->theme_exists( $default_theme ) ) {
				return $default_theme;
			}
		}

		return false;
	}

	/**
	 * Check if a theme exists by looking for the slug.
	 *
	 * @param string $theme_slug
	 *
	 * @return bool
	 */
	function theme_exists( $theme_slug ) {
		return is_dir( WP_CONTENT_DIR . '/themes/' . $theme_slug );
	}

	/**
	 * Check if theme overrides are active.
	 *
	 * @return bool
	 */
	function override_theme() {
		if ( ! $this->is_troubleshooting() ) {
			return false;
		}

		return true;
	}

	/**
	 * Override the default theme.
	 *
	 * Attempt to set one of the default themes, or a theme of the users choosing, as the active one
	 * during Troubleshooting Mode.
	 *
	 * @param $default
	 *
	 * @return bool|string
	 */
	function health_check_troubleshoot_theme_stylesheet( $default ) {
		if ( $this->self_fetching_theme ) {
			return $default;
		}

		if ( ! $this->override_theme() ) {
			return $default;
		}

		if ( empty( $this->current_theme_details ) ) {
			$this->self_fetching_theme   = true;
			$this->current_theme_details = wp_get_theme( $this->current_theme );
			$this->self_fetching_theme   = false;
		}

		// If no theme has been chosen, start off by troubleshooting as a default theme if one exists.
		$default_theme = $this->has_default_theme();
		if ( false === $this->current_theme ) {
			if ( $default_theme ) {
				return $default_theme;
			}
		}

		return $this->current_theme;
	}

	/**
	 * Override the default parent theme.
	 *
	 * If this is a child theme, override the parent and provide our users chosen themes parent instead.
	 *
	 * @param $default
	 *
	 * @return bool|string
	 */
	function health_check_troubleshoot_theme_template( $default ) {
		if ( $this->self_fetching_theme ) {
			return $default;
		}

		if ( ! $this->override_theme() ) {
			return $default;
		}

		if ( empty( $this->current_theme_details ) ) {
			$this->self_fetching_theme   = true;
			$this->current_theme_details = wp_get_theme( $this->current_theme );
			$this->self_fetching_theme   = false;
		}

		// If no theme has been chosen, start off by troubleshooting as a default theme if one exists.
		$default_theme = $this->has_default_theme();
		if ( false === $this->current_theme ) {
			if ( $default_theme ) {
				return $default_theme;
			}
		}

		if ( $this->current_theme_details->parent() ) {
			return $this->current_theme_details->get_template();
		}

		return $this->current_theme;
	}

	/**
	 * Disable Troubleshooting Mode on logout.
	 *
	 * If logged in, disable the Troubleshooting Mode when the logout
	 * event is fired, this ensures we start with a clean slate on
	 * the next login.
	 *
	 * @return void
	 */
	function health_check_troubleshooter_mode_logout() {
		if ( ! $this->is_troubleshooting() ) {
			return;
		}

		if ( isset( $_COOKIE['health-check-disable-plugins'] ) ) {
			$this->disable_troubleshooting_mode();
		}
	}

	function disable_troubleshooting_mode() {
		unset( $_COOKIE['health-check-disable-plugins'] );
		setcookie( 'health-check-disable-plugins', null, 0, COOKIEPATH, COOKIE_DOMAIN );
		delete_option( 'health-check-allowed-plugins' );
		delete_option( 'health-check-default-theme' );
		delete_option( 'health-check-current-theme' );

		delete_option( 'health-check-backup-plugin-list' );
	}

	/**
	 * Catch query arguments.
	 *
	 * When in Troubleshooting Mode, look for various GET variables that trigger
	 * various plugin actions.
	 *
	 * @return void
	 */
	function health_check_troubleshoot_get_captures() {
		if ( ! $this->is_troubleshooting() ) {
			return;
		}

		// Disable Troubleshooting Mode.
		if ( isset( $_GET['health-check-disable-troubleshooting'] ) ) {
			$this->disable_troubleshooting_mode();

			wp_redirect( remove_query_arg( $this->available_query_args ) );
			die();
		}

		// Dismiss notices.
		if ( isset( $_GET['health-check-dismiss-notices'] ) && $this->is_troubleshooting() && is_admin() ) {
			update_option( 'health-check-dashboard-notices', array() );

			wp_redirect( admin_url() );
			die();
		}

		// Enable an individual plugin.
		if ( isset( $_GET['health-check-troubleshoot-enable-plugin'] ) ) {
			$old_allowed_plugins = $this->allowed_plugins;

			$this->allowed_plugins[ $_GET['health-check-troubleshoot-enable-plugin'] ] = $_GET['health-check-troubleshoot-enable-plugin'];

			update_option( 'health-check-allowed-plugins', $this->allowed_plugins );

			if ( ! $this->test_site_state() ) {
				$this->allowed_plugins = $old_allowed_plugins;
				update_option( 'health-check-allowed-plugins', $old_allowed_plugins );

				$this->add_dashboard_notice(
					sprintf(
						// translators: %s: The plugin slug that was enabled.
						__( 'When enabling the plugin, %s, a site failure occurred. Because of this the change was automatically reverted.', 'health-check' ),
						$_GET['health-check-troubleshoot-enable-plugin']
					),
					'warning'
				);
			}

			wp_redirect( remove_query_arg( $this->available_query_args ) );
			die();
		}

		// Disable an individual plugin.
		if ( isset( $_GET['health-check-troubleshoot-disable-plugin'] ) ) {
			$old_allowed_plugins = $this->allowed_plugins;

			unset( $this->allowed_plugins[ $_GET['health-check-troubleshoot-disable-plugin'] ] );

			update_option( 'health-check-allowed-plugins', $this->allowed_plugins );

			if ( ! $this->test_site_state() ) {
				$this->allowed_plugins = $old_allowed_plugins;
				update_option( 'health-check-allowed-plugins', $old_allowed_plugins );

				$this->add_dashboard_notice(
					sprintf(
						// translators: %s: The plugin slug that was disabled.
						__( 'When disabling the plugin, %s, a site failure occurred. Because of this the change was automatically reverted.', 'health-check' ),
						$_GET['health-check-troubleshoot-enable-plugin']
					),
					'warning'
				);
			}

			wp_redirect( remove_query_arg( $this->available_query_args ) );
			die();
		}

		// Change the active theme for this session.
		if ( isset( $_GET['health-check-change-active-theme'] ) ) {
			$old_theme = get_option( 'health-check-current-theme' );

			update_option( 'health-check-current-theme', $_GET['health-check-change-active-theme'] );

			if ( ! $this->test_site_state() ) {
				update_option( 'health-check-current-theme', $old_theme );

				$this->add_dashboard_notice(
					sprintf(
						// translators: %s: The theme slug that was switched to.
						__( 'When switching the active theme to %s, a site failure occurred. Because of this we reverted the theme to the one you used previously.', 'health-check' ),
						$_GET['health-check-change-active-theme']
					),
					'warning'
				);
			}

			wp_redirect( remove_query_arg( $this->available_query_args ) );
			die();
		}
	}

	private function add_dashboard_notice( $message, $severity = 'notice' ) {
		$notices = get_option( 'health-check-dashboard-notices', array() );

		$notices[] = array(
			'severity' => $severity,
			'message'  => $message,
			'time'     => date( 'Y-m-d H:i' ),
		);

		update_option( 'health-check-dashboard-notices', $notices );
	}

	/**
	 * Extend the admin bar.
	 *
	 * When in Troubleshooting Mode, introduce a new element to the admin bar to show
	 * enabled and disabled plugins (if conditions are met), switch between themes
	 * and disable Troubleshooting Mode altogether.
	 *
	 * @param WP_Admin_Bar $wp_menu
	 *
	 * @return void
	 */
	function health_check_troubleshoot_menu_bar( $wp_menu ) {
		if ( ! $this->is_troubleshooting() ) {
			return;
		}

		// We need some admin functions to make this a better user experience, so include that file.
		if ( ! is_admin() ) {
			require_once( trailingslashit( ABSPATH ) . 'wp-admin/includes/plugin.php' );
		}

		// Ensure the theme functions are available to us on every page.
		include_once( trailingslashit( ABSPATH ) . 'wp-admin/includes/theme.php' );

		// Add top-level menu item.
		$wp_menu->add_menu( array(
			'id'    => 'health-check',
			'title' => esc_html__( 'Troubleshooting Mode', 'health-check' ),
		) );

		// Add a link to manage plugins if there are more than 20 set to be active.
		if ( count( $this->active_plugins ) > 20 ) {
			$wp_menu->add_node( array(
				'id'     => 'health-check-plugins',
				'title'  => esc_html__( 'Manage active plugins', 'health-check' ),
				'parent' => 'health-check',
				'href'   => admin_url( 'plugins.php' ),
			) );
		} else {
			$wp_menu->add_node( array(
				'id'     => 'health-check-plugins',
				'title'  => esc_html__( 'Plugins', 'health-check' ),
				'parent' => 'health-check',
			) );

			$wp_menu->add_group( array(
				'id'     => 'health-check-plugins-enabled',
				'parent' => 'health-check-plugins',
			) );
			$wp_menu->add_group( array(
				'id'     => 'health-check-plugins-disabled',
				'parent' => 'health-check-plugins',
			) );

			foreach ( $this->active_plugins as $single_plugin ) {
				$plugin_slug = explode( '/', $single_plugin );
				$plugin_slug = $plugin_slug[0];

				$plugin_data = get_plugin_data( trailingslashit( WP_PLUGIN_DIR ) . $single_plugin );

				$enabled = true;

				if ( in_array( $plugin_slug, $this->allowed_plugins ) ) {
					$label = sprintf(
						// Translators: %s: Plugin slug.
						esc_html__( 'Disable %s', 'health-check' ),
						sprintf(
							'<strong>%s</strong>',
							$plugin_data['Name']
						)
					);
					$url = add_query_arg( array(
						'health-check-troubleshoot-disable-plugin' => $plugin_slug,
					) );
				} else {
					$enabled = false;
					$label   = sprintf(
						// Translators: %s: Plugin slug.
						esc_html__( 'Enable %s', 'health-check' ),
						sprintf(
							'<strong>%s</strong>',
							$plugin_data['Name']
						)
					);
					$url = add_query_arg( array(
						'health-check-troubleshoot-enable-plugin' => $plugin_slug,
					) );
				}

				$wp_menu->add_node( array(
					'id'     => sprintf(
						'health-check-plugin-%s',
						$plugin_slug
					),
					'title'  => $label,
					'parent' => ( $enabled ? 'health-check-plugins-enabled' : 'health-check-plugins-disabled' ),
					'href'   => $url,
				) );
			}
		}

		$wp_menu->add_node( array(
			'id'     => 'health-check-theme',
			'title'  => esc_html__( 'Themes', 'health-check' ),
			'parent' => 'health-check',
		) );

		$themes = wp_prepare_themes_for_js();

		foreach ( $themes as $theme ) {
			$node = array(
				'id'     => sprintf(
					'health-check-theme-%s',
					sanitize_title( $theme['id'] )
				),
				'title'  => sprintf(
					'%s %s',
					// translators: Prefix for the active theme in a listing.
					( $theme['active'] ? esc_html__( 'Active:', 'health-check' ) : '' ),
					$theme['name']
				),
				'parent' => 'health-check-theme',
			);

			if ( ! $theme['active'] ) {
				$node['href'] = add_query_arg( array(
					'health-check-change-active-theme' => $theme['id'],
				) );
			}

			$wp_menu->add_node( $node );
		}

		// Add a link to disable Troubleshooting Mode.
		$wp_menu->add_node( array(
			'id'     => 'health-check-disable',
			'title'  => esc_html__( 'Disable Troubleshooting Mode', 'health-check' ),
			'parent' => 'health-check',
			'href'   => add_query_arg( array(
				'health-check-disable-troubleshooting' => true,
			) ),
		) );
	}

	public function test_site_state() {

		// Make sure the Health_Check_Loopback class is available to us, in case the primary plugin is disabled.
		if ( ! method_exists( 'Health_Check_Loopback', 'can_perform_loopback' ) ) {
			$plugin_file = trailingslashit( WP_PLUGIN_DIR ) . 'health-check/includes/class-health-check-loopback.php';

			// Make sure the file exists, in case someone deleted the plugin manually, we don't want any errors.
			if ( ! file_exists( $plugin_file ) ) {

				// If the plugin files are inaccessible, we can't guarantee for the state of the site, so the default is a bad response.
				return false;
			}

			require_once( $plugin_file );
		}

		$loopback_state = Health_Check_Loopback::can_perform_loopback();

		if ( 'good' !== $loopback_state->status ) {
			return false;
		}

		return true;
	}

	public function dashboard_widget_styles() {
		if ( ! $this->is_troubleshooting() ) {
			return;
		}

		// Check that it's the dashboard page, we don't want to disturb any other pages.
		$screen = get_current_screen();
		if ( 'dashboard' !== $screen->id && 'plugins' !== $screen->id ) {
			return;
		}
		?>
<style type="text/css">
	@media all and (min-width: 783px) {
		#health-check-dashboard-widget {
			margin-top: 3rem;
		}
	}

	#health-check-dashboard-widget .welcome-panel-content {
		max-width: initial;
	}

	#health-check-dashboard-widget .notices .no-notices p {
		color: #bfc3c7;
		font-size: 1.2rem;
	}
	#health-check-dashboard-widget .notices .notice {
		margin-left: 0;
	}
	#health-check-dashboard-widget .notices .dismiss-notices {
		float: right;
		margin-right: 1rem;
	}

	#health-check-dashboard-widget .disable-troubleshooting-mode {
		margin-bottom: 1rem;
	}
	@media all and (min-width: 960px) {
		#health-check-dashboard-widget .disable-troubleshooting-mode {
			position: absolute;
			bottom: 1rem;
			right: 1rem;
		}
	}

	#health-check-dashboard-widget .toggle-visibility {
		display: none;
	}
	#health-check-dashboard-widget .toggle-visibility.visible {
		display: block;
	}

	#health-check-dashboard-widget .welcome-panel-column-container {
		position: initial;
	}

	#health-check-dashboard-widget .welcome-panel-column.is-standalone-button {
		width: 100%;
		text-align: right;
	}
	#health-check-dashboard-widget .welcome-panel-column.is-standalone-button .disable-troubleshooting-mode {
		position: relative;
	}
</style>
		<?php
	}

	public function dashboard_widget_scripts() {
		if ( ! $this->is_troubleshooting() ) {
			return;
		}

		// Check that it's the dashboard page, we don't want to disturb any other pages.
		$screen = get_current_screen();
		if ( 'dashboard' !== $screen->id && 'plugins' !== $screen->id ) {
			return;
		}
		?>
<script type="text/javascript">
	jQuery( document ).ready(function( $ ) {
		$( '.health-check-toggle-visibility' ).click(function( e ) {
			var $elements = $( '.toggle-visibility', $( '#' + $ ( this ).data( 'element' ) ) );

			if ( $elements.is( ':visible' ) ) {
				$elements.attr( 'aria-hidden', 'true' ).toggle();
			} else {
				$elements.attr( 'aria-hidden', 'false' ).toggle();
			}
		});
	});
</script>
		<?php
	}

	public function display_dashboard_widget() {
		if ( ! $this->is_troubleshooting() ) {
			return;
		}

		// Check that it's the dashboard page, we don't want to disturb any other pages.
		$screen = get_current_screen();
		if ( 'dashboard' !== $screen->id && 'plugins' !== $screen->id ) {
			return;
		}

		$notices = get_option( 'health-check-dashboard-notices', array() );
		?>
		<div class="wrap">
			<div id="health-check-dashboard-widget" class="welcome-panel">
				<div class="welcome-panel-content">
					<h2>
						<?php esc_html_e( 'Health Check &mdash; Troubleshooting Mode', 'health-check' ); ?>
					</h2>

					<p class="about-description">
						<?php esc_html_e( 'Your site is currently in Troubleshooting Mode. This has no effect on your site visitors, they will continue to view your site as usual, but for you it will look as if you had just installed WordPress for the first time.', 'health-check' ); ?>
					</p>

					<p class="about-description">
						<?php esc_html_e( 'Here you can enable individual plugins or themes, helping you to find out what might be causing strange behaviors on your site. Do note that any changes you make to settings will be kept when you disable Troubleshooting Mode.', 'health-check' ); ?>
					</p>

					<div class="notices">
						<h3>
							<span class="dashicons dashicons-flag"></span>
							<?php esc_html_e( 'Notices', 'health-check' ); ?>
						</h3>

						<?php if ( empty( $notices ) && 'plugins' !== $screen->id ) : ?>
							<div class="no-notices">
								<p>
									<?php esc_html_e( 'There are no notices to show.', 'health-check' ); ?>
								</p>
							</div>
						<?php endif; ?>

						<?php if ( 'plugins' === $screen->id ) : ?>
							<div class="notice notice-warning inline">
								<p>
									<?php esc_html_e( 'Plugin actions, such as activating and deactivating, are not available while in Troubleshooting Mode.', 'health-check' ); ?>
								</p>
							</div>
						<?php endif; ?>

						<?php
						foreach ( $notices as $notice ) {
							printf(
								'<div class="notice notice-%s inline"><p>%s</p></div>',
								esc_attr( $notice['severity'] ),
								esc_html( $notice['message'] )
							);
						}
						?>

						<?php
						if ( ! empty( $notices ) ) {
							printf(
								'<a href="%s" class="dismiss-notices">%s</a>',
								esc_url( add_query_arg( array(
									'health-check-dismiss-notices' => true,
								) ) ),
								esc_html__( 'Dismiss notices', 'health-check' )
							);
						}
						?>
					</div>

					<div class="welcome-panel-column-container">
						<div class="welcome-panel-column">
							<?php if ( 'plugins' !== $screen->id ) : ?>
								<h3>
									<span class="dashicons dashicons-admin-plugins"></span>
									<?php esc_html_e( 'Available Plugins', 'health-check' ); ?>
								</h3>

								<ul id="health-check-plugins">
									<?php
									$active_plugins   = array();
									$inactive_plugins = array();

									foreach ( $this->active_plugins as $count => $single_plugin ) {
										$plugin_slug = explode( '/', $single_plugin );
										$plugin_slug = $plugin_slug[0];

										$plugin_is_visible = true;
										if ( $count >= 5 ) {
											$plugin_is_visible = false;
										}

										$plugin_data = get_plugin_data( trailingslashit( WP_PLUGIN_DIR ) . $single_plugin );

										$actions = array();

										if ( in_array( $plugin_slug, $this->allowed_plugins ) ) {
											$actions[] = sprintf(
												'<a href="%s" aria-label="%s">%s</a>',
												esc_url( add_query_arg( array(
													'health-check-troubleshoot-disable-plugin' => $plugin_slug,
												) ) ),
												esc_attr(
													sprintf(
														// translators: %s: Plugin name.
														__( 'Disable the plugin, %s, while troubleshooting.', 'health-check' ),
														$plugin_data['Name']
													)
												),
												esc_html__( 'Disable', 'health-check' )
											);
										} else {
											$actions[] = sprintf(
												'<a href="%s" aria-label="%s">%s</a>',
												esc_url( add_query_arg( array(
													'health-check-troubleshoot-enable-plugin' => $plugin_slug,
												) ) ),
												esc_attr(
													sprintf(
														// translators: %s: Plugin name.
														__( 'Enable the plugin, %s, while troubleshooting.', 'health-check' ),
														$plugin_data['Name']
													)
												),
												esc_html__( 'Enable', 'health-check' )
											);
										}

										printf(
											'<li class="%s" aria-hidden="%s">%s - %s</li>',
											( ! $plugin_is_visible ? 'toggle-visibility' : '' ),
											( ! $plugin_is_visible ? 'true' : 'false' ),
											esc_html( $plugin_data['Name'] ),
											implode( ' | ', $actions )
										);
									}
									?>
								</ul>

								<?php if ( count( $this->active_plugins ) > 5 ) : ?>
								<p>
									<button type="button" class="button button-link health-check-toggle-visibility toggle-visibility visible" aria-hidden="false" data-element="health-check-plugins">
										<?php esc_html_e( 'Show all plugins', 'health-check' ); ?>
									</button>

									<button type="button" class="button button-link health-check-toggle-visibility toggle-visibility" aria-hidden="true" data-element="health-check-plugins">
										<?php esc_html_e( 'Show fewer plugins', 'health-check' ); ?>
									</button>
								</p>
								<?php endif; ?>
							<?php endif; ?>
						</div>

						<div class="welcome-panel-column">
							<?php if ( 'plugins' !== $screen->id ) : ?>
								<h3>
									<span class="dashicons dashicons-admin-appearance"></span>
									<?php esc_html_e( 'Available Themes', 'health-check' ); ?>
								</h3>

								<ul id="health-check-themes">
									<?php
									$themes = wp_prepare_themes_for_js();

									foreach ( $themes as $count => $theme ) {
										$active = $theme['active'];

										$theme_is_visible = true;
										if ( $count >= 5 ) {
											$theme_is_visible = false;
										}

										$actions = sprintf(
											'<a href="%s" aria-label="%s">%s</a>',
											esc_url( add_query_arg( array(
												'health-check-change-active-theme' => $theme['id'],
											) ) ),
											esc_attr(
												sprintf(
													// translators: %s: Theme name.
													__( 'Switch the active theme to %s', 'health-check' ),
													$theme['name']
												)
											),
											esc_html__( 'Switch to this theme', 'health-check' )
										);

										$plugin_label = sprintf(
											'%s %s',
											// translators: Prefix for the active theme in a listing.
											( $theme['active'] ? esc_html__( 'Active:', 'health-check' ) : '' ),
											$theme['name']
										);

										if ( ! $theme['active'] ) {
											$plugin_label .= ' - ' . $actions;
										}

										printf(
											'<li class="%s" aria-hidden="%s">%s</li>',
											( $theme_is_visible ? '' : 'toggle-visibility' ),
											( $theme_is_visible ? 'false' : 'true' ),
											$plugin_label
										);
									}
									?>
								</ul>

								<?php if ( count( $themes ) > 5 ) : ?>
									<p>
										<button type="button" class="button button-link health-check-toggle-visibility toggle-visibility visible" aria-hidden="false" data-element="health-check-themes">
											<?php esc_html_e( 'Show all themes', 'health-check' ); ?>
										</button>

										<button type="button" class="button button-link health-check-toggle-visibility toggle-visibility" aria-hidden="true">
											<?php esc_html_e( 'Show fewer themes', 'health-check' ); ?>
										</button>
									</p>
								<?php endif; ?>
							<?php endif; ?>
						</div>

						<div class="welcome-panel-column <?php echo ( 'plugins' === $screen->id ? 'is-standalone-button' : '' ); ?>">
							<?php
							printf(
								'<a href="%s" class="button button-primary button-hero disable-troubleshooting-mode">%s</a>',
								esc_url( add_query_arg( array(
									'health-check-disable-troubleshooting' => true,
								) ) ),
								esc_html__( 'Disable Troubleshooting Mode', 'health-check' )
							);
							?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

}

new Health_Check_Troubleshooting_MU();
