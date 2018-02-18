<?php

/**
 * Checks if wp_mail() works.
 *
 * @package Health Check
 */

/**
 * Class Mail Check
 */
class Health_Check_Mail_Check {

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
		$output       = '';
		$sendmail     = false;
		$email        = sanitize_email( $_POST['email'] );
		$emailsubject = __( 'This is a test message from Health Check.', 'health-check' );
		$emailbody    = __( 'This is a test message from Health Check.', 'health-check' );
		$sendmail     = wp_mail( $email, $emailsubject, $emailbody );

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

}
