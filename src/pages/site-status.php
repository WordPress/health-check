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

	<?php
	include_once( HEALTH_CHECK_PLUGIN_DIRECTORY . '/modals/js-result-warnings.php' );
