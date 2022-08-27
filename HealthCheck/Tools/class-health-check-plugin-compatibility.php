<?php

class Health_Check_Plugin_Compatibility extends Health_Check_Tool {

	public function __construct() {
		$this->label       = __( 'Plugin compatibility', 'health-check' );
		$this->description = sprintf(
			'%s<br>%s',
			__( 'Attempt to identify the compatibility of your plugins before upgrading PHP, note that a compatibility check may not always be accurate, and you may want to contact the plugin author to confirm that things will continue working.', 'health-check' ),
			__( 'The compatibility check will need to send requests to the <a href="https://wptide.org">WPTide</a> project to fetch the test results for each of your plugins.', 'health-check' )
		);

		add_action( 'rest_api_init', array( $this, 'register_plugin_compat_rest_route' ) );

		parent::__construct();
	}

	public function register_plugin_compat_rest_route() {
		register_rest_route(
			'health-check/v1',
			'plugin-compat',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'check_plugin_version' ),
				'permission_callback' => function() {
					return current_user_can( 'view_site_health_checks' );
				},
			)
		);
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

	function check_plugin_version( $request ) {
		if ( ! $request->has_param( 'slug' ) || ! $request->has_param( 'version' ) ) {
			return new WP_Error( 'missing_arg', __( 'The slug, or version, is missing from the request.', 'health-check' ) );
		}

		$slug    = $request->get_param( 'slug' );
		$version = $request->get_param( 'version' );

		/*
		 * Override for the Health Check plugin, which has back-compat code we are aware
		 * of and can account for early on. It should not become a habit to add exceptions for
		 * plugins in this field, this is rather to avoid confusion and concern in users of this plugin specifically.
		 */
		if ( 'health-check/health-check.php' === $slug ) {
			$response = array(
				'version' => '7.4',
			);
		} else {
			$response = array(
				'version' => $this->get_highest_supported_php( $slug, $version ),
			);
		}

		return new WP_REST_Response( $response, 200 );
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
