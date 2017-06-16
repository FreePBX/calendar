<?php
namespace FreePBX\modules\Calendar\driver;
use om\IcalParser;
use Ramsey\Uuid\Uuid;
use it\thecsea\simple_caldav_client\SimpleCalDAVClient;
class Caldav {
	public $driver = 'Caldav';
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
			"name" => _("Remote CalDAV Calendar")
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
			"type" => "caldav",
			"purl" => $data['purl'],
			"surl" => $data['surl'],
			"username" => $data['username'],
			"password" => $data['password'],
			"calendars" => !empty($data['calendars']) ? $data['calendars'] : array(),
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
		return load_view(dirname(__DIR__)."/views/remote_caldav_settings.php",array('action' => 'add', 'calendars' => array(), 'data' => array('next' => 86400)));
	}

	/**
	 * Get the "update" display
	 * @method getEditDisplay
	 * @param  array         $data Array of calendar information
	 * @return string               HTML to display
	 */
	public function getEditDisplay($data) {
		$caldavClient = new SimpleCalDAVClient();
		$caldavClient->connect($data['purl'], $data['username'], $data['password']);
		$cals = $caldavClient->findCalendars();
		$calendars = array();
		foreach($cals as $calendar) {
			$id = $calendar->getCalendarID();
			$calendars[$id] = array(
				"id" => $id,
				"name" => $calendar->getDisplayName(),
				"selected" => in_array($id,$data['calendars'])
			);
		}
		return load_view(dirname(__DIR__)."/views/remote_caldav_settings.php",array('action' => 'edit', 'data' => $data, 'calendars' => $calendars));
	}

	/**
	 * Process Calendar (Updating)
	 * @method processCalendar
	 * @param  array          $calendar Array of calendar information
	 * @return boolean                    true or false
	 */
	public function processCalendar($calendar) {
		$caldavClient = new SimpleCalDAVClient();
		$caldavClient->connect($calendar['purl'], $calendar['username'], $calendar['password']);
		$cals = $caldavClient->findCalendars();
		$finalical =  'BEGIN:VCALENDAR';
		foreach($calendar['calendars'] as $c) {
			if(isset($cals[$c])) {
				$caldavClient->setCalendar($cals[$c]);
				$events = $caldavClient->getEvents();
				if(empty($events)) {
					continue;
				}
				$i = 0;
				$ical = '';
				$begin = '';
				$middle = '';
				foreach($events as $event) {
					$ical = $event->getData();
					if($i == 0){
						preg_match_all("/^(.*)BEGIN:VEVENT/s",$ical,$matches);
						$begin = $matches[1][0];
					}
					preg_match_all("/BEGIN:VEVENT(.*)END:VEVENT/s",$ical,$matches);
					$middle .= $matches[0][0]."\n";
				}
				$finalical .= $begin.$middle."END:VCALENDAR";
				$cal = new IcalParser();
				$cal->parseString($finalical);
				$this->calendar->processiCalEvents($calendar['id'], $cal); //will ids clash? they shouldnt????
			}
		}
		return true;
	}
}
