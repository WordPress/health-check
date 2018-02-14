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
				window.location.href = window.location.href + "&fileintegrity";
			} );
		} );
	</script>
<?php
}
add_action( 'admin_footer', 'health_check_file_integrity_ajax' );
?>

	<div class="notice notice-info inline">
		<h2><?php esc_html_e( 'File Integrity' ); ?></h2>
		<p>
			<?php _e( 'The File Integrity checks all the core files with the <code>checksums</code> provided by the WordPress API to see if they are intact.', 'health-check' ); ?>
		</p>
		<form action="#" id="health-check-file-integrity" method="POST">
			<input type="submit" class="button button-primary" value="<?php esc_html_e( 'Check the Files Integrity', 'health-check' ); ?>">
		</form>
	</div>
	<div class="notice notice-info inline">
		<h2><?php esc_html_e( 'Mail Check' ); ?></h2>
		<p>
			<?php _e( 'The Mail Check will invoke the <code>wp_mail()</code> function and check if it success.', 'health-check' ); ?>
		</p>
		<form action="#" id="health-check-file-integrity" method="POST">
			<input type="submit" class="button button-primary" value="<?php esc_html_e( 'Check the Files Integrity', 'health-check' ); ?>">
		</form>
	</div>

<?php
if ( isset( $_GET['fileintegrity'] ) ) {
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
		if ( file_exists( $filepath . $file ) && md5_file( $filepath . $file ) !== $checksum ) {
			$reason = esc_html__( 'Content changed', 'health-check' );
			array_push( $files, array( $file, $reason ) );
		} elseif ( ! file_exists( $filepath . $file ) ) {
			$reason = esc_html__( 'File not found', 'health-check' );
			array_push( $files, array( $file, $reason ) );
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
			<?php	_e( 'It appears that some files have been tampered with. Please either update WordPress or manually replace the files you see on the list and run the <code>File Integrity</code> check again.', 'health-check' ); ?>
		</p>
	</div>
	<table class="widefat striped file-integrity-table">
		<thead>
			<tr>
				<th>
					<?php esc_html_e( 'Status', 'health-check' ); ?>
				</th>
				<th>
					<?php esc_html_e( 'File', 'health-check' ); ?>
				</th>
				<th>
					<?php esc_html_e( 'Reason', 'health-check' ); ?>
				</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<td>
					<?php esc_html_e( 'Status', 'health-check' ); ?>
				</td>
				<td>
					<?php esc_html_e( 'File', 'health-check' ); ?>
				</td>
				<td>
					<?php esc_html_e( 'Reason', 'health-check' ); ?>
				</td>
			</tr>
		</tfoot>
		<tbody>
			<?php
			foreach ( $files as $tampered ) {
				echo '<tr>';
				echo '<td><span class="error"></span></td>';
				echo '<td>' . esc_attr( $filepath ) . esc_attr( $tampered[0] ) . '</td>';
				echo '<td>' . esc_attr( $tampered[1] ) . '</td>';
				echo '</tr>';
			}
			?>
		</tbody>
	</table>
<?php
	}
}
?>
