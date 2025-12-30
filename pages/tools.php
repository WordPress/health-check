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

<div class="health-check-body">
	<div class="notice notice-warning inline">
		<p>
			<?php esc_html_e( 'The additional tools for the Health Check plugin will be available as a standalone plugin in the future, since the primary Health Check features have been available in WordPress since version 5.2.', 'health-check' ); ?>
		</p>

		<p>
			<a href="https://wordpress.org/plugins/site-health-tools/"><?php esc_html_e( 'Site Health Tools plugin page', 'health-check' ); ?></a>
			-
			<a href="<?php echo esc_url( admin_url( 'plugin-install.php?tab=plugin-information&plugin=site-health-tools&TB_iframe=true&width=772&height=500' ) ); ?>" class="thickbox open-plugin-details-modal" data-title="Site Health Tools"><?php esc_html_e( 'Site Health Tools details', 'health-check' ); ?></a>
		</p>
	</div>

	<h2>
		<?php esc_html_e( 'Tools', 'health-check' ); ?>
	</h2>

	<div id="health-check-tools" role="presentation" class="health-check-accordion">
		<?php
		/**
		 * Filter the features available under the Tools tab.
		 *
		 * You may introduce your own, or modify the behavior of existing tools here,
		 * although we recommend not modifying anything provided by the plugin it self.
		 *
		 * Any interactive elements should be introduced using JavaScript and/or CSS, either
		 * inline, or by enqueueing them via the appropriate actions.
		 *
		 * @param array $args {
		 *     An unassociated array of tabs, listed in the order they are registered.
		 *
		 *     @type array $tab {
		 *         An associated array containing the tab title, and content.
		 *
		 *         @type string $label   A pre-escaped string used to label your tool section.
		 *         @type string $content The content of your tool tab, with any code you may need.
		 *     }
		 * }
		 */
		$tabs = apply_filters( 'health_check_tools_tab', array() );

		foreach ( $tabs as $count => $tab ) :
			?>

		<h3 class="health-check-accordion-heading">
			<button aria-expanded="false" class="health-check-accordion-trigger" aria-controls="health-check-accordion-block-<?php echo esc_attr( $count ); ?>" type="button">
				<span class="title">
					<?php echo $tab['label']; ?>
				</span>
				<span class="icon"></span>
			</button>
		</h3>
		<div id="health-check-accordion-block-<?php echo esc_attr( $count ); ?>" class="health-check-accordion-panel" hidden="hidden">
			<?php echo $tab['content']; ?>
		</div>

		<?php endforeach; ?>
	</div>

	<?php include_once( HEALTH_CHECK_PLUGIN_DIRECTORY . '/modals/diff.php' ); ?>
</div>
