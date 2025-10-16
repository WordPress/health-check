<?php

/**
 * Checks if a .htaccess file exists and is used.
 *
 * @package Health Check
 */

/**
 * Class Mail Check
 */
class Health_Check_Htaccess extends Health_Check_Tool {

	public function init() {
		$this->label       = __( '.htaccess Viewer', 'health-check' );
		$this->description = __( 'The <code>.htaccess</code> file tells your server (if supported) how to handle links and file requests. This file usually requires direct server access to view, but if your system supports these files, you can verify its content here.', 'health-check' );
	}

	public function tab_content() {
		global $wp_rewrite;

		if ( $wp_rewrite->using_mod_rewrite_permalinks() ) {
			if ( file_exists( ABSPATH . '.htaccess' ) ) {
				printf(
					'<pre>%s</pre>',
					esc_html( file_get_contents( ABSPATH . '.htaccess' ) )
				);
			} else {
				printf(
					'<p>%s</p>',
					__( 'Your site is using <code>.htaccess</code> rules to handle permalinks, but no .htaccess file was found. This means that your .htaccess file is not being used to handle requests.', 'health-check' )
				);
			}
		} else {
			printf(
				'<p>%s</p>',
				__( 'Your site is not using <code>.htaccess</code> to handle permalinks. This means that your .htaccess file is not being used to handle requests, and they are most likely handled directly by your web-server software.', 'health-check' )
			);
		}
		?>
		<?php
	}

}
