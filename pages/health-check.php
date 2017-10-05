<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( esc_html__( 'We\'re sorry, but you can not directly access this file.', 'health-check' ) );
}

global $wpdb;

$php_min_version_check = version_compare( HEALTH_CHECK_PHP_MIN_VERSION, PHP_VERSION, '<=' );
$php_rec_version_check = version_compare( HEALTH_CHECK_PHP_REC_VERSION, PHP_VERSION, '<=' );

$mariadb = false;
$mysql_server_version = null;
if ( method_exists( $wpdb, 'db_version' ) ) {
	if ( $wpdb->use_mysqli ) {
		$mysql_server_version = mysqli_get_server_info( $wpdb->dbh );
	} else {
		$mysql_server_version = mysql_get_server_info( $wpdb->dbh );
	}
}

$health_check_mysql_rec_version = HEALTH_CHECK_MYSQL_REC_VERSION;

if ( stristr( $mysql_server_version, 'mariadb' ) ) {
	$version_parts = explode( '-', $mysql_server_version );
	$mysql_server_version = $version_parts[1];

	$mariadb = true;
	$health_check_mysql_rec_version = '10.0';
}

$mysql_min_version_check = version_compare( HEALTH_CHECK_MYSQL_MIN_VERSION, $mysql_server_version, '<=' );
$mysql_rec_version_check = version_compare( $health_check_mysql_rec_version, $mysql_server_version, '<=' );

