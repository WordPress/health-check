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
			if ( false !== strpos( $line, 'WP_DEBUG' ) && false === strpos( $line, '*' ) ) {
				$line  = "define('WP_DEBUG', true);" . PHP_EOL;
				$line .= "define('WP_DEBUG_LOG', false);" . PHP_EOL;
				$line .= "define('WP_DEBUG_DISPLAY', false);" . PHP_EOL;
				$line .= "@ini_set('display_errors', 0);" . PHP_EOL;
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

}
