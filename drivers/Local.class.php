<?php
namespace FreePBX\modules\Calendar\driver;
use Ramsey\Uuid\Uuid;
class Local {
	public $driver = 'Local';
	private $calendar;

	public function __construct($calendar) {
		$this->calendar = $calendar;
	}

	public function getInfo() {
		return array(
			"name" => _("Local Calendar")
		);
	}

	public function addCalendar($data) {
		$uuid = Uuid::uuid4()->toString();
		try {
			$this->updateCalendar($uuid,$data);
		} catch(\Exception $e) {
			$this->calendar->delCalendarByID($uuid);
			throw $e;
		}
	}

	public function updateCalendar($id,$data) {
		if(empty($id)) {
			throw new \Exception("Calendar ID is empty");
		}
		$calendar = array(
			"name" => $data['name'],
			"description" => $data['description'],
			"type" => 'local',
			"timezone" => $data['timezone']
		);
		$this->calendar->setConfig($id,$calendar,'calendars');
		$calendar['id'] = $id;
		return true;
	}

	public function getAddDisplay() {
		return load_view(dirname(__DIR__)."/views/local_settings.php",array('action' => 'add', 'timezone' => $this->calendar->systemtz));
	}

	public function getEditDisplay($data) {
		return load_view(dirname(__DIR__)."/views/local_settings.php",array('action' => 'edit', 'data' => $data, 'timezone' => $data['timezone']));
	}

	public function processCalendar($calendar) {
		return true;
	}
}
