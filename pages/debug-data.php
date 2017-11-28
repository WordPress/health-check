<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'We\'re sorry, but you can not directly access this file.' );
}

$info = Health_Check_Debug_Data::debug_data();
?>


	<div class="notice notice-info inline">
		<p>
			<?php esc_html_e( 'The system information shown below can also be copied and pasted into support requests such as on the WordPress.org forums, or to your theme and plugin developers.', 'health-check' ); ?>
		</p>
		<p>
			<button type="button" class="button button-primary" onclick="document.getElementById('system-information-copy-wrapper').style.display = 'block'; this.style.display = 'none';"><?php esc_html_e( 'Show copy and paste field', 'health-check' ); ?></button>
			<?php if ( 'en_US' !== get_locale() && version_compare( get_bloginfo( 'version' ), '4.7', '>=' ) ) : ?>
				<button type="button" class="button" onclick="document.getElementById('system-information-english-copy-wrapper').style.display = 'block'; this.style.display = 'none';"><?php esc_html_e( 'Show copy and paste field in English', 'health-check' ); ?></button>
			<?php endif; ?>
		</p>

		<?php
		if ( 'en_US' !== get_locale() && version_compare( get_bloginfo( 'version' ), '4.7', '>=' ) ) :

			$english_info = Health_Check_Debug_Data::debug_data( 'en_US' );
		?>
			<div id="system-information-english-copy-wrapper" style="display: none;">
					<textarea id="system-information-english-copy-field" class="widefat" rows="10">`
<?php
						foreach ( $english_info AS $section => $details ) {
							// Skip this section if there are no fields, or the section has been declared as private.
							if ( empty( $details['fields'] ) || ( isset( $details['private'] ) && $details['private'] ) ) {
								continue;
							}

							printf(
								"### %s%s ###\n\n",
								$details['label'],
								( isset( $details['show_count'] ) && $details['show_count'] ? sprintf( ' (%d)', count( $details['fields'] ) ) : '' )
							);

							foreach ( $details['fields'] AS $field ) {
								if ( isset( $field['private'] ) && true === $field['private'] ) {
									continue;
								}

								printf(
									"%s: %s\n",
									$field['label'],
									$field['value']
								);
							}
							echo "\n";
						}
						?>
`</textarea>
			<p>
				<?php esc_html_e( 'Some information may be filtered out from the list you are about to copy, this is information that may be considered private, and is not meant to be shared in a public forum.', 'health-check' ); ?>
				<br>
				<button type="button" class="button button-primary" onclick="document.getElementById('system-information-english-copy-field').select();"><?php esc_html_e( 'Mark field for copying', 'health-check' ); ?></button>
			</p>
		</div>

	<?php endif; ?>

		<div id="system-information-copy-wrapper" style="display: none;">
			<textarea id="system-information-copy-field" class="widefat" rows="10">`
<?php
				foreach ( $info AS $section => $details ) {
					// Skip this section if there are no fields, or the section has been declared as private.
					if ( empty( $details['fields'] ) || ( isset( $details['private'] ) && $details['private'] ) ) {
						continue;
					}

					printf(
						"### %s%s ###\n\n",
						$details['label'],
						( isset( $details['show_count'] ) && $details['show_count'] ? sprintf( ' (%d)', count( $details['fields'] ) ) : '' )
					);

					foreach ( $details['fields'] AS $field ) {
						if ( isset( $field['private'] ) && true === $field['private'] ) {
							continue;
						}

						printf(
							"%s: %s\n",
							$field['label'],
							$field['value']
						);
					}
					echo "\n";
				}
				?>
`</textarea>
			<p>
				<?php esc_html_e( 'Some information may be filtered out from the list you are about to copy, this is information that may be considered private, and is not meant to be shared in a public forum.', 'health-check' ); ?>
				<br>
				<button type="button" class="button button-primary" onclick="document.getElementById('system-information-copy-field').select();"><?php esc_html_e( 'Mark field for copying', 'health-check' ); ?></button>
			</p>
		</div>
	</div>

	<h2 id="system-information-table-of-contents">
		<?php esc_html_e( 'Table of contents', 'health-check' ); ?>
	</h2>
	<div>
		<?php
		$toc = array();

		foreach ( $info AS $section => $details ) {
			if ( empty( $details['fields'] ) ) {
				continue;
			}

			$toc[] = sprintf(
				'<a href="#%s" class="health-check-toc">%s</a>',
				esc_attr( $section ),
				esc_html( $details['label'] )
			);
		}

		echo implode( ' | ', $toc );
		?>
	</div>

<?php
foreach ( $info AS $section => $details ) {
	if ( ! isset( $details['fields'] ) || empty( $details['fields'] ) ) {
		continue;
	}

	printf(
		'<h2 id="%s">%s%s</h2>',
		esc_attr( $section ),
		esc_html( $details['label'] ),
		( isset( $details['show_count'] ) && $details['show_count'] ? sprintf( ' (%d)', count( $details['fields'] ) ) : '' )
	);

	if ( isset( $details['description'] ) && ! empty( $details['description'] ) ) {
		printf(
			'<p>%s</p>',
			wp_kses( $details['description'], array(
				'a'      => array(
					'href' => true
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
		foreach ( $details['fields'] AS $field ) {
			printf(
				'<tr><td>%s</td><td>%s</td></tr>',
				esc_html( $field['label'] ),
				esc_html( $field['value'] )
			);
		}
		?>
		</tbody>
	</table>
	<span style="display: block; width: 100%; text-align: <?php echo ( is_rtl() ? 'left' : 'right' ); ?>">
		<a href="#system-information-table-of-contents" class="health-check-toc"><?php esc_html_e( 'Return to table of contents', 'health-check' ); ?></a>
	</span>
	<?php
}
