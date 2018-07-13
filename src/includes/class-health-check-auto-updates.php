<?php
/**
 * Class for testing automatic updates in the WordPress code.
 *
 * @package Health Check
 */

/**
 * Class Health_Check_Auto_Updates
 */
class Health_Check_Auto_Updates {
	/**
	 * Health_Check_Auto_Updates constructor.
	 *
	 * @uses Health_Check::init()
	 *
	 * @return void
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Initiate the plugin class.
	 *
	 * @return void
	 */
	public function init() {
		include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
	}

	/**
	 * Run tests to determine if auto-updates can run.
	 *
	 * @uses get_class_methods()
	 * @uses substr()
	 * @uses call_user_func()
	 *
	 * @return array
	 */
	public function run_tests() {
		$tests = array();

		foreach ( get_class_methods( $this ) as $method ) {
			if ( 'test_' !== substr( $method, 0, 5 ) ) {
				continue;
			}

			$result = call_user_func( array( $this, $method ) );

			if ( false === $result || null === $result ) {
				continue;
			}

			$result = (object) $result;

			if ( empty( $result->severity ) ) {
				$result->severity = 'warning';
			}

			$tests[ $method ] = $result;
		}

		return $tests;
	}

	/**
	 * Test if file modifications are possible.
	 *
	 * @uses defined()
	 * @uses sprintf()
	 * @uses esc_html__()
	 *
	 * @return array
	 */
	function test_constant_FILE_MODS() {
		if ( defined( 'DISALLOW_FILE_MODS' ) && DISALLOW_FILE_MODS ) {
			return array(
				'desc'     => sprintf(
					/* translators: %s: Name of the constant used. */
					esc_html__( 'The %s constant is defined and enabled.', 'health-check' ),
					'<code>DISALLOW_FILE_MODS</code>'
				),
				'severity' => 'fail',
			);
		}
	}

	/**
	 * Check if automatic updates are disabled with a constant.
	 *
	 * @uses defined()
	 * @uses sprintf()
	 * @uses esc_html__()
	 *
	 * @return array
	 */
	function test_constant_AUTOMATIC_UPDATER_DISABLED() {
		if ( defined( 'AUTOMATIC_UPDATER_DISABLED' ) && AUTOMATIC_UPDATER_DISABLED ) {
			return array(
				'desc'     => sprintf(
					/* translators: %s: Name of the constant used. */
					esc_html__( 'The %s constant is defined and enabled.', 'health-check' ),
					'<code>AUTOMATIC_UPDATER_DISABLED</code>'
				),
				'severity' => 'fail',
			);
		}
	}

	/**
	 * Check if automatic core updates are disabled with a constant.
	 *
	 * @uses defined()
	 * @uses sprintf()
	 * @uses esc_html__()
	 *
	 * @return array
	 */
	function test_constant_WP_AUTO_UPDATE_CORE() {
		if ( defined( 'WP_AUTO_UPDATE_CORE' ) && false === WP_AUTO_UPDATE_CORE ) {
			return array(
				'desc'     => sprintf(
					/* translators: %s: Name of the constant used. */
					esc_html__( 'The %s constant is defined and enabled.', 'health-check' ),
					'<code>WP_AUTO_UPDATE_CORE</code>'
				),
				'severity' => 'fail',
			);
		}
	}

	/**
	 * Check if updates are intercepted by a filter.
	 *
	 * @uses has_filter()
	 * @uses sprintf()
	 * @uses esc_html__()
	 *
	 * @return array
	 */
	function test_wp_version_check_attached() {
		$cookies = wp_unslash( $_COOKIE );
		$timeout = 10;
		$headers = array(
			'Cache-Control' => 'no-cache',
		);

		// Include Basic auth in loopback requests.
		if ( isset( $_SERVER['PHP_AUTH_USER'] ) && isset( $_SERVER['PHP_AUTH_PW'] ) ) {
			$headers['Authorization'] = 'Basic ' . base64_encode( wp_unslash( $_SERVER['PHP_AUTH_USER'] ) . ':' . wp_unslash( $_SERVER['PHP_AUTH_PW'] ) );
		}

		$url = add_query_arg( array(
			'health-check-test-wp_version_check' => true,
		), admin_url() );

		$test = wp_remote_get( $url, compact( 'cookies', 'headers', 'timeout' ) );

		$response = wp_remote_retrieve_body( $test );

		if ( 'yes' !== $response ) {
			return array(
				'desc'     => sprintf(
					/* translators: %s: Name of the filter used. */
					esc_html__( 'A plugin has prevented updates by disabling %s.', 'health-check' ),
					'<code>wp_version_check()</code>'
				),
				'severity' => 'fail',
			);
		}
	}

