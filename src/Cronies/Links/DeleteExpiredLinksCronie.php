<?php

namespace USER_STORY\Cronies\Links;

use USER_STORY\Components\Links\Links;
use USER_STORY\Cronies\AbstractCronie;

/**
 * Responsible for scheduling and executing the deletion of expired links at specified intervals.
 */
class DeleteExpiredLinksCronie extends AbstractCronie {

	/**
	 * Executes the action to delete expired links.
	 *
	 * @return void Does not return any value.
	 */
	public function do_action() {
		Links::deleted_expired_links();
	}

	/**
	 * Time interval to run
	 *
	 * @return string Returns the execution interval as a string.
	 */
	public static function runs_every() {
		return 'every_2_hours';
	}
}
