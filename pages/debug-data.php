<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( esc_html__( 'We\'re sorry, but you can not directly access this file.', 'health-check' ) );
}

global $wpdb;

$upload_dir = wp_upload_dir();
if ( file_exists( ABSPATH . 'wp-config.php') ) {
	$wp_config_path = ABSPATH . 'wp-config.php';
} else if ( @file_exists( dirname( ABSPATH ) . '/wp-config.php' ) && ! @file_exists( dirname( ABSPATH ) . '/wp-settings.php' ) ) {
	$wp_config_path = dirname( ABSPATH ) . '/wp-config.php';
}

$info = array(
	'wp-core'             => array(
		'label'  => __( 'WordPress', 'health-check' ),
		'fields' => array(
			array(
				'label' => __( 'Version', 'health-check' ),
				'value' => get_bloginfo( 'version' )
			),
			array(
				'label' => __( 'Language', 'health-check' ),
				'value' => get_locale()
			),
			array(
				'label'   => __( 'Home URL', 'health-check' ),
				'value'   => get_bloginfo( 'url' ),
				'private' => true
			),
			array(
				'label'   => __( 'Site URL', 'health-check' ),
				'value'   => get_bloginfo( 'wpurl' ),
				'private' => true
			),
			array(
				'label' => __( 'Permalink structure', 'health-check' ),
				'value' => get_option( 'permalink_structure' )
			),
			array(
				'label' => __( 'Is this site using HTTPS', 'health-check' ),
				'value' => ( is_ssl() ? __( 'Yes' ) : __( 'No' ) )
			),
			array(
				'label' => __( 'Can anyone register on this site', 'health-check' ),
				'value' => ( get_option( 'users_can_register' ) ? __( 'Yes', 'health-check' ) : __( 'No', 'health-check' ) )
			),
			array(
				'label' => __( 'Default comment status', 'health-check' ),
				'value' => get_option( 'default_comment_status' )
			),
			array(
				'label' => __( 'Is this a multisite', 'health-check' ),
				'value' => ( is_multisite() ? __( 'Yes', 'health-check' ) : __( 'No', 'health-check' ) )
			)
		),
	),
	'wp-dropins'          => array(
		'label'       => __( 'Drop-ins', 'health-check' ),
		'description' => __( 'Drop-ins are single files that replace or enhance WordPress features in ways that are not possible for traditional plugins', 'health-check' ),
		'fields'      => array()
	),
	'wp-active-theme'     => array(
		'label'  => __( 'Active theme', 'health-check' ),
		'fields' => array()
	),
	'wp-themes'           => array(
		'label'      => __( 'Other themes', 'health-check' ),
		'show_count' => true,
		'fields'     => array()
	),
	'wp-mu-plugins'       => array(
		'label'      => __( 'Must User Plugins', 'health-check' ),
		'show_count' => true,
		'fields'     => array()
	),
	'wp-plugins-active'   => array(
		'label'      => __( 'Active Plugins', 'health-check' ),
		'show_count' => true,
		'fields'     => array()
	),
	'wp-plugins-inactive' => array(
		'label'      => __( 'Inactive Plugins', 'health-check' ),
		'show_count' => true,
		'fields'     => array()
	),
	'wp-server'           => array(
		'label'       => __( 'Server', 'health-check' ),
		'description' => __( 'The options shown below relate to your server setup. If changes are required, you may need your web host\'s assistance.', 'health-check' ),
		'fields'      => array()
	),
	'wp-database'         => array(
		'label'  => __( 'Database', 'health-check' ),
		'fields' => array()
	),
	'wp-constants'        => array(
		'label'       => __( 'WordPress constants', 'health-check' ),
		'description' => __( 'These values represent values set in your websites code which affect WordPress in various ways that may be of importance when seeking help with your site.', 'health-check' ),
		'fields'      => array(
			array(
				'label' => 'ABSPATH',
				'value' => ( ! defined( 'ABSPATH' ) ? __( 'Undefined', 'health-check' ) : ABSPATH )
			),
			array(
				'label' => 'WP_HOME',
				'value' => ( ! defined( 'WP_HOME' ) ? __( 'Undefined', 'health-check' ) : WP_HOME )
			),
			array(
				'label' => 'WP_SITEURL',
				'value' => ( ! defined( 'WP_SITEURL' ) ? __( 'Undefined', 'health-check' ) : WP_SITEURL )
			),
			array(
				'label' => 'WP_DEBUG',
				'value' => ( ! defined( 'WP_DEBUG' ) ? __( 'Undefined', 'health-check' ) : ( WP_DEBUG ? __( 'Enabled', 'health-check' ) : __( 'Disabled', 'health-check' ) ) )
			),
			array(
				'label' => 'WP_MAX_MEMORY_LIMIT',
				'value' => ( ! defined( 'WP_MAX_MEMORY_LIMIT' ) ? __( 'Undefined', 'health-check' ) : WP_MAX_MEMORY_LIMIT )
			),
			array(
				'label' => 'WP_DEBUG_DISPLAY',
				'value' => ( ! defined( 'WP_DEBUG_DISPLAY' ) ? __( 'Undefined', 'health-check' ) : ( WP_DEBUG_DISPLAY ? __( 'Enabled', 'health-check' ) : __( 'Disabled', 'health-check' ) ) )
			),
			array(
				'label' => 'WP_DEBUG_LOG',
				'value' => ( ! defined( 'WP_DEBUG_LOG' ) ? __( 'Undefined', 'health-check' ) : ( WP_DEBUG_LOG ? __( 'Enabled', 'health-check' ) : __( 'Disabled', 'health-check' ) ) )
			),
			array(
				'label' => 'SCRIPT_DEBUG',
				'value' => ( ! defined( 'SCRIPT_DEBUG' ) ? __( 'Undefined', 'health-check' ) : ( SCRIPT_DEBUG ? __( 'Enabled', 'health-check' ) : __( 'Disabled', 'health-check' ) ) )
			),
			array(
				'label' => 'WP_CACHE',
				'value' => ( ! defined( 'WP_CACHE' ) ? __( 'Undefined', 'health-check' ) : ( WP_CACHE ? __( 'Enabled', 'health-check' ) : __( 'Disabled', 'health-check' ) ) )
			),
			array(
				'label' => 'CONCATENATE_SCRIPTS',
				'value' => ( ! defined( 'CONCATENATE_SCRIPTS' ) ? __( 'Undefined', 'health-check' ) : ( CONCATENATE_SCRIPTS ? __( 'Enabled', 'health-check' ) : __( 'Disabled', 'health-check' ) ) )
			),
			array(
				'label' => 'COMPRESS_SCRIPTS',
				'value' => ( ! defined( 'COMPRESS_SCRIPTS' ) ? __( 'Undefined', 'health-check' ) : ( COMPRESS_SCRIPTS ? __( 'Enabled', 'health-check' ) : __( 'Disabled', 'health-check' ) ) )
			),
			array(
				'label' => 'COMPRESS_CSS',
				'value' => ( ! defined( 'COMPRESS_CSS' ) ? __( 'Undefined', 'health-check' ) : ( COMPRESS_CSS ? __( 'Enabled', 'health-check' ) : __( 'Disabled', 'health-check' ) ) )
			),
			array(
				'label' => 'WP_LOCAL_DEV',
				'value' => ( ! defined( 'WP_LOCAL_DEV' ) ? __( 'Undefined', 'health-check' ) : ( WP_LOCAL_DEV ? __( 'Enabled', 'health-check' ) : __( 'Disabled', 'health-check' ) ) )
			)
		)
	),
	'wp-filesystem' => array(
		'label'       => __( 'Filesystem permissions', 'health-check' ),
		'description' => __( 'The status of various locations WordPress needs to write files in various scenarios.', 'health-check' ),
		'fields'      => array(
			array(
				'label' => __( 'The main WordPress directory', 'health-check' ),
				'value' => ( wp_is_writable( ABSPATH ) ? __( 'Writable', 'health-check' ) : __( 'Not writable', 'health-check' ) )
			),
			array(
				'label' => __( 'The wp-content directory', 'health-check' ),
				'value' => ( wp_is_writable( WP_CONTENT_DIR ) ? __( 'Writable', 'health-check' ) : __( 'Not writable', 'health-check' ) )
			),
			array(
				'label' => __( 'The uploads directory', 'health-check' ),
				'value' => ( wp_is_writable( $upload_dir['basedir'] ) ? __( 'Writable', 'health-check' ) : __( 'Not writable', 'health-check' ) )
			),
			array(
				'label' => __( 'The plugins directory', 'health-check' ),
				'value' => ( wp_is_writable( WP_PLUGIN_DIR ) ? __( 'Writable', 'health-check' ) : __( 'Not writable', 'health-check' ) )
			),
			array(
				'label' => __( 'The themes directory', 'health-check' ),
				'value' => ( wp_is_writable( get_template_directory() . '/..' ) ? __( 'Writable', 'health-check' ) : __( 'Not writable', 'health-check' ) )
			),
			array(
				'label' => __( 'The Must User Plugins directory', 'health-check' ),
				'value' => ( wp_is_writable( WPMU_PLUGIN_DIR ) ? __( 'Writable', 'health-check' ) : __( 'Not writable', 'health-check' ) )
			)
		)
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
	foreach ( $network_ids AS $network_id ) {
		$site_count += get_blog_count( $network_id );
	}

	$info['wp-core']['fields'][] = array(
		'label' => __( 'User Count', 'health-check' ),
		'value' => get_user_count()
	);
	$info['wp-core']['fields'][] = array(
		'label' => __( 'Site Count', 'health-check' ),
		'value' => $site_count
	);
	$info['wp-core']['fields'][] = array(
		'label' => __( 'Network Count', 'health-check' ),
		'value' => $network_query->found_networks
	);
} else {
	$user_count = count_users();

	$info['wp-core']['fields'][] = array(
		'label' => __( 'User Count', 'health-check' ),
		'value' => $user_count['total_users']
	);
}

// WordPress features requiring processing.
$wp_dotorg = wp_remote_get( 'https://wordpress.org', array( 'timeout' => 10 ) );
if ( ! is_wp_error( $wp_dotorg ) ) {
	$info['wp-core']['fields'][] = array(
		'label' => __( 'Communication with WordPress.org', 'health-check' ),
		'value' => sprintf(
			__( 'WordPress.org is reachable', 'health-check' )
		)
	);
} else {
	$info['wp-core']['fields'][] = array(
		'label' => __( 'Communication with WordPress.org', 'health-check' ),
		'value' => sprintf(
		// translators: %1$s: The IP address WordPress.org resolves to. %2$s: The error returned by the lookup.
			__( 'Unable to reach WordPress.org at %1$s: %2$s', 'health-check' ),
			gethostbyname( 'wordpress.org' ),
			$wp_dotorg->get_error_message()
		)
	);
}

// Get drop-ins.
$dropins            = get_dropins();
$dropin_description = _get_dropins();
foreach ( $dropins AS $dropin_key => $dropin ) {
	$info['wp-dropins']['fields'][] = array(
		'label' => $dropin_key,
		'value' => $dropin_description[ $dropin_key ][0]
	);
}

// Populate the server debug fields.
$info['wp-server']['fields'][] = array(
	'label' => __( 'Server architecture', 'health-check' ),
	'value' => ( ! function_exists( 'php_uname' ) ? __( 'Unable to determine server architecture', 'health-check' ) : sprintf( '%s %s %s', php_uname( 's' ), php_uname( 'r' ), php_uname( 'm' ) ) )
);
$info['wp-server']['fields'][] = array(
	'label' => __( 'PHP Version', 'health-check' ),
	'value' => ( ! function_exists( 'phpversion' ) ? __( 'Unable to determine PHP version', 'health-check' ) : sprintf( '%s (%s bit mode)', phpversion() , PHP_INT_SIZE * 8 ) )
);
$info['wp-server']['fields'][] = array(
	'label' => __( 'PHP SAPI', 'health-check' ),
	'value' => ( ! function_exists( 'php_sapi_name' ) ? __( 'Unable to determine PHP SAPI', 'health-check' ) : php_sapi_name() )
);

if ( ! function_exists( 'ini_get' ) ) {
	$info['wp-server']['fields'][] = array(
		'label' => __( 'Server settings', 'health-check' ),
		'value' => __( 'Unable to determine some settings as the ini_get() function has been disabled', 'health-check' )
	);
} else {
	$info['wp-server']['fields'][] = array(
		'label' => __( 'PHP max input variables', 'health-check' ),
		'value' => ini_get( 'max_input_vars' )
	);
	$info['wp-server']['fields'][] = array(
		'label' => __( 'PHP time limit', 'health-check' ),
		'value' => ini_get( 'max_execution_time' )
	);
	$info['wp-server']['fields'][] = array(
		'label' => __( 'PHP memory limit', 'health-check' ),
		'value' => ini_get( 'memory_limit' )
	);
	$info['wp-server']['fields'][] = array(
		'label' => __( 'Upload max filesize', 'health-check' ),
		'value' => ini_get( 'upload_max_filesize' )
	);
	$info['wp-server']['fields'][] = array(
		'label' => __( 'PHP post max size', 'health-check' ),
		'value' => ini_get( 'post_max_size' )
	);
}

if ( function_exists( 'curl_version' ) ) {
	$cURL                          = curl_version();
	$info['wp-server']['fields'][] = array(
		'label' => __( 'cURL Version', 'health-check' ),
		'value' => sprintf( '%s %s', $cURL['version'], $cURL['ssl_version'] )
	);
} else {
	$info['wp-server']['fields'][] = array(
		'label' => __( 'cURL Version', 'health-check' ),
		'value' => __( 'Your server does not support cURL', 'health-check' )
	);
}

$info['wp-server']['fields'][] = array(
	'label' => __( 'SUHOSIN installed', 'health-check' ),
	'value' => ( ( extension_loaded( 'suhosin' ) || ( defined( 'SUHOSIN_PATCH' ) && constant( 'SUHOSIN_PATCH' ) ) ) ? __( 'Yes', 'health-check' ) : __( 'No', 'health-check' ) )
);

$info['wp-server']['fields'][] = array(
	'label' => __( 'Is the Imagick library available', 'health-check' ),
	'value' => ( extension_loaded( 'imagick' ) ? __( 'Yes', 'health-check' ) : __( 'No', 'health-check' ) )
);


// Populate the database debug fields.
if ( is_resource( $wpdb->dbh ) ) {
	// Old mysql extension.
	$extension = 'mysql';
} else if ( is_object( $wpdb->dbh ) ) {
	// mysqli or PDO.
	$extension = get_class( $wpdb->dbh );
} else {
	// Unknown sql extension.
	$extension = null;
}

if ( method_exists( $wpdb, 'db_version' ) ) {
	if ( $wpdb->use_mysqli ) {
		$server = mysqli_get_server_info( $wpdb->dbh );
	} else {
		$server = mysql_get_server_info( $wpdb->dbh );
	}
} else {
	$server = null;
}

if ( isset( $wpdb->use_mysqli ) && $wpdb->use_mysqli ) {
	$client_version = $wpdb->dbh->client_info;
} else {
	if ( preg_match( '|[0-9]{1,2}\.[0-9]{1,2}\.[0-9]{1,2}|', mysql_get_client_info(), $matches ) ) {
		$client_version = $matches[0];
	} else {
		$client_version = null;
	}
}

$info['wp-database']['fields'][] = array(
	'label' => __( 'Extension', 'health-check' ),
	'value' => $extension
);
$info['wp-database']['fields'][] = array(
	'label' => __( 'Server version', 'health-check' ),
	'value' => $server
);
$info['wp-database']['fields'][] = array(
	'label' => __( 'Client version', 'health-check' ),
	'value' => $client_version
);
$info['wp-database']['fields'][] = array(
	'label'   => __( 'Database user', 'health-check' ),
	'value'   => $wpdb->dbuser,
	'private' => true
);
$info['wp-database']['fields'][] = array(
	'label'   => __( 'Database host', 'health-check' ),
	'value'   => $wpdb->dbhost,
	'private' => true
);
$info['wp-database']['fields'][] = array(
	'label'   => __( 'Database table', 'health-check' ),
	'value'   => $wpdb->dbname,
	'private' => true
);
$info['wp-database']['fields'][] = array(
	'label' => __( 'Database prefix', 'health-check' ),
	'value' => $wpdb->prefix
);


// List must use plugins if there are any.
$mu_plugins = get_mu_plugins();

foreach ( $mu_plugins AS $plugin_path => $plugin ) {
	$info['wp-mu-plugins']['fields'][] = array(
		'label' => $plugin['Name'],
		// translators: %1$s: Plugin version number. %2$s: Plugin author name.
		'value' => sprintf( __( 'version %1$s by %2$s', 'health-check' ), $plugin['Version'], $plugin['Author'] )
	);
}


// List all available plugins.
$plugins = get_plugins();

foreach ( $plugins AS $plugin_path => $plugin ) {
	$plugin_part = ( is_plugin_active( $plugin_path ) ) ? 'wp-plugins-active' : 'wp-plugins-inactive';

	$info[ $plugin_part ]['fields'][] = array(
		'label' => $plugin['Name'],
		// translators: %1$s: Plugin version number. %2$s: Plugin author name.
		'value' => sprintf( __( 'version %1$s by %2$s', 'health-check' ), $plugin['Version'], $plugin['Author'] )
	);
}


// Populate the section for the currently active theme.
global $_wp_theme_features;
$theme_features = array();
foreach ( $_wp_theme_features AS $feature => $options ) {
	$theme_features[] = $feature;
}

$active_theme                      = wp_get_theme();
$info['wp-active-theme']['fields'] = array(
	array(
		'label' => __( 'Name', 'health-check' ),
		'value' => $active_theme->Name
	),
	array(
		'label' => __( 'Version', 'health-check' ),
		'value' => $active_theme->Version
	),
	array(
		'label' => __( 'Author', 'health-check' ),
		'value' => wp_kses( $active_theme->Author, array() )
	),
	array(
		'label' => __( 'Author website', 'health-check' ),
		'value' => ( $active_theme->offsetGet( 'Author URI' ) ? $active_theme->offsetGet( 'Author URI' ) : __( 'Undefined', 'health-check' ) )
	),
	array(
		'label' => __( 'Parent theme', 'health-check' ),
		'value' => ( $active_theme->parent_theme ? $active_theme->parent_theme : __( 'Not a child theme', 'health-check' ) )
	),
	array(
		'label' => __( 'Supported theme features', 'health-check' ),
		'value' => implode( ', ', $theme_features )
	)
);

// Populate a list of all themes available in the install.
$all_themes = wp_get_themes();

foreach ( $all_themes AS $theme_slug => $theme ) {
	// Ignore the currently active theme from the list of all themes.
	if ( $active_theme->stylesheet == $theme_slug ) {
		continue;
	}

	$info['wp-themes']['fields'][] = array(
		// translators: %1$s: Theme name. %2$s: Theme slug.
		'label' => sprintf( __( '%1$s (%2$s)', 'health-check' ), $theme->Name, $theme_slug ),
		// translators: %1$s Theme version number. %2$s: Theme author name.
		'value' => sprintf( __( 'version %1$s by %2$s', 'health-check' ), $theme->Version, wp_kses( $theme->Author, array() ) )
	);
}


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
?>


	<div class="health-check-notice notice-info">
		<p>
			<?php esc_html_e( 'The system information shown below can also be copied and pasted into support requests such as on the WordPress.org forums, or to your theme and plugin developers.', 'health-check' ); ?>
		</p>
		<p>
			<button type="button" class="button button-primary" onclick="document.getElementById('system-information-copy-wrapper').style.display = 'block'; this.style.display = 'none';"><?php esc_html_e( 'Show copy and paste field', 'health-check' ); ?></button>
		</p>

		<div id="system-information-copy-wrapper" style="display: none;">
			<textarea id="system-information-copy-field" class="widefat" rows="10">`
<?php
				foreach ( $info AS $section => $details ) {
					// Skip this section if there are no fields, or the section has been declared as private.
					if ( empty( $details['fields'] ) || ( isset( $details['private'] ) && $details['private'] ) ) {
						continue;
					}

					printf(
						"### %s%s ###\n\n",
						$details['label'],
						( isset( $details['show_count'] ) && $details['show_count'] ? sprintf( ' (%d)', count( $details['fields'] ) ) : '' )
					);

					foreach ( $details['fields'] AS $field ) {
						if ( isset( $field['private'] ) && true === $field['private'] ) {
							continue;
						}

						printf(
							"%s: %s\n",
							$field['label'],
							$field['value']
						);
					}
					echo "\n";
				}
				?>
`</textarea>
			<p>
				<?php esc_html_e( 'Some information may be filtered out from the list you are about to copy, this is information that may be considers private, and is not meant to be shared in a public forum.', 'health-check' ); ?>
				<br>
				<button type="button" class="button button-primary" onclick="document.getElementById('system-information-copy-field').select();"><?php esc_html_e( 'Mark field for copying', 'health-check' ); ?></button>
			</p>
		</div>
	</div>

	<div id="system-information-table-of-contents">
		<?php
		$toc = array();

		foreach ( $info AS $section => $details ) {
			if ( empty( $details['fields'] ) ) {
				continue;
			}

			$toc[] = sprintf(
				'<a href="#%s">%s</a>',
				esc_attr( $section ),
				esc_html( $details['label'] )
			);
		}

		echo implode( ' | ', $toc );
		?>
	</div>

<?php
foreach ( $info AS $section => $details ) {
	if ( ! isset( $details['fields'] ) || empty( $details['fields'] ) ) {
		continue;
	}

	printf(
		'<h2 id="%s">%s%s</h2>',
		esc_attr( $section ),
		esc_html( $details['label'] ),
		( isset( $details['show_count'] ) && $details['show_count'] ? sprintf( ' (%d)', count( $details['fields'] ) ) : '' )
	);

	if ( isset( $details['description'] ) && ! empty( $details['description'] ) ) {
		printf(
			'<p>%s</p>',
			wp_kses( $details['description'], array(
				'a'      => array(
					'href' => true
				),
				'strong' => true,
				'em'     => true,
			) )
		);
	}
	?>
	<table class="widefat striped health-check-table">
		<tbody>
		<?php
		foreach ( $details['fields'] AS $field ) {
			printf(
				'<tr><td>%s</td><td>%s</td></tr>',
				esc_html( $field['label'] ),
				esc_html( $field['value'] )
			);
		}
		?>
		</tbody>
	</table>
	<span style="display: block; width: 100%; text-align: <?php echo ( is_rtl() ? 'left' : 'right' ); ?>">
		<a href="#system-information-table-of-contents"><?php esc_html_e( 'Return to table of contents', 'health-check' ); ?></a>
	</span>
	<?php
}
