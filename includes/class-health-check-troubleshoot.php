<?php
/**
 * Handle troubleshooting options.
 *
 * @package Health Check
 */

/**
 * Class Health_Check_Troubleshoot
 */
class Health_Check_Troubleshoot {

	/**
	 * Conditionally show a form for providing filesystem credentials when introducing our troubleshooting mode plugin.
	 *
	 * @uses wp_nonce_url()
	 * @uses add_query_arg()
	 * @uses admin_url()
	 * @uses request_filesystem_credentials()
	 * @uses WP_Filesystem
	 *
	 * @return bool
	 */
	static function get_filesystem_credentials() {
		$url   = wp_nonce_url( add_query_arg(
			array(
				'page' => 'health-check',
				'tab'  => 'troubleshoot',
			),
		admin_url() ) );
		$creds = request_filesystem_credentials( $url, '', false, WP_CONTENT_DIR, array( 'health-check-troubleshoot-mode', 'action' ) );
		if ( false === $creds ) {
			return false;
		}

		if ( ! WP_Filesystem( $creds ) ) {
			request_filesystem_credentials( $url, '', true, WPMU_PLUGIN_DIR, array( 'health-check-troubleshoot-mode', 'action' ) );
			return false;
		}

		return true;
	}

	/**
	 * Check if our Must-Use plugin exists.
	 *
	 * @uses file_exists()
	 *
	 * @return bool
	 */
	static function mu_plugin_exists() {
		return file_exists( WPMU_PLUGIN_DIR . '/health-check-disable-plugins.php' );
	}

	/**
	 * Check if the user has been shown the backup warning.
	 *
	 * @uses get_user_meta()
	 * @uses get_current_user_id()
	 *
	 * @return bool
	 */
	static function has_seen_warning() {
		$meta = get_user_meta( get_current_user_id(), 'health-check', true );
		if ( empty( $meta ) ) {
			return false;
		}

		if ( 'seen' === $meta['warning']['backup'] ) {
			return true;
		}

		return false;
	}

	/**
	 * Save the confirmation of having seen a warning.
	 *
	 * @uses get_user_meta()
	 * @uses get_current_user_id()
	 * @uses update_user_meta()
	 *
	 * @return void
	 */
	static function confirm_warning() {
		$user_meta = get_user_meta( get_current_user_id(), 'health-check', true );
		if ( empty( $user_meta ) ) {
			$user_meta = array(
				'warning'
			);
		}

		$user_meta['warning'][ $_POST['warning'] ] = 'seen';

		update_user_meta( get_current_user_id(), 'health-check', $user_meta );
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
	 * @uses HealthCheck::display_notice()
	 * @uses esc_html__()
	 * @uses WP_Filesystem::copy()
	 * @uses trailingslashit()
	 * @uses Health_Check_Troubleshoot::session_started()
	 *
	 * @return bool
	 */
	static function setup_must_use_plugin( $redirect = true ) {
		global $wp_filesystem;

		if ( ! is_dir( WPMU_PLUGIN_DIR ) ) {
			if ( ! $wp_filesystem->mkdir( WPMU_PLUGIN_DIR ) ) {
				HealthCheck::display_notice( esc_html__( 'We were unable to create the mu-plugins directory.', 'health-check' ), 'error' );
				return false;
			}
		}

		if ( ! $wp_filesystem->copy( trailingslashit( HEALTH_CHECK_PLUGIN_DIRECTORY ) . 'assets/mu-plugin/health-check-disable-plugins.php', trailingslashit( WPMU_PLUGIN_DIR ) . 'health-check-disable-plugins.php' ) ) {
			HealthCheck::display_notice( esc_html__( 'We were unable to copy the plugin file required to enable the Troubleshooting Mode.', 'health-check' ), 'error' );
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
	 * @uses Health_Check_Troubleshoot::get_filesystem_credentials()
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
		if ( ! Health_Check_Troubleshoot::get_filesystem_credentials() ) {
			return false;
		}

		$current = get_plugin_data( trailingslashit( HEALTH_CHECK_PLUGIN_DIRECTORY ) . 'assets/mu-plugin/health-check-disable-plugins.php' );
		$active  = get_plugin_data( trailingslashit( WPMU_PLUGIN_DIR ) . 'health-check-disable-plugins.php' );

		$current_version = ( isset( $current['Version'] ) ? $current['Version'] : '0.0' );
		$active_version  = ( isset( $active['Version'] ) ? $active['Version'] : '0.0' );

		if ( version_compare( $current_version, $active_version, '>' ) ) {
			global $wp_filesystem;

			if ( ! $wp_filesystem->copy( trailingslashit( HEALTH_CHECK_PLUGIN_DIRECTORY ) . 'assets/mu-plugin/health-check-disable-plugins.php', trailingslashit( WPMU_PLUGIN_DIR ) . 'health-check-disable-plugins.php', true ) ) {
				HealthCheck::display_notice( esc_html__( 'We were unable to replace the plugin file required to enable the Troubleshooting Mode.', 'health-check' ), 'error' );
				return false;
			}
		}

		return true;
	}

	/**
	 * Output a notice if our Troubleshooting Mode has been initiated.
	 *
	 * @uses HealthCheck::display_notice()
	 * @uses sprintf()
	 * @uses esc_html__()
	 * @uses esc_url()
	 * @uses admin_url()
	 *
	 * @return void
	 */
	static function session_started() {
		HealthCheck::display_notice(
			sprintf(
				'%s<br>%s',
				esc_html__( 'You have successfully enabled Troubleshooting Mode, all plugins will appear inactive until you log out and back in again.', 'health-check' ),
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
	 * @uses Health_Check_Troubleshoot::get_filesystem_credentials()
	 * @uses Health_Check_Troubleshoot::setup_must_use_plugin()
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
				if ( ! Health_Check_Troubleshoot::get_filesystem_credentials() ) {
					return;
				} else {
					Health_Check_Troubleshoot::setup_must_use_plugin();
				}
			}
		}

?>
		<div class="notice inline">
			<form action="" method="post" class="form" style="text-align: center;">
				<input type="hidden" name="health-check-troubleshoot-mode" value="true">

				<p>
					<button type="submit" class="button button-primary">
						<?php esc_html_e( 'Enable Troubleshooting Mode', 'health-check' ); ?>
					</button>
				</p>
			</form>
		</div>

<?php
	}
}
