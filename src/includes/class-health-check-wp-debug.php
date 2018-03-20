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
	 * Enables WP_DEBUG and creates a backup of wp-config.php
	 *
	 * @uses copy()
	 * @uses file()
	 * @uses file_put_contents()
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
		$wp_debug_found  = 'no';

		if ( ! copy( $wpconfig, $wpconfig_backup ) ) {
			$response = array(
				'status'  => 'error',
				'message' => esc_html__( 'Could not create a backup of wp-config.php.', 'health-check' ),
			);
			wp_send_json_error( $response );
		}

		$editing_wpconfig = file( $wpconfig );

		file_put_contents( $wpconfig, '' );

		$write_wpconfig = fopen( $wpconfig, 'w' );

		foreach ( $editing_wpconfig as $line ) {
			if ( false !== strpos( $line, 'WP_DEBUG' ) && false === strpos( $line, '*' ) ) {
				$line           = "define( 'WP_DEBUG', true );" . PHP_EOL;
				$wp_debug_found = 'yes';
			}
			fputs( $write_wpconfig, $line );
		}

		fclose( $write_wpconfig );

		if ( 'no' === $wp_debug_found ) {

			$editing_wpconfig = file( $wpconfig );

			file_put_contents( $wpconfig, '' );

			$write_wpconfig = fopen( $wpconfig, 'w' );

			foreach ( $editing_wpconfig as $line ) {
				if ( false !== strpos( $line, "!defined('ABSPATH')" ) ) {
					$line  = "define( 'WP_DEBUG', true );" . PHP_EOL;
					$line .= "if ( !defined('ABSPATH') )" . PHP_EOL;
				}
				fputs( $write_wpconfig, $line );
			}

			fclose( $write_wpconfig );
		}

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

	/**
	 * Clear debug.log contents
	 *
	 * @uses file_put_contents()
	 * @uses wp_die()
	 *
	 * @return void
	 */
	static function clear_wp_debug() {

		file_put_contents( WP_CONTENT_DIR . '/debug.log', '' );

		$response = array(
			'message' => esc_html__( 'The debug.log has been cleared.', 'health-check' ),
		);

		wp_send_json_success( $response );

	}

}
