<?php
/*
	Plugin Name: Health Check Disable Plugins
	Description: Conditionally disabled plugins on your site for a given session, used to rule out plugin interactions during troubleshooting.
	Version: 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'We\'re sorry, but you can not directly access this file.' );
}

class Health_Check_Troubleshooting_MU {
	private $override_active = true;
	private $default_theme   = true;

	private $available_query_args = array(
		'health-check-disable-plugins',
		'health-check-disable-plugins-hash',
		'health-check-disable-troubleshooting',
		'health-check-toggle-default-theme',
		'health-check-troubleshoot-enable-plugin',
		'health-check-troubleshoot-disable-plugin',
	);

	public function __construct() {
		$this->init();
	}

	public function init() {
		add_action( 'admin_bar_menu', array( $this, 'health_check_troubleshoot_menu_bar' ), 999 );

		add_filter( 'option_active_plugins', array( $this, 'health_check_loopback_test_disable_plugins' ) );
		add_filter( 'option_active_sitewide_plugins', array( $this, 'health_check_loopback_test_disable_plugins' ) );

		add_filter( 'stylesheet', array( $this, 'health_check_troubleshoot_theme' ) );
		add_filter( 'template', array( $this, 'health_check_troubleshoot_theme' ) );

		add_action( 'wp_logout', array( $this, 'health_check_troubleshooter_mode_logout' ) );
		add_action( 'init', array( $this, 'health_check_troubleshoot_get_captures' ) );

		$this->default_theme = ( 'yes' === get_option( 'health-check-default-theme', 'yes' ) ? true : false );
	}

	private function is_troubleshooting() {
		// Check if a session cookie to disable plugins has been set.
		if ( isset( $_COOKIE['health-check-disable-plugins'] ) ) {
			$_GET['health-check-disable-plugin-hash'] = $_COOKIE['health-check-disable-plugins'];
		}


		// If the disable hash isn't set, no need to interact with things.
		if ( ! isset( $_GET['health-check-disable-plugin-hash'] ) ) {
			return false;
		}

		// If the plugin hash is not valid, we also break out
		$disable_hash = get_option( 'health-check-disable-plugin-hash', '' );
		if ( $disable_hash !== $_GET['health-check-disable-plugin-hash'] ) {
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

		$allowed_plugins = get_option( 'health-check-allowed-plugins', array() );

		// If we've received a comma-separated list of allowed plugins, we'll add them to the array of allowed plugins.
		if ( isset( $_GET['health-check-allowed-plugins'] ) ) {
			$allowed_plugins = explode( ',', $_GET['health-check-allowed-plugins'] );
		}

		foreach ( $plugins as $plugin_no => $plugin_path ) {
			// Split up the plugin path, [0] is the slug and [1] holds the primary plugin file.
			$plugin_parts = explode( '/', $plugin_path );

			// We may want to allow individual, or groups of plugins, so introduce a skip-mechanic for those scenarios.
			if ( in_array( $plugin_parts[0], $allowed_plugins ) ) {
				continue;
			}

			// Remove the reference to this plugin.
			unset( $plugins[ $plugin_no ] );
		}

		// Return a possibly modified list of activated plugins.
		return $plugins;
	}

	function health_check_troubleshoot_theme( $theme ) {
		if ( ! $this->is_troubleshooting() || ! $this->override_active || ! $this->default_theme ) {
			return $theme;
		}

		$default_themes = array(
			'twentyseventeen',
			'twentysixteen',
			'twentyfifteen',
			'twentyfourteen',
			'twentythirteen',
			'twentytwelve',
			'twentyeleven',
			'twentyten'
		);

		foreach ( $default_themes AS $default_theme ) {
			if ( is_dir( WP_CONTENT_DIR . '/themes/' . $default_theme ) ) {
				return $default_theme;
			}
		}

		return $theme;
	}

	function health_check_troubleshooter_mode_logout() {
		if ( ! $this->is_troubleshooting() ) {
			return;
		}

		if ( isset( $_COOKIE['health-check-disable-plugins'] ) ) {
			unset( $_COOKIE['health-check-disable-plugins'] );
			setcookie( 'health-check-disable-plugins', null, 0, COOKIEPATH, COOKIE_DOMAIN );
		}
	}

	function health_check_troubleshoot_get_captures() {
		if ( ! $this->is_troubleshooting() ) {
			return;
		}

		if ( isset( $_GET['health-check-disable-troubleshooting'] ) ) {
			unset( $_COOKIE['health-check-disable-plugins'] );
			setcookie( 'health-check-disable-plugins', null, 0, COOKIEPATH, COOKIE_DOMAIN );

			wp_redirect( remove_query_arg( $this->available_query_args ) );
			die();
		}
		if ( isset( $_GET['health-check-troubleshoot-enable-plugin'] ) ) {
			$allowed_plugins                                                    = get_option( 'health-check-allowed-plugins', array() );
			$allowed_plugins[ $_GET['health-check-troubleshoot-enable-plugin'] ] = $_GET['health-check-troubleshoot-enable-plugin'];

			update_option( 'health-check-allowed-plugins', $allowed_plugins );

			wp_redirect( remove_query_arg( $this->available_query_args ) );
			die();
		}
		if ( isset( $_GET['health-check-troubleshoot-disable-plugin'] ) ) {
			$allowed_plugins = get_option( 'health-check-allowed-plugins', array() );
			unset( $allowed_plugins[ $_GET['health-check-troubleshoot-disable-plugin'] ] );

			update_option( 'health-check-allowed-plugins', $allowed_plugins );

			wp_redirect( remove_query_arg( $this->available_query_args ) );
			die();
		}

		if ( isset( $_GET['health-check-toggle-default-theme'] ) ) {
			if ( $this->default_theme ) {
				update_option( 'health-check-default-theme', 'no' );
			}
			else {
				update_option( 'health-check-default-theme', 'yes' );
			}

			wp_redirect( remove_query_arg( $this->available_query_args ) );
			die();
		}
	}

	function health_check_troubleshoot_menu_bar( $wp_menu ) {
		if ( ! $this->is_troubleshooting() ) {
			return;
		}

		$wp_menu->add_menu( array(
			'id'    => 'health-check',
			'title' => esc_html__( 'Troubleshooting Mode', 'health-check' )
		) );

		$wp_menu->add_node( array(
			'id' => 'health-check-plugins',
			'title' => esc_html__( 'Plugins', 'health-check' ),
			'parent' => 'health-check'
		) );

		$wp_menu->add_group( array(
			'id' => 'health-check-plugins-enabled',
			'parent' => 'health-check-plugins'
		) );
		$wp_menu->add_group( array(
			'id' => 'health-check-plugins-disabled',
			'parent' => 'health-check-plugins'
		) );

		$this->override_active = false;
		$all_plugins           = get_option( 'active_plugins' );
		$allowed_plugins       = get_option( 'health-check-allowed-plugins', array() );
		$this->override_active = true;

		foreach ( $all_plugins as $single_plugin ) {
			$plugin_slug = explode( '/', $single_plugin );
			$plugin_slug = $plugin_slug[0];

			$enabled = true;

			if ( in_array( $plugin_slug, $allowed_plugins ) ) {
				$label = sprintf(
					// Translators: %s: Plugin slug.
					esc_html__( 'Click to disable %s', 'health-check' ),
					sprintf(
						'<strong>%s</strong>',
						$plugin_slug
					)
				);
				$url = add_query_arg( array( 'health-check-troubleshoot-disable-plugin' => $plugin_slug ) );
			} else {
				$enabled = false;
				$label = sprintf(
					// Translators: %s: Plugin slug.
					esc_html__( 'Click to enable %s', 'health-check' ),
					sprintf(
						'<strong>%s</strong>',
						$plugin_slug
					)
				);
				$url = add_query_arg( array( 'health-check-troubleshoot-enable-plugin' => $plugin_slug ) );
			}

			$wp_menu->add_node( array(
				'id'     => sprintf(
					'health-check-plugin-%s',
					$plugin_slug
				),
				'title'  => $label,
				'parent' => ( $enabled ? 'health-check-plugins-enabled' : 'health-check-plugins-disabled' ),
				'href'   => $url
			) );
		}

		$wp_menu->add_group( array(
			'id' => 'health-check-theme',
			'parent' => 'health-check'
		) );

		$wp_menu->add_node( array(
			'id'     => 'health-check-default-theme',
			'title'  => ( $this->default_theme ? esc_html__( 'Use your current theme', 'health-check' ) : esc_html__( 'Use a default theme', 'health-check' ) ),
			'parent' => 'health-check-theme',
			'href'   => add_query_arg( array( 'health-check-toggle-default-theme' => true ) )
		) );

		$wp_menu->add_group( array(
			'id' => 'health-check-status',
			'parent' => 'health-check'
		) );

		$wp_menu->add_node( array(
			'id'     => 'health-check-disable',
			'title'  => esc_html__( 'Disable Troubleshooting Mode', 'health-check' ),
			'parent' => 'health-check-status',
			'href'   => add_query_arg( array( 'health-check-disable-troubleshooting' => true ) )
		) );
	}

}

new Health_Check_Troubleshooting_MU();
