<?php

// Check that the file is not accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'We\'re sorry, but you can not directly access this file.' );
}

class Health_Check_Dashboard_Widget {

	public function __construct() {
		add_action( 'wp_dashboard_setup', array( $this, 'dashboard_setup' ) );
	}

	function dashboard_setup() {
		global $wp_meta_boxes;

		if ( ! isset( $wp_meta_boxes['dashboard']['normal']['core']['dashboard_site_health'] ) ) {
			wp_add_dashboard_widget(
				'dashboard_site_health',
				__( 'Site Health Status', 'health-check' ),
				array( $this, 'widget_render' )
			);
		} else {
			$wp_meta_boxes['dashboard']['normal']['core']['dashboard_site_health']['callback'] = array( $this, 'widget_render' );
		}
	}

	function widget_render() {
		$get_issues = get_transient( 'health-check-site-status-result' );

		if ( false !== $get_issues ) {
			$issue_counts = json_decode( $get_issues );
		} else {
			$issue_counts = (object) array(
				'good'        => 0,
				'recommended' => 0,
				'critical'    => 0,
			);
		}

		$issues_total = $issue_counts->recommended + $issue_counts->critical;
		?>
		<div class="health-check-title-section site-health-progress-wrapper loading hide-if-no-js">
			<div class="site-health-progress">
				<svg role="img" aria-hidden="true" focusable="false" width="100%" height="100%" viewBox="0 0 200 200" version="1.1" xmlns="http://www.w3.org/2000/svg">
					<circle r="90" cx="100" cy="100" fill="transparent" stroke-dasharray="565.48" stroke-dashoffset="0"></circle>
					<circle id="bar" r="90" cx="100" cy="100" fill="transparent" stroke-dasharray="565.48" stroke-dashoffset="0"></circle>
				</svg>
			</div>
			<div class="site-health-progress-label">
				<?php if ( false === $get_issues ) : ?>
					<?php _e( 'No information yet&hellip;', 'health-check' ); ?>
				<?php else : ?>
					<?php _e( 'Results are still loading&hellip;', 'health-check' ); ?>
				<?php endif; ?>
			</div>
		</div>

		<?php if ( false === $get_issues ) : ?>
		<p>
			<?php _e( 'No Site Health information has been gathered yet, you can do so by visiting the Site Health page, alternatively the checks will run automatically once every week.', 'health-check' ); ?>
		</p>

		<p>
			<?php
			printf(
				// translators: %s: URL for the Site Health page.
				__( '<a href="%s">Visit the Site Health page</a> to gather information on about your site..', 'health-check' ),
				esc_url( admin_url( 'tools.php?page=health-check' ) )
			);
			?>
		</p>

		<?php else : ?>
		<p>
			<?php if ( $issue_counts->critical > 0 ) : ?>
				<?php _e( 'Your site has critical issues that should be addressed as soon as possible to improve the performance or security of your website.', 'health-check' ); ?>
			<?php elseif ( $issues_total <= 0 ) : ?>
				<?php _e( 'Great job! Your site currently passes all site health checks.', 'health-check' ); ?>
			<?php else : ?>
				<?php _e( 'Your site scores pretty well on the Health Check, but there are still some things you can do to improve the performance and security of your website.', 'health-check' ); ?>
			<?php endif; ?>
		</p>
		<?php endif; ?>

		<?php if ( $issues_total > 0 && false !== $get_issues ) : ?>
		<p>
			<?php
			printf(
				// translators: 1: Count of issues. 2: URL for the Site Health page.
				__( 'Take a look at the <strong>%1$d items</strong> on the <a href="%2$s">Site Health Check status page</a>.', 'health-check' ),
				$issues_total,
				esc_url( admin_url( 'tools.php?page=health-check' ) )
			);
			?>
		</p>
		<?php endif; ?>

		<?php
	}
}
