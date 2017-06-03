<?php
namespace FreePBX\modules\Calendar\driver;
use om\IcalParser;
use Ramsey\Uuid\Uuid;
class Ical {
	public $driver = 'Ical';
	private $calendar;

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
			"name" => _("Remote iCal Calendar")
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
	 * Update calendar by uuid
	 * @method updateCalendar
	 * @param  string         $id   The uuid to update
	 * @param  array         $data Array of data about this calendar
	 * @return boolean               true or false
	 */
	public function updateCalendar($id,$data) {
		if(empty($id)) {
			throw new \Exception("Calendar ID is empty");
		}
		$calendar = array(
			"name" => $data['name'],
			"description" => $data['description'],
			"type" => "ical",
			"url" => $data['url'],
			"next" => !empty($data['next']) ? $data['next'] : 300
		);
		$this->calendar->setConfig($id,$calendar,'calendars');
		$calendar['id'] = $id;
		return $this->processCalendar($calendar);
	}

	/**
	 * Get the "Add" display
	 * @method getAddDisplay
	 * @return string              HTML to display
	 */
	public function getAddDisplay() {
		return load_view(dirname(__DIR__)."/views/remote_ical_settings.php",array('action' => 'add', 'data' => array('next' => 86400)));
	}

	/**
	 * Get the "update" display
	 * @method getEditDisplay
	 * @param  array         $data Array of calendar information
	 * @return string               HTML to display
	 */
	public function getEditDisplay($data) {
		return load_view(dirname(__DIR__)."/views/remote_ical_settings.php",array('action' => 'edit', 'data' => $data));
	}

	/**
	 * Process Calendar (Updating)
	 * @method processCalendar
	 * @param  array          $calendar Array of calendar information
	 * @return boolean                    true or false
	 */
	public function processCalendar($calendar) {
		$req = \FreePBX::Curl()->requests($calendar['url']);
		$cal = new IcalParser();
		$cal->parseString($req->get($calendar['url'])->body);
		return $this->calendar->processiCalEvents($calendar['id'], $cal);
	}
}
