<?php
/**
 * Debug tab contents.
 *
 * @package Health Check
 */

// Make sure the file is not directly accessible.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'We\'re sorry, but you can not directly access this file.' );
}

Health_Check_Debug_Data::check_for_updates();

$info = Health_Check_Debug_Data::debug_data();
?>

<h2>
	<?php esc_html_e( 'Site Info', 'health-check' ); ?>
</h2>

<textarea id="system-information-default-copy-field" class="system-information-copy-wrapper" rows="10"><?php Health_Check_Debug_Data::textarea_format( $info ); ?></textarea>

<?php
if ( 'en_US' !== get_locale() && version_compare( get_bloginfo( 'version' ), '4.7', '>=' ) ) :

	$english_info = Health_Check_Debug_Data::debug_data( 'en_US' );

	// Workaround for locales not being properly loaded back, see issue #30 on GitHub.
	if ( ! is_textdomain_loaded( 'health-check' ) && _get_path_to_translation( 'health-check' ) ) {
		load_textdomain( 'health-check', _get_path_to_translation( 'health-check' ) );
	}
	?>
	<textarea id="system-information-english-copy-field" class="system-information-copy-wrapper" rows="10"><?php Health_Check_Debug_Data::textarea_format( $english_info ); ?></textarea>

<?php endif; ?>

<p>
	<?php esc_html_e( 'You can export the information on this page so it can be easily copied and pasted in support requests such as on the WordPress.org forums, or shared with your website / theme / plugin developers.', 'health-check' ); ?>
</p>

<div class="copy-button-wrapper">
	<button type="button" class="button button-primary health-check-copy-field" data-copy-field="default"><?php esc_html_e( 'Copy to clipboard', 'health-check' ); ?></button>
	<span class="copy-field-success">Copied!</span>
</div>
<?php if ( 'en_US' !== get_locale() && version_compare( get_bloginfo( 'version' ), '4.7', '>=' ) ) : ?>
	<div class="copy-button-wrapper">
		<button type="button" class="button health-check-copy-field" data-copy-field="english"><?php esc_html_e( 'Copy to clipboard (English)', 'health-check' ); ?></button>
		<span class="copy-field-success">Copied!</span>
	</div>
<?php endif; ?>

<dl id="health-check-debug" role="presentation" class="health-check-accordion">

<?php
foreach ( $info as $section => $details ) {
	if ( ! isset( $details['fields'] ) || empty( $details['fields'] ) ) {
		continue;
	}
	?>
	<dt role="heading" aria-level="3">
		<button aria-expanded="false" class="health-check-accordion-trigger" aria-controls="health-check-accordion-block-<?php echo esc_attr( $section ); ?>" id="health-check-accordion-heading-<?php echo esc_attr( $section ); ?>" type="button">
			<span class="title">
				<?php echo esc_html( $details['label'] ); ?>

				<?php if ( isset( $details['show_count'] ) && $details['show_count'] ) : ?>
					<?php printf( '(%d)', count( $details['fields'] ) ); ?>
				<?php endif; ?>
			</span>
			<span class="icon"></span>
		</button>
	</dt>

	<dd id="health-check-accordion-block-<?php echo esc_attr( $section ); ?>" role="region" aria-labelledby="health-check-accordion-heading-<?php echo esc_attr( $section ); ?>" class="health-check-accordion-panel" hidden="hidden">
		<?php
		if ( isset( $details['description'] ) && ! empty( $details['description'] ) ) {
			printf(
				'<p>%s</p>',
				wp_kses( $details['description'], array(
					'a'      => array(
						'href' => true,
					),
					'strong' => true,
					'em'     => true,
				) )
			);
		}
		?>
		<table class="widefat striped health-check-table">
			<tbody>
			<?php
			foreach ( $details['fields'] as $field ) {
				if ( is_array( $field['value'] ) ) {
					$values = '';
					foreach ( $field['value'] as $name => $value ) {
						$values .= sprintf(
							'<li>%s: %s</li>',
							esc_html( $name ),
							esc_html( $value )
						);
					}
				} else {
					$values = esc_html( $field['value'] );
				}

				printf(
					'<tr><td>%s</td><td>%s</td></tr>',
					esc_html( $field['label'] ),
					$values
				);
			}
			?>
			</tbody>
		</table>
	</dd>
	<?php } ?>
</dl>

<?php
printf(
	'<a href="%s" class="button button-primary">%s</a>',
	esc_url( admin_url( '?page=health-check&tab=phpinfo' ) ),
	esc_html__( 'View extended PHP information', 'health-check' )
);
