<?php
/**
 * Handle troubleshooting options.
 *
 * @package Health Check
 */

// Make sure the file is not directly accessible.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'We\'re sorry, but you can not directly access this file.' );
}

/**
 * Class Health_Check_Troubleshoot
 */
class Health_Check_Troubleshoot {

	/**
	 * Initiate the troubleshooting mode by setting meta data and cookies.
	 *
	 * @uses is_array()
	 * @uses md5()
	 * @uses rand()
	 * @uses update_option()
	 * @uses setcookie()
	 *
	 * @param array $allowed_plugins An array of plugins that may be active right away.
	 *
	 * @return void
	 */
	static function initiate_troubleshooting_mode( $allowed_plugins = array() ) {
		if ( ! is_array( $allowed_plugins ) ) {
			$allowed_plugins = (array) $allowed_plugins;
		}

		$loopback_hash = md5( rand() );

		update_option( 'health-check-allowed-plugins', $allowed_plugins );

		update_option( 'health-check-disable-plugin-hash', $loopback_hash . md5( $_SERVER['REMOTE_ADDR'] ) );

		setcookie( 'wp-health-check-disable-plugins', $loopback_hash, 0, COOKIEPATH, COOKIE_DOMAIN );
	}

	/**
	 * Check if our Must-Use plugin exists.
	 *
	 * @uses file_exists()
	 *
	 * @return bool
	 */
	static function mu_plugin_exists() {
		return file_exists( WPMU_PLUGIN_DIR . '/health-check-troubleshooting-mode.php' );
	}

	/**
	 * Check if the old Must-Use plugin exists.
	 *
	 * @uses file_exists()
	 *
	 * @return bool
	 */
	static function old_mu_plugin_exists() {
		return file_exists( WPMU_PLUGIN_DIR . '/health-check-disable-plugins.php' );
	}

	/**
	 * Introduce our Must-Use plugin.
	 *
	 * Move the Must-Use plugin out to the correct directory, and prompt for credentials if required.
	 *
	 * @global $wp_filesystem
	 *
	 * @uses is_dir()
	 * @uses WP_Filesystem::mkdir()
	 * @uses Health_Check::display_notice()
	 * @uses esc_html__()
	 * @uses WP_Filesystem::copy()
	 * @uses trailingslashit()
	 * @uses Health_Check_Troubleshoot::session_started()
	 *
	 * @return bool
	 */
	static function setup_must_use_plugin( $redirect = true ) {
		global $wp_filesystem;

		// Make sure the `mu-plugins` directory exists.
		if ( ! is_dir( WPMU_PLUGIN_DIR ) ) {
			if ( ! $wp_filesystem->mkdir( WPMU_PLUGIN_DIR ) ) {
				Health_Check::display_notice( esc_html__( 'We were unable to create the mu-plugins directory.', 'health-check' ), 'error' );
				return false;
			}
		}

		// Remove instances of the old plugin, to avoid collisions.
		if ( Health_Check_Troubleshoot::old_mu_plugin_exists() ) {
			if ( ! $wp_filesystem->delete( trailingslashit( WPMU_PLUGIN_DIR ) . 'health-check-disable-plugins.php' ) ) {
				Health_Check::display_notice( esc_html__( 'We could not remove the old must-use plugin.', 'health-check' ), 'error' );
				return false;
			}
		}

		// Copy the must-use plugin to the local directory.
		if ( ! $wp_filesystem->copy( trailingslashit( HEALTH_CHECK_PLUGIN_DIRECTORY ) . 'mu-plugin/health-check-troubleshooting-mode.php', trailingslashit( WPMU_PLUGIN_DIR ) . 'health-check-troubleshooting-mode.php' ) ) {
			Health_Check::display_notice( esc_html__( 'We were unable to copy the plugin file required to enable the Troubleshooting Mode.', 'health-check' ), 'error' );
			return false;
		}

		if ( $redirect ) {
			Health_Check_Troubleshoot::session_started();
		}

		return true;
	}

