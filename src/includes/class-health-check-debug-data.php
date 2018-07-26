<?php
/**
 * Class for providing debug data based on a users WordPress environment.
 *
 * @package Health Check
 */

/**
 * Class Health_Check_Debug_Data
 */
class Health_Check_Debug_Data {

	/**
	 * Calls all core funtions to check for updates
	 *
	 * @uses wp_version_check()
	 * @uses wp_update_plugins()
	 * @uses wp_update_themes()
	 *
	 * @return void
	 */
	static function check_for_updates() {

		wp_version_check();
		wp_update_plugins();
		wp_update_themes();

	}

	static function debug_data( $locale = null ) {
		if ( ! empty( $locale ) ) {
			// Change the language used for translations
			if ( function_exists( 'switch_to_locale' ) ) {
				$original_locale = get_locale();
				$switched_locale = switch_to_locale( $locale );
			}
		}
		global $wpdb;

		$upload_dir = wp_upload_dir();
		if ( file_exists( ABSPATH . 'wp-config.php' ) ) {
			$wp_config_path = ABSPATH . 'wp-config.php';
			// phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
		} elseif ( @file_exists( dirname( ABSPATH ) . '/wp-config.php' ) && ! @file_exists( dirname( ABSPATH ) . '/wp-settings.php' ) ) {
			$wp_config_path = dirname( ABSPATH ) . '/wp-config.php';
		}

		$core_current_version = get_bloginfo( 'version' );
		$core_updates         = get_core_updates();
		$core_update_needed   = '';

		foreach ( $core_updates as $core => $update ) {
			if ( 'upgrade' === $update->response ) {
				// translators: %s: Latest WordPress version number.
				$core_update_needed = ' ' . sprintf( __( '( Latest version: %s )', 'health-check' ), $update->version );
			} else {
				$core_update_needed = '';
			}
		}

		$info = array(
			'wp-core'             => array(
				'label'  => __( 'WordPress', 'health-check' ),
				'fields' => array(
					array(
						'label' => __( 'Version', 'health-check' ),
						'value' => $core_current_version . $core_update_needed,
					),
					array(
						'label' => __( 'Language', 'health-check' ),
						'value' => ( ! empty( $locale ) ? $original_locale : get_locale() ),
					),
					array(
						'label'   => __( 'Home URL', 'health-check' ),
						'value'   => get_bloginfo( 'url' ),
						'private' => true,
					),
					array(
						'label'   => __( 'Site URL', 'health-check' ),
						'value'   => get_bloginfo( 'wpurl' ),
						'private' => true,
					),
					array(
						'label' => __( 'Permalink structure', 'health-check' ),
						'value' => get_option( 'permalink_structure' ),
					),
					array(
						'label' => __( 'Is this site using HTTPS?', 'health-check' ),
						'value' => ( is_ssl() ? __( 'Yes', 'health-check' ) : __( 'No', 'health-check' ) ),
					),
					array(
						'label' => __( 'Can anyone register on this site?', 'health-check' ),
						'value' => ( get_option( 'users_can_register' ) ? __( 'Yes', 'health-check' ) : __( 'No', 'health-check' ) ),
					),
					array(
						'label' => __( 'Default comment status', 'health-check' ),
						'value' => get_option( 'default_comment_status' ),
					),
					array(
						'label' => __( 'Is this a multisite?', 'health-check' ),
						'value' => ( is_multisite() ? __( 'Yes', 'health-check' ) : __( 'No', 'health-check' ) ),
					),
				),
			),
			'wp-install-size'     => array(
				'label'  => __( 'Installation size', 'health-check' ),
				'fields' => array(),
			),
			'wp-dropins'          => array(
				'label'       => __( 'Drop-ins', 'health-check' ),
				'description' => __( 'Drop-ins are single files that replace or enhance WordPress features in ways that are not possible for traditional plugins', 'health-check' ),
				'fields'      => array(),
			),
			'wp-active-theme'     => array(
				'label'  => __( 'Active Theme', 'health-check' ),
				'fields' => array(),
			),
			'wp-themes'           => array(
				'label'      => __( 'Other themes', 'health-check' ),
				'show_count' => true,
				'fields'     => array(),
			),
			'wp-mu-plugins'       => array(
				'label'      => __( 'Must Use Plugins', 'health-check' ),
				'show_count' => true,
				'fields'     => array(),
			),
			'wp-plugins-active'   => array(
				'label'      => __( 'Active Plugins', 'health-check' ),
				'show_count' => true,
				'fields'     => array(),
			),
			'wp-plugins-inactive' => array(
				'label'      => __( 'Inactive Plugins', 'health-check' ),
				'show_count' => true,
				'fields'     => array(),
			),
			'wp-media'            => array(
				'label'  => __( 'Media handling', 'health-check' ),
				'fields' => array(),
			),
			'wp-server'           => array(
				'label'       => __( 'Server', 'health-check' ),
				'description' => __( 'The options shown below relate to your server setup. If changes are required, you may need your web host\'s assistance.', 'health-check' ),
				'fields'      => array(),
			),
			'wp-database'         => array(
				'label'  => __( 'Database', 'health-check' ),
				'fields' => array(),
			),
			'wp-constants'        => array(
				'label'       => __( 'WordPress Constants', 'health-check' ),
				'description' => __( 'These values represent values set in your websites code which affect WordPress in various ways that may be of importance when seeking help with your site.', 'health-check' ),
				'fields'      => array(
					array(
						'label' => 'ABSPATH',
						'value' => ( ! defined( 'ABSPATH' ) ? __( 'Undefined', 'health-check' ) : ABSPATH ),
					),
					array(
						'label' => 'WP_HOME',
						'value' => ( ! defined( 'WP_HOME' ) ? __( 'Undefined', 'health-check' ) : WP_HOME ),
					),
					array(
						'label' => 'WP_SITEURL',
						'value' => ( ! defined( 'WP_SITEURL' ) ? __( 'Undefined', 'health-check' ) : WP_SITEURL ),
					),
					array(
						'label' => 'WP_DEBUG',
						'value' => ( ! defined( 'WP_DEBUG' ) ? __( 'Undefined', 'health-check' ) : ( WP_DEBUG ? __( 'Enabled', 'health-check' ) : __( 'Disabled', 'health-check' ) ) ),
					),
					array(
						'label' => 'WP_MAX_MEMORY_LIMIT',
						'value' => ( ! defined( 'WP_MAX_MEMORY_LIMIT' ) ? __( 'Undefined', 'health-check' ) : WP_MAX_MEMORY_LIMIT ),
					),
					array(
						'label' => 'WP_DEBUG_DISPLAY',
						'value' => ( ! defined( 'WP_DEBUG_DISPLAY' ) ? __( 'Undefined', 'health-check' ) : ( WP_DEBUG_DISPLAY ? __( 'Enabled', 'health-check' ) : __( 'Disabled', 'health-check' ) ) ),
					),
					array(
						'label' => 'WP_DEBUG_LOG',
						'value' => ( ! defined( 'WP_DEBUG_LOG' ) ? __( 'Undefined', 'health-check' ) : ( WP_DEBUG_LOG ? __( 'Enabled', 'health-check' ) : __( 'Disabled', 'health-check' ) ) ),
					),
					array(
						'label' => 'SCRIPT_DEBUG',
						'value' => ( ! defined( 'SCRIPT_DEBUG' ) ? __( 'Undefined', 'health-check' ) : ( SCRIPT_DEBUG ? __( 'Enabled', 'health-check' ) : __( 'Disabled', 'health-check' ) ) ),
					),
					array(
						'label' => 'WP_CACHE',
						'value' => ( ! defined( 'WP_CACHE' ) ? __( 'Undefined', 'health-check' ) : ( WP_CACHE ? __( 'Enabled', 'health-check' ) : __( 'Disabled', 'health-check' ) ) ),
					),
					array(
						'label' => 'CONCATENATE_SCRIPTS',
						'value' => ( ! defined( 'CONCATENATE_SCRIPTS' ) ? __( 'Undefined', 'health-check' ) : ( CONCATENATE_SCRIPTS ? __( 'Enabled', 'health-check' ) : __( 'Disabled', 'health-check' ) ) ),
					),
					array(
						'label' => 'COMPRESS_SCRIPTS',
						'value' => ( ! defined( 'COMPRESS_SCRIPTS' ) ? __( 'Undefined', 'health-check' ) : ( COMPRESS_SCRIPTS ? __( 'Enabled', 'health-check' ) : __( 'Disabled', 'health-check' ) ) ),
					),
					array(
						'label' => 'COMPRESS_CSS',
						'value' => ( ! defined( 'COMPRESS_CSS' ) ? __( 'Undefined', 'health-check' ) : ( COMPRESS_CSS ? __( 'Enabled', 'health-check' ) : __( 'Disabled', 'health-check' ) ) ),
					),
					array(
						'label' => 'WP_LOCAL_DEV',
						'value' => ( ! defined( 'WP_LOCAL_DEV' ) ? __( 'Undefined', 'health-check' ) : ( WP_LOCAL_DEV ? __( 'Enabled', 'health-check' ) : __( 'Disabled', 'health-check' ) ) ),
					),
				),
			),
			'wp-filesystem'       => array(
				'label'       => __( 'Filesystem Permissions', 'health-check' ),
				'description' => __( 'The status of various locations WordPress needs to write files in various scenarios.', 'health-check' ),
				'fields'      => array(
					array(
						'label' => __( 'The main WordPress directory', 'health-check' ),
						'value' => ( wp_is_writable( ABSPATH ) ? __( 'Writable', 'health-check' ) : __( 'Not writable', 'health-check' ) ),
					),
					array(
						'label' => __( 'The wp-content directory', 'health-check' ),
						'value' => ( wp_is_writable( WP_CONTENT_DIR ) ? __( 'Writable', 'health-check' ) : __( 'Not writable', 'health-check' ) ),
					),
					array(
						'label' => __( 'The uploads directory', 'health-check' ),
						'value' => ( wp_is_writable( $upload_dir['basedir'] ) ? __( 'Writable', 'health-check' ) : __( 'Not writable', 'health-check' ) ),
					),
					array(
						'label' => __( 'The plugins directory', 'health-check' ),
						'value' => ( wp_is_writable( WP_PLUGIN_DIR ) ? __( 'Writable', 'health-check' ) : __( 'Not writable', 'health-check' ) ),
					),
					array(
						'label' => __( 'The themes directory', 'health-check' ),
						'value' => ( wp_is_writable( get_template_directory() . '/..' ) ? __( 'Writable', 'health-check' ) : __( 'Not writable', 'health-check' ) ),
					),
				),
			),
		);

		if ( is_multisite() ) {
			$network_query = new WP_Network_Query();
			$network_ids   = $network_query->query( array(
				'fields'        => 'ids',
				'number'        => 100,
				'no_found_rows' => false,
			) );

			$site_count = 0;
			foreach ( $network_ids as $network_id ) {
				$site_count += get_blog_count( $network_id );
			}

			$info['wp-core']['fields'][] = array(
				'label' => __( 'User Count', 'health-check' ),
				'value' => get_user_count(),
			);
			$info['wp-core']['fields'][] = array(
				'label' => __( 'Site Count', 'health-check' ),
				'value' => $site_count,
			);
			$info['wp-core']['fields'][] = array(
				'label' => __( 'Network Count', 'health-check' ),
				'value' => $network_query->found_networks,
			);
		} else {
			$user_count = count_users();

			$info['wp-core']['fields'][] = array(
				'label' => __( 'User Count', 'health-check' ),
				'value' => $user_count['total_users'],
			);
		}

		// WordPress features requiring processing.
		$wp_dotorg = wp_remote_get( 'https://wordpress.org', array(
			'timeout' => 10,
		) );
		if ( ! is_wp_error( $wp_dotorg ) ) {
			$info['wp-core']['fields'][] = array(
				'label' => __( 'Communication with WordPress.org', 'health-check' ),
				'value' => sprintf(
					__( 'WordPress.org is reachable', 'health-check' )
				),
			);
		} else {
			$info['wp-core']['fields'][] = array(
				'label' => __( 'Communication with WordPress.org', 'health-check' ),
				'value' => sprintf(
					// translators: %1$s: The IP address WordPress.org resolves to. %2$s: The error returned by the lookup.
					__( 'Unable to reach WordPress.org at %1$s: %2$s', 'health-check' ),
					gethostbyname( 'wordpress.org' ),
					$wp_dotorg->get_error_message()
				),
			);
		}

		$loopback                    = Health_Check_Loopback::can_perform_loopback();
		$info['wp-core']['fields'][] = array(
			'label' => __( 'Create loopback requests', 'health-check' ),
			'value' => $loopback->message,
		);

		// Get drop-ins.
		$dropins            = get_dropins();
		$dropin_description = _get_dropins();
		foreach ( $dropins as $dropin_key => $dropin ) {
			$info['wp-dropins']['fields'][] = array(
				'label' => $dropin_key,
				'value' => $dropin_description[ $dropin_key ][0],
			);
		}

		// Populate the media fields.
		$info['wp-media']['fields'][] = array(
			'label' => __( 'Active editor', 'health-check' ),
			'value' => _wp_image_editor_choose(),
		);

		// Get ImageMagic information, if available.
		if ( class_exists( 'Imagick' ) ) {
			// Save the Imagick instance for later use.
			$imagick         = new Imagick();
			$imagick_version = $imagick->getVersion();
		} else {
			$imagick_version = 'Imagick not available';
		}
		$info['wp-media']['fields'][] = array(
			'label' => __( 'Imagick Module Version', 'health-check' ),
			'value' => ( is_array( $imagick_version ) ? $imagick_version['versionNumber'] : $imagick_version ),
		);
		$info['wp-media']['fields'][] = array(
			'label' => __( 'ImageMagick Version', 'health-check' ),
			'value' => ( is_array( $imagick_version ) ? $imagick_version['versionString'] : $imagick_version ),
		);

		// If Imagick is used as our editor, provide some more information about its limitations.
		if ( 'WP_Image_Editor_Imagick' === _wp_image_editor_choose() && isset( $imagick ) && $imagick instanceof Imagick ) {
			$limits = array(
				'area'   => ( defined( 'imagick::RESOURCETYPE_AREA' ) ? size_format( $imagick->getResourceLimit( imagick::RESOURCETYPE_AREA ) ) : 'Not Available' ),
				'disk'   => ( defined( 'imagick::RESOURCETYPE_DISK' ) ? $imagick->getResourceLimit( imagick::RESOURCETYPE_DISK ) : 'Not Available' ),
				'file'   => ( defined( 'imagick::RESOURCETYPE_FILE' ) ? $imagick->getResourceLimit( imagick::RESOURCETYPE_FILE ) : 'Not Available' ),
				'map'    => ( defined( 'imagick::RESOURCETYPE_MAP' ) ? size_format( $imagick->getResourceLimit( imagick::RESOURCETYPE_MAP ) ) : 'Not Available' ),
				'memory' => ( defined( 'imagick::RESOURCETYPE_MEMORY' ) ? size_format( $imagick->getResourceLimit( imagick::RESOURCETYPE_MEMORY ) ) : 'Not Available' ),
				'thread' => ( defined( 'imagick::RESOURCETYPE_THREAD' ) ? $imagick->getResourceLimit( imagick::RESOURCETYPE_THREAD ) : 'Not Available' ),
			);

			$info['wp-media']['fields'][] = array(
				'label' => __( 'Imagick Resource Limits', 'health-check' ),
				'value' => $limits,
			);
		}

		// Get GD information, if available.
		if ( function_exists( 'gd_info' ) ) {
			$gd = gd_info();
		} else {
			$gd = false;
		}
		$info['wp-media']['fields'][] = array(
			'label' => __( 'GD Version', 'health-check' ),
			'value' => ( is_array( $gd ) ? $gd['GD Version'] : __( 'GD not available', 'health-check' ) ),
		);

		// Get Ghostscript information, if available.
		if ( function_exists( 'exec' ) ) {
			$gs = exec( 'gs --version' );
			$gs = ( ! empty( $gs ) ? $gs : __( 'Not available', 'health-check' ) );
		} else {
			$gs = __( 'Unable to determine if Ghostscript is installed', 'health-check' );
		}
		$info['wp-media']['fields'][] = array(
			'label' => __( 'Ghostscript Version', 'health-check' ),
			'value' => $gs,
		);

		// Populate the server debug fields.
		$info['wp-server']['fields'][] = array(
			'label' => __( 'Server architecture', 'health-check' ),
			'value' => ( ! function_exists( 'php_uname' ) ? __( 'Unable to determine server architecture', 'health-check' ) : sprintf( '%s %s %s', php_uname( 's' ), php_uname( 'r' ), php_uname( 'm' ) ) ),
		);
		$info['wp-server']['fields'][] = array(
			'label' => __( 'PHP Version', 'health-check' ),
			'value' => ( ! function_exists( 'phpversion' ) ? __( 'Unable to determine PHP version', 'health-check' ) : sprintf(
				'%s %s',
				phpversion(),
				( 64 === PHP_INT_SIZE * 8 ? __( '(Supports 64bit values)', 'health-check' ) : '' )
			)
			),
		);
		$info['wp-server']['fields'][] = array(
			'label' => __( 'PHP SAPI', 'health-check' ),
			'value' => ( ! function_exists( 'php_sapi_name' ) ? __( 'Unable to determine PHP SAPI', 'health-check' ) : php_sapi_name() ),
		);

		if ( ! function_exists( 'ini_get' ) ) {
			$info['wp-server']['fields'][] = array(
				'label' => __( 'Server settings', 'health-check' ),
				'value' => __( 'Unable to determine some settings as the ini_get() function has been disabled', 'health-check' ),
			);
		} else {
			$info['wp-server']['fields'][] = array(
				'label' => __( 'PHP max input variables', 'health-check' ),
				'value' => ini_get( 'max_input_vars' ),
			);
			$info['wp-server']['fields'][] = array(
				'label' => __( 'PHP time limit', 'health-check' ),
				'value' => ini_get( 'max_execution_time' ),
			);
			$info['wp-server']['fields'][] = array(
				'label' => __( 'PHP memory limit', 'health-check' ),
				'value' => ini_get( 'memory_limit' ),
			);
			$info['wp-server']['fields'][] = array(
				'label' => __( 'Max input time', 'health-check' ),
				'value' => ini_get( 'max_input_time' ),
			);
			$info['wp-server']['fields'][] = array(
				'label' => __( 'Upload max filesize', 'health-check' ),
				'value' => ini_get( 'upload_max_filesize' ),
			);
			$info['wp-server']['fields'][] = array(
				'label' => __( 'PHP post max size', 'health-check' ),
				'value' => ini_get( 'post_max_size' ),
			);
		}

		if ( function_exists( 'curl_version' ) ) {
			$curl                          = curl_version();
			$info['wp-server']['fields'][] = array(
				'label' => __( 'cURL Version', 'health-check' ),
				'value' => sprintf( '%s %s', $curl['version'], $curl['ssl_version'] ),
			);
		} else {
			$info['wp-server']['fields'][] = array(
				'label' => __( 'cURL Version', 'health-check' ),
				'value' => __( 'Your server does not support cURL', 'health-check' ),
			);
		}

		$info['wp-server']['fields'][] = array(
			'label' => __( 'SUHOSIN installed', 'health-check' ),
			'value' => ( ( extension_loaded( 'suhosin' ) || ( defined( 'SUHOSIN_PATCH' ) && constant( 'SUHOSIN_PATCH' ) ) ) ? __( 'Yes', 'health-check' ) : __( 'No', 'health-check' ) ),
		);

		$info['wp-server']['fields'][] = array(
			'label' => __( 'Is the Imagick library available', 'health-check' ),
			'value' => ( extension_loaded( 'imagick' ) ? __( 'Yes', 'health-check' ) : __( 'No', 'health-check' ) ),
		);

		// Check if a .htaccess file exists.
		if ( is_file( ABSPATH . '/.htaccess' ) ) {
			// If the file exists, grab the content of it.
			$htaccess_content = file_get_contents( ABSPATH . '/.htaccess' );

			// Filter away the core WordPress rules.
			$filtered_htaccess_content = trim( preg_replace( '/\# BEGIN WordPress[\s\S]+?# END WordPress/si', '', $htaccess_content ) );

			$info['wp-server']['fields'][] = array(
				'label' => __( 'htaccess rules', 'health-check' ),
				'value' => ( ! empty( $filtered_htaccess_content ) ? __( 'Custom rules have been added to your htaccess file', 'health-check' ) : __( 'Your htaccess file only contains core WordPress features', 'health-check' ) ),
			);
		}

		// Populate the database debug fields.
		if ( is_resource( $wpdb->dbh ) ) {
			// Old mysql extension.
			$extension = 'mysql';
		} elseif ( is_object( $wpdb->dbh ) ) {
			// mysqli or PDO.
			$extension = get_class( $wpdb->dbh );
		} else {
			// Unknown sql extension.
			$extension = null;
		}

		if ( method_exists( $wpdb, 'db_version' ) ) {
			if ( $wpdb->use_mysqli ) {
				// phpcs:ignore WordPress.DB.RestrictedFunctions.mysql_mysqli_get_server_info
				$server = mysqli_get_server_info( $wpdb->dbh );
			} else {
				// phpcs:ignore WordPress.DB.RestrictedFunctions.mysql_mysql_get_server_info
				$server = mysql_get_server_info( $wpdb->dbh );
			}
		} else {
			$server = null;
		}

		if ( isset( $wpdb->use_mysqli ) && $wpdb->use_mysqli ) {
			$client_version = $wpdb->dbh->client_info;
		} else {
			// phpcs:ignore WordPress.DB.RestrictedFunctions.mysql_mysql_get_client_info
			if ( preg_match( '|[0-9]{1,2}\.[0-9]{1,2}\.[0-9]{1,2}|', mysql_get_client_info(), $matches ) ) {
				$client_version = $matches[0];
			} else {
				$client_version = null;
			}
		}

		$info['wp-database']['fields'][] = array(
			'label' => __( 'Extension', 'health-check' ),
			'value' => $extension,
		);
		$info['wp-database']['fields'][] = array(
			'label' => __( 'Server version', 'health-check' ),
			'value' => $server,
		);
		$info['wp-database']['fields'][] = array(
			'label' => __( 'Client version', 'health-check' ),
			'value' => $client_version,
		);
		$info['wp-database']['fields'][] = array(
			'label'   => __( 'Database user', 'health-check' ),
			'value'   => $wpdb->dbuser,
			'private' => true,
		);
		$info['wp-database']['fields'][] = array(
			'label'   => __( 'Database host', 'health-check' ),
			'value'   => $wpdb->dbhost,
			'private' => true,
		);
		$info['wp-database']['fields'][] = array(
			'label'   => __( 'Database name', 'health-check' ),
			'value'   => $wpdb->dbname,
			'private' => true,
		);
		$info['wp-database']['fields'][] = array(
			'label' => __( 'Database prefix', 'health-check' ),
			'value' => $wpdb->prefix,
		);

		// List must use plugins if there are any.
		$mu_plugins = get_mu_plugins();

		foreach ( $mu_plugins as $plugin_path => $plugin ) {
			$plugin_version = $plugin['Version'];
			$plugin_author  = $plugin['Author'];

			$plugin_version_string = __( 'No version or author information available', 'health-check' );

			if ( ! empty( $plugin_version ) && ! empty( $plugin_author ) ) {
				// translators: %1$s: Plugin version number. %2$s: Plugin author name.
				$plugin_version_string = sprintf( __( 'Version %1$s by %2$s', 'health-check' ), $plugin_version, $plugin_author );
			}
			if ( empty( $plugin_version ) && ! empty( $plugin_author ) ) {
				// translators: %s: Plugin author name.
				$plugin_version_string = sprintf( __( 'By %s', 'health-check' ), $plugin_author );
			}
			if ( ! empty( $plugin_version ) && empty( $plugin_author ) ) {
				// translators: %s: Plugin version number.
				$plugin_version_string = sprintf( __( 'Version %s', 'health-check' ), $plugin_version );
			}

			$info['wp-mu-plugins']['fields'][] = array(
				'label' => $plugin['Name'],
				'value' => $plugin_version_string,
			);
		}

		// List all available plugins.
		$plugins        = get_plugins();
		$plugin_updates = get_plugin_updates();

		foreach ( $plugins as $plugin_path => $plugin ) {
			$plugin_part = ( is_plugin_active( $plugin_path ) ) ? 'wp-plugins-active' : 'wp-plugins-inactive';

			$plugin_version = $plugin['Version'];
			$plugin_author  = $plugin['Author'];

			$plugin_version_string = __( 'No version or author information available', 'health-check' );

			if ( ! empty( $plugin_version ) && ! empty( $plugin_author ) ) {
				// translators: %1$s: Plugin version number. %2$s: Plugin author name.
				$plugin_version_string = sprintf( __( 'Version %1$s by %2$s', 'health-check' ), $plugin_version, $plugin_author );
			}
			if ( empty( $plugin_version ) && ! empty( $plugin_author ) ) {
				// translators: %s: Plugin author name.
				$plugin_version_string = sprintf( __( 'By %s', 'health-check' ), $plugin_author );
			}
			if ( ! empty( $plugin_version ) && empty( $plugin_author ) ) {
				// translators: %s: Plugin version number.
				$plugin_version_string = sprintf( __( 'Version %s', 'health-check' ), $plugin_version );
			}

			if ( array_key_exists( $plugin_path, $plugin_updates ) ) {
				// translators: %s: Latest plugin version number.
				$plugin_update_needed = ' ' . sprintf( __( '( Latest version: %s )', 'health-check' ), $plugin_updates[ $plugin_path ]->update->new_version );
			} else {
				$plugin_update_needed = '';
			}

			$info[ $plugin_part ]['fields'][] = array(
				'label' => $plugin['Name'],
				'value' => $plugin_version_string . $plugin_update_needed,
			);
		}

		// Populate the section for the currently active theme.
		global $_wp_theme_features;
		$theme_features = array();
		if ( ! empty( $_wp_theme_features ) ) {
			foreach ( $_wp_theme_features as $feature => $options ) {
				$theme_features[] = $feature;
			}
		}

		$active_theme  = wp_get_theme();
		$theme_updates = get_theme_updates();

		if ( array_key_exists( $active_theme->stylesheet, $theme_updates ) ) {
			// translators: %s: Latest theme version number.
			$theme_update_needed_active = ' ' . sprintf( __( '( Latest version: %s )', 'health-check' ), $theme_updates[ $active_theme->stylesheet ]->update['new_version'] );
		} else {
			$theme_update_needed_active = '';
		}

		$info['wp-active-theme']['fields'] = array(
			array(
				'label' => __( 'Name', 'health-check' ),
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
				'value' => $active_theme->Name,
			),
			array(
				'label' => __( 'Version', 'health-check' ),
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
				'value' => $active_theme->Version . $theme_update_needed_active,
			),
			array(
				'label' => __( 'Author', 'health-check' ),
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
				'value' => wp_kses( $active_theme->Author, array() ),
			),
			array(
				'label' => __( 'Author website', 'health-check' ),
				'value' => ( $active_theme->offsetGet( 'Author URI' ) ? $active_theme->offsetGet( 'Author URI' ) : __( 'Undefined', 'health-check' ) ),
			),
			array(
				'label' => __( 'Parent theme', 'health-check' ),
				'value' => ( $active_theme->parent_theme ? $active_theme->parent_theme : __( 'Not a child theme', 'health-check' ) ),
			),
			array(
				'label' => __( 'Supported theme features', 'health-check' ),
				'value' => implode( ', ', $theme_features ),
			),
		);

		// Populate a list of all themes available in the install.
		$all_themes = wp_get_themes();

		foreach ( $all_themes as $theme_slug => $theme ) {
			// Ignore the currently active theme from the list of all themes.
			if ( $active_theme->stylesheet == $theme_slug ) {
				continue;
			}
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
			$theme_version = $theme->Version;
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
			$theme_author = $theme->Author;

			$theme_version_string = __( 'No version or author information available', 'health-check' );

			if ( ! empty( $theme_version ) && ! empty( $theme_author ) ) {
				// translators: %1$s: Theme version number. %2$s: Theme author name.
				$theme_version_string = sprintf( __( 'Version %1$s by %2$s', 'health-check' ), $theme_version, wp_kses( $theme_author, array() ) );
			}
			if ( empty( $theme_version ) && ! empty( $theme_author ) ) {
				// translators: %s: Theme author name.
				$theme_version_string = sprintf( __( 'By %s', 'health-check' ), wp_kses( $theme_author, array() ) );
			}
			if ( ! empty( $theme_version ) && empty( $theme_author ) ) {
				// translators: %s: Theme version number.
				$theme_version_string = sprintf( __( 'Version %s', 'health-check' ), $theme_version );
			}

			if ( array_key_exists( $theme_slug, $theme_updates ) ) {
				// translators: %s: Latest theme version number.
				$theme_update_needed = ' ' . sprintf( __( '( Latest version: %s )', 'health-check' ), $theme_updates[ $theme_slug ]->update['new_version'] );
			} else {
				$theme_update_needed = '';
			}

			$info['wp-themes']['fields'][] = array(
				'label' => sprintf(
					// translators: %1$s: Theme name. %2$s: Theme slug.
					__( '%1$s (%2$s)', 'health-check' ),
					// phpcs:ignore WordPress.NamingConventions.ValidVariableName.NotSnakeCaseMemberVar
					$theme->Name,
					$theme_slug
				),
				'value' => $theme_version_string . $theme_update_needed,
			);
		}

		// Add more filesystem checks
		if ( defined( 'WPMU_PLUGIN_DIR' ) && is_dir( WPMU_PLUGIN_DIR ) ) {
			$info['wp-filesystem']['fields'][] = array(
				'label' => __( 'The Must Use Plugins directory', 'health-check' ),
				'value' => ( wp_is_writable( WPMU_PLUGIN_DIR ) ? __( 'Writable', 'health-check' ) : __( 'Not writable', 'health-check' ) ),
			);
		}

		$info['wp-install-size']['fields'] = Health_Check_Debug_Data::get_installation_size();

		/**
		 * Add or modify new debug sections.
		 *
		 * Plugin or themes may wish to introduce their own debug information without creating additional admin pages for this
		 * kind of information as it is rarely needed, they can then utilize this filter to introduce their own sections.
		 *
		 * This filter intentionally does not include the fields introduced by core as those should always be un-modified
		 * and reliable for support related scenarios, take note that the core fields will take priority if a filtered value
		 * is trying to use the same array keys.
		 *
		 * Array keys added by core are all prefixed with `wp-`, plugins and themes are encouraged to use their own slug as
		 * a prefix, both for consistency as well as avoiding key collisions.
		 *
		 * @since 4.9.0
		 *
		 * @param array $args {
		 *     The debug information to be added to the core information page.
		 *
		 *     @type string  $label        The title for this section of the debug output.
		 *     @type string  $description  Optional. A description for your information section which may contain basic HTML
		 *                                 markup: `em`, `strong` and `a` for linking to documentation or putting emphasis.
		 *     @type boolean $show_count   Optional. If set to `true` the amount of fields will be included in the title for
		 *                                 this section.
		 *     @type boolean $private      Optional. If set to `true` the section and all associated fields will be excluded
		 *                                 from the copy-paste text area.
		 *     @type array   $fields {
		 *         An associative array containing the data to be displayed.
		 *
		 *         @type string  $label    The label for this piece of information.
		 *         @type string  $value    The output that is of interest for this field.
		 *         @type boolean $private  Optional. If set to `true` the field will not be included in the copy-paste text area
		 *                                 on top of the page, allowing you to show, for example, API keys here.
		 *     }
		 * }
		 */
		$external_info = apply_filters( 'debug_information', array() );

		// Merge the core and external debug fields.
		$info = array_replace_recursive( $info, array_replace_recursive( $external_info, $info ) );

		if ( ! empty( $locale ) ) {
			// Change the language used for translations
			if ( function_exists( 'restore_previous_locale' ) && $switched_locale ) {
				restore_previous_locale();
			}
		}

		return $info;
	}

