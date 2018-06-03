<?php
/**
 * Tests to determine if the WordPress loopbacks are able to run unhindered.
 *
 * @package Health Check
 */

/**
 * Class Health_Check_Loopback
 */
class Health_Check_Loopback {
	/**
	 * Run a loopback test on our site.
	 *
	 * @uses wp_unslash()
	 * @uses base64_encode()
	 * @uses admin_url()
	 * @uses add_query_arg()
	 * @uses is_array()
	 * @uses implode()
	 * @uses wp_remote_get()
	 * @uses compact()
	 * @uses is_wp_error()
	 * @uses wp_remote_retrieve_response_code()
	 * @uses sprintf()
	 *
	 * @param null|string       $disable_plugin_hash Optional. A hash to send with our request to disable any plugins.
	 * @param null|string|array $allowed_plugins     Optional. A string or array of approved plugin slugs that can run even when we globally ignore plugins.
	 *
	 * @return object
	 */
	static function can_perform_loopback( $disable_plugin_hash = null, $allowed_plugins = null ) {
		$cookies = wp_unslash( $_COOKIE );
		$timeout = 10;
		$headers = array(
			'Cache-Control' => 'no-cache',
		);

		// Include Basic auth in loopback requests.
		if ( isset( $_SERVER['PHP_AUTH_USER'] ) && isset( $_SERVER['PHP_AUTH_PW'] ) ) {
			$headers['Authorization'] = 'Basic ' . base64_encode( wp_unslash( $_SERVER['PHP_AUTH_USER'] ) . ':' . wp_unslash( $_SERVER['PHP_AUTH_PW'] ) );
		}

		$url = admin_url();

		if ( ! empty( $disable_plugin_hash ) ) {
			$url = add_query_arg( array(
				'health-check-disable-plugin-hash' => $disable_plugin_hash,
			), $url );
		}
		if ( ! empty( $allowed_plugins ) ) {
			if ( ! is_array( $allowed_plugins ) ) {
				$allowed_plugins = (array) $allowed_plugins;
			}

			$url = add_query_arg(
				array(
					'health-check-allowed-plugins' => implode( ',', $allowed_plugins ),
				),
				$url
			);
		}

		$r = wp_remote_get( $url, compact( 'cookies', 'headers', 'timeout' ) );

		if ( is_wp_error( $r ) ) {
			return (object) array(
				'status'  => 'error',
				'message' => sprintf(
					'%s<br>%s',
					esc_html__( 'The loopback request to your site failed, this may prevent WP_Cron from working, along with theme and plugin editors.', 'health-check' ),
					sprintf(
						/* translators: %1$d: The HTTP response code. %2$s: The error message returned. */
						esc_html__( 'Error encountered: (%1$d) %2$s', 'health-check' ),
						wp_remote_retrieve_response_code( $r ),
						$r->get_error_message()
					)
				),
			);
		}

		if ( 200 !== wp_remote_retrieve_response_code( $r ) ) {
			return (object) array(
				'status'  => 'warning',
				'message' => sprintf(
					/* translators: %d: The HTTP response code returned. */
					esc_html__( 'The loopback request returned an unexpected status code, %d, this may affect tools such as WP_Cron, or theme and plugin editors.', 'health-check' ),
					wp_remote_retrieve_response_code( $r )
				),
			);
		}

		return (object) array(
			'status'  => 'good',
			'message' => __( 'The loopback request to your site completed successfully.', 'health-check' ),
		);
	}

