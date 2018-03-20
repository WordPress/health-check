<?php

/**
 * Enables / disables WP_DEBUG.
 *
 * @package Health Check
 */

/**
 * Class Health Check WP Debug
 */
class Health_Check_WP_Debug {

	/**
	 * Checks if WP_DEBUG is enabled and acts accordingly
	 *
	 * @uses Health_Check_WP_Debug::enable_wp_debug()
	 * @uses Health_Check_WP_Debug::disable_wp_debug()
	 *
	 * @return void
	 */
	static function check_wp_debug() {
		if ( ! WP_DEBUG ) {
			Health_Check_WP_Debug::enable_wp_debug();
		} else {
			Health_Check_WP_Debug::disable_wp_debug();
		}
	}

	/**
	 * Enables WP_DEBUG and creates a backup of wp-config.php
	 *
	 * @uses copy()
	 * @uses file()
	 * @uses fopen()
	 * @uses strpos()
	 * @uses fputs()
	 * @uses fclose()
	 * @uses wp_send_json_error()
	 * @uses wp_send_json_succes()
	 * @uses unlink()
	 *
	 * @return void
	 */
	static function enable_wp_debug() {

		$wpconfig        = ABSPATH . 'wp-config.php';
		$wpconfig_backup = ABSPATH . 'wp-config_hc_backup.php';
		$wpconfig_temp   = ABSPATH . 'wp-config_hc_temp.php';

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

		$editing_wpconfig = file( $wpconfig_temp );

		$write_temp_wpconfig = fopen( $wpconfig_temp, 'w' );

		foreach ( $editing_wpconfig as $line ) {
			// find and remove the WP_DEBUG_LOG
			if ( false !== strpos( $line, 'WP_DEBUG_LOG' ) ) {
				$line = '';
			}
			// find and remove the WP_DEBUG_DISPLAY
			if ( false !== strpos( $line, 'WP_DEBUG_DISPLAY' ) ) {
				$line = '';
			}
			// find and remove the display_errors
			if ( false !== strpos( $line, 'display_errors' ) ) {
				$line = '';
			}
			// find and replace WP_DEBUG
			if ( false !== strpos( $line, 'WP_DEBUG' ) && false === strpos( $line, '*' ) ) {
				$line  = "define('WP_DEBUG', true);" . PHP_EOL;
				$line .= "define('WP_DEBUG_LOG', true);" . PHP_EOL;
				$line .= "define('WP_DEBUG_DISPLAY', false);" . PHP_EOL;
				$line .= "@ini_set('display_errors', 0);" . PHP_EOL . PHP_EOL;
			} else {
				// if no WP_DEBUG find the ABSPATH and prepend with WP_DEBUG
				if ( false !== strpos( $line, "!defined('ABSPATH')" ) ) {
					$line  = "define('WP_DEBUG', true);" . PHP_EOL;
					$line .= "define('WP_DEBUG_LOG', true);" . PHP_EOL;
					$line .= "define('WP_DEBUG_DISPLAY', false);" . PHP_EOL;
					$line .= "@ini_set('display_errors', 0);" . PHP_EOL . PHP_EOL;
					$line .= "if ( !defined('ABSPATH') )" . PHP_EOL;
				}
			}
			fputs( $write_temp_wpconfig, $line );
		}

		fclose( $write_temp_wpconfig );

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

	/**
	 * Restores the original wp-config.php or disables WP_DEBUG
	 *
	 * @uses fopen()
	 * @uses copy()
	 * @uses wp_send_json_error()
	 * @uses wp_send_json_succes()
	 *
	 * @return void
	 */
	static function disable_wp_debug() {

		$wpconfig        = ABSPATH . 'wp-config.php';
		$wpconfig_backup = ABSPATH . 'wp-config_hc_backup.php';

		if ( fopen( $wpconfig_backup, 'r' ) ) {
			if ( ! copy( $wpconfig_backup, $wpconfig ) ) {
				$response = array(
					'status'  => 'error',
					'message' => esc_html__( 'Could create wp-config.php from the backup file.', 'health-check' ),
				);
				wp_send_json_error( $response );
			} else {
				$response = array(
					'status'  => 'success',
					'message' => esc_html__( 'WP_DEBUG was disabled.', 'health-check' ),
				);

				wp_send_json_success( $response );
			}
		}

		// TODO: disable wp_debug if backup doesn't exist.
	}

	/**
	 * Read debug.log contents
	 *
	 * @uses file_exists()
	 * @uses fopen()
	 * @uses die()
	 * @uses fwrite()
	 * @uses fclose()
	 * @uses WP_CONTENT_DIR
	 * @uses wp_die()
	 * @uses wp_send_json_success()
	 *
	 * @return void
	 */
	static function read_wp_debug() {

		// check if debug.log exists else create it to avoid error.
		if ( ! file_exists( WP_CONTENT_DIR . '/debug.log' ) ) {
			$debug_log = fopen( WP_CONTENT_DIR . '/debug.log', 'w' ) or die( 'Cannot create debug.log!' );
			fwrite( $debug_log, '' );
			fclose( $debug_log );
		}

		$debug_contents = file_get_contents( WP_CONTENT_DIR . '/debug.log' );

		$response = array(
			'message' => $debug_contents,
		);

		wp_send_json_success( $response );

	}

}
