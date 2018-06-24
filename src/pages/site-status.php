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
		<p>
			<?php esc_html_e( 'The health check shows critical information about your WordPress configuration and items that require your attention.', 'health-check' ); ?>
		</p>
	</div>

	<table class="widefat striped health-check-table">
		<tbody>
			<tr>
				<td><?php esc_html_e( 'WordPress Version', 'health-check' ); ?></td>
				<td class="health-check-site-status-test" data-site-status="wordpress_version">
					<span class="spinner is-active"></span>
				</td>
			</tr>

			<tr>
				<td><?php esc_html_e( 'Plugin Versions', 'health-check' ); ?></td>
				<td class="health-check-site-status-test" data-site-status="plugin_version">
					<span class="spinner is-active"></span>
				</td>
			</tr>

			<tr>
				<td><?php esc_html_e( 'Theme Versions', 'health-check' ); ?></td>
				<td class="health-check-site-status-test" data-site-status="theme_version">
					<span class="spinner is-active"></span>
				</td>
			</tr>

			<tr>
				<td><?php esc_html_e( 'PHP Version', 'health-check' ); ?></td>
				<td class="health-check-site-status-test" data-site-status="php_version">
					<span class="spinner is-active"></span>
				</td>
			</tr>

			<tr>
				<td>
					<?php esc_html_e( 'Database Server version', 'health-check' ); ?>
				</td>
				<td class="health-check-site-status-test" data-site-status="sql_server">
					<span class="spinner is-active"></span>
				</td>
			</tr>

			<tr>
				<td><?php esc_html_e( 'JSON Extension', 'health-check' ); ?></td>
				<td class="health-check-site-status-test" data-site-status="json_extension">
					<span class="spinner is-active"></span>
				</td>
			</tr>

			<tr>
				<td><?php esc_html_e( 'MySQL utf8mb4 support', 'health-check' ); ?></td>
				<td class="health-check-site-status-test" data-site-status="utf8mb4_support">
					<span class="spinner is-active"></span>
				</td>
			</tr>

			<tr>
				<td><?php esc_html_e( 'Communication with WordPress.org', 'health-check' ); ?></td>
				<td class="health-check-site-status-test" data-site-status="dotorg_communication">
					<span class="spinner is-active"></span>
				</td>
			</tr>

			<tr>
				<td><?php esc_html_e( 'HTTPS status', 'health-check' ); ?></td>
				<td class="health-check-site-status-test" data-site-status="https_status">
					<span class="spinner is-active"></span>
				</td>
			</tr>

			<tr>
				<td><?php esc_html_e( 'Secure communication', 'health-check' ); ?></td>
				<td class="health-check-site-status-test" data-site-status="ssl_support">
					<span class="spinner is-active"></span>
				</td>
			</tr>

			<tr>
				<td><?php esc_html_e( 'Scheduled events', 'health-check' ); ?></td>
				<td class="health-check-site-status-test" data-site-status="scheduled_events">
					<span class="spinner is-active"></span>
				</td>
			</tr>

			<tr>
				<td><?php esc_html_e( 'Background updates', 'health-check' ); ?></td>
				<td class="health-check-site-status-test" data-site-status="background_updates">
					<span class="spinner is-active"></span>
				</td>
			</tr>

			<tr>
				<td><?php esc_html_e( 'Loopback request', 'health-check' ); ?></td>
				<td class="health-check-site-status-test" data-site-status="loopback_requests">
					<span class="spinner is-active"></span>
				</td>
			</tr>
		</tbody>
	</table>

	<?php
	include_once( HEALTH_CHECK_PLUGIN_DIRECTORY . '/modals/js-result-warnings.php' );
