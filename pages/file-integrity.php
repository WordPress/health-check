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

/**
 * Adds the jQuery required to initiate the files check.
 */
function health_check_file_integrity_ajax() {
?>
	<script type="text/javascript" >
		jQuery( document ).ready( function($) {
			jQuery( '#health-check-file-integrity' ).submit( function( e ) {
				e.preventDefault();
				window.location.href = window.location.href + "&check";
			} );
		} );
	</script>
<?php
}
add_action( 'admin_footer', 'health_check_file_integrity_ajax' );
?>

	<div class="notice notice-info inline">
		<p>
			<?php esc_html_e( 'The File Integrity checks all the core files with the Checksums provided by the WordPress API to see if they are intact.', 'health-check' ); ?>
		</p>
	</div>
	<form action="#" id="health-check-file-integrity" method="POST">
		<input type="submit" class="button button-primary" value="<?php esc_html_e( 'Check the Files Integrity' ); ?>">
	</form>

<?php
if ( isset( $_GET['check'] ) ) {
	// Setup variables.
	$wpversion = get_bloginfo( 'version' );
	$wplocale  = get_locale();
	$filepath  = ABSPATH;
	$files     = array();

	// Setup API Call.
	$checksumapi = wp_remote_get( 'https://api.wordpress.org/core/checksums/1.0/?version=' . $wpversion . '&locale=' . $wplocale );

	// Encode the API response body.
	$checksumapibody = json_decode( wp_remote_retrieve_body( $checksumapi ), true );

	// Parse the results.
	foreach ( $checksumapibody['checksums'] as $file => $checksum ) {
		// Check the files.
		if ( md5_file( $filepath . $file ) !== $checksum ) {
			array_push( $files, $file );
		}
	}

	// Display success message.
	if ( empty( $files ) ) {
?>

	<div class="notice notice-success inline">
		<p>
			<?php	esc_html_e( 'All files passed the check. Everything seems to be ok!', 'health-check' ); ?>
		</p>
	</div>

	<?php
	} else {
		// Display error message and files table.
	?>

	<div class="notice notice-error inline">
		<p>
			<?php	esc_html_e( 'It appears that some files have been tampered with. Please either updated WordPress or manually replace them and run the File Integrity check again.', 'health-check' ); ?>
		</p>
	</div>
	<table class="widefat striped file-integrity-table">
		<thead>
			<tr>
				<th>
					<?php esc_html_e( 'Status' ); ?>
				</th>
				<th>
					<?php esc_html_e( 'File' ); ?>
				</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<td>
					<?php esc_html_e( 'Status' ); ?>
				</td>
				<td>
					<?php esc_html_e( 'File' ); ?>
				</td>
			</tr>
		</tfoot>
		<tbody>
			<?php
			foreach ( $files as $tampered ) {
				echo '<tr><td><span class="error"></span></td><td>' . esc_attr( $filepath ) . esc_attr( $tampered ) . '</td></tr>';
			}
			?>
		</tbody>
	</table>
<?php
	}
}
?>