$json_check = HealthCheck::json_check();
$db_dropin = file_exists( WP_CONTENT_DIR . '/db.php' );
?>

	<div class="health-check-notice notice-info">
		<p>
			<?php esc_html_e( 'The health check shows critical information about your WordPress configuration and items that require your attention.', 'health-check' ); ?>
		</p>
	</div>

	<table class="widefat striped health-check-table">
		<tbody>
			<tr>
				<td><?php esc_html_e( 'PHP Version', 'health-check' ); ?></td>
				<td>
					<?php
						$status = 'good';
						$notice = array();

						if ( ! $php_rec_version_check ) {
							$status = 'warning';
							$notice[] = sprintf(
								// translators: %s: Recommended PHP version
								esc_html__( 'For performance and security reasons, we strongly recommend running PHP version %s or higher.', 'health-check' ),
								HEALTH_CHECK_PHP_REC_VERSION
							);
						}

						if ( ! $php_min_version_check ) {
							$status = 'error';
							$notice[] = sprintf(
								// translators: %1$s: Current PHP version. %2$s: Recommended PHP version. %3$s: Minimum PHP version.
								esc_html__( 'Your version of PHP, %1$s, is very outdated and no longer receiving security updates. You should contact your host for an upgrade, WordPress recommends using PHP version %2$s, but will work with version %3$s or newer.', 'health-check' ),
								PHP_VERSION,
								HEALTH_CHECK_PHP_REC_VERSION,
								HEALTH_CHECK_PHP_MIN_VERSION
							);
						}

						printf(
							'<span class="%s"></span> %s',
							esc_attr( $status ),
							sprintf(
								'%s%s',
								PHP_VERSION,
								( ! empty( $notice ) ? ' - ' . implode( '<br>', $notice ) : '' )
							)
						);
					?>

				</td>
			</tr>

			<tr>
				<td>
					<?php
						if ( ! $mariadb ) {
							esc_html_e( 'MySQL Server version', 'health-check' );
						}
						else {
							esc_html_e( 'MariaDB Server version', 'health-check' );
						}
					?>
				</td>
				<td>
					<?php
					$status = 'good';
					$notice = array();

					if ( ! $mysql_rec_version_check ) {
						$status = 'warning';
						$notice[] = sprintf(
							// translators: %s: Database server recommended version number.
							esc_html__( 'For performance and security reasons, we strongly recommend running MySQL version %s or higher.', 'health-check' ),
							$health_check_mysql_rec_version
						);
					}

					if ( ! $mysql_min_version_check ) {
						$status = 'error';
						$notice[] = sprintf(
							// translators: %s: Database server minimum version number.
							esc_html__( 'WordPress 3.2+ requires MySQL version %s', 'health-check' ),
							HEALTH_CHECK_MYSQL_MIN_VERSION
						);
					}

					if ( $db_dropin ) {
						// translators: %s: The database engine in use (MySQL or MariaDB).
						$notice[] = wp_kses( sprintf( __( 'You are using a <code>wp-content/db.php</code> drop-in which might mean that a %s database is not being used.', 'health-check' ), ( $mariadb ? 'MariaDB' : 'MySQL' ) ), array( 'code' => true ) );
					}

					printf(
						'<span class="%s"></span> %s',
						esc_attr( $status ),
						sprintf(
							'%s%s',
							esc_html( $mysql_server_version ),
							( ! empty( $notice ) ? ' - ' . implode( '<br>', $notice ) : '' )
						)
					);
					?>
				</td>
			</tr>

			<tr>
				<td><?php esc_html_e( 'JSON Extension', 'health-check' ); ?></td>
				<td>
					<?php
						$status = 'good';
						$notice = array();

						if ( ! $json_check ) {
							printf(
								'<span class="error"> %s',
								esc_html__( 'The PHP install on your server has the JSON extension disabled and is therefore not compatible with WordPress 3.2 or newer.', 'health-check' )
							);
						}
						else {
							printf(
								'<span class="good"> %s',
								esc_html__( 'Your PHP install supports JSON.', 'health-check' )
							);
						}
					?>
				</td>
			</tr>

			<tr>
				<td><?php esc_html_e( 'MySQL utf8mb4 support', 'health-check' ); ?></td>
				<td>
					<?php
						if ( $mariadb ) {
							if ( version_compare( $mysql_server_version, '5.5.3', '<' ) ) {
								printf(
									'<span class="warning"></span> %s',
									esc_html__( 'WordPress\' utf8mb4 support requires MySQL version %s or greater', 'health-check' )
								);
							} else {
								printf(
									'<span class="good"></span> %s',
									esc_html__( 'Your MySQL version supports utf8mb4', 'health-check' )
								);
							}
						}
						// MariaDB introduced utf8mb4 support in 5.5.0
						else {
							if ( version_compare( $mysql_server_version, '5.5.0', '<' ) ) {
								printf(
									'<span class="warning"></span> %s',
									esc_html__( 'WordPress\' utf8mb4 support requires MariaDB version %s or greater', 'health-check' )
								);
							}
							else {
								printf(
									'<span class="good"></span> %s',
									esc_html__( 'Your MariaDB version supports utf8mb4', 'health-check' )
								);
							}
						}

						if ( $wpdb->use_mysqli ) {
							$mysql_client_version = mysqli_get_client_info();
						} else {
							$mysql_client_version = mysql_get_client_info();
						}

						/*
						 * libmysql has supported utf8mb4 since 5.5.3, same as the MySQL server.
						 * mysqlnd has supported utf8mb4 since 5.0.9.
						 */
						if ( false !== strpos( $mysql_client_version, 'mysqlnd' ) ) {
							$mysql_client_version = preg_replace( '/^\D+([\d.]+).*/', '$1', $mysql_client_version );
							if ( version_compare( $mysql_client_version, '5.0.9', '<' ) ) {
								printf(
									'<br><span class="warning"></span> %s',
									sprintf(
										__( 'WordPress\' utf8mb4 support requires MySQL client library (%1$s) version %2$s or newer.', 'health-check' ),
										'mysqlnd',
										'5.0.9'
									)
								);
							}
						}
						else {
							if ( version_compare( $mysql_client_version, '5.5.3', '<' ) ) {
								printf(
									'<br><span class="warning"></span> %s',
									sprintf(
										__( 'WordPress\' utf8mb4 support requires MySQL client library (%1$s) version %2$s or newer.', 'health-check' ),
										'libmysql',
										'5.5.3'
									)
								);
							}
						}
					?>
				</td>
			</tr>

			<tr>
				<td><?php esc_html_e( 'Communication with WordPress.org', 'health-check' ); ?></td>
				<td>
					<?php
						$wp_dotorg = wp_remote_get( 'https://wordpress.org', array( 'timeout' => 10 ) );
						if ( ! is_wp_error( $wp_dotorg ) ) {
							printf(
								'<span class="good"></span> %s',
								esc_html__( 'WordPress.org is reachable from your server.', 'health-check' )
							);
						}
						else {
							printf(
								'<span class="error"></span> %s',
								sprintf(
									// translators: %1$s: The IP address WordPress.org resolves to. %2$s: The error returned by the lookup.
									__( 'Unable to reach WordPress.org at %1$s: %2$s', 'health-check' ),
									gethostbyname( 'wordpress.org' ),
									$wp_dotorg->get_error_message()
								)
							);
						}
					?>
				</td>
			</tr>

			<tr>
				<td><?php esc_html_e( 'HTTPS status', 'health-check' ); ?></td>
				<td>
					<?php
						if ( is_ssl() ) {
							printf(
								'<span class="good"></span> %s',
								esc_html__( 'You are accessing this website using HTTPS.', 'health-check' )
							);
						}
						else {
							printf(
								'<span class="warning"></span> %s',
								esc_html__( 'You are not using HTTPS to access this website.', 'health-check' )
							);
						}
					?>
				</td>
			</tr>
		</tbody>
	</table>
