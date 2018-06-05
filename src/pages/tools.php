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

<dl id="health-check-tools" role="presentation" class="health-check-accordion">
	<?php
	$tabs = apply_filters( 'health_check_tools_tab', array() );

	foreach ( $tabs as $count => $tab ) :
	?>

	<dt role="heading" aria-level="2">
		<button aria-expanded="false" class="health-check-accordion-trigger" aria-controls="health-check-accordion-block-<?php echo esc_attr( $count ); ?>" id="health-check-accordion-heading-<?php echo esc_attr( $count ); ?>" type="button">
			<span class="title">
				<?php echo $tab['label']; ?>
			</span>
			<span class="icon"></span>
		</button>
	</dt>
	<dd id="health-check-accordion-block-<?php echo esc_attr( $count ); ?>" role="region" aria-labelledby="health-check-accordion-heading-<?php echo esc_attr( $count ); ?>" class="health-check-accordion-panel" hidden="hidden">
		<?php echo $tab['content']; ?>
	</dd>

	<?php endforeach; ?>
</dl>

<?php
include_once( HEALTH_CHECK_PLUGIN_DIRECTORY . '/modals/diff.php' );
