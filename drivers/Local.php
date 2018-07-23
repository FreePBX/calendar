<?php
namespace FreePBX\modules\Calendar\drivers;
use Ramsey\Uuid\Uuid;
use Carbon\Carbon;
use FreePBX\modules\IcalParser\IcalRangedParser;
use Eluceo\iCal\Component\Calendar as iCalendar;
use Eluceo\iCal\Component\Event;
use Eluceo\iCal\Property\Event\RecurrenceRule;
class Local extends Base {
	public $driver = 'Local';

	public function getInfo() {
		return array(
			"name" => _("Local Calendar")
		);
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

	public function updateEvent($calendarID, $event) {
		$calendar = $this->calendar->getCalendarByID($calendarID);

		$timezone = !empty($event['timezone']) ? $event['timezone'] : $calendar['timezone'];
		$vCalendar = new iCalendar($calendarID);
		$vEvent = new Event();
		// Make sure there is a title
		if (!isset($event['title']) || !trim($event['title'])) {
			throw new \Exception("No title provided");
		}
		$vEvent->setSummary(trim($event['title']));
		if (!isset($event['description'])) {
			$vEvent->setDescription("");
		} else {
			$vEvent->setDescription(trim($event['description']));
		}

		$vEvent->setUseTimezone(true);
		//$vEvent->setUseUtc(false);

		$vEvent->setDtStart(new Carbon($event['startdate']." ".$event['starttime'], $timezone));
		$vEvent->setDtEnd(new Carbon($event['enddate']." ".$event['endtime'], $timezone));
		if(!empty($event['allday']) && $event['allday'] == "yes") {
			$vEvent->setDtStart(new Carbon($event['startdate'], $timezone));
			$vEvent->setDtEnd(new Carbon($event['enddate'], $timezone));
		}
		if(!empty($event['reoccurring']) && $event['reoccurring'] == "yes") {
			if(!empty($event['rstartdate'])) {
				$vEvent->setDtStart(Carbon::createFromTimestamp($event['rstartdate'], $timezone));
				$vEvent->setDtEnd(Carbon::createFromTimestamp($event['renddate'], $timezone));
			}
			$recurrenceRule = new RecurrenceRule();
			switch($event['repeats']) {
				case "0":
					$recurrenceRule->setFreq(RecurrenceRule::FREQ_DAILY);
				break;
				case "1":
					$recurrenceRule->setByDay("MO,TU,WE,TH,FR");
					$recurrenceRule->setFreq(RecurrenceRule::FREQ_WEEKLY);
				break;
				case "2":
					$recurrenceRule->setByDay("MO,WE,FR");
					$recurrenceRule->setFreq(RecurrenceRule::FREQ_WEEKLY);
				break;
				case "3":
					$recurrenceRule->setByDay("TU,TH");
					$recurrenceRule->setFreq(RecurrenceRule::FREQ_WEEKLY);
				break;
				case "4":
					if(!empty($event['weekday']) && is_array($event['weekday'])) {
						$days = array();
						foreach($event['weekday'] as $day) {
							switch($day) {
								case "0":
									$days[] = 'MO';
								break;
								case "1":
									$days[] = 'TU';
								break;
								case "2":
									$days[] = 'WE';
								break;
								case "3":
									$days[] = 'TH';
								break;
								case "4":
									$days[] = 'FR';
								break;
								case "5":
									$days[] = 'SA';
								break;
								case "6":
									$days[] = 'SU';
								break;
								default:
								break;
							}
						}
						$recurrenceRule->setByDay(implode(",",$days));
					}
					$recurrenceRule->setFreq(RecurrenceRule::FREQ_WEEKLY);
				break;
				case "5":
					$recurrenceRule->setFreq(RecurrenceRule::FREQ_MONTHLY);
				break;
				case "6":
					$recurrenceRule->setFreq(RecurrenceRule::FREQ_YEARLY);
				break;
				default:
				break;
			}
			if(!empty($event['repeat-count'])) {
				$recurrenceRule->setInterval($event['repeat-count']);
			}
			if(!empty($event['occurrences'])) {
				$recurrenceRule->setCount($event['occurrences']);
			}
			if(!empty($event['afterdate'])) {
				$recurrenceRule->setUntil(new Carbon($event['afterdate'], $timezone));
			}

			$vEvent->setRecurrenceRule($recurrenceRule);
		}
		$uuid = ($event['eventid'] == 'new') ? (string)Uuid::uuid4() : $event['eventid'];
		$vEvent->setUniqueId($uuid);

		$vCalendar->addComponent($vEvent);

		$cal = new IcalRangedParser();
		$cal->setStartRange(new \DateTime());
		$end = new \DateTime();
		$end->add(new \DateInterval('P2M'));
		$cal->setEndRange($end);
		$render = $vCalendar->render();
		$render = str_replace('"','',$render); //TODO: bad
		$cal->parseString($render);
		$this->calendar->deleteEvent($calendarID,$uuid); //TODO this is strange

		foreach($cal->getSortedEvents() as $event) {
			$this->calendar->processiCalEvent($calendarID, $event, $vEvent->render());
		}
		$this->saveiCal($calendarID,$this->calendar->exportiCalCalendar($calendarID));
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
