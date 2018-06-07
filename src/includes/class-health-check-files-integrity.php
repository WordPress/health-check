<?php

/**
 * Check all core files against the checksums provided by WordPress API.
 *
 * @package Health Check
 */

/**
 * Class Files_Integrity
 */
class Health_Check_Files_Integrity {

	/**
	 * Gathers checksums from WordPress API and cross checks the core files in the current installation.
	 *
	 * @return void
	 */
	static function run_files_integrity_check() {

		$checksums = Health_Check_Files_Integrity::call_checksum_api();

		$files = Health_Check_Files_Integrity::parse_checksum_results( $checksums );

		Health_Check_Files_Integrity::create_the_response( $files );

	}

	/**
	* Calls the WordPress API on the checksums endpoint
	*
	* @uses get_bloginfo()
	* @uses get_locale()
	* @uses ABSPATH
	* @uses wp_remote_get()
	* @uses get_bloginfo()
	* @uses strpos()
	* @uses unset()
	*
	* @return array
	*/
	static function call_checksum_api() {
		// Setup variables.
		$wpversion = get_bloginfo( 'version' );
		$wplocale  = get_locale();

		// Setup API Call.
		$checksumapi = wp_remote_get( 'https://api.wordpress.org/core/checksums/1.0/?version=' . $wpversion . '&locale=' . $wplocale, array( 'timeout' => 10000 ) );

		// Encode the API response body.
		$checksumapibody = json_decode( wp_remote_retrieve_body( $checksumapi ), true );

		// Remove the wp-content/ files from checking
		foreach ( $checksumapibody['checksums'] as $file => $checksum ) {
			if ( false !== strpos( $file, 'wp-content/' ) ) {
				unset( $checksumapibody['checksums'][ $file ] );
			}
		}

		return $checksumapibody;
	}

	/**
	* Parses the results from the WordPress API call
	*
	* @uses file_exists()
	* @uses md5_file()
	* @uses ABSPATH
	*
	* @param array $checksums
	*
	* @return array
	*/
	static function parse_checksum_results( $checksums ) {
		$filepath = ABSPATH;
		$files    = array();
		// Parse the results.
		foreach ( $checksums['checksums'] as $file => $checksum ) {
			// Check the files.
			if ( file_exists( $filepath . $file ) && md5_file( $filepath . $file ) !== $checksum ) {
				$reason = esc_html__( 'Content changed', 'health-check' ) . ' <a href="#health-check-diff" data-file="' . $file . '">' . esc_html__( '(View Diff)', 'health-check' ) . '</a>';
				array_push( $files, array( $file, $reason ) );
			} elseif ( ! file_exists( $filepath . $file ) ) {
				$reason = esc_html__( 'File not found', 'health-check' );
				array_push( $files, array( $file, $reason ) );
			}
		}
		return $files;
	}

	/**
	* Generates the response
	*
	* @uses wp_send_json_success()
	* @uses wp_die()
	* @uses ABSPATH
	*
	* @param null|array $files
	*
	* @return void
	*/
	static function create_the_response( $files ) {
		$filepath = ABSPATH;
		$output   = '';

		if ( empty( $files ) ) {
			$output .= '<div class="notice notice-success inline"><p>';
			$output .= esc_html__( 'All files passed the check. Everything seems to be ok!', 'health-check' );
			$output .= '</p></div>';
		} else {
			$output .= '<div class="notice notice-error inline"><p>';
			$output .= esc_html__( 'It appears as if some files may have been modified.', 'health-check' );
			$output .= '<br>' . esc_html__( 'One possible reason for this may be that your installation contains translated versions. An easy way to clear this is to reinstall WordPress. Don\'t worry. This will only affect WordPress\' own files, not your themes, plugins or uploaded media.', 'health-check' );
			$output .= '</p></div><table class="widefat striped file-integrity-table"><thead><tr><th>';
			$output .= esc_html__( 'Status', 'health-check' );
			$output .= '</th><th>';
			$output .= esc_html__( 'File', 'health-check' );
			$output .= '</th><th>';
			$output .= esc_html__( 'Reason', 'health-check' );
			$output .= '</th></tr></thead><tfoot><tr><td>';
			$output .= esc_html__( 'Status', 'health-check' );
			$output .= '</td><td>';
			$output .= esc_html__( 'File', 'health-check' );
			$output .= '</td><td>';
			$output .= esc_html__( 'Reason', 'health-check' );
			$output .= '</td></tr></tfoot><tbody>';
			foreach ( $files as $tampered ) {
				$output .= '<tr>';
				$output .= '<td><span class="error"></span></td>';
				$output .= '<td>' . $filepath . $tampered[0] . '</td>';
				$output .= '<td>' . $tampered[1] . '</td>';
				$output .= '</tr>';
			}
			$output .= '</tbody>';
			$output .= '</table>';
		}

		$response = array(
			'message' => $output,
		);

		wp_send_json_success( $response );

		wp_die();
	}

	/**
	* Generates Diff view
	*
	* @uses get_bloginfo()
	* @uses wp_remote_get()
	* @uses wp_remote_retrieve_body()
	* @uses wp_send_json_success()
	* @uses wp_die()
	* @uses ABSPATH
	* @uses FILE_USE_INCLUDE_PATH
	* @uses wp_text_diff()
	*
	*
	* @return void
	*/
	static function view_file_diff() {
		$filepath         = ABSPATH;
		$file             = $_POST['file'];
		$wpversion        = get_bloginfo( 'version' );
		$local_file_body  = file_get_contents( $filepath . $file, FILE_USE_INCLUDE_PATH );
		$remote_file      = wp_remote_get( 'https://core.svn.wordpress.org/tags/' . $wpversion . '/' . $file );
		$remote_file_body = wp_remote_retrieve_body( $remote_file );
		$diff_args        = array(
			'show_split_view' => true,
		);

		$output   = '<table class="diff"><thead><tr class="diff-sub-title"><th>';
		$output  .= esc_html__( 'Original', 'health-check' );
		$output  .= '</th><th>';
		$output  .= esc_html__( 'Modified', 'health-check' );
		$output  .= '</th></tr></table>';
		$output  .= wp_text_diff( $remote_file_body, $local_file_body, $diff_args );
		$response = array(
			'message' => $output,
		);

		wp_send_json_success( $response );

		wp_die();
	}

	/**
	 * Add the Files integrity checker to the tools tab.
	 *
	 * @param array $tabs
	 *
	 * return array
	 */
	static function tools_tab( $tabs ) {
		ob_start();
		?>

		<div>
			<p>
				<?php _e( 'The File Integrity checks all the core files with the <code>checksums</code> provided by the WordPress API to see if they are intact. If there are changes you will be able to make a Diff between the files hosted on WordPress.org and your installation to see what has been changed.', 'health-check' ); ?>
			</p>
			<form action="#" id="health-check-file-integrity" method="POST">
				<p>
					<input type="submit" class="button button-primary" value="<?php esc_html_e( 'Check the Files Integrity', 'health-check' ); ?>">
				</p>
			</form>

			<div id="tools-file-integrity-response-holder">
				<span class="spinner"></span>
			</div>
		</div>

		<?php
		$tab_content = ob_get_clean();

		$tabs[] = array(
			'label'   => esc_html__( 'File Integrity', 'health-check' ),
			'content' => $tab_content,
		);

		return $tabs;
	}
}
