<?php

class Health_Check_Plugin_Compatibility extends Health_Check_Tool {

	public function __construct() {
		$this->label       = __( 'Plugin compatibility', 'health-check' );
		$this->description = sprintf(
			'%s<br>%s',
			__( 'Attempt to identify the compatibility of your plugins before upgrading PHP, note that a compatibility check may not always be accurate, and you may want to contact the plugin author to confirm that things will continue working.', 'health-check' ),
			__( 'The compatibility check will need to send requests to the <a href="https://wptide.org">WPTide</a> project to fetch the test results for each of your plugins.', 'health-check' )
		);

		add_action( 'wp_ajax_health-check-tools-plugin-compat', array( $this, 'check_plugin_version' ) );

		parent::__construct();
	}

	public function tab_content() {
		?>
		<table class="wp-list-table widefat fixed striped" id="health-check-tool-plugin-compat-list">
			<thead>
				<tr>
					<th><?php _e( 'Plugin', 'health-check' ); ?></th>
					<th><?php _e( 'Version', 'health-check' ); ?></th>
					<th><?php _e( 'Minimum PHP', 'health-check' ); ?></th>
					<th><?php _e( 'Highest supported PHP', 'health-check' ); ?></th>
				</tr>
			</thead>

			<tbody>
			<?php
			$plugins = get_plugins();

			foreach ( $plugins as $slug => $plugin ) {
				printf(
					'<tr data-plugin-slug="%s" data-plugin-version="%s" data-plugin-checked="false"><td>%s</td><td>%s</td><td>%s</td><td class="supported-version">%s</td></tr>',
					esc_attr( $slug ),
					esc_attr( $plugin['Version'] ),
					$plugin['Name'],
					$plugin['Version'],
					( isset( $plugin['RequiresPHP'] ) && ! empty( $plugin['RequiresPHP'] ) ? $plugin['RequiresPHP'] : '&mdash;' ),
					'<span class="spinner"></span>'
				);
			}
			?>
			</tbody>
		</table>

		<p>
			<button type="button" class="button button-primary" id="health-check-tool-plugin-compat">
				<?php _e( 'Check plugins', 'health-check' ); ?>
			</button>
		</p>
		<?php
	}

	function check_plugin_version() {
		check_ajax_referer( 'health-check-tools-plugin-compat' );

		if ( ! current_user_can( 'view_site_health_checks' ) ) {
			wp_send_json_error();
		}

		$response = array(
			'version' => $this->get_highest_supported_php( $_POST['slug'], $_POST['version'] ),
		);

		wp_send_json_success( $response );

		wp_die();
	}

	function get_highest_supported_php( $slug, $version ) {
		$versions = $this->get_supported_php( $slug, $version );

		if ( empty( $versions ) ) {
			return __( 'Could not be determined', 'health-check' );
		}

		$highest = 0;

		foreach ( $versions as $version ) {
			if ( $highest < $version ) {
				$highest = $version;
			}
		}

		return $highest;
	}

	function get_supported_php( $slug, $version ) {
		// Clean up the slug, in case it's got more details
		if ( stristr( $slug, '/' ) ) {
			$parts = explode( '/', $slug );
			$slug  = $parts[0];
		}

		$transient_name = sprintf(
			'health-check-tide-%s-%s',
			$slug,
			$version
		);

		$tide_versions = get_transient( $transient_name );

		if ( false === $tide_versions ) {
			$tide_api_respone = wp_remote_get(
				sprintf(
					'https://wptide.org/api/tide/v1/audit/wporg/plugin/%s',
					$slug
				)
			);

			$tide_response = wp_remote_retrieve_body( $tide_api_respone );

			$json = json_decode( $tide_response );

			if ( empty( $json ) ) {
				$tide_versions = array();
			} else {
				$tide_versions = $json[0]->reports->phpcs_phpcompatibility->compatible_versions;
			}

			set_transient( $transient_name, $tide_versions, 1 * WEEK_IN_SECONDS );
		}

		return $tide_versions;
	}
}

new Health_Check_Plugin_Compatibility();
