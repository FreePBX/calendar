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
	public function getInfo() {
		return array(
			"name" => _("Remote iCal Calendar")
		);
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
		$cal = new IcalRangedParser();
		$cal->setStartRange(new \DateTime());
		$end = new \DateTime();
		$end->add(new \DateInterval('P2M'));
		$cal->setEndRange($end);
		$finalical = $req->get($calendar['url'])->body;
		$cal->parseString($finalical);
		$this->calendar->processiCalEvents($calendar['id'], $cal, $finalical);
		$this->saveiCal($calendar['id'],$finalical);
		return true;
	}
}
