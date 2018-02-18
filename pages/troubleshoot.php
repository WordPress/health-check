<?php
/**
 * The Troubleshooting tab contents.
 *
 * @package Health Check
 */

// Make sure the file is not directly accessible.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'We\'re sorry, but you can not directly access this file.' );
}

?>
<div class="notice notice-warning inline">
	<p>
		<?php esc_html_e( 'When troubleshooting issues on your site, you are likely to be told to disable all plugins and switch to the default theme.', 'health-check' ); ?>
		<?php esc_html_e( 'Understandably, you do not wish to do so as it may affect your site visitors, leaving them with lost functionality.', 'health-check' ); ?>
	</p>

	<p>
		<?php esc_html_e( 'By enabling the Troubleshooting Mode, all plugins will appear inactive and your site will switch to the default theme only for you. All other users will see your site as usual.', 'health-check' ); ?>
	</p>

	<p>
		<?php esc_html_e( 'A Troubleshooting Mode menu is added to your admin bar, which will allow you to enable plugins individually, switch back to your current theme, and disable Troubleshooting Mode.', 'health-check' ); ?>
	</p>

	<p>
		<?php esc_html_e( 'Please note, that due to how Must Use plugins work, any such plugin will not be disabled for the troubleshooting session.', 'health-check' ); ?>
	</p>
</div>

<?php
Health_Check_Troubleshoot::show_enable_troubleshoot_form();

if ( ! Health_Check_Troubleshoot::has_seen_warning() ) {
	include_once( HEALTH_CHECK_PLUGIN_DIRECTORY . '/modals/backup-warning.php' );
}