	/**
	 * Check if our Must-Use plugin needs updating, and do so if necessary.
	 *
	 * @global $wp_filesystem
	 *
	 * @uses Health_Check_Troubleshoot::mu_plugin_exists()
	 * @uses Health_Check::get_filesystem_credentials()
	 * @uses get_plugin_data()
	 * @uses trailingslashit()
	 * @uses version_compare()
	 * @uses WP_Filesystem::copy()
	 * @uses esc_html__()
	 *
	 * @return bool
	 */
	static function maybe_update_must_use_plugin() {
		if ( ! Health_Check_Troubleshoot::mu_plugin_exists() ) {
			return false;
		}
		if ( ! Health_Check::get_filesystem_credentials() ) {
			return false;
		}

		$current = get_plugin_data( trailingslashit( HEALTH_CHECK_PLUGIN_DIRECTORY ) . 'mu-plugin/health-check-troubleshooting-mode.php' );
		$active  = get_plugin_data( trailingslashit( WPMU_PLUGIN_DIR ) . 'health-check-troubleshooting-mode.php' );

		$current_version = ( isset( $current['Version'] ) ? $current['Version'] : '0.0' );
		$active_version  = ( isset( $active['Version'] ) ? $active['Version'] : '0.0' );

		if ( version_compare( $current_version, $active_version, '>' ) ) {
			global $wp_filesystem;

			if ( ! $wp_filesystem->copy( trailingslashit( HEALTH_CHECK_PLUGIN_DIRECTORY ) . 'mu-plugin/health-check-troubleshooting-mode.php', trailingslashit( WPMU_PLUGIN_DIR ) . 'health-check-troubleshooting-mode.php', true ) ) {
				Health_Check::display_notice( esc_html__( 'We were unable to replace the plugin file required to enable the Troubleshooting Mode.', 'health-check' ), 'error' );
				return false;
			}
		}

		return true;
	}

	/**
	 * Output a notice if our Troubleshooting Mode has been initiated.
	 *
	 * @uses Health_Check::display_notice()
	 * @uses sprintf()
	 * @uses esc_html__()
	 * @uses esc_url()
	 * @uses admin_url()
	 *
	 * @return void
	 */
	static function session_started() {
		Health_Check::display_notice(
			sprintf(
				'%s<br>%s',
				esc_html__( 'You have successfully enabled Troubleshooting Mode, all plugins will appear inactive until you disable Troubleshooting Mode, or log out and back in again.', 'health-check' ),
				sprintf(
					'<a href="%1$s">%2$s</a><script type="text/javascript">window.location = "%1$s";</script>',
					esc_url( admin_url( '/' ) ),
					esc_html__( 'Return to the Dashboard', 'health-check' )
				)
			)
		);
	}

	/**
	 * Display the form for enabling troubleshooting mode.
	 *
	 * @uses printf()
	 * @uses esc_html__()
	 * @uses Health_Check_Troubleshoot::mu_plugin_exists()
	 * @uses Health_Check_Troubleshoot::maybe_update_must_use_plugin()
	 * @uses Health_Check_Troubleshoot::session_started()
	 * @uses Health_Check::get_filesystem_credentials()
	 * @uses Health_Check_Troubleshoot::setup_must_use_plugin()
	 * @uses esc_html_e()
	 *
	 * @return void
	 */

	/**
	 * Display the form for enabling troubleshooting mode.
	 *
	 * @uses printf()
	 * @uses esc_html__()
	 * @uses Health_Check_Troubleshoot::mu_plugin_exists()
	 * @uses Health_Check_Troubleshoot::maybe_update_must_use_plugin()
	 * @uses Health_Check_Troubleshoot::session_started()
	 * @uses Health_Check::get_filesystem_credentials()
	 * @uses Health_Check_Troubleshoot::setup_must_use_plugin()
	 * @uses Health_Check_Troubleshooting_MU::is_troubleshooting()
	 * @uses esc_url()
	 * @uses add_query_arg()
	 * @uses esc_html_e()
	 *
	 * @return void
	 */
	static function show_enable_troubleshoot_form() {
		if ( isset( $_POST['health-check-troubleshoot-mode'] ) ) {
			if ( Health_Check_Troubleshoot::mu_plugin_exists() ) {
				if ( ! Health_Check_Troubleshoot::maybe_update_must_use_plugin() ) {
					return;
				}
				Health_Check_Troubleshoot::session_started();
			} else {
				if ( ! Health_Check::get_filesystem_credentials() ) {
					return;
				} else {
					Health_Check_Troubleshoot::setup_must_use_plugin();
				}
			}
		}

		?>
		<div>

		<?php
		$troubleshooting = null;

		if ( class_exists( 'Health_Check_Troubleshooting_MU' ) ) {
			$troubleshooting = new Health_Check_Troubleshooting_MU();
		}

		if ( null !== $troubleshooting && is_callable( array( $troubleshooting, 'is_troubleshooting' ) ) && $troubleshooting->is_troubleshooting() ) :
			?>
			<p style="text-align: center;">
				<a class="button button-primary" href="<?php echo esc_url( add_query_arg( array( 'health-check-disable-troubleshooting' => true ) ) ); ?>">
					<?php esc_html_e( 'Disable Troubleshooting Mode', 'health-check' ); ?>
				</a>
			</p>

		<?php else : ?>

			<form action="" method="post" class="form" style="text-align: center;">
				<?php wp_nonce_field( 'health-check-enable-troubleshooting' ); ?>
				<input type="hidden" name="health-check-troubleshoot-mode" value="true">
				<p>
					<button type="submit" class="button button-primary">
						<?php esc_html_e( 'Enable Troubleshooting Mode', 'health-check' ); ?>
					</button>
				</p>
			</form>

		<?php endif; ?>

		</div>

		<?php
	}
}
