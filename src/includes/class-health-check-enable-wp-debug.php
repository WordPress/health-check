<?php

/**
 * Enables / disables WP_DEBUG.
 *
 * @package Health Check
 */

/**
 * Class Enable WP Debug
 */
class Health_Check_Enable_WP_Debug {

	/**
	 * Checks if WP_DEBUG is enabled and acts accordingly
	 *
	 * @uses Health_Check_Enable_WP_Debug::enable_wp_debug()
	 * @uses Health_Check_Enable_WP_Debug::disable_wp_debug()
	 *
	 * @return void
	 */
	static function check_wp_debug() {
		if ( ! WP_DEBUG ) {
			Health_Check_Enable_WP_Debug::enable_wp_debug();
		} else {
			Health_Check_Enable_WP_Debug::disable_wp_debug();
		}
	}

	/**
	 * Enables WP_DEBUG
	 *
	 * @uses copy()
	 * @uses fopen()
	 * @uses feof()
	 * @uses fgets()
	 * @uses stristr()
	 * @uses fputs()
	 * @uses fclose()
	 * @uses unlink()
	 *
	 * @return void
	 */
	static function enable_wp_debug() {

		$wpconfig        = ABSPATH . 'wp-config.php';
		$wpconfig_backup = ABSPATH . 'wp-config_hcbk.php';
		$wpconfig_temp   = ABSPATH . 'wp-config_hctemp.php';
		$find            = "define('WP_DEBUG', false);";
		$hc_debug        = "// Enable WP_DEBUG mode
define( 'WP_DEBUG', true );

// Enable Debug logging to the /wp-content/debug.log file
define( 'WP_DEBUG_LOG', true );

// Disable display of errors and warnings
define( 'WP_DEBUG_DISPLAY', false );
@ini_set( 'display_errors', 0 );";

		if ( ! copy( $wpconfig, $wpconfig_backup ) ) {
			$response = array(
				'status'  => 'error',
				'message' => esc_html__( 'Could not create a backup of wp-config.php.', 'health-check' ),
			);
			wp_send_json_error( $response );
		}

		if ( ! copy( $wpconfig, $wpconfig_temp ) ) {
			$response = array(
				'status'  => 'error',
				'message' => esc_html__( 'Could not create a temp file of wp-config.php.', 'health-check' ),
			);
			wp_send_json_error( $response );
		}

		$read_wpconfig  = fopen( $wpconfig, 'r' );
		$write_wpconfig = fopen( $wpconfig_temp, 'w' );

		$edited = false;

		while ( ! feof( $read_wpconfig ) ) {
			$line = fgets( $read_wpconfig );
			if ( stristr( $line, $find ) ) {
				$line   = $hc_debug . "\n";
				$edited = true;
			}
			fputs( $write_wpconfig, $line );
		}

		fclose( $read_wpconfig );
		fclose( $write_wpconfig );

		if ( ! copy( $wpconfig_temp, $wpconfig ) ) {
			$response = array(
				'status'  => 'error',
				'message' => esc_html__( 'Could create wp-config.php from the temp file.', 'health-check' ),
			);
			wp_send_json_error( $response );
		}

		unlink( $wpconfig_temp );

		$response = array(
			'status'  => 'success',
			'message' => esc_html__( 'WP_DEBUG was enabled.', 'health-check' ),
		);

		wp_send_json_success( $response );

	}

}
