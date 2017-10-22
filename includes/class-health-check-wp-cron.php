<?php

class Health_Check_WP_Cron {
	public $schedules;
	public $crons;
	public $last_missed_cron = null;

	public function __construct() {
		$this->init();
	}

	public function init() {
		$this->schedules = wp_get_schedules();
		$this->get_cron_tasks();
	}

	/**
	 * Populate our list of cron events and store them to a class-wide variable.
	 *
	 * Derived from `get_cron_events()` in WP Crontrol (https://plugins.svn.wordpress.org/wp-crontrol)
	 * by John Blackburn
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
	 * @return bool|WP_Error
	 */
	public function has_missed_cron() {
		if ( is_wp_error( $this->crons ) ) {
			return $this->crons;
		}

		foreach( $this->crons as $id => $cron ) {
			if ( ( $cron->time - time() ) < 0 ) {
				$this->last_missed_cron = $cron->hook;
				return true;
			}
		}

		return false;
	}
}