	/**
	 * Check if automatic updates are disabled by a filter.
	 *
	 * @uses apply_filters()
	 * @uses sprintf()
	 * @uses esc_html__()
	 *
	 * @return array
	 */
	function test_filters_automatic_updater_disabled() {
		if ( apply_filters( 'automatic_updater_disabled', false ) ) {
			return array(
				'desc'     => sprintf(
					/* translators: %s: Name of the filter used. */
					esc_html__( 'The %s filter is enabled.', 'health-check' ),
					'<code>automatic_updater_disabled</code>'
				),
				'severity' => 'fail',
			);
		}
	}

	/**
	 * Check if automatic updates have tried to run, but failed, previously.
	 *
	 * @uses get_site_option()
	 * @uses esc_html__()
	 * @uses sprintf()
	 *
	 * @return array|bool
	 */
	function test_if_failed_update() {
		$failed = get_site_option( 'auto_core_update_failed' );

		if ( ! $failed ) {
			return false;
		}

		if ( ! empty( $failed['critical'] ) ) {
			$desc  = esc_html__( 'A previous automatic background update ended with a critical failure, so updates are now disabled.', 'health-check' );
			$desc .= ' ' . esc_html__( 'You would have received an email because of this.', 'health-check' );
			$desc .= ' ' . esc_html__( "When you've been able to update using the \"Update Now\" button on Dashboard > Updates, we'll clear this error for future update attempts.", 'health-check' );
			$desc .= ' ' . sprintf(
				/* translators: %s: Code of error shown. */
				esc_html__( 'The error code was %s.', 'health-check' ),
				'<code>' . $failed['error_code'] . '</code>'
			);
			return array(
				'desc'     => $desc,
				'severity' => 'warning',
			);
		}

		$desc = esc_html__( 'A previous automatic background update could not occur.', 'health-check' );
		if ( empty( $failed['retry'] ) ) {
			$desc .= ' ' . esc_html__( 'You would have received an email because of this.', 'health-check' );
		}

		$desc .= ' ' . esc_html__( "We'll try again with the next release.", 'health-check' );
		$desc .= ' ' . sprintf(
			/* translators: %s: Code of error shown. */
			esc_html__( 'The error code was %s.', 'health-check' ),
			'<code>' . $failed['error_code'] . '</code>'
		);
		return array(
			'desc'     => $desc,
			'severity' => 'warning',
		);
	}

