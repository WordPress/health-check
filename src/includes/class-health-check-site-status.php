<?php

// Make sure the file is not directly accessible.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'We\'re sorry, but you can not directly access this file.' );
}

class Health_Check_Site_Status {
	private $php_min_version_check;
	private $php_supported_version_check;
	private $php_rec_version_check;

	private $mysql_min_version_check;
	private $mysql_rec_version_check;

	public  $mariadb                        = false;
	private $mysql_server_version           = null;
	private $health_check_mysql_rec_version = null;

	public function __construct() {
		$this->init();
	}

	public function init() {
		$this->php_min_version_check       = version_compare( HEALTH_CHECK_PHP_MIN_VERSION, PHP_VERSION, '<=' );
		$this->php_supported_version_check = version_compare( HEALTH_CHECK_PHP_SUPPORTED_VERSION, PHP_VERSION, '<=' );
		$this->php_rec_version_check       = version_compare( HEALTH_CHECK_PHP_REC_VERSION, PHP_VERSION, '<=' );

		$this->prepare_sql_data();

		add_action( 'wp_ajax_health-check-site-status', array( $this, 'site_status' ) );

		add_action( 'wp_ajax_health-check-site-status-result', array( $this, 'site_status_result' ) );

		add_action( 'wp_loaded', array( $this, 'check_wp_version_check_exists' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_action( 'health-check-scheduled-site-status-check', array( $this, 'scheduled_check' ) );
	}

	public function enqueue_scripts() {
		if ( ! is_admin() ) {
			return;
		}

		if ( ! isset( $_GET['page'] ) || 'health-check' !== $_GET['page'] ) {
			return;
		}
	}

	private function prepare_sql_data() {
		global $wpdb;

		if ( method_exists( $wpdb, 'db_version' ) ) {
			if ( $wpdb->use_mysqli ) {
				// phpcs:ignore WordPress.DB.RestrictedFunctions.mysql_mysqli_get_server_info
				$mysql_server_type = mysqli_get_server_info( $wpdb->dbh );
			} else {
				// phpcs:ignore WordPress.DB.RestrictedFunctions.mysql_mysql_get_server_info
				$mysql_server_type = mysql_get_server_info( $wpdb->dbh );
			}

			$this->mysql_server_version = $wpdb->get_var( 'SELECT VERSION()' );
		}

		$this->health_check_mysql_rec_version = HEALTH_CHECK_MYSQL_REC_VERSION;

		if ( stristr( $mysql_server_type, 'mariadb' ) ) {
			$this->mariadb                        = true;
			$this->health_check_mysql_rec_version = '10.0';
		}

		$this->mysql_min_version_check = version_compare( HEALTH_CHECK_MYSQL_MIN_VERSION, $this->mysql_server_version, '<=' );
		$this->mysql_rec_version_check = version_compare( $this->health_check_mysql_rec_version, $this->mysql_server_version, '<=' );
	}

	public function check_wp_version_check_exists() {
		if ( ! is_admin() || ! is_user_logged_in() || ! current_user_can( 'manage_options' ) || ! isset( $_GET['health-check-test-wp_version_check'] ) ) {
			return;
		}

		echo ( has_filter( 'wp_version_check', 'wp_version_check' ) ? 'yes' : 'no' );

		die();
	}

	public function site_status_result() {
		check_ajax_referer( 'health-check-site-status-result' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		set_transient( 'health-check-site-status-result', wp_json_encode( $_POST['counts'] ) );
	}

	public function site_status() {
		check_ajax_referer( 'health-check-site-status' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		$function = sprintf(
			'json_test_%s',
			$_POST['feature']
		);

		if ( ! method_exists( $this, $function ) || ! is_callable( array( $this, $function ) ) ) {
			return;
		}

		$call = call_user_func( array( $this, $function ) );
	}

	/**
	 * Tests for WordPress version and outputs it.
	 *
	 * @return array
	 */
	public function get_test_wordpress_version() {
		$result = array(
			'label'       => '',
			'status'      => '',
			'badge'       => array(
				'label' => 'Security',
				'color' => 'red',
			),
			'description' => '',
			'actions'     => '',
			'test'        => 'wordpress_version',
		);

		$core_current_version = get_bloginfo( 'version' );
		$core_updates         = get_core_updates();

		if ( ! is_array( $core_updates ) ) {
			$result['status'] = 'recommended';

			$result['label'] = sprintf(
				// translators: %s: Your current version of WordPress.
				esc_html__( 'WordPress version %s', 'health-check' ),
				$core_current_version
			);

			$result['description'] = sprintf(
				'<p>%s</p>',
				esc_html__( 'We were unable to check if any new versions of WordPress are available.', 'health-check' )
			);

			$result['actions'] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( admin_url( 'update-core.php?force-check=1' ) ),
				esc_html__( 'Check for updates manually', 'health-check' )
			);
		} else {
			foreach ( $core_updates as $core => $update ) {
				if ( 'upgrade' === $update->response ) {
					$current_version = explode( '.', $core_current_version );
					$new_version     = explode( '.', $update->version );

					$current_major = $current_version[0] . '.' . $current_version[1];
					$new_major     = $new_version[0] . '.' . $new_version[1];

					$result['label'] = sprintf(
						// translators: %s: The latest version of WordPress available.
						esc_html__( 'WordPress update available (%s)', 'health-check' ),
						$update->version
					);

					$result['actions'] = sprintf(
						'<a href="%s">%s</a><a href="%s">%s</a>',
						esc_url( admin_url( '' ) ),
						esc_html__( 'Install the latest version of WordPress', 'health-check' ),
						esc_url( 'TODO: Link to release notes' ),
						esc_html__( 'Read the release notes', 'health-check' )
					);

					if ( $current_major !== $new_major ) {
						// This is a major version mismatch.
						$result['status']      = 'recommended';
						$result['description'] = sprintf(
							'<p>%s</p>',
							esc_html__( 'A new version of WordPress is available.', 'health-check' )
						);
					} else {
						// This is a minor version, sometimes considered more critical.
						$result['status']      = 'critical';
						$result['description'] = sprintf(
							'<p>%s</p>',
							esc_html__( 'A new minor update is available for your site. Because minor updates often address security, it\'s important to install them.', 'health-check' )
						);
					}
				} else {
					$result['status'] = 'good';
					$result['label']  = sprintf(
						// translators: %s: The current version of WordPress installed on this site.
						esc_html__( 'Your WordPress version is up to date (%s)', 'health-check' ),
						$core_current_version
					);

					$result['description'] = sprintf(
						'<p>%s</p>',
						esc_html__( 'You are currently running the latest version of WordPress available, keep it up!', 'health-check' )
					);
				}
			}
		}

		return $result;
	}

	public function test_wordpress_version() {
		$check = $this->get_test_wordpress_version();

		printf( '<span class="%s"></span> %s', esc_attr( $check['status'] ), esc_html( $check['label'] ) );
	}

	public function json_wordpress_version() {
		wp_send_json_success( $this->get_test_wordpress_version() );
	}

	/**
	 * Check if the user is currently in Troubleshooting Mode or not.
	 *
	 * @return bool
	 */
	public function is_troubleshooting() {
		// Check if a session cookie to disable plugins has been set.
		if ( isset( $_COOKIE['wp-health-check-disable-plugins'] ) ) {
			$_GET['health-check-disable-plugin-hash'] = $_COOKIE['wp-health-check-disable-plugins'];
		}

		// If the disable hash isn't set, no need to interact with things.
		if ( ! isset( $_GET['health-check-disable-plugin-hash'] ) ) {
			return false;
		}

		$disable_hash = get_option( 'health-check-disable-plugin-hash', null );

		if ( empty( $disable_hash ) ) {
			return false;
		}

		// If the plugin hash is not valid, we also break out
		if ( $disable_hash !== $_GET['health-check-disable-plugin-hash'] ) {
			return false;
		}

		return true;
	}

	public function get_test_plugin_version() {
		$result = array(
			'label'       => esc_html__( 'Your plugins are up to date', 'health-check' ),
			'status'      => 'good',
			'badge'       => array(
				'label' => 'Security',
				'color' => 'red',
			),
			'description' => sprintf(
				'<p>%s</p>',
				esc_html__( 'Plugins extend your site\'s functionality with things like contact forms, ecommerce and much more. That means they have deep access to your site, so it\'s vital to keep them up to date.', 'health-check' )
			),
			'actions'     => '',
			'test'        => 'plugin_version',
		);

		$plugins        = get_plugins();
		$plugin_updates = get_plugin_updates();

		$show_unused_plugins  = true;
		$plugins_have_updates = false;
		$plugins_active       = 0;
		$plugins_total        = 0;
		$plugins_needs_update = 0;

		if ( $this->is_troubleshooting() ) {
			$show_unused_plugins = false;
		}

		foreach ( $plugins as $plugin_path => $plugin ) {
			$plugins_total++;

			if ( is_plugin_active( $plugin_path ) ) {
				$plugins_active++;
			}

			$plugin_version = $plugin['Version'];

			if ( array_key_exists( $plugin_path, $plugin_updates ) ) {
				$plugins_needs_update++;
				$plugins_have_updates = true;
			}
		}

		if ( $plugins_needs_update > 0 ) {
			$result['status'] = 'critical';

			$result['label'] = esc_html__( 'You have plugins waiting to be updated', 'health-check' );

			$result['description'] .= sprintf(
				'<p>%s</p>',
				sprintf(
					// translators: %d: The amount of outdated plugins.
					esc_html( _n(
						'Your site has %d plugin waiting to be updated.',
						'Your site has %d plugins waiting for updates.',
						$plugins_needs_update,
						'health-check'
					) ),
					$plugins_needs_update
				)
			);
		} else {
			$result['description'] .= sprintf(
				'<p>%s</p>',
				sprintf(
					// translators: %d: The amount of plugins.
					esc_html( _n(
						'Your site has %d active plugin, and it is up to date.',
						'Your site has %d active plugins, and they are all up to date.',
						$plugins_active,
						'health-check'
					) ),
					$plugins_active
				)
			);
		}

		if ( ( $plugins_total > $plugins_active ) && $show_unused_plugins ) {
			$unused_plugins = $plugins_total - $plugins_active;

			$result['status'] = 'recommended';

			$result['label'] = esc_html__( 'Inactive plugins should be removed', 'health-check' );

			$result['description'] .= sprintf(
				'<p>%s</p>',
				sprintf(
					// translators: %d: The amount of inactive plugins.
					esc_html( _n(
						'Your site has %d inactive plugin. Inactive plugins are tempting targets for attackers. if you\'re not going to use a plugin, we recommend you remove it.',
						'Your site has %d inactive plugins. Inactive plugins are tempting targets for attackers. if you\'re not going to use a plugin, we recommend you remove it.',
						$unused_plugins,
						'health-check'
					) ),
					$unused_plugins
				)
			);
		}

		return $result;
	}

	public function get_test_theme_version() {
		$result = array(
			'label'       => esc_html__( 'Your themes are up to date', 'health-check' ),
			'status'      => 'good',
			'badge'       => array(
				'label' => 'Security',
				'color' => 'red',
			),
			'description' => sprintf(
				'<p>%s</p>',
				esc_html__( 'Themes add your site\'s look and feel. It\'s important to keep them up to date -- to stay consistent with your brand and keep your site secure.', 'health-check' )
			),
			'actions'     => '',
			'test'        => 'theme_version',
		);

		$theme_updates = get_theme_updates();

		$themes_total        = 0;
		$themes_need_updates = 0;
		$themes_inactive     = 0;

		// This value is changed during processing to determine how many themes are considered a reasonable amount.
		$allowed_theme_count = 1;

		$has_default_theme  = false;
		$has_unused_themes  = false;
		$show_unused_themes = true;

		if ( $this->is_troubleshooting() ) {
			$show_unused_themes = false;
		}

		// Populate a list of all themes available in the install.
		$all_themes   = wp_get_themes();
		$active_theme = wp_get_theme();

		foreach ( $all_themes as $theme_slug => $theme ) {
			$themes_total++;

			if ( WP_DEFAULT_THEME === $theme_slug ) {
				$has_default_theme = true;
			}

			if ( array_key_exists( $theme_slug, $theme_updates ) ) {
				$themes_need_updates++;
			}
		}

		// If this is a child theme, increase the allowed theme count by one, to account for the parent.
		if ( $active_theme->parent() ) {
			$allowed_theme_count++;
		}

		// If there's a default theme installed, we count that as allowed as well.
		if ( $has_default_theme ) {
			$allowed_theme_count++;
		}

		if ( $themes_total > $allowed_theme_count ) {
			$has_unused_themes = true;
			$themes_inactive   = ( $themes_total - $allowed_theme_count );
		}

		if ( $themes_need_updates > 0 ) {
			$result['status'] = 'critical';

			$result['label'] = esc_html__( 'You have themes waiting to be updated', 'health-check' );

			$result['description'] .= sprintf(
				'<p>%s</p>',
				sprintf(
					// translators: %d: The amount of outdated themes.
					esc_html( _n(
						'Your site has %d theme waiting to be updated.',
						'Your site has %d themes waiting to be updated.',
						$themes_need_updates,
						'health-check'
					) ),
					$themes_need_updates
				)
			);
		} else {
			$result['description'] .= sprintf(
				'<p>%s</p>',
				sprintf(
					// translators: %d: The amount of themes.
					esc_html( _n(
						'Your site has %d installed theme, and it is up to date.',
						'Your site has %d installed themes, and they are all up to date.',
						$themes_total,
						'health-check'
					) ),
					$themes_total
				)
			);
		}

		if ( $has_unused_themes && $show_unused_themes ) {

			// This is a child theme, so we want to be a bit more explicit in our messages.
			if ( $active_theme->parent() ) {
				$result['status'] = 'recommended';

				$result['label'] = esc_html__( 'You should remove inactive themes', 'health-check' );

				$result['description'] .= sprintf(
					'<p>%s</p>',
					sprintf(
						// translators: %1$d: The amount of inactive themes. %2$s: The default theme for WordPress. %3$s: The currently active theme. %4$s: The active themes parent theme.
						esc_html( _n(
							'Your site has %1$d inactive theme. To enhance your site’s security, we recommend you remove any themes you\'re not using. You should keep %2$s, the default WordPress theme, %3$s, your current theme and %4$s, its parent theme.',
							'Your site has %1$d inactive themes. To enhance your site’s security, we recommend you remove any themes you\'re not using. You should keep %2$s, the default WordPress theme, %3$s, your current theme and %4$s, its parent theme.',
							$themes_inactive,
							'health-check'
						) ),
						$themes_inactive,
						WP_DEFAULT_THEME,
						$active_theme->name,
						$active_theme->parent()->name
					)
				);

			} else {
				$result['status'] = 'recommended';

				$result['label'] = esc_html__( 'You should remove inactive themes', 'health-check' );

				$result['description'] .= sprintf(
					'<p>%s</p>',
					sprintf(
						// translators: %1$d: The amount of inactive themes. %2$s: The default theme for WordPress. %3$s: The currently active theme.
						esc_html( _n(
							'Your site has %1$d inactive theme, other than %2$s, the default WordPress theme, and %3$s, your active theme. We recommend removing any unused themes to enhance your sites security.',
							'Your site has %1$d inactive themes, other than %2$s, the default WordPress theme, and %3$s, your active theme. We recommend removing any unused themes to enhance your sites security.',
							$themes_inactive,
							'health-check'
						) ),
						$themes_inactive,
						WP_DEFAULT_THEME,
						$active_theme->name
					)
				);
			}
		}

		if ( ! $has_default_theme ) {
			$result['status'] = 'recommended';

			$result['label'] = esc_html__( 'Have a default theme available', 'health-check' );

			$result['description'] .= sprintf(
				'<p>%s</p>',
				esc_html__( 'Your site does not have any default theme. Default themes are used by WordPress automatically if anything is wrong with your normal theme.', 'health-check' )
			);
		}

		return $result;
	}

	public function get_test_php_version() {
		$result = array(
			'label'       => sprintf(
				// translators: %s: The current PHP version
				esc_html__( 'PHP is up to date (%s)', 'health-check' ),
				PHP_VERSION
			),
			'status'      => 'good',
			'badge'       => array(
				'label' => 'Security',
				'color' => 'red',
			),
			'description' => sprintf(
				'<p>%s</p>',
				esc_html__( 'PHP is the language your web server runs. WordPress uses it to get content from the database and build your site\'s pages in real time.', 'health-check' )
			),
			'actions'     => '',
			'test'        => 'php_version',
		);

		if ( ! $this->php_min_version_check ) {
			$result['status'] = 'critical';

			$result['label'] = esc_html__( 'Your PHP version requires an update', 'health-check' );

			$result['actions'] = sprintf(
				'<a href="%s">%s</a>',
				esc_url(
					_x( 'https://wordpress.org/support/upgrade-php/', 'The link to the Update PHP page, which may be localized.', 'health-check' )
				),
				esc_html__( 'Learn more about why you should update PHP', 'health-check' )
			);

			$result['description'] .= sprintf(
				'<p>%s</p>',
				sprintf(
					// translators: %1$s: Current PHP version. %2$s: Recommended PHP version. %3$s: Minimum PHP version.
					esc_html__( 'Your version of PHP, %1$s, is very outdated and no longer getting security updates, exposing your site to attack. Please contact your host and get an upgrade to %2$s, which is the version WordPress recommends. If that\'s not possible, your site will run with version %3$s or newer.', 'health-check' ),
					PHP_VERSION,
					HEALTH_CHECK_PHP_REC_VERSION,
					HEALTH_CHECK_PHP_MIN_VERSION
				)
			);
		} elseif ( ! $this->php_supported_version_check ) {
			$result['status'] = 'recommended';

			$result['label'] = esc_html__( 'Your PHP version should be updated', 'health-check' );

			$result['actions'] = sprintf(
				'<a href="%s">%s</a>',
				esc_url(
					_x( 'https://wordpress.org/support/upgrade-php/', 'The link to the Update PHP page, which may be localized.', 'health-check' )
				),
				esc_html__( 'Learn more about why you should update PHP', 'health-check' )
			);

			$result['description'] .= sprintf(
				'<p>%s</p>',
				sprintf(
					// translators: %1$s: Current PHP version. %2$s: Recommended PHP version.
					esc_html__( 'Your version of PHP, %1$s, is very outdated and no longer receiving security updates. Please contact your host for an upgrade. WordPress recommends PHP version %2$s.', 'health-check' ),
					PHP_VERSION,
					HEALTH_CHECK_PHP_REC_VERSION
				)
			);
		} elseif ( ! $this->php_rec_version_check ) {
			$result['status'] = 'recommended';

			$result['label'] = esc_html__( 'We recommend that you update PHP', 'health-check' );

			$result['actions'] = sprintf(
				'<a href="%s">%s</a>',
				esc_url(
					_x( 'https://wordpress.org/support/upgrade-php/', 'The link to the Update PHP page, which may be localized.', 'health-check' )
				),
				esc_html__( 'Learn more about why you should update PHP', 'health-check' )
			);

			$result['description'] .= sprintf(
				'<p>%s</p>',
				sprintf(
					// translators: %s: Recommended PHP version
					esc_html__( 'For best performance we recommend using PHP %s or higher.', 'health-check' ),
					HEALTH_CHECK_PHP_REC_VERSION
				)
			);
		}

		return $result;
	}

	public function child_test_php_extension_availability( $extension = null, $function = null ) {
		// If no extension or function is passed, claim to fail testing, as we have nothing to test against.
		if ( null === $extension && null === $function ) {
			return false;
		}

		$available = true;

		if ( null !== $extension && ! extension_loaded( $extension ) ) {
			$available = false;
		}
		if ( null !== $function && ! function_exists( $function ) ) {
			$available = false;
		}

		return $available;
	}

	public function get_test_php_extensions() {
		$result = array(
			'label'       => esc_html__( 'Required and recommended modules are installed', 'health-check' ),
			'status'      => 'good',
			'badge'       => array(
				'label' => 'Performance',
				'color' => 'orange',
			),
			'description' => sprintf(
				'<p>%s</p><p>%s</p>',
				esc_html__( 'PHP modules perform most of the tasks on the server that make your site run.', 'health-check' ),
				sprintf(
					// translators: %s: Link to the hosting group page about recommended PHP modules.
					esc_html__( 'The Hosting team maintains a list of those modules, both recommended and required, in %s.', 'health-check' ),
					sprintf(
						'<a href="https://make.wordpress.org/hosting/handbook/handbook/server-environment/#php-extensions">%s</a>',
						esc_html__( 'the team handbook', 'health-check' )
					)
				)
			),
			'actions'     => '',
			'test'        => 'php_extensions',
		);

		/*
		 * An array representing all the modules we wish to test for.
		 *
		 * array $modules {
		 *     An associated array of modules to test for.
		 *
		 *     array $module {
		 *         An associated array of module properties used during testing.
		 *         One of either `$function` or `$extension` must be provided, or they will fail by default.
		 *
		 *         string $function     Optional. A function name to test for the existence of.
		 *         string $extension    Optional. An extension to check if is loaded in PHP.
		 *         bool   $required     Is this a required feature or not.
		 *         string $fallback_for Optional. The module this module replaces as a fallback.
		 *     }
		 * }
		 */
		$modules = array(
			'bcmath'    => array(
				'function' => 'bcadd',
				'required' => false,
			),
			'curl'      => array(
				'function' => 'curl_version',
				'required' => false,
			),
			'exif'      => array(
				'function' => 'exif_read_data',
				'required' => false,
			),
			'filter'    => array(
				'function' => 'filter_list',
				'required' => false,
			),
			'fileinfo'  => array(
				'function' => 'finfo_file',
				'required' => false,
			),
			'mod_xml'   => array(
				'extension' => 'libxml',
				'required'  => false,
			),
			'mysqli'    => array(
				'function' => 'mysqli_connect',
				'required' => false,
			),
			'libsodium' => array(
				'function'            => 'sodium_compare',
				'required'            => false,
				'php_bundled_version' => '7.2.0',
			),
			'openssl'   => array(
				'function' => 'openssl_encrypt',
				'required' => false,
			),
			'pcre'      => array(
				'function' => 'preg_match',
				'required' => false,
			),
			'imagick'   => array(
				'extension' => 'imagick',
				'required'  => false,
			),
			'gd'        => array(
				'extension'    => 'gd',
				'required'     => false,
				'fallback_for' => 'imagick',
			),
			'mcrypt'    => array(
				'extension'    => 'mcrypt',
				'required'     => false,
				'fallback_for' => 'libsodium',
			),
			'xmlreader' => array(
				'extension'    => 'xmlreader',
				'required'     => false,
				'fallback_for' => 'xml',
			),
			'zlib'      => array(
				'extension'    => 'zlib',
				'required'     => false,
				'fallback_for' => 'zip',
			),
		);

		$failures = array();

		foreach ( $modules as $library => $module ) {
			$extension = ( isset( $module['extension'] ) ? $module['extension'] : null );
			$function  = ( isset( $module['function'] ) ? $module['function'] : null );

			// If this module is a fallback for another function, check if that other function passed.
			if ( isset( $module['fallback_for'] ) ) {
				/*
				 * If that other function has a failure, mark this module as required for normal operations.
				 * If that other function hasn't failed, skip this test as it's only a fallback.
				 */
				if ( isset( $failures[ $module['fallback_for'] ] ) ) {
					$module['required'] = true;
				} else {
					continue;
				}
			}

			if ( ! $this->child_test_php_extension_availability( $extension, $function ) && ( ! isset( $module['php_bundled_version'] ) || version_compare( PHP_VERSION, $module['php_bundled_version'], '<' ) ) ) {
				if ( $module['required'] ) {
					$result['status'] = 'critical';
				}

				if ( ! $module['required'] && 'good' === $result['status'] ) {
					$result['status'] = 'recommended';
				}

				$failures[ $library ] = sprintf(
					'<span class="%s"><span class="screen-reader-text">%s</span></span> %s',
					( $module['required'] ? 'error' : 'warning' ),
					( $module['required'] ? esc_html__( 'Error', 'health-check' ) : esc_html__( 'Warning', 'health-check' ) ),
					sprintf(
						// translators: %1$2: If a module is required or recommended. %2$s: The module name.
						__( 'The %1$s module, %2$s, is not installed, or has been disabled.', 'health-check' ),
						( $module['required'] ? __( 'required', 'health-check' ) : __( 'optional', 'health-check' ) ),
						$library
					)
				);
			}
		}

		if ( ! empty( $failures ) ) {
			$output = '<ul>';

			foreach ( $failures as $failure ) {
				$output .= sprintf(
					'<li>%s</li>',
					$failure
				);
			}

			$output .= '</ul>';
		}

		if ( 'good' !== $result['status'] ) {
			if ( 'recommended' === $result['status'] ) {
				$result['label'] = esc_html__( 'One or more recommended modules are missing', 'health-check' );
			}
			if ( 'critical' === $result['status'] ) {
				$result['label'] = esc_html__( 'One or more required modules are missing', 'health-check' );
			}

			$result['description'] .= sprintf(
				'<p>%s</p>',
				$output
			);
		}

		return $result;
	}

	public function get_test_sql_server() {
		$result = array(
			'label'       => esc_html__( 'SQL server is up to date', 'health-check' ),
			'status'      => 'good',
			'badge'       => array(
				'label' => 'Security',
				'color' => 'red',
			),
			'description' => sprintf(
				'<p>%s</p>',
				esc_html__( 'The SQL server is the database where WordPress stores all your site’s content and settings', 'health-check' )
			),
			'actions'     => '',
			'test'        => 'sql_server',
		);

		$db_dropin = file_exists( WP_CONTENT_DIR . '/db.php' );

		if ( ! $this->mysql_rec_version_check ) {
			$result['status'] = 'recommended';

			$result['label'] = esc_html__( 'Outdated SQL server', 'health-check' );

			$result['description'] .= sprintf(
				'<p>%s</p>',
				sprintf(
					// translators: %1$s: The database engine in use (MySQL or MariaDB). %2$s: Database server recommended version number.
					esc_html__( 'For optimal performance and security reasons, we recommend running %1$s version %2$s or higher. Contact your web hosting company to correct this.', 'health-check' ),
					( $this->mariadb ? 'MariaDB' : 'MySQL' ),
					$this->health_check_mysql_rec_version
				)
			);
		}

		if ( ! $this->mysql_min_version_check ) {
			$result['status'] = 'critical';

			$result['label'] = esc_html__( 'Severely outdated SQL server', 'health-check' );

			$result['description'] .= sprintf(
				'<p>%s</p>',
				sprintf(
					// translators: %1$s: The database engine in use (MySQL or MariaDB). %2$s: Database server minimum version number.
					esc_html__( 'WordPress requires %1$s version %2$s or higher. Contact your web hosting company to correct this.', 'health-check' ),
					( $this->mariadb ? 'MariaDB' : 'MySQL' ),
					HEALTH_CHECK_MYSQL_MIN_VERSION
				)
			);
		}

		if ( $db_dropin ) {
			// translators: %s: The database engine in use (MySQL or MariaDB).
			$result['description'] .= sprintf(
				'<p>%s</p>',
				wp_kses(
					sprintf(
						// translators: %s: The name of the database engine being used.
						__( 'You are using a <code>wp-content/db.php</code> drop-in which might mean that a %s database is not being used.', 'health-check' ),
						( $this->mariadb ? 'MariaDB' : 'MySQL' )
					),
					array(
						'code' => true,
					)
				)
			);
		}

		return $result;
	}

	public function get_test_utf8mb4_support() {
		global $wpdb;

		$result = array(
			'label'       => esc_html__( 'UTF8MB4 is supported', 'health-check' ),
			'status'      => 'good',
			'badge'       => array(
				'label' => 'Performance',
				'color' => 'orange',
			),
			'description' => sprintf(
				'<p>%s</p>',
				esc_html__( 'UTF8MB4 is a database storage attribute that makes sure your site can store non-English text and other strings (for instance emoticons) without unexpected problems.', 'health-check' )
			),
			'actions'     => '',
			'test'        => 'utf8mb4_support',
		);

		if ( ! $this->mariadb ) {
			if ( version_compare( $this->mysql_server_version, '5.5.3', '<' ) ) {
				$result['status'] = 'recommended';

				$result['label'] = esc_html__( 'UTF8MB4 requires an SQL update', 'health-check' );

				$result['description'] .= sprintf(
					'<p>%s</p>',
					sprintf(
						/* translators: %1$s: Database engine name. %2$s: Version number. */
						esc_html__( 'WordPress\' utf8mb4 support requires %1$s version %2$s or greater', 'health-check' ),
						'MySQL',
						'5.5.3'
					)
				);
			} else {
				$result['description'] .= sprintf(
					'<p>%s</p>',
					esc_html__( 'Your MySQL version supports utf8mb4', 'health-check' )
				);
			}
		} else { // MariaDB introduced utf8mb4 support in 5.5.0
			if ( version_compare( $this->mysql_server_version, '5.5.0', '<' ) ) {
				$result['status'] = 'recommended';

				$result['label'] = esc_html__( 'UTF8MB4 requires an SQL update', 'health-check' );

				$result['description'] .= sprintf(
					'<p>%s</p>',
					sprintf(
						/* translators: %1$s: Database engine name. %2$s: Version number. */
						esc_html__( 'WordPress\' utf8mb4 support requires %1$s version %2$s or greater', 'health-check' ),
						'MariaDB',
						'5.5.0'
					)
				);
			} else {
				$result['description'] .= sprintf(
					'<p>%s</p>',
					esc_html__( 'Your MariaDB version supports utf8mb4', 'health-check' )
				);
			}
		}

		if ( $wpdb->use_mysqli ) {
			// phpcs:ignore WordPress.DB.RestrictedFunctions.mysql_mysqli_get_client_info
			$mysql_client_version = mysqli_get_client_info();
		} else {
			// phpcs:ignore WordPress.DB.RestrictedFunctions.mysql_mysql_get_client_info
			$mysql_client_version = mysql_get_client_info();
		}

		/*
		 * libmysql has supported utf8mb4 since 5.5.3, same as the MySQL server.
		 * mysqlnd has supported utf8mb4 since 5.0.9.
		 */
		if ( false !== strpos( $mysql_client_version, 'mysqlnd' ) ) {
			$mysql_client_version = preg_replace( '/^\D+([\d.]+).*/', '$1', $mysql_client_version );
			if ( version_compare( $mysql_client_version, '5.0.9', '<' ) ) {
				$result['status'] = 'recommended';

				$result['label'] = esc_html__( 'UTF8MB4 requires a newer client library', 'health-check' );

				$result['description'] .= sprintf(
					'<p>%s</p>',
					sprintf(
						/* translators: %1$s: Name of the library, %2$s: Number of version. */
						esc_html__( 'WordPress\' utf8mb4 support requires MySQL client library (%1$s) version %2$s or newer.', 'health-check' ),
						'mysqlnd',
						'5.0.9'
					)
				);
			}
		} else {
			if ( version_compare( $mysql_client_version, '5.5.3', '<' ) ) {
				$result['status'] = 'recommended';

				$result['label'] = esc_html__( 'UTF8MB4 requires a newer client library', 'health-check' );

				$result['description'] .= sprintf(
					'<p>%s</p>',
					sprintf(
						/* translators: %1$s: Name of the library, %2$s: Number of version. */
						__( 'WordPress\' utf8mb4 support requires MySQL client library (%1$s) version %2$s or newer.', 'health-check' ),
						'libmysql',
						'5.5.3'
					)
				);
			}
		}

		return $result;
	}

	public function get_test_dotorg_communication() {
		$result = array(
			'label'       => esc_html__( 'Can communicate with WordPress.org', 'health-check' ),
			'status'      => '',
			'badge'       => array(
				'label' => 'Security',
				'color' => 'red',
			),
			'description' => sprintf(
				'<p>%s</p>',
				esc_html__( 'Communicating with the WordPress servers is used to check for new versions, Communicating with the WordPress servers is used to check for new versions, and to both install and update WordPress core, themes or plugins.', 'health-check' )
			),
			'actions'     => '',
			'test'        => 'dotorg_communication',
		);

		$wp_dotorg = wp_remote_get( 'https://wordpress.org', array(
			'timeout' => 10,
		) );
		if ( ! is_wp_error( $wp_dotorg ) ) {
			$result['status'] = 'good';
		} else {
			$result['status'] = 'critical';

			$result['label'] = esc_html__( 'Could not reach WordPress.org', 'health-check' );

			$result['description'] .= sprintf(
				'<p>%s</p>',
				sprintf(
					'<span class="error"><span class="screen-reader-text">%s</span></span> %s',
					esc_html__( 'Error', 'health-check' ),
					sprintf(
						// translators: %1$s: The IP address WordPress.org resolves to. %2$s: The error returned by the lookup.
						__( 'Your site is unable to reach WordPress.org at %1$s, and returned the error: %2$s', 'health-check' ),
						gethostbyname( 'wordpress.org' ),
						$wp_dotorg->get_error_message()
					)
				)
			);
		}

		return $result;
	}

	public function json_test_dotorg_communication() {
		wp_send_json_success( $this->get_test_dotorg_communication() );
	}

	public function get_test_is_in_debug_mode() {
		$result = array(
			'label'       => esc_html__( 'Your site is not set to output debug information', 'health-check' ),
			'status'      => 'good',
			'badge'       => array(
				'label' => 'Security',
				'color' => 'red',
			),
			'description' => sprintf(
				'<p>%s</p>',
				esc_html__( 'Debug mode is often enabled to gather more details about an error or site failure, but may contain sensitive information which should not be available on a publicly available website.', 'health-check' )
			),
			'actions'     => '',
			'test'        => 'is_in_debug_mode',
		);

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
				$result['label'] = esc_html__( 'Your site is set to log errors to a potentially public file.', 'health-check' );

				$result['status'] = 'critical';

				$result['description'] .= sprintf(
					'<p>%s</p>',
					esc_html__( 'The value, WP_DEBUG_LOG, has been added to this websites configuration file. This means any errors on the site will be written to a file which is potentially available to normal users.', 'health-check' )
				);
			}

			if ( defined( 'WP_DEBUG_DISPLAY' ) && WP_DEBUG_DISPLAY ) {
				$result['label'] = esc_html__( 'Your site is set to display errors to site visitors.', 'health-check' );

				$result['status'] = 'critical';

				$result['description'] .= sprintf(
					'<p>%s</p>',
					esc_html__( 'The value, WP_DEBUG_DISPLAY, has either been added to your configuration file, or left with its default value. This will make errors display on the front end of your site.', 'health-check' )
				);
			}
		}

		return $result;
	}

	public function json_test_is_in_debug_mode() {
		wp_send_json_success( $this->get_test_is_in_debug_mode() );
	}

	public function get_test_https_status() {
		$result = array(
			'label'       => '',
			'status'      => '',
			'badge'       => array(
				'label' => 'Security',
				'color' => 'red',
			),
			'description' => '',
			'actions'     => '',
			'test'        => 'https_status',
		);

		if ( is_ssl() ) {
			$wp_url   = get_bloginfo( 'wpurl' );
			$site_url = get_bloginfo( 'url' );

			if ( 'https' !== substr( $wp_url, 0, 5 ) || 'https' !== substr( $site_url, 0, 5 ) ) {
				$result['status'] = 'recommended';

				$result['label'] = esc_html__( 'Only parts of your site are using HTTPS', 'health-check' );

				$result['description'] = sprintf(
					'<p>%s</p>',
					sprintf(
						// translators: %s: URL to Settings > General to change options.
						__( 'You are accessing this website using HTTPS, but your <a href="%s">WordPress Address</a> is not set up to use HTTPS by default.', 'health-check' ),
						esc_url( admin_url( 'options-general.php' ) )
					)
				);

				$result['actions'] = sprintf(
					'<a href="%s">%s</a>',
					esc_url( admin_url( 'options-general.php' ) ),
					esc_html__( 'Update your site addresses', 'health-check' )
				);
			} else {
				$result['status'] = 'good';

				$result['label'] = esc_html__( 'Your website is using an active HTTPS connection.', 'health-check' );
			}
		} else {
			$result['status'] = 'recommended';

			$result['label'] = esc_html__( 'Your site does not use HTTPS', 'health-check' );

			$result['description'] = sprintf(
				'<p>%s</p>',
				esc_html__( 'An HTTPS connection is needed for many features on the web today, it also gains the trust of your visitors by helping to protecting their online privacy.', 'health-check' )
			);

			$result['actions'] = sprintf(
				'<a href="%s">%s</a>',
				esc_url(
					// translators: Website for explaining HTTPS and why it should be used.
					__( 'https://www.cloudflare.com/learning/security/why-use-https/', 'health-check' )
				),
				esc_html__( 'Read more about why you should use HTTPS', 'health-check' )
			);
		}

		return $result;
	}

	public function get_test_ssl_support() {
		$result = array(
			'label'       => '',
			'status'      => '',
			'badge'       => array(
				'label' => 'Security',
				'color' => 'red',
			),
			'description' => sprintf(
				'<p>%s</p>',
				esc_html__( 'Securely communicating between servers are needed for transactions such as fetching files, conducting sales on store sites, and much more.', 'health-check' )
			),
			'actions'     => '',
			'test'        => 'ssl_support',
		);

		$supports_https = wp_http_supports( array( 'ssl' ) );

		if ( $supports_https ) {
			$result['status'] = 'good';

			$result['label'] = esc_html__( 'Your site can communicate securely with other services.', 'health-check' );
		} else {
			$result['status'] = 'critical';

			$result['label'] = esc_html__( 'Your site is unable to communicate securely with other services.', 'health-check' );

			$result['description'] .= sprintf(
				'<p>%s</p>',
				esc_html__( 'Talk to your web host about OpenSSL support for PHP', 'health-check' )
			);
		}

		return $result;
	}

	public function get_test_scheduled_events() {
		$result = array(
			'label'       => esc_html__( 'Scheduled events are running', 'health-check' ),
			'status'      => 'good',
			'badge'       => array(
				'label' => 'Performance',
				'color' => 'orange',
			),
			'description' => sprintf(
				'<p>%s</p>',
				esc_html__( 'Scheduled events are what periodically looks for updates to plugins, themes and WordPress it self. It is also what makes sure scheduled posts are published on time. It may also be used by various plugins to make sure that planned actions are executed.', 'health-check' )
			),
			'actions'     => '',
			'test'        => 'scheduled_events',
		);

		$scheduled_events = new Health_Check_WP_Cron();

		if ( is_wp_error( $scheduled_events->has_missed_cron() ) ) {
			$result['status'] = 'critical';

			$result['label'] = esc_html__( 'It was not possible to check your scheduled events', 'health-check' );

			$result['description'] = sprintf(
				'<p>%s</p>',
				sprintf(
					// translators: %s: The error message returned while from the cron scheduler.
					esc_html__( 'While trying to test your sites scheduled events, the following error was returned: %s', 'health-check' ),
					$scheduled_events->has_missed_cron()->get_error_message()
				)
			);
		} else {
			if ( $scheduled_events->has_missed_cron() ) {
				$result['status'] = 'recommended';

				$result['label'] = esc_html__( 'A scheduled event has failed', 'health-check' );

				$result['description'] = sprintf(
					'<p>%s</p>',
					sprintf(
						// translators: %s: The name of the failed cron event.
						esc_html__( 'The scheduled event, %s, failed to run. Your site still works, but this may indicate that scheduling posts or automated updates may not work as intended.', 'health-check' ),
						$scheduled_events->last_missed_cron
					)
				);
			}
		}

		return $result;
	}

	public function get_test_background_updates() {
		$result = array(
			'label'       => esc_html__( 'Background updates are working', 'health-check' ),
			'status'      => 'good',
			'badge'       => array(
				'label' => 'Security',
				'color' => 'red',
			),
			'description' => sprintf(
				'<p>%s</p>',
				esc_html__( 'Background updates ensure that WordPress can auto-update if a security update is released for the version you are currently using.', 'health-check' )
			),
			'actions'     => '',
			'test'        => 'background_updates',
		);

		$automatic_updates = new Health_Check_Auto_Updates();
		$tests             = $automatic_updates->run_tests();

		$output = '<ul>';

		foreach ( $tests as $test ) {
			$severity_string = esc_html__( 'Passed', 'health-check' );

			if ( 'fail' === $test->severity ) {
				$result['label'] = esc_html__( 'Background updates are not working as expected', 'health-check' );

				$result['status'] = 'critical';

				$severity_string = esc_html__( 'Error', 'health-check' );
			}

			if ( 'warning' === $test->severity && 'good' === $result['status'] ) {
				$result['label'] = esc_html__( 'Background updates may not be working properly', 'health-check' );

				$result['status'] = 'recommended';

				$severity_string = esc_html__( 'Warning', 'health-check' );
			}

			$output .= sprintf(
				'<li><span class="%s"><span class="screen-reader-text">%s</span></span> %s</li>',
				esc_attr( $test->severity ),
				$severity_string,
				$test->desc
			);
		}

		$output .= '</ul>';

		if ( 'good' !== $result['status'] ) {
			$result['description'] .= sprintf(
				'<p>%s</p>',
				$output
			);
		}

		return $result;
	}

	public function json_test_background_updates() {
		wp_send_json_success( $this->get_test_background_updates() );
	}

	public function get_test_extension_updates() {
		$result = array(
			'label'       => esc_html__( 'Plugin and theme updates are working', 'health-check' ),
			'status'      => 'good',
			'badge'       => array(
				'label' => 'Security',
				'color' => 'red',
			),
			'description' => sprintf(
				'<p>%s</p>',
				esc_html__( 'Plugins or themes may have their own way of handling updates, which could break or negatively impact normal updates in WordPress.', 'health-check' )
			),
			'actions'     => '',
			'test'        => 'extension_updates',
		);

		$updates = new Health_Check_Updates();
		$tests   = $updates->run_tests();

		$output = '<ul>';

		foreach ( $tests as $test ) {
			$severity_string = esc_html__( 'Passed', 'health-check' );

			if ( 'fail' === $test->severity ) {
				$result['label'] = esc_html__( 'Plugin or theme updates are not working', 'health-check' );

				$result['status'] = 'critical';

				$severity_string = esc_html__( 'Error', 'health-check' );
			}

			if ( 'warning' === $test->severity && 'good' === $result['status'] ) {
				$result['label'] = esc_html__( 'Some plugin or theme updates may not work as expected', 'health-check' );

				$result['status'] = 'recommended';

				$severity_string = esc_html__( 'Warning', 'health-check' );
			}

			$output .= sprintf(
				'<li><span class="%s"><span class="screen-reader-text">%s</span></span> %s</li>',
				esc_attr( $test->severity ),
				$severity_string,
				$test->desc
			);
		}

		$output .= '</ul>';

		if ( 'critical' === $result['status'] ) {
			$result['description'] .= sprintf(
				'<p>%s</p>',
				$output
			);
		}

		return $result;
	}

	public function get_test_loopback_requests() {
		$result = array(
			'label'       => esc_html__( 'Your site can perform loopback requests', 'health-check' ),
			'status'      => 'good',
			'badge'       => array(
				'label' => 'Performance',
				'color' => 'orange',
			),
			'description' => sprintf(
				'<p>%s</p>',
				esc_html__( 'Loopback requests are used to run scheduled events, and are also used by the built-in editors for themes and plugins to verify code stability.', 'health-check' )
			),
			'actions'     => '',
			'test'        => 'loopback_requests',
		);

		$check_loopback = Health_Check_Loopback::can_perform_loopback();

		$result['status'] = $check_loopback->status;

		if ( 'good' !== $check_loopback->status ) {
			$result['label'] = esc_html__( 'Your site could not complete a loopback request', 'health-check' );

			$result['description'] .= sprintf(
				'<p>%s</p>',
				$check_loopback->message
			);
		}

		if ( 'critical' === $check_loopback->status ) {
			$result['actions'] .= sprintf(
				'<button type="button" id="loopback-no-plugins" class="button button-primary">%s</button>',
				esc_html__( 'Test without plugins', 'health-check' )
			);
		}

		return $result;
	}

	public function json_test_loopback_requests() {
		wp_send_json_success( $this->get_test_loopback_requests() );
	}

	public function get_test_http_requests() {
		$result = array(
			'label'       => esc_html__( 'HTTP requests seem to be working as expected', 'health-check' ),
			'status'      => 'good',
			'badge'       => array(
				'label' => 'Performance',
				'color' => 'orange',
			),
			'description' => sprintf(
				'<p>%s</p>',
				esc_html__( 'It is possible for site maintainers to block all, or some, communication to other sites and services. If set up incorrectly, this may prevent plugins and themes from working as intended.', 'health-check' )
			),
			'actions'     => '',
			'test'        => 'http_requests',
		);

		$blocked = false;
		$hosts   = array();

		if ( defined( 'WP_HTTP_BLOCK_EXTERNAL' ) ) {
			$blocked = true;
		}

		if ( defined( 'WP_ACCESSIBLE_HOSTS' ) ) {
			$hosts = explode( ',', WP_ACCESSIBLE_HOSTS );
		}

		if ( $blocked && 0 === sizeof( $hosts ) ) {
			$result['status'] = 'critical';

			$result['label'] = esc_html__( 'HTTP requests are blocked', 'health-check' );

			$result['description'] .= sprintf(
				'<p>%s</p>',
				esc_html__( 'HTTP requests have been blocked by the WP_HTTP_BLOCK_EXTERNAL constant, with no allowed hosts.', 'health-check' )
			);
		}

		if ( $blocked && 0 < sizeof( $hosts ) ) {
			$result['status'] = 'recommended';

			$result['label'] = esc_html__( 'HTTP requests are partially blocked', 'health-check' );

			$result['description'] .= sprintf(
				'<p>%s</p>',
				sprintf(
					/* translators: %s: List of hostnames whitelisted. */
					esc_html__( 'HTTP requests have been blocked by the WP_HTTP_BLOCK_EXTERNAL constant, with some hosts whitelisted: %s.', 'health-check' ),
					implode( ',', $hosts )
				)
			);
		}

		return $result;
	}

	public function get_test_rest_availability() {
		$result = array(
			'label'       => esc_html__( 'The REST API is available', 'health-check' ),
			'status'      => 'good',
			'badge'       => array(
				'label' => 'Performance',
				'color' => 'orange',
			),
			'description' => sprintf(
				'<p>%s</p>',
				esc_html__( 'The REST API is one way WordPress, and other applications, communicate with the server. One example is the block editor screen, which relies on this to display, and save, your posts and pages.', 'health-check' )
			),
			'actions'     => '',
			'test'        => 'rest_availability',
		);

		$cookies = wp_unslash( $_COOKIE );
		$timeout = 10;
		$headers = array(
			'Cache-Control' => 'no-cache',
			'X-WP-Nonce'    => wp_create_nonce( 'wp_rest' ),
		);

		// Include Basic auth in loopback requests.
		if ( isset( $_SERVER['PHP_AUTH_USER'] ) && isset( $_SERVER['PHP_AUTH_PW'] ) ) {
			$headers['Authorization'] = 'Basic ' . base64_encode( wp_unslash( $_SERVER['PHP_AUTH_USER'] ) . ':' . wp_unslash( $_SERVER['PHP_AUTH_PW'] ) );
		}

		$url = rest_url( 'wp/v2/types/post' );

		// We only need the first post to ensure this works, to make it low impact.
		$url = add_query_arg( array(
			'context' => 'edit',
		), $url );

		$r = wp_remote_get( $url, compact( 'cookies', 'headers', 'timeout' ) );

		if ( is_wp_error( $r ) ) {
			$result['status'] = 'critical';

			$result['label'] = esc_html__( 'The REST API encountered an error', 'health-check' );

			$result['description'] .= sprintf(
				'<p>%s</p>',
				sprintf(
					'%s<br>%s',
					esc_html__( 'The REST API request failed due to an error.', 'health-check' ),
					sprintf(
						/* translators: %1$d: The HTTP response code. %2$s: The error message returned. */
						esc_html__( 'Error encountered: (%1$d) %2$s', 'health-check' ),
						wp_remote_retrieve_response_code( $r ),
						$r->get_error_message()
					)
				)
			);
		} elseif ( 200 !== wp_remote_retrieve_response_code( $r ) ) {
			$result['status'] = 'recommended';

			$result['label'] = esc_html__( 'The REST API encountered an unexpected result', 'health-check' );

			$result['description'] .= sprintf(
				'<p>%s</p>',
				sprintf(
					/* translators: %1$d: The HTTP response code returned. %2$s: The error message returned. */
					esc_html__( 'The REST API call gave the following unexpected result: (%1$d) %2$s.', 'health-check' ),
					wp_remote_retrieve_response_code( $r ),
					wp_remote_retrieve_body( $r )
				)
			);
		} else {
			$json = json_decode( wp_remote_retrieve_body( $r ), true );

			if ( false !== $json && ! isset( $json['capabilities'] ) ) {
				$result['status'] = 'recommended';

				$result['label'] = esc_html__( 'The REST API did not behave correctly', 'health-check' );

				$result['description'] .= sprintf(
					'<p>%s</p>',
					esc_html__( 'The REST API did not process the \'context\' query parameter correctly.', 'health-check' )
				);
			}
		}

		return $result;
	}

	/**
	 * Return a set of tests that belong to the site status page.
	 *
	 * Each site status test is defined here, they may be `direct` tests, that run on page load,
	 * or `async` tests which will run later down the line via JavaScript calls to improve page
	 * performance and hopefully also user experiences.
	 *
	 * @return array
	 */
	public static function get_tests() {
		$tests = array(
			'direct' => array(
				'wordpress_version' => array(
					'label' => __( 'WordPress Version', 'health-check' ),
					'test'  => 'wordpress_version',
				),
				'plugin_version'    => array(
					'label' => __( 'Plugin Versions', 'health-check' ),
					'test'  => 'plugin_version',
				),
				'theme_version'     => array(
					'label' => __( 'Theme Versions', 'health-check' ),
					'test'  => 'theme_version',
				),
				'php_version'       => array(
					'label' => __( 'PHP Version', 'health-check' ),
					'test'  => 'php_version',
				),
				'sql_server'        => array(
					'label' => __( 'Database Server version', 'health-check' ),
					'test'  => 'sql_server',
				),
				'php_extensions'    => array(
					'label' => __( 'PHP Extensions', 'health-check' ),
					'test'  => 'php_extensions',
				),
				'utf8mb4_support'   => array(
					'label' => __( 'MySQL utf8mb4 support', 'health-check' ),
					'test'  => 'utf8mb4_support',
				),
				'https_status'      => array(
					'label' => __( 'HTTPS status', 'health-check' ),
					'test'  => 'https_status',
				),
				'ssl_support'       => array(
					'label' => __( 'Secure communication', 'health-check' ),
					'test'  => 'ssl_support',
				),
				'scheduled_events'  => array(
					'label' => __( 'Scheduled events', 'health-check' ),
					'test'  => 'scheduled_events',
				),
				'extension_updates' => array(
					'label' => __( 'Plugin and Theme Updates', 'health-check' ),
					'test'  => 'extension_updates',
				),
				'http_requests'     => array(
					'label' => __( 'HTTP Requests', 'health-check' ),
					'test'  => 'http_requests',
				),
				'debug_enabled'     => array(
					'label' => __( 'Debugging enabled', 'health-check' ),
					'test'  => 'is_in_debug_mode',
				),
			),
			'async'  => array(
				'dotorg_communication' => array(
					'label' => __( 'Communication with WordPress.org', 'health-check' ),
					'test'  => 'dotorg_communication',
				),
				'background_updates'   => array(
					'label' => __( 'Background updates', 'health-check' ),
					'test'  => 'background_updates',
				),
				'loopback_requests'    => array(
					'label' => __( 'Loopback request', 'health-check' ),
					'test'  => 'loopback_requests',
				),
			),
		);

		// Conditionally include REST rules if the function for it exists.
		if ( function_exists( 'rest_url' ) ) {
			$tests['direct']['rest_availability'] = array(
				'label' => __( 'REST API availability', 'health-check' ),
				'test'  => 'rest_availability',
			);
		}

		/**
		 * Add or modify which site status tests are ran on a site.
		 *
		 * The site health is determined by a set of tests based on best practices from
		 * both the WordPress Hosting Team, but also web standards in general.
		 *
		 * Some sites may not have the same requirements, for example the automatic update
		 * checks may be handled by a host, and are therefore disabled in core.
		 * Or maybe you want ot introduce a new test, is caching enabled/disabled/stale.
		 *
		 * Test may be added either as direct, or asynchronous ones
		 *
		 * @param array $args {
		 *
		 * }
		 */
		$tests = apply_filters( 'health_check_site_status_tests', $tests );

		return $tests;
	}

	public function scheduled_check() {
		// Bootstrap wp-admin, as WP_Cron doesn't do this for us.
		require_once( trailingslashit( ABSPATH ) . 'wp-admin/includes/admin.php' );

		$bulk_tests = Health_Check_Site_Status::get_tests();

		$results = array();

		$site_status = array(
			'good'        => 0,
			'recommended' => 0,
			'critical'    => 0,
		);

		$tests = array_merge( $bulk_tests['direct'], $bulk_tests['async'] );

		foreach ( $tests as $test ) {
			$function = sprintf(
				'get_test_%s',
				$test['test']
			);

			if ( method_exists( $this, $function ) && is_callable( array( $this, $function ) ) ) {
				$results[] = call_user_func( array( $this, $function ) );
			} else {
				$results[] = call_user_func( $test['test'] );
			}
		}

		foreach ( $results as $result ) {
			if ( 'critical' === $result['status'] ) {
				$site_status['critical']++;
			} elseif ( 'recommended' === $result['status'] ) {
				$site_status['recommended']++;
			} else {
				$site_status['good']++;
			}
		}

		set_transient( 'health-check-site-status-result', json_encode( $site_status ) );
	}
}

global $health_check_site_status;
$health_check_site_status = new Health_Check_Site_Status();
