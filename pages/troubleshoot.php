<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'We\'re sorry, but you can not directly access this file.' );
}

?>
<div class="notice notice-warning inline">
	<p>
		<?php esc_html_e( 'When troubleshooting issues on your site, you are likely to be told to disable all plugins.', 'health-check' ); ?>
		<?php esc_html_e( 'Understandably, you do not wish to do so as it may affect your site visitors, leaving them with lost functionality.', 'health-check' ); ?>
	</p>

	<p>
		<?php esc_html_e( 'By enabling the troubleshooting mode, all plugins will appear deactivated for your current logged in session, but all other users will see your site as usual.', 'health-check' ); ?>
	</p>

	<p>
		<?php esc_html_e( 'Please note, that due to how Must Use plugins work, any such plugin will not be disabled for the troubleshooting session.', 'health-check' ); ?>
	</p>
</div>

<?php Health_Check_Troubleshoot::show_enable_troubleshoot_form(); ?>
