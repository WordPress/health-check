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

<div class="health-check-body">
	<h2>
		<?php esc_html_e( 'Screenshots', 'health-check' ); ?>
	</h2>

	<div class="notice notice-warning inline">
		<p>
			<?php esc_html_e( 'This is a beta-feature, and some inconsistencies in screenshots is to be expected, please keep that, and the fact that your pages may show sensitive information in mind when sharing screenshots.', 'health-check' ); ?>
		</p>
	</div>

	<p>
		<?php
		printf(
			/* translators: %s: The label of the "Take screenshot" button. */
			esc_html__( 'To take a screenshot for sharing with support users, you may click the %s button, found at the top of every page, and they will be stored securely on your site until you wish to share them.', 'health-check' ),
			sprintf(
				'<strong>%s</strong>',
				esc_html_x( 'Take Screenshot', 'Description of the button label', 'health-check' )
			)
		);
		?>
	</p>

	<div class="health-check-screenshots">
		<?php
		$screenshots = get_posts(
			array(
				'post_type'      => 'health-check-images',
				'posts_per_page' => -1,
			)
		);

		if ( ! $screenshots ) {
			echo '<p><em>' . esc_html__( 'You have not taken any screenshots, return here when you have to view them.', 'health-check' ) . '</em></p>';
		}

		foreach ( $screenshots as $screenshot ) {
			$controls = array(
				'view'   => sprintf(
					'<a href="%s" class="health-check-screenshot-view button button-primary" target="_blank">%s</a>',
					esc_url(
						add_query_arg(
							array(
								'health-check-screenshot' => $screenshot->hash_id,
							),
							site_url()
						)
					),
					esc_html__( 'View screenshot', 'health-check' )
				),
				'embed'  => sprintf(
					'<button type="button" class="health-check-screenshot-embed button button-default" data-clipboard-text="%s"><span class="prompt">%s</span><span class="success hidden">%s</span></button>',
					esc_attr(
						sprintf(
							'<img src="%s" alt="%s"></img>',
							add_query_arg(
								array(
									'health-check-screenshot' => $screenshot->hash_id,
								),
								site_url()
							),
							esc_attr( $screenshot->post_title )
						)
					),
					esc_html__( 'Copy forum markup', 'health-check' ),
					esc_html__( 'Copied!', 'health-check' )
				),
				'delete' => sprintf(
					'<a href="%s" class="health-check-screenshot-delete button button-delete">%s</a>',
					esc_url(
						wp_nonce_url(
							add_query_arg(
								array(
									'tab' => 'screenshots',
									'health-check-delete-screenshot' => $screenshot->ID,
								),
								admin_url( 'site-health.php' )
							),
							'health-check-delete-screenshot'
						)
					),
					esc_html__( 'Delete screenshot', 'health-check' )
				),
			);

			printf(
				'<div class="screenshot">%s<div class="meta"><span class="title">%s</span><span class="date">%s</span><div class="actions">%s</div></div></div>',
				sprintf(
					'<img src="%s" alt="%s" />',
					( $screenshot->screenshot ),
					esc_attr( $screenshot->post_title )
				),
				esc_html( $screenshot->post_title ),
				sprintf(
					// translators: %s: The date and time the screenshot was taken.
					esc_html__( 'Published on %s', 'health-check' ),
					esc_html( $screenshot->post_date )
				),
				implode( ' ', $controls )
			);
		}
		?>
	</div>
</div>
