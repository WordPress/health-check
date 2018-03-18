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

<dl id="health-check-tools" role="presentation" class="health-check-accordion">
	<dt role="heading" aria-level="2">
		<button aria-expanded="true" class="health-check-accordion-trigger" aria-controls="health-check-accordion-block-1" id="health-check-accordion-heading-1" type="button">
			<span class="title">
				<?php esc_html_e( 'File Integrity', 'health-check' ); ?>
			</span>
			<span class="icon"></span>
		</button>
	</dt>
	<dd id="health-check-accordion-block-1" role="region" aria-labelledby="health-check-accordion-heading-1" class="health-check-accordion-panel">
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
	</dd>

	<dt role="heading" aria-level="2">
		<button aria-expanded="false" class="health-check-accordion-trigger" aria-controls="health-check-accordion-block-2" id="health-check-accordion-heading-2" type="button">
			<span class="title">
				<?php esc_html_e( 'Mail Check', 'health-check' ); ?>
			</span>
			<span class="icon"></span>
		</button>
	</dt>
	<dd id="health-check-accordion-block-2" role="region" aria-labelledby="health-check-accordion-heading-2" class="health-check-accordion-panel" hidden="hidden">
		<div>
			<p>
				<?php _e( 'The Mail Check will invoke the <code>wp_mail()</code> function and check if it succeeds. We will use the E-mail address you have set up, but you can change it below if you like.', 'health-check' ); ?>
			</p>
			<form action="#" id="health-check-mail-check" method="POST">
				<table class="widefat tools-email-table">
					<tr>
						<td>
							<p>
								<?php
								$current_user = wp_get_current_user();
								?>
								<label for="email"><?php _e( 'E-mail', 'health-check' ); ?></label>
								<input type="text" name="email" id="email" value="<?php echo $current_user->user_email; ?>">
							</p>
						</td>
						<td>
							<p>
								<label for="email_message"><?php _e( 'Additional message', 'health-check' ); ?></label>
								<input type="text" name="email_message" id="email_message" value="">
							</p>
						</td>
					</tr>
				</table>
				<input type="submit" class="button button-primary" value="<?php esc_html_e( 'Send test mail', 'health-check' ); ?>">
			</form>

			<div id="tools-mail-check-response-holder">
				<span class="spinner"></span>
			</div>
		</div>
	</dd>
</dl>

<?php
include_once( HEALTH_CHECK_PLUGIN_DIRECTORY . '/modals/diff.php' );
