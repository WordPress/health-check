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

<div class="site-status-all-clear hide">
	<p class="icon">
		<span class="dashicons dashicons-yes"></span>
	</p>

	<p class="encouragement">
		<?php _e( 'Great job!', 'health-check' ); ?>
	</p>

	<p>
		<?php _e( 'Everything is running smoothly here.', 'health-check' ); ?>
	</p>
</div>

<div class="site-status-has-issues">
	<h2>
		<?php _e( 'Site Health Status', 'health-check' ); ?>
	</h2>

	<p><?php _e( 'The site health check shows critical information about your WordPress configuration and items that require your attention.', 'health-check' ); ?></p>

	<div class="site-health-issues-wrapper" id="health-check-issues-critical">
		<h3 class="site-health-issue-count-title">
			<?php
			/* translators: %s: number of critical issues found */
			printf( _n( '%s Critical issue', '%s Critical issues', 0, 'health-check' ), '<span class="issue-count">0</span>' );
			?>
		</h3>

		<div id="health-check-site-status-critical" class="health-check-accordion issues"></div>
	</div>

	<div class="site-health-issues-wrapper" id="health-check-issues-recommended">
		<h3 class="site-health-issue-count-title">
			<?php
			/* translators: %s: number of recommended improvements */
			printf( _n( '%s Recommended improvement', '%s Recommended improvements', 0, 'health-check' ), '<span class="issue-count">0</span>' );
			?>
		</h3>

		<div id="health-check-site-status-recommended" class="health-check-accordion issues"></div>
	</div>
</div>

<div class="site-health-view-more">
	<button type="button" class="button site-health-view-passed" aria-expanded="false" aria-controls="health-check-issues-good">
		<?php _e( 'Passed tests', 'health-check' ); ?>
		<span class="icon"></span>
	</button>
</div>

<div class="site-health-issues-wrapper hidden" id="health-check-issues-good">
	<h3 class="site-health-issue-count-title">
		<?php
		/* translators: %s: number of items with no issues */
		printf( _n( '%s Item with no issues detected', '%s Items with no issues detected', 0, 'health-check' ), '<span class="issue-count">0</span>' );
		?>
	</h3>

	<div id="health-check-site-status-good" class="health-check-accordion issues"></div>
</div>

<script id="tmpl-health-check-issue" type="text/template">
	<h4 class="health-check-accordion-heading">
		<button aria-expanded="false" class="health-check-accordion-trigger" aria-controls="health-check-accordion-block-{{ data.test }}" type="button">
			<span class="title">{{ data.label }}</span>
			<span class="badge {{ data.badge.color }}">{{ data.badge.label }}</span>
			<span class="icon"></span>
		</button>
	</h4>
	<div id="health-check-accordion-block-{{ data.test }}" class="health-check-accordion-panel" hidden="hidden">
		{{{ data.description }}}
		<div class="actions">
			<p class="button-container">{{{ data.actions }}}</p>
		</div>
	</div>
</script>

<?php
include_once( HEALTH_CHECK_PLUGIN_DIRECTORY . '/modals/js-result-warnings.php' );
