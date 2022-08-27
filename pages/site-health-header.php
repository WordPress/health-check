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

	<nav class="health-check-tabs-wrapper hide-if-no-js" aria-label="<?php esc_attr_e( 'Secondary menu', 'health-check' ); ?>">
		<?php
		$tabs        = Health_Check::tabs();
		$current_tab = Health_Check::current_tab();

		foreach ( $tabs as $tab => $label ) {
			if ( ! empty( $tab ) ) {
				$url = add_query_arg(
					array(
						'page' => 'site-health',
						'tab'  => $tab,
					),
					admin_url( 'tools.php?page=site-health' )
				);
			} else {
				$url = admin_url( 'tools.php?page=site-health' );
			}

			printf(
				'<a href="%s" class="health-check-tab health-check-%s-tab %s"%s>%s</a>',
				esc_url( $url ),
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
