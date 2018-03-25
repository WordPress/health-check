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
	 * Enables WP_DEBUG
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
	 *
	 * @return void
	 */
	static function enable_wp_debug() {

		$wpconfig = ABSPATH . 'wp-config.php';

		$wp_debug_found = 'no';

		$editing_wpconfig = file( $wpconfig );

		file_put_contents( $wpconfig, '' );

		$write_wpconfig = fopen( $wpconfig, 'w' );

		foreach ( $editing_wpconfig as $line ) {
			if ( false !== strpos( $line, "'WP_DEBUG'" ) || false !== strpos( $line, '"WP_DEBUG"' ) ) {
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
	 * Disables WP_DEBUG and restores the original wp-config.php
	 *
	 * @uses fopen()
	 * @uses copy()
	 * @uses wp_send_json_error()
	 * @uses wp_send_json_succes()
	 *
	 * @return void
	 */
	static function disable_wp_debug() {

		$wpconfig = ABSPATH . 'wp-config.php';

		$editing_wpconfig = file( $wpconfig );

		file_put_contents( $wpconfig, '' );

		$write_wpconfig = fopen( $wpconfig, 'w' );

		foreach ( $editing_wpconfig as $line ) {
			if ( false !== strpos( $line, "'WP_DEBUG'" ) || false !== strpos( $line, '"WP_DEBUG"' ) ) {
				$line = "define( 'WP_DEBUG', false );" . PHP_EOL;
			}
			fputs( $write_wpconfig, $line );
		}

		fclose( $write_wpconfig );

		$response = array(
			'status'  => 'success',
			'message' => esc_html__( 'WP_DEBUG was disabled.', 'health-check' ),
		);

		wp_send_json_success( $response );

	}

	/**
	 * Enables WP_DEBUG_LOG
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
	 *
	 * @return void
	 */
	static function enable_wp_debug_log() {

		$wpconfig = ABSPATH . 'wp-config.php';

		$wp_debug_log_found = 'no';

		$editing_wpconfig = file( $wpconfig );

		file_put_contents( $wpconfig, '' );

		$write_wpconfig = fopen( $wpconfig, 'w' );

		foreach ( $editing_wpconfig as $line ) {
			if ( false !== strpos( $line, "'WP_DEBUG_LOG'" ) || false !== strpos( $line, '"WP_DEBUG_LOG"' ) ) {
				$line               = "define( 'WP_DEBUG_LOG', true );" . PHP_EOL;
				$wp_debug_log_found = 'yes';
			}
			fputs( $write_wpconfig, $line );
		}

		fclose( $write_wpconfig );

		if ( 'no' === $wp_debug_log_found ) {

			$editing_wpconfig = file( $wpconfig );

			file_put_contents( $wpconfig, '' );

			$write_wpconfig = fopen( $wpconfig, 'w' );

			foreach ( $editing_wpconfig as $line ) {
				if ( false !== strpos( $line, "!defined('ABSPATH')" ) ) {
					$line  = "define( 'WP_DEBUG_LOG', true );" . PHP_EOL;
					$line .= "if ( !defined('ABSPATH') )" . PHP_EOL;
				}
				fputs( $write_wpconfig, $line );
			}

			fclose( $write_wpconfig );
		}

		$response = array(
			'status'  => 'success',
			'message' => esc_html__( 'WP_DEBUG_LOG was enabled.', 'health-check' ),
		);

		wp_send_json_success( $response );

	}

	/**
	 * Disables WP_DEBUG_LOG
	 *
	 * @uses file_exists()
	 * @uses fopen()
	 * @uses copy()
	 * @uses strpos()
	 * @uses fputs()
	 * @uses fclose()
	 * @uses wp_send_json_error()
	 * @uses wp_send_json_succes()
	 *
	 * @return void
	 */
	static function disable_wp_debug_log() {

		$wpconfig = ABSPATH . 'wp-config.php';

		$editing_wpconfig = file( $wpconfig );

		file_put_contents( $wpconfig, '' );

		$write_wpconfig = fopen( $wpconfig, 'w' );

		foreach ( $editing_wpconfig as $line ) {
			if ( false !== strpos( $line, "'WP_DEBUG_LOG'" ) || false !== strpos( $line, '"WP_DEBUG_LOG"' ) ) {
				$line = "define( 'WP_DEBUG_LOG', false );" . PHP_EOL;
			}
			fputs( $write_wpconfig, $line );
		}

		fclose( $write_wpconfig );

		$response = array(
			'status'  => 'success',
			'message' => esc_html__( 'WP_DEBUG_LOG was disabled.', 'health-check' ),
		);

		wp_send_json_success( $response );

	}

	/**
	 * Check if wp-config backup exists
	 *
	 * @uses file_exists()
	 *
	 * @return bool true/false depending if the backup exists
	 */
	static function check_wp_config_backup() {
		$wpconfig_backup = ABSPATH . 'wp-config_hc_backup.php';

		if ( file_exists( $wpconfig_backup ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Creates a backup of wp-config.php
	 *
	 * @uses file_exists()
	 * @uses copy()
	 *
	 * @return string $output Success/Fail
	 */
	static function create_wp_config_backup() {
		$wpconfig        = ABSPATH . 'wp-config.php';
		$wpconfig_backup = ABSPATH . 'wp-config_hc_backup.php';

		if ( ! copy( $wpconfig, $wpconfig_backup ) ) {
			$response = array(
				'status'  => 'error',
				'message' => esc_html__( 'wp-config.php backup failed.', 'health-check' ),
			);
			wp_send_json_error( $response );
		}

		$response = array(
			'status'  => 'success',
			'message' => esc_html__( 'wp-config.php backup was created.', 'health-check' ),
		);

		wp_send_json_success( $response );
	}

	/**
	 * Restores a backup of wp-config.php
	 *
	 * @uses file_exists()
	 * @uses copy()
	 * @uses unlink()
	 *
	 * @return string $output Success/Fail
	 */
	static function restore_wp_config_backup() {
		$wpconfig        = ABSPATH . 'wp-config.php';
		$wpconfig_backup = ABSPATH . 'wp-config_hc_backup.php';

		if ( ! copy( $wpconfig_backup, $wpconfig ) ) {
			$response = array(
				'status'  => 'error',
				'message' => esc_html__( 'wp-config.php restore failed.', 'health-check' ),
			);
			wp_send_json_error( $response );
		}

		$response = array(
			'status'  => 'success',
			'message' => esc_html__( 'wp-config.php backup was restored.', 'health-check' ),
		);

		unlink( $wpconfig_backup );

		wp_send_json_success( $response );
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

	/**
	 * Checks the debug constants
	 *
	 * @uses WP_DEBUG
	 * @uses WP_DEBUG_LOG
	 * @uses WP_DEBUG_DISPLAY
	 * @uses SCRIPT_DEBUG
	 *
	 * @return string $output html representation for enable / disabled debug constants
	 */
	static function check_wp_debug_constants() {

		$enabled  = '<span style="color:green;">' . esc_html__( 'Enabled', 'health-check' ) . '</span>';
		$disabled = '<span style="color:red;">' . esc_html__( 'Disabled', 'health-check' ) . '</span>';

		$output  = '<div class="notice notice-info inline">';
		$output .= '<p><strong>WP_DEBUG:</strong> ';
		$output .= ( defined( 'WP_DEBUG' ) && WP_DEBUG ? $enabled : $disabled );
		$output .= ' | <strong>WP_DEBUG_LOG:</strong> ';
		$output .= ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ? $enabled : $disabled );
		$output .= ' | <strong>WP_DEBUG_DISPLAY:</strong> ';
		$output .= ( defined( 'WP_DEBUG_DISPLAY' ) && WP_DEBUG_DISPLAY ? $enabled : $disabled );
		$output .= ' | <strong>SCRIPT_DEBUG:</strong> ';
		$output .= ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? $enabled : $disabled );
		$output .= ' | <strong>SAVEQUERIES:</strong> ';
		$output .= ( defined( 'SAVEQUERIES' ) && SAVEQUERIES ? $enabled : $disabled );
		$output .= '</p>';
		$output .= '</div>';

		return $output;

	}

}
