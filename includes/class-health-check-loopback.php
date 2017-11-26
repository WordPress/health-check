<?php

/**
 * Class Health_Check_Loopback
 */
class Health_Check_Loopback {
	/**
	 * Run a loopback test on our site.
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
			$url = add_query_arg( array( 'health-check-disable-plugin-hash' => $disable_plugin_hash ), $url );
		}
		if ( ! empty( $allowed_plugins ) ) {
			if ( ! is_array( $allowed_plugins ) ) {
				$allowed_plugins = (array) $allowed_plugins;
			}

			$url = add_query_arg( array( 'health-check-allowed-plugins' => implode( ',', $allowed_plugins ) ), $url );
		}

		$r = wp_remote_get( $url, compact( 'cookies', 'headers', 'timeout' ) );

		if ( is_wp_error( $r ) ) {
			return (object) array(
				'status'  => 'error',
				'message' => __( 'The loopback request to your site took too long to complete, this may prevent WP_Cron from working, along with theme and plugin editors.', 'health-check' )
			);
		}

		if ( 200 !== wp_remote_retrieve_response_code( $r ) ) {
			return (object) array(
				'status'  => 'warning',
				'message' => sprintf(
					/* translators: %d: The HTTP response code returned. */
					__( 'The loopback request returned an unexpected status code, %d, this may affect tools such as WP_Cron, or theme and plugin editors.', 'health-check' ),
					wp_remote_retrieve_response_code( $r )
				)
			);
		}

		return (object) array(
			'status'  => 'good',
			'message' => __( 'The loopback request to your site completed successfully.' )
		);
	}

	static function loopback_no_plugins() {
		ob_start();

		$needs_creds = false;

		if ( ! Health_Check_Troubleshoot::mu_plugin_exists() ) {
			if ( ! Health_Check_Troubleshoot::get_filesystem_credentials() ) {
				$needs_creds = true;
			} else {
				$check_output = Health_Check_Troubleshoot::setup_must_use_plugin();
				if ( false === $check_output ) {
					$needs_creds = true;
				}
			}
		}

		$result = ob_get_clean();

		if ( $needs_creds ) {
			wp_send_json_error( $result );
			die();
		}

		$loopback_hash = md5( rand() );
		update_option( 'health-check-disable-plugin-hash', $loopback_hash );

		$no_plugin_test = Health_Check_Loopback::can_perform_loopback( $loopback_hash );

		$message = sprintf(
			'<br><span class="%s"></span> %s: %s',
			esc_attr( $no_plugin_test->status ),
			esc_html__( 'Result from testing without any plugins active', 'health-check' ),
			esc_html( $no_plugin_test->message )
		);

		if ( 'error' !== $no_plugin_test->status ) {
			$message .= '<br><button type="button" id="loopback-individual-plugins" class="button button-primary">Test individual plugins</button>';
		}

		$response = array(
			'message' => $message
		);

		wp_send_json_success( $response );

		die();
	}

	static function loopback_test_individual_plugins() {
		ob_start();

		$needs_creds = false;

		if ( ! Health_Check_Troubleshoot::mu_plugin_exists() ) {
			if ( ! Health_Check_Troubleshoot::get_filesystem_credentials() ) {
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

		foreach( $all_plugins as $single_plugin ) {
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
				esc_html( $single_test->message )
			);
		}

		$response = array(
			'message' => $message
		);

		wp_send_json_success( $response );

		die();
	}
}