<?php
namespace FreePBX\modules\Calendar\drivers;
use FreePBX\modules\Calendar\IcalParser\IcalRangedParser;
use Ramsey\Uuid\Uuid;
class Ical extends Base {
	public $driver = 'Ical';

	/**
	 * Get Information about this driver
	 * @method getInfo
	 * @return array  array of information
	 */
	public static function getInfo() {
		return ["name" => _("Remote iCal Calendar")];
	}

	/**
	 * Get the "update" display
	 * @method getEditDisplay
	 * @param  array         $data Array of calendar information
	 * @return string               HTML to display
	 */
	public static function getEditDisplay($data) {
		return load_view(dirname(__DIR__)."/views/remote_ical_settings.php",['action' => 'edit', 'data' => $data]);
	}

	/**
	 * Get the "Add" display
	 * @method getAddDisplay
	 * @return string              HTML to display
	 */
	public static function getAddDisplay() {
		return load_view(dirname(__DIR__)."/views/remote_ical_settings.php",['action' => 'add', 'data' => ['next' => 86400]]);
	}

	/**
	 * Update calendar by uuid
	 * @method updateCalendar
	 * @param  string         $id   The uuid to update
	 * @param  array         $data Array of data about this calendar
	 * @return boolean               true or false
	 */
	public function updateCalendar($data) {
		$calendar = ["name" => $data['name'], "description" => $data['description'], "type" => "ical", "url" => $data['url'], "next" => !empty($data['next']) ? $data['next'] : 300];
		$ret = parent::updateCalendar($calendar);
		$this->processCalendar();
		return $ret;
	}

	public function processCalendar() {
		$req = \FreePBX::Curl()->requests($this->calendar['url']);
		$finalical = $req->get($this->calendar['url'])->body;
		if (!preg_match('/BEGIN:VCALENDAR/', (string) $finalical)) {
			return  ["status" => false, "message" => _("Invalid ical remote URL")];
		}
		$this->saveiCal($finalical);
		return  ["status" => true, "message" => _("Calendar processed successfully")];
	}
}
