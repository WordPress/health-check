<?php

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

		add_action( 'wp_loaded', array( $this, 'check_wp_version_check_exists' ) );
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

	public function site_status() {
		check_ajax_referer( 'health-check-site-status' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		$function = sprintf(
			'test_%s',
			$_POST['feature']
		);

		if ( ! method_exists( $this, $function ) || ! is_callable( array( $this, $function ) ) ) {
			die();
		}

		$call = call_user_func( array( $this, $function ) );

		die();
	}

	/**
	 * Tests for WordPress version and outputs it.
	 *
	 * @return void It is an AJAX call.
	 */
	public function test_wordpress_version() {
		$core_current_version = get_bloginfo( 'version' );
		$core_updates         = get_core_updates();

		// Prepare for a class and text for later use.
		$text  = '';
		$class = '';

		if ( ! is_array( $core_updates ) ) {
			$class = 'warning';
			$text  = sprintf(
				// translators: %s: Your current version of WordPress.
				__( '%s - We were unable to check if any new versions are available.', 'health-check' ),
				$core_current_version
			);
		} else {
			foreach ( $core_updates as $core => $update ) {
				if ( 'upgrade' === $update->response ) {
					$current_version = explode( '.', $core_current_version );
					$new_version     = explode( '.', $update->version );

					$current_major = $current_version[0] . '.' . $current_version[1];
					$new_major     = $new_version[0] . '.' . $new_version[1];

					if ( $current_major !== $new_major ) {
						// This is a major version mismatch.
						$class = 'warning';
						$text  = sprintf(
							// translators: %1$s: Your current version of WordPress. %2$s The latest version of WordPress available.
							__( '%1$s ( Latest version: %2$s )', 'health-check' ),
							$core_current_version,
							$update->version
						);
					} else {
						// This is a minor version, sometimes considered more critical.
						$class = 'error';
						$text  = sprintf(
							// translators: %1$s: Your current version of WordPress. %2$s The latest version of WordPress available.
							__( '%1$s ( Latest version: %2$s ) - We strongly urge you to update, as minor updates are often security related.', 'health-check' ),
							$core_current_version,
							$update->version
						);
					}
				} else {
					$class = 'good';
					$text  = $core_current_version;
				}
			}
		}

		printf( '<span class="%1$s"></span> %2$s', esc_attr( $class ), esc_html( $text ) );
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

	public function test_plugin_version() {
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

		echo '<ul>';

		if ( $plugins_needs_update > 0 ) {
			printf(
				'<li><span class="error"></span> %s',
				sprintf(
					// translators: %d: The amount of outdated plugins.
					esc_html( _n(
						'Your site has %d plugin waiting to be updated.',
						'Your site has %d plugins waiting to be updated.',
						$plugins_needs_update,
						'health-check'
					) ),
					$plugins_needs_update
				)
			);
		} else {
			printf(
				'<li><span class="good"></span> %s',
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
			printf(
				'<li><span class="warning"></span> %s',
				sprintf(
					// translators: %d: The amount of inactive plugins.
					esc_html( _n(
						'Your site has %d inactive plugin, it is recommended to remove any unused plugins to enhance your site security.',
						'Your site has %d inactive plugins, it is recommended to remove any unused plugins to enhance your site security.',
						$unused_plugins,
						'health-check'
					) ),
					$unused_plugins
				)
			);
		}

		echo '</ul>';
	}

	public function test_theme_version() {
		$theme_updates = get_theme_updates();

		$themes_total        = 0;
		$themes_need_updates = 0;
		$themes_inactive     = 0;

		// This value is changed dduring processing to determine how many themes are considered a reasonable amount.
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

		echo '<ul>';

		if ( $themes_need_updates > 0 ) {
			printf(
				'<li><span class="error"></span> %s',
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
			printf(
				'<li><span class="good"></span> %s',
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
				printf(
					'<li><span class="warning"></span> %s',
					sprintf(
						// translators: %1$d: The amount of inactive themes. %2$s: The default theme for WordPress. %3$s: The currently active theme. %4$s: The active themes parent theme.
						esc_html( _n(
							'Your site has %1$d inactive theme. To enhance your sites security it is recommended to remove any unused themes. You should keep %2$s, the default WordPress theme, %3$s, your current theme and %4$s, the parent theme.',
							'Your site has %1$d inactive themes. To enhance your sites security it is recommended to remove any unused themes. You should keep %2$s, the default WordPress theme, %3$s, your current theme and %4$s, the parent theme.',
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
				printf(
					'<li><span class="warning"></span> %s',
					sprintf(
						// translators: %1$d: The amount of inactive themes. %2$s: The default theme for WordPress. %3$s: The currently active theme.
						esc_html( _n(
							'Your site has %1$d inactive theme, other than %2$s, the default WordPress theme, and %3$s, your active theme. It is recommended to remove any unused themes to enhance your sites security.',
							'Your site has %1$d inactive themes, other than %2$s, the default WordPress theme, and %3$s, your active theme. It is recommended to remove any unused themes to enhance your sites security.',
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
			printf(
				'<li><span class="warning"></span> %s',
				esc_html__( 'Your site does not have a default theme, default themes are used by WordPress automatically if anything is wrong with your normal theme.', 'health-check' )
			);
		}

		echo '</ul>';
	}

	public function test_php_version() {
		$status = 'good';
		$notice = array();

		if ( ! $this->php_min_version_check ) {
			$status   = 'error';
			$notice[] = sprintf(
				'<a href="%s">%s</a>',
				esc_url(
					_x( 'https://wordpress.org/support/upgrade-php/', 'The link to the Update PHP page, which may be localized.', 'health-check' )
				),
				sprintf(
					// translators: %1$s: Current PHP version. %2$s: Recommended PHP version. %3$s: Minimum PHP version.
					esc_html__( 'Your version of PHP, %1$s, is very outdated and no longer receiving security updates and is not supported by WordPress. You should contact your host for an upgrade, WordPress recommends using PHP version %2$s, but will work with version %3$s or newer.', 'health-check' ),
					PHP_VERSION,
					HEALTH_CHECK_PHP_REC_VERSION,
					HEALTH_CHECK_PHP_MIN_VERSION
				)
			);
		} elseif ( ! $this->php_supported_version_check ) {
			$status   = 'warning';
			$notice[] = sprintf(
				'<a href="%s">%s</a>',
				esc_url(
					_x( 'https://wordpress.org/support/upgrade-php/', 'The link to the Update PHP page, which may be localized.', 'health-check' )
				),
				sprintf(
					// translators: %1$s: Current PHP version. %2$s: Recommended PHP version.
					esc_html__( 'Your version of PHP, %1$s, is very outdated and no longer receiving security updates. You should contact your host for an upgrade, WordPress recommends using PHP version %2$s.', 'health-check' ),
					PHP_VERSION,
					HEALTH_CHECK_PHP_REC_VERSION
				)
			);
		} elseif ( ! $this->php_rec_version_check ) {
			$status   = 'warning';
			$notice[] = sprintf(
				'<a href="%s">%s</a>',
				esc_url(
					_x( 'https://wordpress.org/support/upgrade-php/', 'The link to the Update PHP page, which may be localized.', 'health-check' )
				),
				sprintf(
					// translators: %s: Recommended PHP version
					esc_html__( 'For best performance we recommend using PHP %s or higher.', 'health-check' ),
					HEALTH_CHECK_PHP_REC_VERSION
				)
			);
		}

		printf(
			'<span class="%s"></span> %s',
			esc_attr( $status ),
			sprintf(
				'%s%s',
				PHP_VERSION,
				( ! empty( $notice ) ? ' - ' . implode( '<br>', $notice ) : '' )
			)
		);
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

	public function test_php_extensions() {
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
				'function' => 'sodium_compare',
				'required' => false,
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

			if ( ! $this->child_test_php_extension_availability( $extension, $function ) ) {
				$failures[ $library ] = sprintf(
					'<span class="%s"></span> %s',
					( $module['required'] ? 'error' : 'warning' ),
					sprintf(
						// translators: %1$2: If a module is required or recommended. %2$s: The module name.
						__( 'The %1$s module, %2$s, is not installer, or has been disabled.', 'health-check' ),
						( $module['required'] ? __( 'required', 'health-check' ) : __( 'optional', 'health-check' ) ),
						$library
					)
				);
			}
		}

		if ( ! empty( $failures ) ) {
			echo '<ul>';

			foreach ( $failures as $failure ) {
				printf(
					'<li>%s</li>',
					$failure
				);
			}

			echo '</ul>';
		} else {
			printf(
				'<span class="good"></span> %s',
				__( 'All required and recommended modules are installed.', 'health-check' )
			);
		}
	}

	public function test_sql_server() {
		$status = 'good';
		$notice = array();

		$db_dropin = file_exists( WP_CONTENT_DIR . '/db.php' );

		if ( ! $this->mysql_rec_version_check ) {
			$status   = 'warning';
			$notice[] = sprintf(
				// translators: %1$s: The database engine in use (MySQL or MariaDB). %2$s: Database server recommended version number.
				esc_html__( 'For performance and security reasons, we strongly recommend running %1$s version %2$s or higher.', 'health-check' ),
				( $this->mariadb ? 'MariaDB' : 'MySQL' ),
				$this->health_check_mysql_rec_version
			);
		}

		if ( ! $this->mysql_min_version_check ) {
			$status   = 'error';
			$notice[] = sprintf(
				// translators: %1$s: The database engine in use (MySQL or MariaDB). %2$s: Database server minimum version number.
				esc_html__( 'WordPress 3.2+ requires %1$s version %2$s or higher.', 'health-check' ),
				( $this->mariadb ? 'MariaDB' : 'MySQL' ),
				HEALTH_CHECK_MYSQL_MIN_VERSION
			);
		}

		if ( $db_dropin ) {
			// translators: %s: The database engine in use (MySQL or MariaDB).
			$notice[] = wp_kses(
				sprintf(
					// translators: %s: The name of the database engine being used.
					__( 'You are using a <code>wp-content/db.php</code> drop-in which might mean that a %s database is not being used.', 'health-check' ),
					( $this->mariadb ? 'MariaDB' : 'MySQL' )
				),
				array(
					'code' => true,
				)
			);
		}

		printf(
			'<span class="%s"></span> %s',
			esc_attr( $status ),
			sprintf(
				'%s%s',
				esc_html( $this->mysql_server_version ),
				( ! empty( $notice ) ? '<br> - ' . implode( '<br>', $notice ) : '' )
			)
		);
	}

	public function test_utf8mb4_support() {
		global $wpdb;

		if ( ! $this->mariadb ) {
			if ( version_compare( $this->mysql_server_version, '5.5.3', '<' ) ) {
				printf(
					'<span class="warning"></span> %s',
					sprintf(
						/* translators: %s: Number of version. */
						esc_html__( 'WordPress\' utf8mb4 support requires MySQL version %s or greater', 'health-check' ),
						'5.5.3'
					)
				);
			} else {
				printf(
					'<span class="good"></span> %s',
					esc_html__( 'Your MySQL version supports utf8mb4', 'health-check' )
				);
			}
		} else { // MariaDB introduced utf8mb4 support in 5.5.0
			if ( version_compare( $this->mysql_server_version, '5.5.0', '<' ) ) {
				printf(
					'<span class="warning"></span> %s',
					sprintf(
						/* translators: %s: Number of version. */
						esc_html__( 'WordPress\' utf8mb4 support requires MariaDB version %s or greater', 'health-check' ),
						'5.5.0'
					)
				);
			} else {
				printf(
					'<span class="good"></span> %s',
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
				printf(
					'<br><span class="warning"></span> %s',
					sprintf(
						/* translators: %1$s: Name of the library, %2$s: Number of version. */
						__( 'WordPress\' utf8mb4 support requires MySQL client library (%1$s) version %2$s or newer.', 'health-check' ),
						'mysqlnd',
						'5.0.9'
					)
				);
			}
		} else {
			if ( version_compare( $mysql_client_version, '5.5.3', '<' ) ) {
				printf(
					'<br><span class="warning"></span> %s',
					sprintf(
						/* translators: %1$s: Name of the library, %2$s: Number of version. */
						__( 'WordPress\' utf8mb4 support requires MySQL client library (%1$s) version %2$s or newer.', 'health-check' ),
						'libmysql',
						'5.5.3'
					)
				);
			}
		}
	}

	public function test_dotorg_communication() {
		$wp_dotorg = wp_remote_get( 'https://wordpress.org', array(
			'timeout' => 10,
		) );
		if ( ! is_wp_error( $wp_dotorg ) ) {
			printf(
				'<span class="good"></span> %s',
				esc_html__( 'WordPress.org is reachable from your server.', 'health-check' )
			);
		} else {
			printf(
				'<span class="error"></span> %s',
				sprintf(
					// translators: %1$s: The IP address WordPress.org resolves to. %2$s: The error returned by the lookup.
					__( 'Unable to reach WordPress.org at %1$s: %2$s', 'health-check' ),
					gethostbyname( 'wordpress.org' ),
					$wp_dotorg->get_error_message()
				)
			);
		}
	}

	public function test_https_status() {
		if ( is_ssl() ) {
			$wp_url   = get_bloginfo( 'wpurl' );
			$site_url = get_bloginfo( 'url' );

			if ( 'https' !== substr( $wp_url, 0, 5 ) || 'https' !== substr( $site_url, 0, 5 ) ) {
				printf(
					'<span class="warning"></span> %s',
					sprintf(
						// translators: %s: URL to Settings > General to change options.
						__( 'You are accessing this website using HTTPS, but your <a href="%s">WordPress Address</a> is not set up to use HTTPS by default.', 'health-check' ),
						esc_url( admin_url( 'options-general.php' ) )
					)
				);
			} else {
				printf(
					'<span class="good"></span> %s',
					esc_html__( 'You are accessing this website using HTTPS.', 'health-check' )
				);
			}
		} else {
			printf(
				'<span class="warning"></span> %s',
				esc_html__( 'You are not using HTTPS to access this website.', 'health-check' )
			);
		}
	}

	public function test_ssl_support() {
		$supports_https = wp_http_supports( array( 'ssl' ) );

		if ( $supports_https ) {
			printf(
				'<span class="good"></span> %s',
				esc_html__( 'Your WordPress install can communicate securely with other services.', 'health-check' )
			);
		} else {
			printf(
				'<span class="error"></span> %s',
				esc_html__( 'Your WordPress install cannot communicate securely with other services. Talk to your web host about OpenSSL support for PHP.', 'health-check' )
			);
		}
	}

	public function test_scheduled_events() {
		$scheduled_events = new Health_Check_WP_Cron();

		if ( is_wp_error( $scheduled_events->has_missed_cron() ) ) {
			printf(
				'<span class="error"></span> %s',
				esc_html( $scheduled_events->has_missed_cron()->get_error_message() )
			);
		} else {
			if ( $scheduled_events->has_missed_cron() ) {
				printf(
					'<span class="warning"></span> %s',
					sprintf(
						// translators: %s: The name of the failed cron event.
						esc_html__( 'A scheduled event (%s) has failed to run. Your site still works, but this may indicate that scheduling posts or automated updates may not work as intended.', 'health-check' ),
						$scheduled_events->last_missed_cron
					)
				);
			} else {
				printf(
					'<span class="good"></span> %s',
					esc_html__( 'No scheduled events have been missed.', 'health-check' )
				);
			}
		}
	}

	public function test_background_updates() {
		$automatic_updates = new Health_Check_Auto_Updates();
		$tests             = $automatic_updates->run_tests();

		echo '<ul>';

		foreach ( $tests as $test ) {
			printf(
				'<li><span class="%s"></span> %s</li>',
				esc_attr( $test->severity ),
				$test->desc
			);
		}

		echo '</ul>';
	}

	public function test_extension_updates() {
		$updates = new Health_Check_Updates();
		$tests   = $updates->run_tests();

		echo '<ul>';

		foreach ( $tests as $test ) {
			printf(
				'<li><span class="%s"></span> %s</li>',
				esc_attr( $test->severity ),
				$test->desc
			);
		}

		echo '</ul>';
	}

	public function test_loopback_requests() {
		$check_loopback = Health_Check_Loopback::can_perform_loopback();

		printf(
			'<span class="%s"></span> %s',
			esc_attr( $check_loopback->status ),
			$check_loopback->message
		);

		if ( 'error' === $check_loopback->status ) {
			printf(
				'<br><button type="button" id="loopback-no-plugins" class="button button-primary">%s</button>',
				esc_html__( 'Test without plugins', 'health-check' )
			);
		}
	}

	public function test_http_requests() {
		$blocked = false;
		$hosts   = array();

		if ( defined( 'WP_HTTP_BLOCK_EXTERNAL' ) ) {
			$blocked = true;
		}

		if ( defined( 'WP_ACCESSIBLE_HOSTS' ) ) {
			$hosts = explode( ',', WP_ACCESSIBLE_HOSTS );
		}

		if ( $blocked && 0 === sizeof( $hosts ) ) {
			printf(
				'<span class="%s"></span> %s',
				esc_attr( 'fail' ),
				esc_html__( 'HTTP requests have been blocked by the WP_HTTP_BLOCK_EXTERNAL constant, with no allowed hosts.', 'health-check' )
			);
		}

		if ( $blocked && 0 < sizeof( $hosts ) ) {
			printf(
				'<span class="%s"></span> %s',
				esc_attr( 'warning' ),
				sprintf(
					/* translators: %s: List of hostnames whitelisted. */
					esc_html__( 'HTTP requests have been blocked by the WP_HTTP_BLOCK_EXTERNAL constant, with some hosts whitelisted: %s.', 'health-check' ),
					implode( ',', $hosts )
				)
			);
		}

		if ( ! $blocked ) {
			printf(
				'<span class="%s"></span> %s',
				esc_attr( 'good' ),
				esc_html__( 'HTTP requests should be working as expected.', 'health-check' )
			);
		}
	}

	public function test_rest_availability() {
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
			printf(
				'<span class="error"></span> %s',
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
			printf(
				'<span class="warning"></span> %s',
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
				printf(
					'<span class="warning"></span> %s',
					esc_html__( 'The REST API did not process the \'context\' query parameter correctly.', 'health-check' )
				);
			} else {
				printf(
					'<span class="good"></span> %s',
					__( 'The REST API is available.', 'health-check' )
				);
			}
		}
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
				array(
					'label' => __( 'WordPress Version', 'health-check' ),
					'test'  => 'wordpress_version',
				),
				array(
					'label' => __( 'Plugin Versions', 'health-check' ),
					'test'  => 'plugin_version',
				),
				array(
					'label' => __( 'Theme Versions', 'health-check' ),
					'test'  => 'theme_version',
				),
				array(
					'label' => __( 'PHP Version', 'health-check' ),
					'test'  => 'php_version',
				),
				array(
					'label' => __( 'Database Server version', 'health-check' ),
					'test'  => 'sql_server',
				),
				array(
					'label' => __( 'PHP Extensions', 'health-check' ),
					'test'  => 'php_extensions',
				),
				array(
					'label' => __( 'MySQL utf8mb4 support', 'health-check' ),
					'test'  => 'utf8mb4_support',
				),
				array(
					'label' => __( 'HTTPS status', 'health-check' ),
					'test'  => 'https_status',
				),
				array(
					'label' => __( 'Secure communication', 'health-check' ),
					'test'  => 'ssl_support',
				),
				array(
					'label' => __( 'Scheduled events', 'health-check' ),
					'test'  => 'scheduled_events',
				),
				array(
					'label' => __( 'Plugin and Theme Updates', 'health-check' ),
					'test'  => 'extension_updates',
				),
				array(
					'label' => __( 'HTTP Requests', 'health-check' ),
					'test'  => 'http_requests',
				),
			),
			'async'  => array(
				array(
					'label' => __( 'Communication with WordPress.org', 'health-check' ),
					'test'  => 'dotorg_communication',
				),
				array(
					'label' => __( 'Background updates', 'health-check' ),
					'test'  => 'background_updates',
				),
				array(
					'label' => __( 'Loopback request', 'health-check' ),
					'test'  => 'loopback_requests',
				),
			),
		);

		// Conditionally include REST rules if the function for it exists.
		if ( function_exists( 'rest_url' ) ) {
			$tests['direct'][] = array(
				'label' => __( 'REST API availability', 'health-check' ),
				'test'  => 'rest_availability',
			);
		}

		return $tests;
	}
}

global $health_check_site_status;
$health_check_site_status = new Health_Check_Site_Status();
