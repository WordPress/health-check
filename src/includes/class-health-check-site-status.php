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

	public function site_status() {
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

	public function test_json_extension() {
		$json_check = Health_Check::json_check();

		$status = 'good';
		$notice = array();

		if ( ! $json_check ) {
			printf(
				'<span class="error"> %s',
				esc_html__( 'The PHP install on your server has the JSON extension disabled and is therefore not compatible with WordPress 3.2 or newer.', 'health-check' )
			);
		} else {
			printf(
				'<span class="good"> %s',
				esc_html__( 'Your PHP install supports JSON.', 'health-check' )
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

		foreach ( $tests as $test ) {
			printf(
				'<li><span class="%s"></span> %s</li>',
				esc_attr( $test->severity ),
				$test->desc
			);
		}
	}

	public function test_loopback_requests() {
		$check_loopback = Health_Check_Loopback::can_perform_loopback();

		printf(
			'<span class="%s"></span> %s',
			esc_attr( $check_loopback->status ),
			$check_loopback->message
		);

		if ( 'error' === $check_loopback->status ) {
			echo '<br><button type="button" id="loopback-no-plugins" class="button button-primary">Test without plugins</button>';
		}
	}
}

new Health_Check_Site_Status();
