<?php

class Health_Check_Loopback {
	static function can_perform_loopback() {
		$cookies = wp_unslash( $_COOKIE );
		$timeout = 100;
		$headers = array(
			'Cache-Control' => 'no-cache',
		);

		// Include Basic auth in loopback requests.
		if ( isset( $_SERVER['PHP_AUTH_USER'] ) && isset( $_SERVER['PHP_AUTH_PW'] ) ) {
			$headers['Authorization'] = 'Basic ' . base64_encode( wp_unslash( $_SERVER['PHP_AUTH_USER'] ) . ':' . wp_unslash( $_SERVER['PHP_AUTH_PW'] ) );
		}

		$url = admin_url();

		$r = wp_remote_get( $url, compact( 'cookies', 'headers', 'timeout' ) );

		if ( is_wp_error( $r ) ) {
			return (object) array(
				'status'  => 'error',
				'message' => __( 'Unable to perform a loopback request to your site, this may prevent WP_Cron from working, along with theme and plugin editors.', 'health-check' )
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
}