	/**
	 * Perform the loopback check, but ensure no plugins are enabled when we do so.
	 *
	 * @uses ob_start()
	 * @uses Health_Check_Troubleshoot::mu_plugin_exists()
	 * @uses HealthCheck::get_filesystem_credentials()
	 * @uses Health_Check_Troubleshoot::setup_must_use_plugin()
	 * @uses Health_Check_Troubleshoot::maybe_update_must_use_plugin()
	 * @uses ob_get_clean()
	 * @uses wp_send_json_error()
	 * @uses md5()
	 * @uses rand()
	 * @uses update_option()
	 * @uses Health_Check_Loopback::can_perform_loopback()
	 * @uses sprintf()
	 * @uses esc_attr()
	 * @uses esc_html__()
	 * @uses esc_html()
	 * @uses wp_send_json_success()
	 *
	 * @return void
	 */
	static function loopback_no_plugins() {
		ob_start();

		$needs_creds = false;

		if ( ! Health_Check_Troubleshoot::mu_plugin_exists() ) {
			if ( ! HealthCheck::get_filesystem_credentials() ) {
				$needs_creds = true;
			} else {
				$check_output = Health_Check_Troubleshoot::setup_must_use_plugin();
				if ( false === $check_output ) {
					$needs_creds = true;
				}
			}
		} else {
			if ( ! Health_Check_Troubleshoot::maybe_update_must_use_plugin() ) {
				$needs_creds = true;
			}
		}

		$result = ob_get_clean();

		if ( $needs_creds ) {
			wp_send_json_error( $result );
			die();
		}

		$loopback_hash = md5( rand() );
		update_option( 'health-check-disable-plugin-hash', $loopback_hash );
		update_option( 'health-check-default-theme', 'yes' );

		$no_plugin_test = Health_Check_Loopback::can_perform_loopback( $loopback_hash );

		$message = sprintf(
			'<br><span class="%s"></span> %s: %s',
			esc_attr( $no_plugin_test->status ),
			esc_html__( 'Result from testing without any plugins active and a default theme', 'health-check' ),
			$no_plugin_test->message
		);

		if ( 'error' !== $no_plugin_test->status ) {
			$message .= '<br><button type="button" id="loopback-individual-plugins" class="button button-primary">Test individual plugins</button>';
		}

		$response = array(
			'message' => $message,
		);

		delete_option( 'health-check-default-theme' );

		wp_send_json_success( $response );

		die();
	}

	/**
	 * Test individual plugins for loopback compatibility issues.
	 *
	 * This function will perform the loopback check, without any plugins, then conditionally enables one plugin at a time.
	 *
	 * @uses ob_start()
	 * @uses Health_Check_Troubleshoot::mu_plugin_exists()
	 * @uses HealthCheck::get_filesystem_credentials()
	 * @uses Health_Check_Troubleshoot::setup_must_use_plugin()
	 * @uses ob_get_clean()
	 * @uses wp_send_json_error()
	 * @uses delete_option()
	 * @uses get_option()
	 * @uses md5()
	 * @uses rand()
	 * @uses update_option()
	 * @uses explode()
	 * @uses Health_Check_Loopback::can_perform_loopback()
	 * @uses sprintf()
	 * @uses esc_attr()
	 * @uses esc_html__()
	 * @uses esc_html()
	 * @uses wp_send_json_success()
	 *
	 * @return void
	 */
	static function loopback_test_individual_plugins() {
		ob_start();

		$needs_creds = false;

		if ( ! Health_Check_Troubleshoot::mu_plugin_exists() ) {
			if ( ! HealthCheck::get_filesystem_credentials() ) {
				$needs_creds = true;
			} else {
				Health_Check_Troubleshoot::setup_must_use_plugin();
			}
		}

		$result = ob_get_clean();

		if ( $needs_creds ) {
			wp_send_json_error( $result );
			die();
		}

		delete_option( 'health-check-disable-plugin-hash' );

		$all_plugins = get_option( 'active_plugins' );

		$loopback_hash = md5( rand() );
		update_option( 'health-check-disable-plugin-hash', $loopback_hash );

		$message = '';

		foreach ( $all_plugins as $single_plugin ) {
			$plugin_slug = explode( '/', $single_plugin );
			$plugin_slug = $plugin_slug[0];

			$single_test = Health_Check_Loopback::can_perform_loopback( $loopback_hash, $plugin_slug );

			$message .= sprintf(
				'<br><span class="%s"></span> %s: %s',
				esc_attr( $single_test->status ),
				sprintf(
					// Translators: %s: Plugin slug being tested.
					esc_html__( 'Testing %s', 'health-check' ),
					$plugin_slug
				),
				$single_test->message
			);
		}

		// Test without a theme active.
		update_option( 'health-check-default-theme', 'yes' );

		$theme_test = Health_Check_Loopback::can_perform_loopback( $loopback_hash, '' );

		$message .= sprintf(
			'<br><span class="%s"></span> %s: %s',
			esc_attr( $theme_test->status ),
			esc_html__( 'Testing a default theme', 'health-check' ),
			$theme_test->message
		);

		delete_option( 'health-check-default-theme' );

		$response = array(
			'message' => $message,
		);

		wp_send_json_success( $response );

		die();
	}
}
