<?php

class Health_Check_Troubleshoot {

	static function get_filesystem_credentials() {
		$url = wp_nonce_url( add_query_arg( array( 'page' => 'health-check', 'tab' => 'troubleshoot' ), admin_url() ) );
		$creds = request_filesystem_credentials( $url, '', false, WP_CONTENT_DIR, array( 'health-check-troubleshoot-mode-confirmed', 'health-check-troubleshoot-mode', 'action' ) );
		if ( false === $creds ) {
			return false;
		}

		if ( ! WP_Filesystem( $creds ) ) {
			request_filesystem_credentials( $url, '', true, WPMU_PLUGIN_DIR, array( 'health-check-troubleshoot-mode-confirmed', 'health-check-troubleshoot-mode', 'action' ) );
			return false;
		}

		return true;
	}

	static function mu_plugin_exists() {
		return file_exists( WPMU_PLUGIN_DIR . '/health-check-disable-plugins.php' );
	}

	static function setup_must_use_plugin() {
		global $wp_filesystem;

		if ( ! is_dir( WPMU_PLUGIN_DIR ) ) {
			if ( ! $wp_filesystem->mkdir( WPMU_PLUGIN_DIR ) ) {
				HealthCheck::display_notice( esc_html__( 'We were unable to create the mu-plugins directory.', 'health-check' ), 'error' );
				return false;
			}
		}

		if ( ! $wp_filesystem->copy( trailingslashit( HEALTH_CHECK_PLUGIN_DIRECTORY ) . 'assets/mu-plugin/health-check-disable-plugins.php', trailingslashit( WPMU_PLUGIN_DIR ) . 'health-check-disable-plugins.php' ) ) {
			HealthCheck::display_notice( esc_html__( 'We were unable to copy the plugin file required to run in troubleshooting mode.' ,'health-check' ), 'error' );
			return false;
		}

		Health_Check_Troubleshoot::session_started();

		return true;
	}

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
				HealthCheck::display_notice( esc_html__( 'We were unable to replace the plugin file required to run in troubleshooting mode.' ,'health-check' ), 'error' );
				return false;
			}
		}

		return true;
	}

	static function session_started() {
		HealthCheck::display_notice(
			sprintf(
				'%s<br>%s',
				esc_html__( 'You have successfully started troubleshooting mode, all plugins will appear inactive until you log out and back in again.', 'health-check' ),
				sprintf(
					'<a href="%1$s">%2$s</a><script type="text/javascript">window.location = "%1$s";</script>',
					esc_url( admin_url( '/' ) ),
					esc_html__( 'Return to the Dashboard' )
				)
			)
		);
	}

	static function show_enable_troubleshoot_form() {
		if ( isset( $_POST['health-check-troubleshoot-mode'] ) ) {
			if ( ! isset( $_POST['health-check-troubleshoot-mode-confirmed'] ) ) {
				printf(
					'<div class="notice notice-error inline"><p>%s</p></div>',
					esc_html__( 'You did not check that you understand how to leave troubleshooter mode, please read the explanation and confirm that you understand the procedure first.', 'health-check' )
				);
			}
			else {
				if ( Health_Check_Troubleshoot::mu_plugin_exists() ) {
					if ( ! Health_Check_Troubleshoot::maybe_update_must_use_plugin() ) {
						return;
					}
					Health_Check_Troubleshoot::session_started();
				}
				else {
					if ( ! Health_Check_Troubleshoot::get_filesystem_credentials() ) {
						return;
					} else {
						Health_Check_Troubleshoot::setup_must_use_plugin();
					}
				}
			}
		}

?>
		<div class="notice inline">
			<form action="" method="post" class="form" style="text-align: center;">
				<input type="hidden" name="health-check-troubleshoot-mode" value="true">

				<p>
					<label>
						<input type="checkbox" name="health-check-troubleshoot-mode-confirmed">
						<?php esc_html_e( 'I understand that troubleshooter mode is active until the next time I log out', 'health-check' ); ?>
					</label>

				</p>

				<p>
					<button type="submit" class="button button-primary">
						<?php esc_html_e( 'Enter troubleshooting mode', 'health-check' ); ?>
					</button>
				</p>
			</form>
		</div>

<?php
	}
}
