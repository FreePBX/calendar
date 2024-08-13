<?php
namespace FreePBX\modules\Calendar\drivers;
use FreePBX\modules\Calendar\IcalParser\IcalRangedParser;
use Ramsey\Uuid\Uuid;
use it\thecsea\simple_caldav_client\SimpleCalDAVClient;
use it\thecsea\simple_caldav_client\CalDAVException;
use Carbon\Carbon;
class Caldav extends Base {
	public $driver = 'Caldav';

	/**
	 * Get Information about this driver
	 * @method getInfo
	 * @return array  array of information
	 */
	public static function getInfo() {
		return ["name" => _("Remote CalDAV Calendar")];
	}

	/**
	 * Get the "Add" display
	 * @method getAddDisplay
	 * @return string              HTML to display
	 */
	public static function getAddDisplay() {
		return load_view(dirname(__DIR__)."/views/remote_caldav_settings.php",['action' => 'add', 'calendars' => [], 'data' => ['next' => 86400]]);
	}

	/**
	 * Get the "update" display
	 * @method getEditDisplay
	 * @param  array         $data Array of calendar information
	 * @return string               HTML to display
	 */
	public static function getEditDisplay($data) {
		$caldavClient = new SimpleCalDAVClient();
        $calendars = [];
      	
        try {
          $caldavClient->connect($data['purl'], $data['username'], $data['password']);
          $cals = $caldavClient->findCalendars();
          foreach($cals as $calendar) {
              $id = $calendar->getCalendarID();
              $calendars[$id] = ["id" => $id, "name" => $calendar->getDisplayName(), "selected" => in_array($id,$data['calendars'])];
          }
        } catch(CalDAVException) {
          $calendars[0] = ["id" => 0, "name" => _('Exception occured while retrieving the list of calendars. Are connection parameters correct?'), "selected" => false];
        }
		return load_view(dirname(__DIR__)."/views/remote_caldav_settings.php",['action' => 'edit', 'data' => $data, 'calendars' => $calendars]);
	}

	/**
	 * Update calendar by uuid
	 * @method updateCalendar
	 * @param  string         $id   The uuid to update
	 * @param  array         $data Array of data about this calendar
	 * @return boolean               true or false
	 */
	public function updateCalendar($data) {
		$calendar = ["name" => $data['name'], "description" => $data['description'], "type" => "caldav", "purl" => $data['purl'] ?? '', "surl" => $data['surl'] ?? '', "username" => $data['username'], "password" => $data['password'], "calendars" => !empty($data['calendars']) ? $data['calendars'] : [], "next" => !empty($data['next']) ? $data['next'] : 300];
		$ret = $this->processCalendar();
		parent::updateCalendar($calendar);
		return $ret;
	}


	/**
	 * Process Calendar (Updating)
	 * @method processCalendar
	 * @param  array          $calendar Array of calendar information
	 * @return boolean                    true or false
	 */
	public function processCalendar() {
		$headerSection = null;
  $eventsSection = null;
  $caldavClient = new SimpleCalDAVClient();
		$caldavClient->connect($this->calendar['purl'], $this->calendar['username'], $this->calendar['password']);
		$cals = $caldavClient->findCalendars();
		$start = Carbon::Now()->subYear();
		$end = Carbon::Now()->addYear();
		foreach($this->calendar['calendars'] as $c) {
			if(isset($cals[$c])) {
				$caldavClient->setCalendar($cals[$c]);
				$events = $caldavClient->getEvents($start->format('Ymd\THis\Z'),$end->format('Ymd\THis\Z'));
				$i = 0;
				$ical = '';
				$headerSection = 'BEGIN:VCALENDAR';
				$eventsSection = '';
				foreach($events as $event) {
					$ical = $event->getData();
					if($i == 0){
						preg_match_all("/^(.*)BEGIN:VEVENT/s",$ical,$matches);
						$eventsSection .= ($matches[1][0] ?? '');
					}
					preg_match_all("/BEGIN:VEVENT(.*)END:VEVENT/s",$ical,$matches);
					$eventsSection .= ($matches[0][0] ?? '')."\n";
					$i++;
				}
			}
		}
		$finalical = $headerSection.$eventsSection."END:VCALENDAR";
		$this->saveiCal($finalical);
		return true;
	}
}
