<?php

/**
 * Checks if wp_mail() works.
 *
 * @package Health Check
 */

// Make sure the file is not directly accessible.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'We\'re sorry, but you can not directly access this file.' );
}

/**
 * Class Mail Check
 */
class Health_Check_Mail_Check extends Health_Check_Tool {

	public function __construct() {
		$this->label       = __( 'Mail Check', 'health-check' );
		$this->description = __( 'The Mail Check will invoke the <code>wp_mail()</code> function and check if it succeeds. We will use the E-mail address you have set up, but you can change it below if you like.', 'health-check' );

		add_action( 'wp_ajax_health-check-mail-check', array( $this, 'run_mail_check' ) );

		parent::__construct();
	}

	/**
	 * Checks if wp_mail() works.
	 *
	 * @uses sanitize_email()
	 * @uses wp_mail()
	 * @uses wp_send_json_success()
	 * @uses wp_die()
	 *
	 * @return void
	 */
	static function run_mail_check() {
		check_ajax_referer( 'health-check-mail-check' );

		if ( ! current_user_can( 'view_site_health_checks' ) ) {
			wp_send_json_error();
		}

		$output        = '';
		$sendmail      = false;
		$email         = sanitize_email( $_POST['email'] );
		$email_message = sanitize_text_field( $_POST['email_message'] );
		$wp_address    = get_bloginfo( 'url' );
		$wp_name       = get_bloginfo( 'name' );
		$date          = date_i18n( get_option( 'date_format' ), current_time( 'timestamp' ) ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
		$time          = date_i18n( get_option( 'time_format' ), current_time( 'timestamp' ) ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested

		// translators: %s: website url.
		$email_subject = sprintf( esc_html__( 'Health Check – Test Message from %s', 'health-check' ), $wp_address );

		$email_body = sprintf(
			// translators: %1$s: website name. %2$s: website url. %3$s: The date the message was sent. %4$s: The time the message was sent.
			__( 'Hi! This test message was sent by the Health Check plugin from %1$s (%2$s) on %3$s at %4$s. Since you’re reading this, it obviously works.', 'health-check' ),
			$wp_name,
			$wp_address,
			$date,
			$time,
			$email_message
		);

		if ( ! empty( $email_message ) ) {
			$email_body .= "\n\n" . sprintf(
				// translators: %s: The custom message that may be included with the email.
				__( 'Additional message from admin: %s', 'health-check' ),
				$email_message
			);
		}

		$sendmail = wp_mail( $email, $email_subject, $email_body );

		if ( ! empty( $sendmail ) ) {
			$output .= '<div class="notice notice-success inline"><p>';
			$output .= __( 'We have just sent an e-mail using <code>wp_mail()</code> and it seems to work. Please check your inbox and spam folder to see if you received it.', 'health-check' );
			$output .= '</p></div>';
		} else {
			$output .= '<div class="notice notice-error inline"><p>';
			$output .= esc_html__( 'It seems there was a problem sending the e-mail.', 'health-check' );
			$output .= '</p></div>';
		}

		$response = array(
			'message' => $output,
		);

		wp_send_json_success( $response );

		wp_die();

	}

	/**
	 * Add the Mail Checker to the tools tab.
	 *
	 * @return void
	 */
	public function tab_content() {
		?>
			<form action="#" id="health-check-mail-check" method="POST">
				<table class="widefat tools-email-table">
					<tr>
						<td>
							<p>
								<?php
								$current_user = wp_get_current_user();
								?>
								<label for="email"><?php _e( 'Email', 'health-check' ); ?></label>
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
		<?php
	}
}

new Health_Check_Mail_Check();