	/**
	 * Check if WordPress is controlled by a VCS (Git, Subversion etc).
	 *
	 * @uses dirname()
	 * @uses array_unique()
	 * @uses is_dir()
	 * @uses rtrim()
	 * @uses apply_filters()
	 * @uses sprintf()
	 * @uses esc_html__()
	 *
	 * @param string $context The path to check from.
	 *
	 * @return array
	 */
	function _test_is_vcs_checkout( $context ) {
		$context_dirs = array( ABSPATH );
		$vcs_dirs     = array( '.svn', '.git', '.hg', '.bzr' );
		$check_dirs   = array();

		foreach ( $context_dirs as $context_dir ) {
			// Walk up from $context_dir to the root.
			do {
				$check_dirs[] = $context_dir;

				// Once we've hit '/' or 'C:\', we need to stop. dirname will keep returning the input here.
				if ( dirname( $context_dir ) == $context_dir ) {
					break;
				}

				// Continue one level at a time.
			} while ( $context_dir = dirname( $context_dir ) );
		}

		$check_dirs = array_unique( $check_dirs );

		// Search all directories we've found for evidence of version control.
		foreach ( $vcs_dirs as $vcs_dir ) {
			foreach ( $check_dirs as $check_dir ) {
				// phpcs:ignore
				if ( $checkout = @is_dir( rtrim( $check_dir, '\\/' ) . "/$vcs_dir" ) ) {
					break 2;
				}
			}
		}

		if ( $checkout && ! apply_filters( 'automatic_updates_is_vcs_checkout', true, $context ) ) {
			return array(
				'desc'     => sprintf(
					// translators: %1$s: Folder name. %2$s: Version control directory. %3$s: Filter name.
					esc_html__( 'The folder %1$s was detected as being under version control (%2$s), but the %3$s filter is allowing updates.', 'health-check' ),
					'<code>' . $check_dir . '</code>',
					"<code>$vcs_dir</code>",
					'<code>automatic_updates_is_vcs_checkout</code>'
				),
				'severity' => 'info',
			);
		}

		if ( $checkout ) {
			return array(
				'desc'     => sprintf(
					// translators: %1$s: Folder name. %2$s: Version control directory.
					esc_html__( 'The folder %1$s was detected as being under version control (%2$s).', 'health-check' ),
					'<code>' . $check_dir . '</code>',
					"<code>$vcs_dir</code>"
				),
				'severity' => 'fail',
			);
		}

		return array(
			'desc'     => esc_html__( 'No version control systems were detected.', 'health-check' ),
			'severity' => 'pass',
		);
	}

	/**
	 * Check if the absolute path is under Version Control.
	 *
	 * @uses Health_Check_Auto_Updates::_test_is_vcs_checkout()
	 *
	 * @return array
	 */
	function test_vcs_ABSPATH() {
		$result = $this->_test_is_vcs_checkout( ABSPATH );
		return $result;
	}

	/**
	 * Check if we can access files without providing credentials.
	 *
	 * @uses Automatic_Upgrader_Skin
	 * @uses Automatic_Upgrader_Skin::request_filesystem_credentials()
	 * @uses esc_html__()
	 *
	 * @return array
	 */
	function test_check_wp_filesystem_method() {
		$skin    = new Automatic_Upgrader_Skin;
		$success = $skin->request_filesystem_credentials( false, ABSPATH );

		if ( ! $success ) {
			$desc  = esc_html__( 'Your installation of WordPress prompts for FTP credentials to perform updates.', 'health-check' );
			$desc .= ' ' . esc_html__( '(Your site is performing updates over FTP due to file ownership. Talk to your hosting company.)', 'health-check' );

			return array(
				'desc'     => $desc,
				'severity' => 'fail',
			);
		}

		return array(
			'desc'     => esc_html__( "Your installation of WordPress doesn't require FTP credentials to perform updates.", 'health-check' ),
			'severity' => 'pass',
		);
	}

