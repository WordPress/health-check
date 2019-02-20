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

    <div class="issues-wrapper" id="health-check-issues-critical">
        <h2>
            <span class="issue-count">0</span> <?php esc_html_e( 'Critical issues', 'health-check' ); ?>
        </h2>
        <div class="issues"></div>
    </div>

    <div class="issues-wrapper" id="health-check-issues-recommended">
        <h2>
            <span class="issue-count">0</span> <?php esc_html_e( 'Recommended improvements', 'health-check' ); ?>
        </h2>
        <div class="issues"></div>
    </div>

    <div class="issues-wrapper" id="health-check-issues-good">
        <h2>
            <span class="issue-count">0</span> <?php esc_html_e( 'Items with no issues detected', 'health-check' ); ?>
        </h2>
        <div class="issues"></div>
    </div>

	<?php
	include_once( HEALTH_CHECK_PLUGIN_DIRECTORY . '/modals/js-result-warnings.php' );
