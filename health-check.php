<?php
/*
	Plugin Name: Health Check
	Plugin URI: http://wordpress.org/extend/plugins/health-check/
	Description: Checks the health of your WordPress install.
	Author: The WordPress.org community
	Version: 0.3
	Author URI: http://wordpress.org/extend/plugins/health-check/
 */
define( 'HEALTH_CHECK_PHP_MIN_VERSION', '5.2.4' );
define( 'HEALTH_CHECK_PHP_REC_VERSION', '5.6' );
define( 'HEALTH_CHECK_MYSQL_MIN_VERSION', '5.0' );
define( 'HEALTH_CHECK_MYSQL_REC_VERSION', '5.5' );

class HealthCheck {

	static function action_plugins_loaded() {
		add_action( 'admin_menu', array( 'HealthCheck', 'action_admin_menu' ) );
		add_filter( 'plugin_row_meta', array( 'HealthCheck', 'settings_link' ), 10, 2 );
	}

	static function action_admin_menu() {
		add_dashboard_page( __( 'Heath Check', 'health-check' ), __( 'Heath Check', 'health-check' ), 'manage_options', 'health-check', array( 'HealthCheck', 'dashboard_page' ) );
	}

	static function settings_link( $meta, $name ) {
		if ( plugin_basename( __FILE__ ) === $name ) {
			$meta[] = sprintf( '<a href="%s">' . __( 'Health Check', 'health-check' ) . '</a>', menu_page_url( 'health-check', false ) );
		}

		return $meta;
	}

	static function dashboard_page() {
		?>
		<div class="wrap"><?php screen_icon(); ?>
			<h2><?php _e( 'Heath Check', 'health-check' ); ?></h2>
			<?php echo HealthCheck::get_message(); ?>
		</div>
		<?php
	}

