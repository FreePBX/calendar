<?php
namespace FreePBX\modules\Calendar\drivers;
use Ramsey\Uuid\Uuid;
abstract class Base {
	protected $calendar;
	public function __construct($calendar) {
		$this->calendar = $calendar;
	}

	/**
	 * Get Information about this driver
	 * @method getInfo
	 * @return array  array of information
	 */
	public function getInfo() {
		return array(
			"name" => _("Unknown Calendar")
		);
	}

	/**
	 * Add new calendar of this type
	 * @method addCalendar
	 * @param  array      $data Array of data about this calendar
	 */
	public function addCalendar($data) {
		$uuid = Uuid::uuid4()->toString();
		try {
			$this->updateCalendar($uuid,$data);
		} catch(\Exception $e) {
			$this->calendar->delCalendarByID($uuid);
			throw $e;
		}
	}

	/**
	 * Save full raw calendar ical
	 * @method saveiCal
	 * @param  string   $id   The calendar ID
	 * @param  string   $ical The calendar ical
	 */
	public function saveiCal($id,$ical) {
		$this->calendar->setConfig($id,$ical,'calendar-ical');
	}

	/**
	 * Update calendar by uuid
	 * @method updateCalendar
	 * @param  string         $id   The uuid to update
	 * @param  array         $data Array of data about this calendar
	 * @return boolean               true or false
	 */
	public function updateCalendar($id,$data) {
		throw new \Exception("Update Calendar is not defined");
	}

	/**
	 * Get the "Add" display
	 * @method getAddDisplay
	 * @return string              HTML to display
	 */
	public function getAddDisplay() {
		return '';
	}

	/**
	 * Get the "update" display
	 * @method getEditDisplay
	 * @param  array         $data Array of calendar information
	 * @return string               HTML to display
	 */
	public function getEditDisplay($data) {
		return '';
	}

	/**
	 * Process Calendar (Updating)
	 * @method processCalendar
	 * @param  array          $calendar Array of calendar information
	 * @return boolean                    true or false
	 */
	public function processCalendar($calendar) {
		return true;
	}
}