	/**
	 * Check if core files are writeable by the web user/group.
	 *
	 * @global $wp_filesystem
	 *
	 * @uses Automatic_Upgrader_Skin
	 * @uses Automatic_Upgrader_Skin::request_filesystem_credentials()
	 * @uses WP_Filesystem
	 * @uses WP_Filesystem::method
	 * @uses get_core_checksums()
	 * @uses strpos()
	 * @uses sprintf()
	 * @uses esc_html__()
	 * @uses array_keys()
	 * @uses substr()
	 * @uses file_exists()
	 * @uses is_writable()
	 * @uses count()
	 * @uses array_slice()
	 * @uses implode()
	 *
	 * @return array|bool
	 */
	function test_all_files_writable() {
		global $wp_filesystem;
		include ABSPATH . WPINC . '/version.php'; // $wp_version; // x.y.z

		$skin    = new Automatic_Upgrader_Skin;
		$success = $skin->request_filesystem_credentials( false, ABSPATH );

		if ( ! $success ) {
			return false;
		}

		WP_Filesystem();

		if ( 'direct' != $wp_filesystem->method ) {
			return false;
		}

		$checksums = get_core_checksums( $wp_version, 'en_US' );
		$dev       = ( false !== strpos( $wp_version, '-' ) );
		// Get the last stable version's files and test against that
		if ( ! $checksums && $dev ) {
			$checksums = get_core_checksums( (float) $wp_version - 0.1, 'en_US' );
		}

		// There aren't always checksums for development releases, so just skip the test if we still can't find any
		if ( ! $checksums && $dev ) {
			return false;
		}

		if ( ! $checksums ) {
			$desc = sprintf(
				// translators: %s: WordPress version
				esc_html__( "Couldn't retrieve a list of the checksums for WordPress %s.", 'health-check' ),
				$wp_version
			);
			$desc .= ' ' . esc_html__( 'This could mean that connections are failing to WordPress.org.', 'health-check' );
			return array(
				'desc'     => $desc,
				'severity' => 'warning',
			);
		}

		$unwritable_files = array();
		foreach ( array_keys( $checksums ) as $file ) {
			if ( 'wp-content' == substr( $file, 0, 10 ) ) {
				continue;
			}
			if ( ! file_exists( ABSPATH . '/' . $file ) ) {
				continue;
			}
			if ( ! is_writable( ABSPATH . '/' . $file ) ) {
				$unwritable_files[] = $file;
			}
		}

		if ( $unwritable_files ) {
			if ( count( $unwritable_files ) > 20 ) {
				$unwritable_files   = array_slice( $unwritable_files, 0, 20 );
				$unwritable_files[] = '...';
			}
			return array(
				'desc'     => esc_html__( 'Some files are not writable by WordPress:', 'health-check' ) . ' <ul><li>' . implode( '</li><li>', $unwritable_files ) . '</li></ul>',
				'severity' => 'fail',
			);
		} else {
			return array(
				'desc'     => esc_html__( 'All of your WordPress files are writable.', 'health-check' ),
				'severity' => 'pass',
			);
		}
	}

	/**
	 * Check if the install is using a development branch and can use nightly packages.
	 *
	 * @uses strpos()
	 * @uses defined()
	 * @uses sprintf()
	 * @uses esc_html__()
	 * @uses apply_filters()
	 *
	 * @return array|bool
	 */
	function test_accepts_dev_updates() {
		include ABSPATH . WPINC . '/version.php'; // $wp_version; // x.y.z
		// Only for dev versions
		if ( false === strpos( $wp_version, '-' ) ) {
			return false;
		}

		if ( defined( 'WP_AUTO_UPDATE_CORE' ) && ( 'minor' === WP_AUTO_UPDATE_CORE || false === WP_AUTO_UPDATE_CORE ) ) {
			return array(
				'desc'     => sprintf(
					/* translators: %s: Name of the constant used. */
					esc_html__( 'WordPress development updates are blocked by the %s constant.', 'health-check' ),
					'<code>WP_AUTO_UPDATE_CORE</code>'
				),
				'severity' => 'fail',
			);
		}

		if ( ! apply_filters( 'allow_dev_auto_core_updates', $wp_version ) ) {
			return array(
				'desc'     => sprintf(
					/* translators: %s: Name of the filter used. */
					esc_html__( 'WordPress development updates are blocked by the %s filter.', 'health-check' ),
					'<code>allow_dev_auto_core_updates</code>'
				),
				'severity' => 'fail',
			);
		}
	}

	/**
	 * Check if the site supports automatic minor updates.
	 *
	 * @uses defined()
	 * @uses sprintf()
	 * @uses esc_html__()
	 * @uses apply_filters()
	 *
	 * @return array
	 */
	function test_accepts_minor_updates() {
		if ( defined( 'WP_AUTO_UPDATE_CORE' ) && false === WP_AUTO_UPDATE_CORE ) {
			return array(
				'desc'     => sprintf(
					/* translators: %s: Name of the constant used. */
					esc_html__( 'WordPress security and maintenance releases are blocked by %s.', 'health-check' ),
					"<code>define( 'WP_AUTO_UPDATE_CORE', false );</code>"
				),
				'severity' => 'fail',
			);
		}

		if ( ! apply_filters( 'allow_minor_auto_core_updates', true ) ) {
			return array(
				'desc'     => sprintf(
					/* translators: %s: Name of the filter used. */
					esc_html__( 'WordPress security and maintenance releases are blocked by the %s filter.', 'health-check' ),
					'<code>allow_minor_auto_core_updates</code>'
				),
				'severity' => 'fail',
			);
		}
	}
}
