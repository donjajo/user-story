<?php

namespace USER_STORY\Objects;

use stdClass;

abstract class AbstractObject {

	/**
	 * Load data from database row
	 *
	 * @param stdClass $row database row.
	 * @return mixed
	 */
	abstract public static function load_from_object( $row );

	/**
	 * Map fields to column
	 *
	 * @return array
	 */
	abstract protected function column_map();

	/**
	 * Persist data to DB
	 *
	 * @return mixed
	 */
	abstract public function save();
}