	public static function get_installation_size() {
		$uploads_dir = wp_upload_dir();

		$sizes = array(
			'wp'      => array(
				'path' => ABSPATH,
				'size' => 0,
			),
			'themes'  => array(
				'path' => trailingslashit( get_theme_root() ),
				'size' => 0,
			),
			'plugins' => array(
				'path' => WP_PLUGIN_DIR,
				'size' => 0,
			),
			'uploads' => array(
				'path' => $uploads_dir['basedir'],
				'size' => 0,
			),
		);

		$inaccurate = false;

		foreach ( $sizes as $size => $attributes ) {
			try {
				$sizes[ $size ]['size'] = Health_Check_Debug_Data::get_directory_size( $attributes['path'] );
			} catch ( Exception $e ) {
				$inaccurate = true;
			}
		}

		$size_db = Health_Check_Debug_Data::get_database_size();

		$size_total = $sizes['wp']['size'] + $size_db;

		$result = array(
			array(
				'label' => __( 'Uploads Directory', 'health-check' ),
				'value' => size_format( $sizes['uploads']['size'], 2 ),
			),
			array(
				'label' => __( 'Themes Directory', 'health-check' ),
				'value' => size_format( $sizes['themes']['size'], 2 ),
			),
			array(
				'label' => __( 'Plugins Directory', 'health-check' ),
				'value' => size_format( $sizes['plugins']['size'], 2 ),
			),
			array(
				'label' => __( 'Database size', 'health-check' ),
				'value' => size_format( $size_db, 2 ),
			),
			array(
				'label' => __( 'Whole WordPress Directory', 'health-check' ),
				'value' => size_format( $sizes['wp']['size'], 2 ),
			),
			array(
				'label' => __( 'Total installation size', 'health-check' ),
				'value' => sprintf(
					'%s%s',
					size_format( $size_total, 2 ),
					( false === $inaccurate ? '' : __( '- Some errors, likely caused by invalid permissions, were encountered when determining the size of your installation. This means the values represented may be inaccurate.', 'health-check' ) )
				),
			),
		);

		return $result;
	}

	public static function get_directory_size( $path ) {
		$size = 0;

		foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $path ) ) as $file ) {
			$size += $file->getSize();
		}

		return $size;
	}

	public static function get_database_size() {
		global $wpdb;
		$size = 0;
		$rows = $wpdb->get_results( 'SHOW TABLE STATUS', ARRAY_A );

		if ( $wpdb->num_rows > 0 ) {
			foreach ( $rows as $row ) {
				$size += $row['Data_length'] + $row['Index_length'];
			}
		}

		return $size;
	}
}
