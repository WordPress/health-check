<div class="site-status-all-clear">
	<p class="icon wp-logo">
		<span class="dashicons dashicons-wordpress"></span>
	</p>

	<p class="encouragement">
		<?php _e( 'WordPress update needed!', 'health-check' ); ?>
	</p>

	<p>
		<?php
			printf(
				// translators: %s: The current WordPress version used on this site.
				__( 'You are running WordPress version, %s. To fully utilize the Site Health features, you need WordPress 5.2 or newer.', 'health-check' ),
				get_bloginfo( 'version' )
			);
		?>
	</p>
</div>
