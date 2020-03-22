<?php
/**
 * Perform tests to see if WP_Cron is operating as it should.
 *
 * @package Health Check
 */

// Make sure the file is not directly accessible.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'We\'re sorry, but you can not directly access this file.' );
}

/**
 * Class Health_Check_WP_Cron
 */
class Health_Check_WP_Cron {
	public $schedules;
	public $crons;
	public $last_missed_cron     = null;
	public $last_late_cron       = null;
	private $timeout_missed_cron = null;
	private $timeout_late_cron   = null;

	/**
	 * Health_Check_WP_Cron constructor.
	 */
	public function __construct() {
		$this->init();

		$this->timeout_late_cron   = 0;
		$this->timeout_missed_cron = - 5 * MINUTE_IN_SECONDS;

		if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) {
			$this->timeout_late_cron   = - 15 * MINUTE_IN_SECONDS;
			$this->timeout_missed_cron = - 1 * HOUR_IN_SECONDS;
		}
	}

	/**
	 * Initiate the class
	 *
	 * @uses wp_get_schedules()
	 * @uses Health_Check_WP_Cron::get_cron_tasks()
	 *
	 * @return void
	 */
	public function init() {
		$this->schedules = wp_get_schedules();
		$this->get_cron_tasks();
	}

	/**
	 * Populate our list of cron events and store them to a class-wide variable.
	 *
	 * Derived from `get_cron_events()` in WP Crontrol (https://plugins.svn.wordpress.org/wp-crontrol)
	 * by John Blackburn.
	 *
	 * @uses _get_cron_array()
	 * @uses WP_Error
	 *
	 * @return void
	 */
	private function get_cron_tasks() {
		$cron_tasks = _get_cron_array();

		if ( empty( $cron_tasks ) ) {
			$this->crons = new WP_Error( 'no_tasks', __( 'No scheduled events exist on this site.', 'health-check' ) );
			return;
		}

		$this->crons = array();

		foreach ( $cron_tasks as $time => $cron ) {
			foreach ( $cron as $hook => $dings ) {
				foreach ( $dings as $sig => $data ) {

					$this->crons[ "$hook-$sig-$time" ] = (object) array(
						'hook'     => $hook,
						'time'     => $time,
						'sig'      => $sig,
						'args'     => $data['args'],
						'schedule' => $data['schedule'],
						'interval' => isset( $data['interval'] ) ? $data['interval'] : null,
					);

				}
			}
		}
	}

	/**
	 * Check if any scheduled tasks have been missed.
	 *
	 * Returns a boolean value of `true` if a scheduled task has been missed and ends processing.
	 * If the list of crons is an instance of WP_Error, return the instance instead of a boolean value.
	 *
	 * @uses is_wp_error()
	 * @uses time()
	 *
	 * @return bool|WP_Error
	 */
	public function has_missed_cron() {
		if ( is_wp_error( $this->crons ) ) {
			return $this->crons;
		}

		foreach ( $this->crons as $id => $cron ) {
			if ( ( $cron->time - time() ) < $this->timeout_missed_cron ) {
				$this->last_missed_cron = $cron->hook;
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if any scheduled tasks are late.
	 *
	 * Returns a boolean value of `true` if a scheduled task is late and ends processing. If the list of
	 * crons is an instance of WP_Error, return the instance instead of a boolean value.
	 *
	 * @return bool|WP_Error true if a cron is late, false if it wasn't. WP_Error if the cron is set to that.
	 */
	public function has_late_cron() {
		if ( is_wp_error( $this->crons ) ) {
			return $this->crons;
		}

		foreach ( $this->crons as $id => $cron ) {
			$cron_offset = $cron->time - time();
			if (
				$cron_offset >= $this->timeout_missed_cron &&
				$cron_offset < $this->timeout_late_cron
			) {
				$this->last_late_cron = $cron->hook;
				return true;
			}
		}

		return false;
	}
}
