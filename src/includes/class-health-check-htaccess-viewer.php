<?php
/**
 * Class for displaying the .htaccess file for the site.
 *
 * @package Health Check
 */

/**
 * Class Health_Check_Htaccess
 */
class Health_Check_Htaccess_Viewer {
	/**
	 * Callback function for the tool_tabs filter that adds content
	 * to the tools accordion.
	 *
	 * @param  array $tabs Associative array of tools.
	 *
	 * @return array Associative array.
	 */
	public static function tools_tab( $tabs ) {
		// check if it is a file and readable.
		if ( ! is_readable( ABSPATH . '.htaccess' ) ) {
			$tabs['htaccess-viewer'] = array(
				'label'   => esc_html__( 'Contents of .htaccess', 'health-check' ),
				'content' => sprintf( '<div><p>%s</p></div>', esc_html__( 'It appears there is no .htaccess file or is not a readable file.', 'health-check' ) ),
			);
		} else {
			// it is a file.
			$htaccess_file = file_get_contents( ABSPATH . '/.htaccess' );

			// Remove WordPress rules.
			$filtered_htaccess_content = trim( preg_replace( '/\# BEGIN WordPress[\s\S]+?# END WordPress/si', '', $htaccess_file ) );

			ob_start(); ?>
			<div>
				<p>
					<button type="button" class="button button-primary health-check-copy-field"><?php esc_html_e( 'Mark field for copying', 'health-check' ); ?></button>
				</p>
				<textarea id="htaccess-contents" class="widefat" rows="15"><?php echo htmlspecialchars( file_get_contents( ABSPATH . '.htaccess' ) ); ?></textarea>
			</div>
			<?php
			$content = ob_get_clean();

			$tabs['htacces-viewer'] = array(
				'label'   => esc_html__( 'Contents of .htaccess', 'health-check' ),
				'content' => $content,
			);
		}

		// return our tabs.
		return $tabs;
	}
}
