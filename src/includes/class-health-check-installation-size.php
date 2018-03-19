<?php

/**
 * Class for retrieving WordPress directories & database sizes
 *
 * @package Health Check
 */

/**
 * Class Health_Check_Installation_Size
 */
class Health_Check_Installation_Size {

	/**
	 * Gathers all WordPress Installation sizes
	 *
	 * @uses get_theme_root()
	 * @uses wp_upload_dir()
	 * @uses size_format()
	 *
	 * @return void
	 */
	static function get_installation_size() {

		$wp_path              = ABSPATH;
		$wp_templates_path    = get_theme_root() . '/';
		$wp_plugins_path      = ABSPATH . 'wp-content/plugins/';
		$wp_uploads_path      = wp_upload_dir();
		$wp_uploads_path_base = $wp_uploads_path['basedir'];

		$wpp    = Health_Check_Installation_Size::get_directory_size( $wp_path );
		$wptp   = Health_Check_Installation_Size::get_directory_size( $wp_templates_path );
		$wppl   = Health_Check_Installation_Size::get_directory_size( $wp_plugins_path );
		$wpup   = Health_Check_Installation_Size::get_directory_size( $wp_uploads_path_base );
		$dbsize = Health_Check_Installation_Size::get_database_size();

		$tts = $wpp + $dbsize;

		$output  = '<table class="widefat striped" installation-size>';
		$output .= '<tr><td>' . esc_html__( 'Uploads Directory:', 'health-check' ) . '</td><td>' . size_format( $wpup, 2 ) . '</td></tr>';
		$output .= '<tr><td>' . esc_html__( 'Themes Directory:', 'health-check' ) . '</td><td>' . size_format( $wptp, 2 ) . '</td></tr>';
		$output .= '<tr><td>' . esc_html__( 'Plugins Directory:', 'health-check' ) . '</td><td>' . size_format( $wppl, 2 ) . '</td></tr>';
		$output .= '<tr><td colspan="2"><hr></td></tr>';
		$output .= '<tr><td>' . esc_html__( 'Database:', 'health-check' ) . '</td><td>' . size_format( $dbsize, 2 ) . '</td></tr>';
		$output .= '<tr><td>' . esc_html__( 'WordPress Directory:', 'health-check' ) . '</td><td>' . size_format( $wpp, 2 ) . '</td></tr>';
		$output .= '<tr><td colspan="2"><hr></td></tr>';
		$output .= '<tr><td>' . esc_html__( 'Total Installation Size:', 'health-check' ) . '</td><td>' . size_format( $tts, 2 ) . '</td></tr>';
		$output .= '</table>';

		$response = array(
			'message' => $output,
		);

		wp_send_json_success( $response );

	}

	/**
	 * Reads the path given and gets the file sizes
	 *
	 * @uses opendir()
	 * @uses readdir()
	 * @uses is_link()
	 * @uses is_dir()
	 * @uses Health_Check_Installation_Size::get_directory_size()
	 * @uses is_file()
	 * @uses filesize()
	 *
	 * @return string $size The total dir size in bytes
	 */
	static function get_directory_size( $path ) {

		$size = 0;

		if ( $dir = opendir( $path ) ) {
			while ( false !== ( $file = readdir( $dir ) ) ) {
				$nextpath = $path . '/' . $file;
				if ( '.' !== $file && '..' !== $file && ! is_link( $nextpath ) ) {
					if ( is_dir( $nextpath ) ) {
						$nextsize = Health_Check_Installation_Size::get_directory_size( $nextpath );
						$size    += $nextsize;
					} elseif ( is_file( $nextpath ) ) {
						$size += filesize( $nextpath );
					}
				}
			}
		}

		closedir( $dir );

		return $size;

	}

	/**
	 * Returns the total database size
	 *
	 * @uses $wpdb
	 * @uses get_results()
	 * @uses size_format()
	 *
	 * @return string $size The total database size
	 */
	static function get_database_size() {
		global $wpdb;
		$size = 0;
		$rows = $wpdb->get_results( 'SHOW TABLE STATUS', ARRAY_A );

		if ( $wpdb->num_rows > 0 ) {
			foreach ( $rows as $row ) {
				$size += $row['Data_length'] + $row['Index_length'];
			}
		}

		return $size;
	}

}
