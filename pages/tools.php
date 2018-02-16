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
			<?php _e( 'The File Integrity checks all the core files with the <code>checksums</code> provided by the WordPress API to see if they are intact. If there are changes you will be able to make a Diff between the files hosted on WordPress.org and your installation to see what has been changed.', 'health-check' ); ?>
		</p>
		<form action="#" id="health-check-file-integrity" method="POST">
			<input type="submit" class="button button-primary" value="<?php esc_html_e( 'Check the Files Integrity', 'health-check' ); ?>">
		</form>
	</div>
	<div class="notice notice-info inline">
		<h2><?php esc_html_e( 'Mail Check' ); ?></h2>
		<p>
			<?php _e( 'The Mail Check will invoke the <code>wp_mail()</code> function and check if it success. We will use the E-mail address you have set up, but you can change it bellow if you like.', 'health-check' ); ?>
		</p>
		<form action="#" id="health-check-mail-check" method="POST">
			<p>
				<?php
					$current_user = wp_get_current_user();
				?>
				<label for="email"><?php  _e( 'E-mail', 'health-check' ); ?></label>
				<input type="text" name="email" id="email" value="<?php echo $current_user->user_email; ?>">
			</p>
			<input type="submit" class="button button-primary" value="<?php esc_html_e( 'Check Mail', 'health-check' ); ?>">
		</form>
	</div>

	<div id="tools-response-holder">
		<span class="spinner"></span>
	</div>

	<div id="health-check-diff-modal">
		<div id="health-check-diff-modal-content">
			<a id="health-check-diff-modal-close-ref" href="#health-check-diff-modal-close"><span class="dashicons dashicons-no"></span></a>
			<span class="spinner"></span>
		</div>
	</div>
