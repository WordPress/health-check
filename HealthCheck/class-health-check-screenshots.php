<?php
/**
 * Automate the screenshot process for end users seeking support.
 *
 * @package Health Check
 */

// Make sure the file is not directly accessible.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'We\'re sorry, but you can not directly access this file.' );
}

/**
 * Class Health_Check_Screenshots
 */
class Health_Check_Screenshots {

	private $allowed_image_mimes = array(
		'image/jpeg',
	);

	private $should_404 = false;

	public function __construct() {
		$feature_status = get_option( 'health-check-beta-features', false );

		if ( $feature_status ) {
			add_action( 'admin_init', array( $this, 'delete_screenshot' ) );

			add_action( 'init', array( $this, 'display_screenshot' ), 0 );

			add_action( 'init', array( $this, 'register_post_type' ) );
			add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

			add_action( 'admin_bar_menu', array( $this, 'admin_menubar_button' ), 999 );

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

			add_filter( 'site_health_navigation_tabs', array( $this, 'add_site_health_navigation_tabs' ), 20 );
			add_action( 'site_health_tab_content', array( $this, 'add_site_health_tab_content' ) );

			add_action( 'wp', array( $this, 'maybe_404' ) );
		}
	}

	public function maybe_404() {
		if ( ! $this->should_404 ) {
			return;
		}

		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
	}

	public function delete_screenshot() {
		if ( ! is_admin() || ! isset( $_GET['health-check-delete-screenshot'] ) || ! $this->user_can_screenshot() ) {
			return;
		}

		// Validate nonces.
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'health-check-delete-screenshot' ) ) {
			return;
		}

		wp_delete_post( $_GET['health-check-delete-screenshot'], true );

		wp_safe_redirect( admin_url( 'site-health.php?tab=screenshots' ) );
	}

	public function display_screenshot() {
		if ( ! isset( $_GET['health-check-screenshot'] ) ) {
			return;
		}

		$screenshot_id = $_GET['health-check-screenshot'];
		$screenshot    = get_posts(
			array(
				'post_type'      => 'health-check-images',
				'posts_per_page' => 1,
				'meta_key'       => 'hash_id',
				'meta_value'     => $screenshot_id,
			)
		);

		if ( empty( $screenshot ) ) {
			$this->should_404 = true;
			return;
		}

		if ( is_array( $screenshot ) ) {
			$screenshot = $screenshot[0];
		}

		$image = $screenshot->screenshot;
		$image = explode( ';', $image, 2 );

		$image_type = str_replace( 'data:', '', $image[0] );

		if ( ! in_array( $image_type, $this->allowed_image_mimes, true ) ) {
			return;
		}

		header( 'Content-Type: ' . $image_type );

		if ( isset( $_GET['dl'] ) ) {
			header( 'Content-Disposition: attachment; filename="' . sanitize_title( $screenshot->post_title ) . '.jpeg"' );
		}

		$data = str_replace( 'base64,', '', $image[1] );
		echo base64_decode( $data );

		die();
	}

	public function add_site_health_tab_content( $tab ) {
		if ( 'screenshots' !== $tab ) {
			return;
		}

		include_once( HEALTH_CHECK_PLUGIN_DIRECTORY . '/pages/screenshots.php' );
	}

	public function add_site_health_navigation_tabs( $tabs ) {
		return array_merge(
			$tabs,
			array(
				'screenshots' => esc_html__( 'Screenshots', 'health-check' ),
			)
		);
	}

	public function user_can_screenshot() {
		return current_user_can( 'view_site_health_checks' );
	}

	public function register_post_type() {
		register_post_type(
			'health-check-images',
			array(
				'labels'              => array(
					'name'          => __( 'Screenshots', 'health-check' ),
					'singular_name' => __( 'Screenshot', 'health-check' ),
				),
				'public'              => false,
				'show_ui'             => false,
				'show_in_menu'        => false,
				'show_in_nav_menus'   => false,
				'show_in_admin_bar'   => false,
				'exclude_from_search' => true,
				'has_archive'         => false,
				'hierarchical'        => false,
				'rewrite'             => false,
				'query_var'           => false,
				'supports'            => array( 'title' ),
			)
		);
	}

	public function register_rest_routes() {
		register_rest_route(
			'health-check/v1',
			'/screenshot',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'store_screenshot' ),
				'permission_callback' => array( $this, 'user_can_screenshot' ),
				'args'                => array(
					'nonce'      => array(
						'required'          => true,
						'validate_callback' => function( $param, $request, $key ) {
							return wp_verify_nonce( $param, 'health-check-screenshot' );
						},
					),
					'label'      => array(
						'required'          => true,
						'validate_callback' => function( $param, $request, $key ) {
							return is_string( $param ) && ! empty( $param );
						},
					),
					'screenshot' => array(
						'required'          => true,
						'validate_callback' => function( $param, $request, $key ) {
							return is_string( $param ) && 'data:image/jpeg;' === substr( $param, 0, 16 );
						},
					),
				),
			)
		);
	}

	public function store_screenshot( \WP_REST_Request $request ) {
		// Create a new post in the `health-check-images` post type.
		$post_id = wp_insert_post(
			array(
				'post_type'   => 'health-check-images',
				'post_title'  => sanitize_text_field( $request->get_param( 'label' ) ),
				'post_status' => 'publish',
				'meta_input'  => array(
					'screenshot' => $request->get_param( 'screenshot' ),
					'hash_id'    => wp_hash( $request->get_param( 'screenshot' ) ),
				),
			)
		);
	}

	public function enqueue_scripts() {
		if ( ! $this->user_can_screenshot() ) {
			return;
		}

		$asset = include HEALTH_CHECK_PLUGIN_DIRECTORY . 'build/health-check-global.asset.php';

		wp_enqueue_script( 'health-check-global', trailingslashit( HEALTH_CHECK_PLUGIN_URL ) . 'build/health-check-global.js', array( 'jquery', 'wp-a11y' ), $asset['version'] );

		wp_localize_script(
			'health-check-global',
			'HealthCheckTools',
			array(
				'nonce' => array(
					'rest'       => wp_create_nonce( 'wp_rest' ),
					'screenshot' => wp_create_nonce( 'health-check-screenshot' ),
				),
				'rest'  => array(
					'screenshot' => rest_url( 'health-check/v1/screenshot' ),
				),
			)
		);
	}

	public function admin_menubar_button( $wp_menu ) {
		if ( ! $this->user_can_screenshot() ) {
			return;
		}

		if ( ! is_admin() ) {
			require_once( trailingslashit( ABSPATH ) . 'wp-admin/includes/plugin.php' );
		}

		// Add top-level menu item.
		$wp_menu->add_menu(
			array(
				'id'    => 'health-check-screenshot',
				'title' => esc_html__( 'Take screenshot', 'health-check' ),
				'href'  => '#',
				'meta'  => array(
					'class' => 'health-check-take-screenshot',
				),
			)
		);
	}

}

new Health_Check_Screenshots();
