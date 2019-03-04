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

global $health_check_site_status;
?>

<h2>
	<?php esc_html_e( 'Site Health Status', 'health-check' ); ?>
</h2>

<div class="issues-wrapper" id="health-check-issues-critical">
	<h3>
		<span class="issue-count">0</span> <?php esc_html_e( 'Critical issues', 'health-check' ); ?>
	</h3>

	<dl id="health-check-site-status-critical" role="presentation" class="health-check-accordion issues"></dl>
</div>

<div class="issues-wrapper" id="health-check-issues-recommended">
	<h3>
		<span class="issue-count">0</span> <?php esc_html_e( 'Recommended improvements', 'health-check' ); ?>
	</h3>

	<dl id="health-check-site-status-recommended" role="presentation" class="health-check-accordion issues"></dl>
</div>

<div class="view-more">
	<button type="button" class="button button-link site-health-view-passed" aria-expanded="false">
		<?php esc_html_e( 'Show passed tests', 'health-check' ); ?>
	</button>
</div>

<div class="issues-wrapper" id="health-check-issues-good">
	<h3>
		<span class="issue-count">0</span> <?php esc_html_e( 'Items with no issues detected', 'health-check' ); ?>
	</h3>

	<dl id="health-check-site-status-good" role="presentation" class="health-check-accordion issues"></dl>
</div>

<?php
include_once( HEALTH_CHECK_PLUGIN_DIRECTORY . '/modals/js-result-warnings.php' );
