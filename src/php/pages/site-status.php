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

<div class="site-status-all-clear">
	<p class="icon wp-logo">
		<span class="dashicons dashicons-wordpress"></span>
	</p>

	<h2 class="encouragement">
		<?php _e( 'WordPress update needed!', 'health-check' ); ?>
	</h2>

	<p>
		<?php
		printf(
			// translators: %s: The current WordPress version used on this site.
			__( 'You are running an older version of WordPress, version %s. The Site Health features from this plugin were added to version 5.2 of WordPress.', 'health-check' ),
			get_bloginfo( 'version' )
		);
		?>
	</p>

	<br>

	<p>
		<?php _e( 'Due to this, some functionality has been removed from the plugin, you can find these features in a more recent version of WordPress it self.', 'health-check' ); ?>
	</p>

	<br>

	<p>
		<?php _e( 'The plugin itself still offers you the ability to troubleshoot issues with your installation, and various tools associated with this functionality.', 'health-check' ); ?>
	</p>
</div>
