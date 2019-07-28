<?php

// Manually include the versions file as we can't always rely on `get_bloginfo()` to fetch versions.
include ABSPATH . WPINC . '/version.php';

if ( ! function_exists( 'wp_check_php_version' ) && version_compare( '5.1', $wp_version, '>' ) ) {
	/**
	 * Fallback function replicating core behavior from WordPress 5.1.0 to check PHP versions.
	 *
	 * @return array|bool|mixed|object|WP_Error
	 */
	function wp_check_php_version() {
		$version = phpversion();
		$key     = md5( $version );

		$response = get_site_transient( 'php_check_' . $key );
		if ( false === $response ) {
			$url = 'http://api.wordpress.org/core/serve-happy/1.0/';
			if ( wp_http_supports( array( 'ssl' ) ) ) {
				$url = set_url_scheme( $url, 'https' );
			}

			$url = add_query_arg( 'php_version', $version, $url );

			$response = wp_remote_get( $url );

			if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
				return false;
			}

			/**
			 * Response should be an array with:
			 *  'recommended_version' - string - The PHP version recommended by WordPress.
			 *  'is_supported' - boolean - Whether the PHP version is actively supported.
			 *  'is_secure' - boolean - Whether the PHP version receives security updates.
			 *  'is_acceptable' - boolean - Whether the PHP version is still acceptable for WordPress.
			 */
			$response = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( ! is_array( $response ) ) {
				return false;
			}

			set_site_transient( 'php_check_' . $key, $response, WEEK_IN_SECONDS );
		}

		if ( isset( $response['is_acceptable'] ) && $response['is_acceptable'] ) {
			/**
			 * Filters whether the active PHP version is considered acceptable by WordPress.
			 *
			 * Returning false will trigger a PHP version warning to show up in the admin dashboard to administrators.
			 *
			 * This filter is only run if the wordpress.org Serve Happy API considers the PHP version acceptable, ensuring
			 * that this filter can only make this check stricter, but not loosen it.
			 *
			 * @since 5.1.1
			 *
			 * @param bool   $is_acceptable Whether the PHP version is considered acceptable. Default true.
			 * @param string $version       PHP version checked.
			 */
			$response['is_acceptable'] = (bool) apply_filters( 'wp_is_php_version_acceptable', true, $version );
		}

		return $response;
	}
}

if ( ! function_exists( 'wp_get_update_php_url' ) && version_compare( '5.1', $wp_version, '>' ) ) {
	/**
	 * Fallback function replicating core behavior from WordPress 5.1.0 to check PHP versions.
	 *
	 * @return string URL to learn more about updating PHP.
	 */
	function wp_get_update_php_url() {
		$default_url = _x( 'https://wordpress.org/support/update-php/', 'localized PHP upgrade information page', 'health-check' );

		$update_url = $default_url;
		if ( false !== getenv( 'WP_UPDATE_PHP_URL' ) ) {
			$update_url = getenv( 'WP_UPDATE_PHP_URL' );
		}

		/**
		 * Filters the URL to learn more about updating the PHP version the site is running on.
		 *
		 * Providing an empty string is not allowed and will result in the default URL being used. Furthermore
		 * the page the URL links to should preferably be localized in the site language.
		 *
		 * @since 5.1.0
		 *
		 * @param string $update_url URL to learn more about updating PHP.
		 */
		$update_url = apply_filters( 'wp_update_php_url', $update_url );

		if ( empty( $update_url ) ) {
			$update_url = $default_url;
		}

		return $update_url;
	}
}

if ( ! function_exists( 'is_countable' ) && version_compare( '4.9.6', $wp_version, '>' ) ) {
	/**
	 * Fallback function replicating core behavior from WordPress 4.9.6 to check PHP versions.
	 *
	 * Polyfill for is_countable() function added in PHP 7.3.
	 *
	 * Verify that the content of a variable is an array or an object
	 * implementing the Countable interface.
	 *
	 * @param mixed $var The value to check.
	 *
	 * @return bool True if `$var` is countable, false otherwise.
	 */
	function is_countable( $var ) {
		return ( is_array( $var )
			|| $var instanceof Countable
			|| $var instanceof SimpleXMLElement
			|| $var instanceof ResourceBundle
		);
	}
}

if ( ! function_exists( 'get_user_count' ) && version_compare( '4.8', $wp_version, '>' ) ) {
	/**
	 * Fallback function replicating core behavior from WordPress 4.8.0 to check PHP versions.
	 *
	 * @return int Number of active users on the network.
	 */
	function get_user_count( $network_id = null ) {
		return get_network_option( $network_id, 'user_count' );
	}
}

if ( ! function_exists( 'get_user_locale' ) && version_compare( '4.7', $wp_version, '>' ) ) {
	/**
	 * Fallback function replicating core behavior from WordPress 4.7.0 to check PHP versions.
	 *
	 * @return string The locale of the user.
	 */
	function get_user_locale( $user_id = 0 ) {
		$user = false;
		if ( 0 === $user_id && function_exists( 'wp_get_current_user' ) ) {
			$user = wp_get_current_user();
		} elseif ( $user_id instanceof WP_User ) {
			$user = $user_id;
		} elseif ( $user_id && is_numeric( $user_id ) ) {
			$user = get_user_by( 'id', $user_id );
		}

		if ( ! $user ) {
			return get_locale();
		}

		$locale = $user->locale;
		return $locale ? $locale : get_locale();
	}
}

if ( ! function_exists( 'wp_get_upload_dir' ) && version_compare( '4.5', $wp_version, '>' ) ) {
	/**
	 * Fallback function replicating core behavior from WordPress 4.5.0 to check PHP versions.
	 *
	 * @return array See `wp_upload_dir()` for description.
	 */
	function wp_get_upload_dir() {
		return wp_upload_dir( null, false );
	}
}
