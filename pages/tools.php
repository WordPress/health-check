<?php
/**
 * Health Check tab contents.
 *
 * @package Health Check
 */

// Make sure the file is not directly accessible.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'We\'re sorry, but you can not directly access this file.' );
}

?>

	<div class="notice notice-info inline">
		<h2><?php esc_html_e( 'File Integrity' ); ?></h2>
		<p>
			<?php _e( 'The File Integrity checks all the core files with the <code>checksums</code> provided by the WordPress API to see if they are intact.', 'health-check' ); ?>
		</p>
		<form action="#" id="health-check-file-integrity" method="POST">
			<input type="submit" class="button button-primary" value="<?php esc_html_e( 'Check the Files Integrity', 'health-check' ); ?>">
		</form>
	</div>
	<div class="notice notice-info inline">
		<h2><?php esc_html_e( 'Mail Check' ); ?></h2>
		<p>
			<?php _e( 'The Mail Check will invoke the <code>wp_mail()</code> function and check if it success.', 'health-check' ); ?>
		</p>
		<form action="#" id="health-check-file-integrity" method="POST">
			<input type="submit" class="button button-primary" value="<?php esc_html_e( 'Check the Files Integrity', 'health-check' ); ?>">
		</form>
	</div>

	<div id="tools-response-holder">
		<span class="spinner"></span>
	</div>