	static function get_message() {
		global $wpdb;

		$all_pass = true;

		$php_min_version_check = version_compare( HEALTH_CHECK_PHP_MIN_VERSION, PHP_VERSION, '<=' );
		$php_rec_version_check = version_compare( HEALTH_CHECK_PHP_REC_VERSION, PHP_VERSION, '<=' );

		$mysql_min_version_check = version_compare( HEALTH_CHECK_MYSQL_MIN_VERSION, $wpdb->db_version(), '<=' );
		$mysql_rec_version_check = version_compare( HEALTH_CHECK_MYSQL_REC_VERSION, $wpdb->db_version(), '<=' );

		$json_check = HealthCheck::json_check();
		$db_dropin = file_exists( WP_CONTENT_DIR . '/db.php' );

		$message = '<p>' . sprintf( __( 'Your server is running PHP version <strong>%1$s</strong> and MySQL version <strong>%2$s</strong>.', 'health-check' ), PHP_VERSION, $wpdb->db_version() ) . '</p>';
		$success = '';
		$warning = '';
		$error = '';

		if ( ! $php_min_version_check ) {
			$error .= "<p><strong>" . __( 'Error:', 'health-check' ) . "</strong> " . sprintf( __( 'WordPress 3.2+ requires PHP version %s.', 'health-check' ), HEALTH_CHECK_PHP_MIN_VERSION ) . "</p>";
			$all_pass = false;
		}

		if ( ! $mysql_min_version_check ) {
			$error .= "<p><strong>" . __( 'Error:', 'health-check' ) . "</strong> " . sprintf( __( 'WordPress 3.2+ requires MySQL version %s', 'health-check' ), HEALTH_CHECK_MYSQL_MIN_VERSION ) . "</p>";
			$all_pass = false;

			if ( $db_dropin ) {
				$error .= "<p><strong>" . __( 'Note:', 'health-check' ) . "</strong> " . __( 'You are using a <code>wp-content/db.php</code> drop-in which may not being using a MySQL database.', 'health-check' ) . "</p>";
			}
		}

		if ( ! $json_check ) {
			$error .= "<p><strong>".__('Note:', 'health-check')."</strong> ".__('The PHP install on your server has the JSON extension disabled and is therefore not compatible with WordPress 3.2.', 'health-check')."</p>";
			$all_pass = false;
		}

		if ( version_compare( $wpdb->db_version(), '5.5.3', '<' ) ) {
			$warning .= "<p><strong>" . __( 'Error:', 'health-check' ) . "</strong> " . sprintf( __( "WordPress' utf8mb4 support requires MySQL version %s", 'health-check' ), $wpdb->db_version(), '5.5.3' ) . "</p>";
			$all_pass = false;
		}

		if ( $wpdb->use_mysqli ) {
			$mysql_client_version = mysqli_get_client_info();
		} else {
			$mysql_client_version = mysql_get_client_info();
		}

		/*
		 * libmysql has supported utf8mb4 since 5.5.3, same as the MySQL server.
		 * mysqlnd has supported utf8mb4 since 5.0.9.
		 */
		if ( false !== strpos( $mysql_client_version, 'mysqlnd' ) ) {
			$mysql_client_version = preg_replace( '/^\D+([\d.]+).*/', '$1', $mysql_client_version );
			if ( version_compare( $mysql_client_version, '5.0.9', '<' ) ) {
				$warning .= "<p><strong>" . __( 'Warning:', 'health-check' ) . "</strong> " . sprintf( __( 'WordPress\' utf8mb4 support requires MySQL client library (%1$s) version %2$s.', 'health-check' ), 'mysqlnd', '5.0.9' ) . "</p>";
				$all_pass = false;
			}
		} else {
			if ( version_compare( $mysql_client_version, '5.5.3', '<' ) ) {
				$warning .= "<p><strong>" . __( 'Warning:', 'health-check' ) . "</strong> " . sprintf( __( 'WordPress\' utf8mb4 support requires MySQL client library (%1$s) version %2$s.', 'health-check' ), 'libmysql', '5.5.3' ) . "</p>";
				$all_pass = false;
			}
		}

		if ( ! $php_rec_version_check ) {
			$warning .= "<p><strong>" . __( 'Warning:', 'health-check' ) . "</strong> " . sprintf( __( 'For performance and security reasons, we strongly recommend running PHP version %s or higher.', 'health-check' ), HEALTH_CHECK_PHP_REC_VERSION ) . "</p>";
			$all_pass = false;
		}

		if ( ! $mysql_rec_version_check ) {
			$error .= "<p><strong>" . __( 'Error:', 'health-check' ) . "</strong> " . sprintf( __( 'For performance and security reasons, we strongly recommend running MySQL version %s or higher.', 'health-check' ), HEALTH_CHECK_MYSQL_REC_VERSION ) . "</p>";
			$all_pass = false;

			if ( $db_dropin ) {
				$error .= "<p><strong>" . __( 'Note:', 'health-check' ) . "</strong> " . __( 'You are using a <code>wp-content/db.php</code> drop-in which may not being using a MySQL database.', 'health-check' ) . "</p>";
			}
		}

		if ( $all_pass ) {
			$success = "<p><strong>" . __( 'Excellent:', 'health-check' ) . "</strong> " . __( 'Everything is up to date!', 'health-check' ) . "</p>";
		}

		if ( $error ) {
			$message .= "<div id='health-check-warning' class='notice notice-error'>" . $error . "</div>";
		}

		if ( $warning ) {
			$message .= "<div id='health-check-warning' class='notice notice-warning'>" . $warning . "</div>";
		}

		if ( $success ) {
			$message .= "<div id='health-check-warning' class='notice notice-success'>" . $success . "</div>";
		}

		if ( $error || $warning ) {
			$message .= "<p>". sprintf( __('Once your host has upgraded your server you can visit <a href="%s">Dashboard > Health Check</a> to check your compatibility again.', 'health-check' ), menu_page_url( 'health-check', false ) ) ."</p>";
		}

		return $message;
	}

	static function json_check() {
		$extension_loaded = extension_loaded( 'json' );
		$functions_exist = function_exists( 'json_encode' ) && function_exists( 'json_decode' );
		$functions_work = function_exists( 'json_encode' ) && ( '' != json_encode( 'my test string' ) );

		return $extension_loaded && $functions_exist && $functions_work;
	}
}
/* Initialize ourselves */
add_action('plugins_loaded', array('HealthCheck','action_plugins_loaded'));
?>
