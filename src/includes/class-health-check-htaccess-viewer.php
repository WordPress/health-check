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
		// check if it is a file.
		if ( ! is_file( ABSPATH . '.htaccess' ) ) {
			return $tabs;
		}

		// It does so let's get the contents
		$htaccess_file = file_get_contents( ABSPATH . '/.htaccess' );

		// Remove WordPress rules.
		$filtered_htaccess_content = trim( preg_replace( '/\# BEGIN WordPress[\s\S]+?# END WordPress/si', '', $htaccess_file ) );

		// Return since it is only core added rules.
		if ( '' === $filtered_htaccess_content ) {
			return $tabs;
		}

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

		// return the tabs array since it is a filter.
		return $tabs;
	}
}
