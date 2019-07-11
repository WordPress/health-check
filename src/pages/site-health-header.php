<?php
/**
 * The header template, printed on every Health Check related page.
 *
 * @package Health Check
 */

// Make sure the file is not directly accessible.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'We\'re sorry, but you can not directly access this file.' );
}
?>

<div class="health-check-header">
	<div class="health-check-title-section">
		<h1>
			<?php esc_html_e( 'Site Health', 'health-check' ); ?>
		</h1>
	</div>

	<div class="health-check-title-section site-health-progress-wrapper loading hide-if-no-js">
		<div class="site-health-progress">
			<svg role="img" aria-hidden="true" focusable="false" width="100%" height="100%" viewBox="0 0 200 200" version="1.1" xmlns="http://www.w3.org/2000/svg">
				<circle r="90" cx="100" cy="100" fill="transparent" stroke-dasharray="565.48" stroke-dashoffset="0"></circle>
				<circle id="bar" r="90" cx="100" cy="100" fill="transparent" stroke-dasharray="565.48" stroke-dashoffset="0"></circle>
			</svg>
		</div>
		<div class="site-health-progress-label">
			<?php _e( 'Results are still loading&hellip;', 'health-check' ); ?>
		</div>
	</div>

	<nav class="health-check-tabs-wrapper hide-if-no-js" aria-label="<?php esc_attr_e( 'Secondary menu', 'health-check' ); ?>">
		<?php
		$tabs        = Health_Check::tabs();
		$current_tab = Health_Check::current_tab();

		foreach ( $tabs as $tab => $label ) {
			printf(
				'<a href="%s" class="health-check-tab health-check-%s-tab %s"%s>%s</a>',
				sprintf(
					'%s&tab=%s',
					menu_page_url( 'health-check', false ),
					$tab
				),
				esc_attr( $tab ),
				( $current_tab === $tab ? 'active' : '' ),
				( $current_tab === $tab ? ' aria-current="true"' : '' ),
				$label
			);
		}
		?>
	</nav>
	<div class="wp-clearfix"></div>
</div>

<hr class="wp-header-end">

<div class="notice notice-error hide-if-js">
	<p><?php _e( 'The Site Health check requires JavaScript.', 'health-check' ); ?></p>
</div>

<div class="health-check-body hide-if-no-js">
