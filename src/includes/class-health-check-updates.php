<?php
/**
 * Class for testing plugin/theme updates in the WordPress code.
 *
 * @package Health Check
 */

/**
 * Class Health_Check_Updates
 */
class Health_Check_Updates {
	/**
	 * Health_Check_Updates constructor.
	 *
	 * @uses HealthCheck::init()
	 *
	 * @return void
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Initiate the plugin class.
	 *
	 * @return void
     */
	public function init() {
		//include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
	}

	/**
	 * Run tests to determine if updates can run.
	 *
	 * @uses get_class_methods()
	 * @uses substr()
	 * @uses call_user_func()
	 *
	 * @return array
	 */
	public function run_tests() {
		$tests = array();

		foreach ( get_class_methods( $this ) as $method ) {
			if ( 'test_' !== substr( $method, 0, 5 ) ) {
				continue;
			}

			$result = call_user_func( array( $this, $method ) );

			if ( false === $result || null === $result ) {
				continue;
			}

			$result = (object) $result;

			if ( empty( $result->severity ) ) {
				$result->severity = 'warning';
			}

			$tests[ $method ] = $result;
		}

		return $tests;
	}

	/**
	 * Check if plugin updates have had any filters removed.
	 *
	 * @uses has_filter()
	 * @uses wp_next_scheduled()
	 * @uses esc_html__()
	 *
	 * @return array
	 */
	function test_wp_plugin_update_filters() {
		$tests = function() {
			$test1 = has_filter( 'load-plugins.php', 'wp_update_plugins' );
			$test2 = has_filter( 'load-update.php', 'wp_update_plugins' );
			$test3 = has_filter( 'load-update-core.php', 'wp_update_plugins' );
			$test4 = has_filter( 'wp_update_plugins', 'wp_update_plugins' );
			$test5 = has_filter( 'admin_init', '_maybe_update_plugins' );
			$test6 = wp_next_scheduled( 'wp_update_plugins' );

			return $test1 && $test2 && $test3 && $test4 && $test5 && $test6;
		};

		if ( ! $tests ) {
			return array(
				'desc'     => esc_html__( 'Plugin updates may have been disabled.', 'health-check' ),
				'severity' => 'warning',
			);
		}
        
		return array(
			'desc'     => esc_html__( 'Plugin updates should be working as expected.', 'health-check' ),
			'severity' => 'pass',
		);
	}
    
	/**
	 * Check if theme updates have had any filters removed.
	 *
	 * @uses has_filter()
	 * @uses wp_next_scheduled()
	 * @uses esc_html__()
	 *
	 * @return array
	 */
	function test_wp_theme_update_filters() {
		$tests = function() {
			$test1 = has_filter( 'load-plugins.php', 'wp_update_themes' );
			$test2 = has_filter( 'load-update.php', 'wp_update_themes' );
			$test3 = has_filter( 'load-update-core.php', 'wp_update_themes' );
			$test4 = has_filter( 'wp_update_themes', 'wp_update_themes' );
			$test5 = has_filter( 'admin_init', '_maybe_update_themes' );
			$test6 = wp_next_scheduled( 'wp_update_themes' );

			return $test1 && $test2 && $test3 && $test4 && $test5 && $test6;
		};

		if ( ! $tests ) {
			return array(
				'desc'     => esc_html__( 'Theme updates may have been disabled.', 'health-check' ),
				'severity' => 'warning',
			);
        }

		return array(
			'desc'     => esc_html__( 'Theme updates should be working as expected.', 'health-check' ),
			'severity' => 'pass',
		);
	}
}