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

	<div class="notice notice-info inline">
		<p>
			<?php esc_html_e( 'The health check shows critical information about your WordPress configuration and items that require your attention.', 'health-check' ); ?>
		</p>
	</div>

	<dl id="health-check-tools" role="presentation" class="health-check-accordion">
		<dt role="heading" aria-level="2">
			<button aria-expanded="true" class="health-check-accordion-trigger" aria-controls="health-check-accordion-block-error" id="health-check-accordion-heading-error" type="button">
			<span class="title">
				<span class="error"></span> <?php esc_html_e( 'Critical items that need immediate attention', 'health-check' ); ?>
			</span>
				<span class="icon"></span>
			</button>
		</dt>
		<dd id="health-check-accordion-block-error" role="region" aria-labelledby="health-check-accordion-heading-error" class="health-check-accordion-panel">
			<table class="widefat striped health-check-table">
				<tbody>
					<tr class="no-entries"><td><?php esc_html_e( 'There are no critical items that needs immediate attention at this time.', 'health-check' ); ?></td></tr>
				</tbody>
			</table>
		</dd>

		<dt role="heading" aria-level="2">
			<button aria-expanded="true" class="health-check-accordion-trigger" aria-controls="health-check-accordion-block-warning" id="health-check-accordion-heading-warning" type="button">
			<span class="title">
				<span class="warning"></span> <?php esc_html_e( 'Non-critical items that may improve your experience', 'health-check' ); ?>
			</span>
				<span class="icon"></span>
			</button>
		</dt>
		<dd id="health-check-accordion-block-warning" role="region" aria-labelledby="health-check-accordion-heading-warning" class="health-check-accordion-panel">
			<table class="widefat striped health-check-table">
				<tbody>
					<tr class="no-entries"><td><?php esc_html_e( 'There are no pending improvements at this time.', 'health-check' ); ?></td></tr>
				</tbody>
			</table>
		</dd>

		<dt role="heading" aria-level="2">
			<button aria-expanded="false" class="health-check-accordion-trigger" aria-controls="health-check-accordion-block-good" id="health-check-accordion-heading-good" type="button">
			<span class="title">
				<span class="good"></span> <?php esc_html_e( 'Items with no issues detected', 'health-check' ); ?>
			</span>
				<span class="icon"></span>
			</button>
		</dt>
		<dd id="health-check-accordion-block-good" role="region" aria-labelledby="health-check-accordion-heading-good" class="health-check-accordion-panel" hidden="hidden">
			<table class="widefat striped health-check-table">
				<tbody>
					<tr class="no-entries"><td><?php esc_html_e( 'None of the site tests are passing at this time.', 'health-check' ); ?></td></tr>
				</tbody>
			</table>
		</dd>
	</dl>

	<div id="health-check-site-status-test-wrapper">
		<table class="widefat striped health-check-table">
			<tbody>
			<?php
			$tests = Health_Check_Site_Status::get_tests();
			foreach ( $tests['direct'] as $test ) :
				?>
				<tr>
					<td><?php echo esc_html( $test['label'] ); ?></td>
					<td class="" data-site-status="<?php echo esc_attr( $test['test'] ); ?>">
						<?php
						$test_function = sprintf(
							'test_%s',
							$test['test']
						);

						if ( method_exists( $health_check_site_status, $test_function ) && is_callable( array( $health_check_site_status, $test_function ) ) ) {
							call_user_func( array( $health_check_site_status, $test_function ) );
						}
						?>
					</td>
				</tr>
			<?php endforeach; ?>

			<?php foreach ( $tests['async'] as $test ) : ?>
				<tr>
					<td><?php echo esc_html( $test['label'] ); ?></td>
					<td class="health-check-site-status-test" data-site-status="<?php echo esc_attr( $test['test'] ); ?>">
						<span class="spinner is-active"></span>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>

	<?php
	include_once( HEALTH_CHECK_PLUGIN_DIRECTORY . '/modals/js-result-warnings.php' );
