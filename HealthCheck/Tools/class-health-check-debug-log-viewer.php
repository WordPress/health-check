<?php

class Health_Check_Debug_Log_Viewer extends Health_Check_Tool {

	public function __construct() {
		$this->label       = __( 'Debug logs', 'health-check' );
		$this->description = __( 'The details below are gathered from your <code>debug.log</code> file, and is displayed because the <code>WP_DEBUG_LOG</code> constant has been set to allow logging of warnings and errors.', 'health-check' );

		parent::__construct();
	}

	private function read_debug_log() {
		if ( ! defined( 'WP_DEBUG_LOG' ) || false === WP_DEBUG_LOG ) {
			return null;
		}

		$logfile = WP_DEBUG_LOG;

		/*
		 * `WP_DEBUG_LOG` can be a boolean value, or a path to a file.
		 * In the case of a boolean value of `true`, the default file location path will be used.
		 */
		if ( is_bool( $logfile ) ) {
			$logfile = WP_CONTENT_DIR . '/debug.log';
		}

		if ( ! file_exists( $logfile ) ) {
			return null;
		}

		$debug_log = @file_get_contents( $logfile );

		if ( false === $debug_log ) {
			return sprintf(
				// translators: %s: The path to the debug log file.
				__( 'The debug log file found at `%s`, could not be read.', 'health-check' ),
				$logfile
			);
		}

		return $debug_log;
	}

	public function tab_content() {
		if ( ! defined( 'WP_DEBUG_LOG' ) || false === WP_DEBUG_LOG ) :
			printf(
				'<p>%s</p>',
				__( 'Because the <code>WP_DEBUG_LOG</code> constant is not set to allow logging of errors and warnings, and there is therefore no more details here.', 'health-check' )
			);
			printf(
				'<p>%s</p>',
				sprintf(
					// translators: %s: The URL to the Debugging in WordPress article.
					__( 'You can read more about the <code>WP_DEBUG_LOG</code> constant, and how to enable it, in the <a href="%s">Debugging in WordPress</a> article.', 'health-check' ),
					// translators: The localized URL to the Debugging in WordPress article, if available.
					__( 'https://wordpress.org/documentation/article/debugging-in-wordpress/#wp_debug_log', 'health-check' )
				)
			);
			?>
		<?php else : ?>
			<label class="screen-reader-text" for="health-check-debug-log-viewer"><?php _e( 'Debug log contents', 'health-check' ); ?></label>
			<textarea style="width:100%;" id="health-check-debug-log-viewer" rows="20" readonly><?php echo esc_textarea( $this->read_debug_log() ); ?></textarea>
			<?php
		endif;
	}
}

new Health_Check_Debug_Log_Viewer();
