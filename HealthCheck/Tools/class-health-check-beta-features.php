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
class Health_Check_Beta_Features extends Health_Check_Tool {

	public function __construct() {
		$this->label       = __( 'Beta features', 'health-check' );
		$this->description = __( 'The plugin may contain beta features, which you as the site owner can enable or disable as you wish.', 'health-check' );

		parent::__construct();

		add_action( 'admin_init', array( $this, 'toggle_beta_features' ) );
	}

	public function toggle_beta_features() {
		if ( ! isset( $_GET['health-check-beta-features'] ) ) {
			return;
		}

		if ( ! isset( $_GET['_wpnonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'health-check-beta-features' ) ) {
			return;
		}

		if ( 'enable' === $_GET['health-check-beta-features'] ) {
			update_option( 'health-check-beta-features', true );
		} else {
			update_option( 'health-check-beta-features', false );
		}

		wp_safe_redirect( admin_url( 'site-health.php?tab=tools' ) );
	}

	public function tab_content() {
		$feature_status = get_option( 'health-check-beta-features', false );

		if ( ! $feature_status ) {
			printf(
				'<a href="%s" class="button button-primary">%s</a>',
				esc_url( wp_nonce_url( add_query_arg( 'health-check-beta-features', 'enable' ), 'health-check-beta-features' ) ),
				esc_html__( 'Enable beta features', 'health-check' )
			);
		} else {
			printf(
				'<a href="%s" class="button button-primary">%s</a>',
				esc_url( wp_nonce_url( add_query_arg( 'health-check-beta-features', 'disable' ), 'health-check-beta-features' ) ),
				esc_html__( 'Disable beta features', 'health-check' )
			);
		}
	}

}

new Health_Check_Beta_Features();
