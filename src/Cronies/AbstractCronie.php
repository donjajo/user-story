<?php

namespace USER_STORY\Cronies;

/**
 * Abstract class representing a cron job with customizable schedules and actions.
 */
abstract class AbstractCronie {
	/**
	 * Class construct.
	 */
	public function __construct() {
		add_filter( 'cron_schedules', array( $this, 'add_custom_schedules' ) );
		add_action( static::action_name(), array( $this, 'do_action' ) );
	}

	/**
	 * Executes the action associated with the defined functionality.
	 *
	 * This method must be implemented by any subclass to define specific behavior for the action.
	 */
	abstract public function do_action();

	/**
	 * Adds custom schedules to the existing cron job schedules.
	 *
	 * @param array $schedules The existing array of cron schedules.
	 *
	 * @return array The modified array of cron schedules with custom intervals added.
	 */
	public function add_custom_schedules( array $schedules ) {
		$schedules['every_2_hours'] = array(
			'interval' => 3600 * 2,
			'display'  => __( 'Every 2 hours', 'user-story' ),
		);

		return $schedules;
	}

	/**
	 * Retrieves the timestamp for the next scheduled run.
	 *
	 * @return int The current Unix timestamp indicating the next run time.
	 */
	public static function next_run() {
		return time();
	}

	/**
	 * Schedules a recurring event if it is not already scheduled.
	 *
	 * @return bool Returns true if the event is successfully scheduled or already exists, false if scheduling fails.
	 */
	public static function schedule() {
		if ( static::runs_every() && ! wp_next_scheduled( static::action_name() ) ) {
			$scheduled = wp_schedule_event( static::next_run(), static::runs_every(), static::action_name(), array(), true );
			if ( is_wp_error( $scheduled ) ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( $scheduled->get_error_message() );

				return false;
			}
		}

		return true;
	}

	/**
	 * WordPress schedule for cron to run on
	 *
	 * @return string
	 */
	public static function runs_every() {
		return '';
	}

	/**
	 * Logs a message with a timestamp.
	 *
	 * @param string $msg The message to log.
	 * @return void
	 */
	public function log( $msg ) {
		printf( "[%s]: %s\n", esc_html( gmdate( 'Y-m-d H:i:s' ) ), esc_html( $msg ) );
	}

	/**
	 * Unschedules a previously scheduled action hook.
	 *
	 * This method clears the scheduled action hook associated with the static::action_name().
	 * If an error occurs during the unscheduling process, it logs the error and returns false.
	 *
	 * @return bool Returns true if the action hook was successfully unscheduled, or false if an error occurred.
	 */
	public static function unschedule() {
		$unscheduled = wp_clear_scheduled_hook( static::action_name(), array(), true );
		if ( is_wp_error( $unscheduled ) ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( $unscheduled->get_error_message() );

			return false;
		}

		return true;
	}

	/**
	 * Generates and returns the action name specific to the class.
	 *
	 * @return string The action name combining a prefix and the class name.
	 */
	public static function action_name() {
		return 'cronie_' . static::class;
	}
}
