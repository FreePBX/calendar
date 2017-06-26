<?php
namespace FreePBX\modules;
use Moment\Moment;
use Moment\CustomFormats\MomentJs;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use it\thecsea\simple_caldav_client\SimpleCalDAVClient;
use om\IcalParser;
use Eluceo\iCal\Component\Calendar as iCalendar;
use Eluceo\iCal\Component\Event;
use Eluceo\iCal\Property\Event\RecurrenceRule;
use \jamesiarmes\PhpEws\Client;
use \FreePBX\modules\Calendar\PhpEws\Calendar as EWSCalendar;
use malkusch\lock\mutex\FlockMutex;

include __DIR__."/vendor/autoload.php";
include __DIR__."/PhpEws/Calendar.php";

class Calendar extends \DB_Helper implements \BMO {
	private $now; //right now, private so it doesnt keep updating
	private $drivers;
	private $guimessage;

	public function __construct($freepbx = null) {
		if ($freepbx == null) {
			throw new Exception("Not given a FreePBX Object");
		}
		$this->FreePBX = $freepbx;
		$this->db = $freepbx->Database;
		$this->systemtz = $this->FreePBX->View()->getTimezone();
		$this->now = Carbon::now($this->systemtz);
		$this->eventDefaults = array(
				'uid' => '',
				'user' => '',
				'description' => '',
				'hookdata' => '',
				'active' => true,
				'generatehint' => false,
				'generatefc' => false,
				'eventtype' => 'calendaronly',
				'weekdays' => '',
				'monthdays' => '',
				'months' => '',
				'timezone' => $this->systemtz,
				'startdate' => '',
				'enddate' => '',
				'starttime' => '',
				'endtime' => '',
				'repeatinterval' => '',
				'frequency' => '',
				'truedest' => '',
				'falsedest' => ''
			);
	}

	public function setTimezone($timezone) {
		if(empty($timezone)) {
			return false;
		}
		$this->systemtz = $timezone;
		$this->now = Carbon::now($this->systemtz);
	}

	public function backup() {}
	public function restore($backup) {}
	public function install(){
		$crons = $this->FreePBX->Cron->getAll();
		foreach($crons as $c) {
			if(preg_match('/fwconsole calendar sync/',$c,$matches)) {
				$this->FreePBX->Cron->remove($c);
			}
		}

		$ampbin = $this->FreePBX->Config->get('AMPSBIN');
		$this->FreePBX->Cron->add(array(
			'minute' => '*/1',
			"command" => $ampbin.'/fwconsole calendar sync 2>&1 > /dev/null')
		);
	}
	public function uninstall(){
		$crons = $this->FreePBX->Cron->getAll();
		foreach($crons as $c) {
			if(preg_match('/fwconsole calendar sync/',$c,$matches)) {
				FreePBX::Cron()->remove($c);
			}
		}
	}
	public function doConfigPageInit($page) {
		switch ($page) {
			case 'calendar':
				$action = isset($_REQUEST['action'])?$_REQUEST['action']:'';
				switch($action) {
					case "add":
						if(isset($_POST['name'])) {
							$type = $_POST['type'];
							$driver = $this->getDriver($type);
							$allCalendars = $this->listCalendars();
							try {
								return $driver->addCalendar($_POST);
							} catch(\Exception $e) {
								$this->guimessage = array(
									"type" => "danger",
									"message" => $e->getMessage()
								);
							}
						}
					break;
					case "edit":
						if(isset($_POST['name'])) {
							$id = $_POST['id'];
							$type = $_POST['type'];
							$driver = $this->getDriver($type);
							try {
								return $driver->updateCalendar($id,$_POST);
							} catch(\Exception $e) {
								$this->guimessage = array(
									"type" => "danger",
									"message" => $e->getMessage()
								);
							}
						}
					break;
					case "delete":
						$this->delCalendarByID($_REQUEST['id']);
					break;
				}
			break;
			case 'calendargroups':
				$action = isset($_REQUEST['action'])?$_REQUEST['action']:'';
				$description = isset($_REQUEST['description'])?$_REQUEST['description']:'';
				$events = isset($_REQUEST['events'])?$_REQUEST['events']:array();
				switch($action) {
					case "add":
						if(isset($_POST['name'])) {
							$name = !empty($_POST['name']) ? $_POST['name'] : array();
							$calendars = !empty($_POST['calendars']) ? $_POST['calendars'] : array();
							$categories = !empty($_POST['categories']) ? $_POST['categories'] : array();
							$events = !empty($_POST['events']) ? $_POST['events'] : array();
							$this->addGroup($name,$calendars,$categories,$events);
						}
					break;
					case "edit":
						if(isset($_POST['name'])) {
							$id = $_POST['id'];
							$name = !empty($_POST['name']) ? $_POST['name'] : array();
							$calendars = !empty($_POST['calendars']) ? $_POST['calendars'] : array();
							$categories = !empty($_POST['categories']) ? $_POST['categories'] : array();
							$events = !empty($_POST['events']) ? $_POST['events'] : array();
							$this->updateGroup($id,$name,$calendars,$categories,$events);
						}
					break;
					case "delete":
						$id = $_GET['id'];
						$this->deleteGroup($id);
					break;
				}
			break;
		}
	}

	public function getAllDrivers() {
		if(!empty($this->drivers)) {
			return $this->drivers;
		}
		foreach(glob(__DIR__."/drivers/*.class.php") as $driver) {
			$name = basename($driver);
			$name = explode(".",$name);
			$name = $name[0];
			$name = ucfirst(strtolower($name));
			$class = "\FreePBX\modules\Calendar\driver\\".$name;
			include($driver);
			$this->drivers[$name] = new $class($this);
		}
		return $this->drivers;
	}

	/**
	 * Get Calendar Driver
	 * @method getDriver
	 * @param  string    $driver The driver name
	 * @return object            The object
	 */
	public function getDriver($driver) {
		$driver = basename($driver);
		$driver = ucfirst(strtolower($driver));
		if(!empty($this->drivers[$driver])) {
			return $this->drivers[$driver];
		}
		if(!file_exists(__DIR__."/drivers/".$driver.".class.php")) {
			throw new \Exception("Driver [$driver] does not exist!");
		}
		include(__DIR__."/drivers/".$driver.".class.php");
		$class = "\FreePBX\modules\Calendar\driver\\".$driver;
		$this->drivers[$driver] = new $class($this);
		return $this->drivers[$driver];
	}

	public function ajaxRequest($req, &$setting) {
		switch($req){
			case 'grid':
			case 'events':
			case 'eventform':
			case 'delevent':
			case 'groupsgrid':
			case 'groupeventshtml':
			case 'getcaldavcals':
			case 'getewscals':
			case 'updatesource':
			case 'ewsautodetect':
			case 'duplicate':
				return true;
		}
		return false;
	}
	public function ajaxHandler() {
		switch ($_REQUEST['command']) {
			case 'ewsautodetect':
				try {
					$settings = EWSCalendar::autoDiscoverSettings($_POST['email'], $_POST['password']);
				} catch(\Exception $e) {
					return array("status" => false, "message" => $e->getMessage());
				}
				$settings['status'] = true;
				return $settings;
			break;
			case 'getewscals':
				$server = $_POST['purl'];
				$username = $_POST['username'];
				$password = $_POST['password'];
				$version = constant('\jamesiarmes\PhpEws\Client::'.$_POST['version']);
				$ews = new EWSCalendar($server, $username, $password, $version);
				$chtml = '';
				foreach($ews->getAllCalendars() as $c) {
					$chtml .= '<option value="'.$c['id'].'">'.$c['name'].'</option>';
				}
				return array("calshtml" => $chtml);
			break;
			case 'getcaldavcals':
				$caldavClient = new SimpleCalDAVClient();
				try {
					$caldavClient->connect($_POST['purl'], $_POST['username'], $_POST['password']);
				} catch (\Exception $e) {
					$chtml = $e->getMessage().'<input type="hidden" id="urlerror" value="error">';
					return array("calshtml" => $chtml, 'status' => false);
				}
				$calendars = $caldavClient->findCalendars();
				$chtml = '';
				foreach($calendars as $calendar) {
					$chtml .= '<option value="'.$calendar->getCalendarID().'">'.$calendar->getDisplayName().'</option>';
				}
				return array("calshtml" => $chtml, 'status' => true);
			break;
			case 'groupeventshtml':
				$allCalendars = $this->listCalendars();
				$calendars = !empty($_POST['calendars']) ? $_POST['calendars'] : array();
				$dcategories = !empty($_POST['categories']) ? $_POST['categories'] : array();
				$categories = array();
				foreach($dcategories as $cat) {
					$parts = explode("_",$cat,2);
					$categories[$parts[0]][] = $parts[1];
				}
				$chtml = '';
				foreach($calendars as $calendarID) {
					$cats = $this->getCategoriesByCalendarID($calendarID);
					if(empty($cats)){
						$chtml .= '<optgroup label="'.sprintf(_("No Categories for %s"),$allCalendars[$calendarID]['name']).'">';
						continue;
					}
					$chtml .= '<optgroup label="'.$allCalendars[$calendarID]['name'].'">';
					foreach($cats as $name => $events) {
						$chtml .= '<option value="'.$calendarID.'_'.$name.'">'.$name.'</option>';
					}
					$chtml .= '</optgroup>';
				}
				$ehtml = '';
				foreach($calendars as $calendarID) {
					$events = $this->listEvents($calendarID);
					if(empty($events)){
						$ehtml .= '<optgroup label="'.sprintf(_("No Events for %s"),$allCalendars[$calendarID]['name']).'">';
						continue;
					}
					if(!empty($categories[$calendarID])) {
						$valid = array();
						$cats = $this->getCategoriesByCalendarID($calendarID);
						foreach($cats as $category => $evts) {
							if(in_array($category,$categories[$calendarID])) {
								$evts = array_flip($evts);
								$valid = array_merge($valid,$evts);
							}
						}
						$events = array_intersect_key($events,$valid);
					} elseif(!empty($categories)) {
						$events = array();
					}
					$ehtml .= '<optgroup label="'.$allCalendars[$calendarID]['name'].'">';
					foreach($events as $event) {
						$extended = $event['allDay'] ? $event['startdate'] : $event['startdate'].' '._('to').' '.$event['enddate'];
						$ehtml .= '<option value="'.$calendarID.'_'.$event['uid'].'">'.$event['name'].' ('.$extended.')</option>';
					}
					$ehtml .= '</optgroup>';
				}
				return array("eventshtml" => $ehtml, "categorieshtml" => $chtml);
			break;
			case 'delevent':
				$calendarID = $_POST['calendarid'];
				$eventID = $_POST['eventid'];
				$this->deleteEvent($calendarID,$eventID);
			break;
			case 'duplicate':
				$name = $_REQUEST['value'];
				$id = $_REQUEST['id'];
				$calendars = $this ->listCalendars();
				//check whether its edit or add
				if (array_key_exists($id,$calendars)){
					// its an edit check name changed or duplicated
					// so unset the array key and before doing the duplicate check
					unset($calendars[$id]);
				}
				$calnames = array();
				foreach($calendars as $cal) {
					$calnames[] = $cal['name'];
				}
				if (in_array($name, $calnames)){
					return array('value' => 1);
				}
				else{
					return array('value' => 0);
				}
			break;
			case 'grid':
				$calendars = $this->listCalendars();
				$final = array();
				foreach($calendars as $id => $data) {
					$data['id'] = $id;
					$final[] = $data;
				}
				return $final;
			break;
			case 'events':
				$start = new Carbon($_GET['start'],$_GET['timezone']);
				$end = new Carbon($_GET['end'],$_GET['timezone']);
				$events = $this->listEvents($_REQUEST['calendarid'],$start, $end);
				$events = is_array($events) ? $events : array();
				return array_values($events);
			break;
			case 'eventform':
				$calendarID = $_POST['calendarid'];
				$calendar = $this->getCalendarByID($calendarID);

				$timezone = !empty($_POST['timezone']) ? $_POST['timezone'] : $calendar['timezone'];
				$vCalendar = new iCalendar($calendarID);
				$vEvent = new Event();
				$vEvent->setUseTimezone(true);
				// Make sure there is a title
				if (!isset($_POST['title']) || !trim($_POST['title'])) {
					throw new \Exception("No title provided");
				}
				$vEvent->setSummary(trim($_POST['title']));
				if (!isset($_POST['description'])) {
					$vEvent->setDescription("");
				} else {
					$vEvent->setDescription(trim($_POST['description']));
				}
				$vEvent->setDtStart(new Carbon($_POST['startdate']." ".$_POST['starttime'], $timezone));
				$vEvent->setDtEnd(new Carbon($_POST['enddate']." ".$_POST['endtime'], $timezone));
				if(!empty($_REQUEST['allday']) && $_REQUEST['allday'] == "yes") {
					$vEvent->setDtStart(new Carbon($_POST['startdate'], $timezone));
					$vEvent->setDtEnd(new Carbon($_POST['enddate'], $timezone));
				}
				if(!empty($_REQUEST['reoccurring']) && $_REQUEST['reoccurring'] == "yes") {
					if(!empty($_POST['rstartdate'])) {
						$vEvent->setDtStart(Carbon::createFromTimestamp($_POST['rstartdate'], $timezone));
						$vEvent->setDtStart(Carbon::createFromTimestamp($_POST['renddate'], $timezone));
					}
					$recurrenceRule = new RecurrenceRule();
					switch($_REQUEST['repeats']) {
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
							if(!empty($_REQUEST['weekday']) && is_array($_REQUEST['weekday'])) {
								$days = array();
								foreach($_REQUEST['weekday'] as $day) {
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
					if(!empty($_REQUEST['repeat-count'])) {
						$recurrenceRule->setInterval($_REQUEST['repeat-count']);
					}
					if(!empty($_REQUEST['occurrences'])) {
						$recurrenceRule->setCount($_REQUEST['occurrences']);
					}
					if(!empty($_REQUEST['afterdate'])) {
						$recurrenceRule->setUntil(new Carbon($_POST['afterdate'], $timezone));
					}

					$vEvent->setRecurrenceRule($recurrenceRule);
				}
				$uuid = ($_REQUEST['eventid'] == 'new') ? (string)Uuid::uuid4() : $_REQUEST['eventid'];
				$vEvent->setUniqueId($uuid);

				$vCalendar->addComponent($vEvent);

				$cal = new IcalParser();
				$render = $vCalendar->render();
				$render = str_replace('"','',$render); //TODO: bad
				$cal->parseString($render);
				$this->deleteEvent($calendarID,$uuid); //TODO this is strange
				foreach($cal->getSortedEvents() as $event) {
					$this->processiCalEvent($calendarID, $event);
				}
			break;
			case 'groupsgrid':
				$groups =  $this->listGroups();
				$final = array();
				foreach($groups as $id => $data) {
					$data['id'] = $id;
					$final[] = $data;
				}
				return $final;
			case 'updatesource':
				return $this->refreshCalendarById($_REQUEST['calendarid']);
				break;
		}
	}

	public function showCalendarGroupsPage() {
		$action = !empty($_GET['action']) ? $_GET['action'] : '';
		switch($action) {
			case "add":
				$calendars = $this->listCalendars();
				return load_view(__DIR__."/views/calendargroups.php",array("calendars" => $calendars, "action" => _("Add")));
			break;
			case "edit":
				$calendars = $this->listCalendars();
				$group = $this->getGroup($_REQUEST['id']);
				return load_view(__DIR__."/views/calendargroups.php",array("calendars" => $calendars, "group" => $group,'id' => $_GET['id'],"action" => _("Edit")));
			break;
			case "view":
			break;
			default:
				return load_view(__DIR__."/views/calendargroupgrid.php",array());
			break;
		}
	}

	public function showCalendarPage() {
		$action = !empty($_GET['action']) ? $_GET['action'] : '';
		switch($action) {
			case "add":
				$type = !empty($_GET['type']) ? $_GET['type'] : '';
				$driver = $this->getDriver($type);
				return $driver->getAddDisplay();
			break;
			case "edit":
				$data = $this->getCalendarByID($_GET['id']);
				$driver = $this->getDriver($data['type']);
				return $driver->getEditDisplay($data);
			break;
			case "view":
				$data = $this->getCalendarByID($_GET['id']);
				\Moment\Moment::setLocale('en_US'); //get this from freepbx...
				$locale = \Moment\MomentLocale::getLocaleContent();
				return load_view(__DIR__."/views/calendar.php",array('action' => 'view', 'type' => $data['type'], 'data' => $data, 'locale' => $locale));
			break;
			default:
				$dropdown = array();
				$drivers = $this->getAllDrivers();
				foreach($drivers as $driver => $object) {
					$dropdown[$driver] = $object->getInfo()['name'];
				}
				return load_view(__DIR__."/views/grid.php",array('message' => $this->guimessage, 'dropdown' => $dropdown));
			break;
		}
	}

	/**
	 * Get Event by Event ID
	 * @param  string $calendarID The calendar ID
	 * @param  string $id The event ID
	 * @return array     The returned event array
	 */
	public function getEvent($calendarID,$eventID) {
		$events = $this->getAll($calendarID.'-events');
		return isset($events[$eventID]) ? $events[$eventID] : false;
	}

	/**
	 * List Calendars
	 * @return array The returned calendar array
	 */
	public function listCalendars() {
		$calendars = $this->getAll('calendars');
		return $calendars;
	}

	public function namesJSON(){
		$cals = $this->listCalendars();
		$ret = array();
		$cals = is_array($cals)?$cals:array();
		foreach ($cals as $cal) {
			if(isset($cal['name'])){
				$ret[] = $cal['name'];
			}
		}
		return '<script> var calnames='.json_encode($ret).'</script>';
	}
	/**
	 * Delete Calendar by ID
	 * @param  string $id The calendar ID
	 */
	public function delCalendarByID($id) {
		$this->setConfig($id,false,'calendars');
		$this->delById($id."-events");
		$this->delById($id."-linked-events");
		$this->delById($id."-categories-events");
	}

	/**
	 * Get Calendar by ID
	 * @param  string $id The Calendar ID
	 * @return array     Calendar data
	 */
	public function getCalendarByID($id) {
		$final = $this->getConfig($id,'calendars');
		if(empty($final)) {
			return false;
		}
		$final['id'] = $id;
		if (!isset($final['calendars']) || !is_array($final['calendars'])) {
			$final['calendars'] = array();
		}
		$final['timezone'] = !empty($final['timezone']) ? $final['timezone'] : $this->systemtz;
		return $final;
	}

	/**
	 * Expand Recurring Days
	 * @param  string $id    Event ID
	 * @param  array $event Array of event information
	 * @return array        Array of Event information
	 */
	public function expandRecurring($id, $event) {
		if(!$event['recurring']) {
			$event['linkedid'] = $id;
			$event['uid'] = $id;
			$tmp['rstartdate'] = '';
			return array($event['uid'] => $event);
		}
		$final = array();
		$i = 0;
		$startdate = null;
		$enddate = null;
		foreach($event['events'] as $evt) {
			$tmp = $event;
			unset($tmp['events']);
			//TODO: This is ugly, work on it later
			$tmp['starttime'] = $evt['starttime'];
			$tmp['endtime'] = $evt['endtime'];
			$tmp['linkedid'] = $id;
			$tmp['uid'] = $id."_".$i;
			if($i == 0){
				$startdate = $tmp['starttime'];
				$enddate = $tmp['endtime'];
			}
			$tmp['rstartdate'] = $startdate;
			$tmp['renddate'] = $enddate;
			$final[$tmp['uid']] = $tmp;
			$i++;
		}

		return $final;
	}

	/**
	 * List Events
	 * @param  string $calendarID The calendarID to reference
	 * @param  object $start  Carbon Object
	 * @param  object $stop   Carbon Object
	 * @param  bool $subevents Break date ranges in to daily events.
	 * @return array  an array of events
	 */
	public function listEvents($calendarID, $start = null, $stop = null, $subevents = false) {
		$return = array();
		$calendar = $this->getCalendarByID($calendarID);
		$data = $this->getAll($calendarID.'-events');
		$events = array();
		foreach($data as $id => $event) {
			$d = $this->expandRecurring($id, $event);
			$events = array_merge($events,$d);
		}

		if(!empty($start) && !empty($stop)){
			$events = $this->eventFilterDates($events, $start, $stop, $calendar['timezone']);
		}

		foreach($events as $uid => $event){
			$starttime = !empty($event['starttime'])?$event['starttime']:'00:00:00';
			$endtime = !empty($event['endtime'])?$event['endtime']:'23:59:59';
			$event['ustarttime'] = $event['starttime'];
			$event['uendtime'] = $event['endtime'];
			$event['title'] = $event['name'];
			$event['uid'] = $uid;
			if(($event['starttime'] != $event['endtime']) && $subevents) {
				$startrange = Carbon::createFromTimeStamp($event['starttime'],$calendar['timezone']);
				$endrange = Carbon::createFromTimeStamp($event['endtime'],$calendar['timezone']);
				$daterange = new \DatePeriod($startrange, CarbonInterval::day(), $endrange);
				$i = 0;
				foreach($daterange as $d) {
					$tempevent = $event;
					$tempevent['uid'] = $uid.'_'.$i;
					$tempevent['ustarttime'] = $event['starttime'];
					$tempevent['uendtime'] = $event['endtime'];
					$tempevent['startdate'] = $d->format('Y-m-d');
					$tempevent['enddate'] = $d->format('Y-m-d');
					$tempevent['starttime'] = $d->format('H:i:s');
					$tempevent['endtime'] = $d->format('H:i:s');
					$tempevent['start'] = sprintf('%sT%s',$tempevent['startdate'],$tempevent['starttime']);
					$tempevent['end'] = sprintf('%sT%s',$tempevent['enddate'],$tempevent['endtime']);
					$tempevent['allDay'] = ($event['endtime'] - $event['starttime']) === 86400;
					//$tempevent['now'] = $this->now->between($start, $end);
					$tempevent['parent'] = $event;
					$return[$tempevent['uid']] = $tempevent;
					$i++;
				}
			}else{
				$event['ustarttime'] = $event['starttime'];
				$event['uendtime'] = $event['endtime'];

				$start = Carbon::createFromTimeStamp($event['ustarttime'],$calendar['timezone']);
				if($event['starttime'] == $event['endtime']) {
					$event['allDay'] = true;
					$end = $start->copy()->addDay();
				} else {
					$event['allDay'] = ($event['endtime'] - $event['starttime']) === 86400;
					$end = Carbon::createFromTimeStamp($event['uendtime'],$calendar['timezone']);
				}

				$event['uid'] = $uid;
				$event['startdate'] = $start->format('Y-m-d');
				$event['enddate'] = $end->format('Y-m-d');
				$event['starttime'] = $start->format('H:i:s');
				$event['endtime'] = $end->format('H:i:s');
				$event['start'] = sprintf('%sT%s',$event['startdate'],$event['starttime']);
				$event['end'] = sprintf('%sT%s',$event['enddate'],$event['endtime']);
				$event['now'] = $this->now->between($start, $end);

				$return[$uid] = $event;
			}
		}
		uasort($return, function($a, $b) {
			if ($a['ustarttime'] == $b['ustarttime']) {
				return 0;
			}
			return ($a['ustarttime'] < $b['ustarttime']) ? -1 : 1;
		});
		return $return;
	}

	/**
	 * Filter Event Dates
	 * @param  array $data  Array of Events
	 * @param  object $start  Carbon Object
	 * @param  object $stop   Carbon Object
	 * @return array  an array of events
	 */
	public function eventFilterDates($data, $start, $end, $timezone){
		$final = $data;
		foreach ($data as $key => $value) {
			if(!isset($value['starttime']) || !isset($value['endtime'])){
				unset($final[$key]);
				continue;
			}
			$tz = isset($value['timezone'])?$value['timezone']:$timezone;
			$startdate = Carbon::createFromTimeStamp($value['starttime'],$tz);
			$enddate = Carbon::createFromTimeStamp($value['endtime'],$tz);

			if($start->between($startdate,$enddate) || $end->between($startdate,$enddate)) {
				continue;
			}

			if($startdate->between($start,$end) || $enddate->between($start,$end)) {
				continue;
			}

			$daysLong = $startdate->diffInDays($enddate);
			if($daysLong > 0) {
				$daterange = new \DatePeriod($startdate, CarbonInterval::day(), $enddate);
				foreach($daterange as $d) {
					if($d->between($start,$end)) {
						continue(2);
					}
				}
			}
			unset($final[$key]);
		}
		return $final;
	}

	/**
	 * Add Event to specific calendar
	 * @param string $calendarID  The Calendar ID
	 * @param string $eventID     The Event ID, if null will auto generatefc
	 * @param string $name        The event name
	 * @param string $description The event description
	 * @param string $starttime   The event start timezone
	 * @param string $endtime     The event end time
	 * @param boolean $recurring  Is this a recurring event
	 * @param array $rrules       Recurring rules
	 * @param array $categories   The categories assigned to this event
	 */
	public function addEvent($calendarID,$eventID=null,$name,$description,$starttime,$endtime,$timezone=null,$recurring=false,$rrules=array(),$categories=array()){
		$eventID = !is_null($eventID) ? $eventID : Uuid::uuid4()->toString();
		$this->updateEvent($calendarID,$eventID,$name,$description,$starttime,$endtime,$timezone,$recurring,$rrules,$categories);
	}

	/**
	 * Update Event on specific calendar
	 * @param string $calendarID  The Calendar ID
	 * @param string $eventID     The Event ID, if null will auto generatefc
	 * @param string $name        The event name
	 * @param string $description The event description
	 * @param string $starttime   The event start timezone
	 * @param string $endtime     The event end time
	 * @param boolean $recurring  Is this a recurring event
	 * @param array $rrules       Recurring rules
	 * @param array $categories   The categories assigned to this event
	 */
	public function updateEvent($calendarID,$eventID,$name,$description,$starttime,$endtime,$timezone=null,$recurring=false,$rrules=array(),$categories=array()) {
		if(!isset($eventID) || is_null($eventID) || trim($eventID) == "") {
			throw new \Exception("Event ID can not be blank");
		}
		$event = array(
			"name" => $name,
			"description" => $description,
			"recurring" => $recurring,
			"rrules" => $rrules,
			"events" => array(),
			"categories" => $categories,
			"timezone" => $timezone
		);
		if($recurring) {
			$oldEvent = $this->getConfig($eventID,$calendarID."-events");
			if(!empty($oldEvent)) {
				$event['events'] = $oldEvent['events'];
			}
			$event['events'][] = array(
				"starttime" => $starttime,
				"endtime" => $endtime
			);
		} else {
			$event['starttime'] = $starttime;
			$event['endtime'] = $endtime;
		}

		$this->setConfig($eventID,$event,$calendarID."-events");

		foreach($categories as $category) {
			$events = $this->getConfig($category,$calendarID."-categories-events");
			if(empty($events)) {
				$events = array(
					$eventID
				);
			} elseif(!in_array($eventID,$events)) {
				$events[] = $eventID;
			}
			$this->setConfig($category,$events,$calendarID."-categories-events");
		}
	}

	/**
	 * Delete event from specific calendar
	 * @param  string $calendarID The Calendar ID
	 * @param  string $eventID    The event ID
	 */
	public function deleteEvent($calendarID,$eventID) {
		$this->setConfig($eventID,false,$calendarID."-events");
	}

	/**
	 * Sync Calendars
	 */
	public function sync($output) {
		$cal = $this;
		$mutex = new FlockMutex(fopen(__FILE__, "r"));
		$mutex->synchronized(function () use ($cal,$output) {
			$calendars = $cal->listCalendars();
			foreach($calendars as $id => $calendar) {
				$output->write("\tSyncing ".$calendar['name']."...");
				$last = $cal->getConfig($id,'calendar-sync');
				$last = !empty($last) ? $last : 0;
				$next = !empty($calendar['next']) ? $calendar['next'] : 300;
				if($calendar['type'] !== "local" && ($last + $next) < time()) {
					$calendar['id'] = $id;
					$cal->processCalendar($calendar);
					$cal->setConfig($id,time(),'calendar-sync');
					$output->writeln("Done");
					$this->FreePBX->Hooks->processHooks($calendar['id']);
				} else {
					$output->writeln("Skipping");
				}
			}
		});

	}

	/**
	 * Process remote calendar actions
	 * @param  array $calendar Calendar information (From getCalendarByID)
	 */
	public function processCalendar($calendar) {
		if(empty($calendar['id'])) {
			throw new \Exception("Calendar ID can not be empty!");
		}

		$driver = $this->getDriver($calendar['type']);
		return $driver->processCalendar($calendar);
	}

	/**
	 * Process iCal Type events
	 * @param  string     $calendarID The Calendar ID
	 * @param  IcalParser $cal        IcalParser Object reference of events
	 */
	public function processiCalEvents($calendarID, IcalParser $cal) {
		//dont let sql update until the end of this
		//This might be bad.. ok it probably is bad. We should just get a Range of events
		//works for now though.
		$this->db->beginTransaction();

		//Trash old events because tracking by UIDs for Google is a whack-attack
		//The UIDs for matching elements should still match unless the calendar
		//has drastically changed and I couldn't track them even if I wanted to!!
		$this->delById($calendarID."-events");
		$this->delById($calendarID."-linked-events");
		$this->delById($calendarID."-categories-events");

		foreach ($cal->getSortedEvents() as $event) {
			if($event['DTSTART']->format('U') == 0) {
				continue;
			}

			$event['UID'] = isset($event['UID']) ? $event['UID'] : 0;

			$this->processiCalEvent($calendarID, $event);
		}


		$this->db->commit(); //now update just incase this takes a long time
	}

	/**
	 * Process single iCalEvent
	 * @param  string $calendarID The Calendar ID
	 * @param  array $event      The iCal Event
	 */
	public function processiCalEvent($calendarID, $event) {
		$event['UID'] = isset($event['UID']) ? $event['UID'] : 0;

		if(!empty($event['RECURRING'])) {
			$recurring = true;
			$rrules = array(
				"frequency" => $event['RRULE']['FREQ'],
				"days" => !empty($event['RRULE']['BYDAY']) ? explode(",",$event['RRULE']['BYDAY']) : array(),
				"byday" => !empty($event['RRULE']['BYDAY']) ? $event['RRULE']['BYDAY'] : array(),
				"interval" => !empty($event['RRULE']['INTERVAL']) ? $event['RRULE']['INTERVAL'] : "",
				"count" => !empty($event['RRULE']['COUNT']) ? $event['RRULE']['COUNT'] : "",
				"until" => !empty($event['RRULE']['UNTIL']) ? $event['RRULE']['UNTIL']->format('U') : ""
			);
		} else {
			$recurring = false;
			$rrules = array();
		}

		$categories = (!empty($event['CATEGORIES']) && is_array($event['CATEGORIES'])) ? $event['CATEGORIES'] : array();

		$event['DESCRIPTION'] = !empty($event['DESCRIPTION']) ? $event['DESCRIPTION'] : "";

		// If there is no end event, set it to the start time
		if (!isset($event['DTEND']) || !is_object($event['DTEND'])) {
			$event['DTEND'] = clone $event['DTSTART'];
		}
		if($event['DTSTART']->getTimezone() != $event['DTEND']->getTimezone()) {
			throw new \Exception("Start timezone and end timezone are different! Not sure what to do here".json_encode($event));
		}
		$tz = $event['DTSTART']->getTimezone();
		$timezone = $tz->getName();
		$timezone = ($timezone == 'Z') ? null : $timezone;
		$this->updateEvent($calendarID,$event['UID'],htmlspecialchars_decode($event['SUMMARY'], ENT_QUOTES),htmlspecialchars_decode($event['DESCRIPTION'], ENT_QUOTES),$event['DTSTART']->format('U'),$event['DTEND']->format('U'),$timezone,$recurring,$rrules,$categories);
	}

	/**
	 * Get all the Categories by Calendar ID
	 * @param  string $calendarID The Calendar ID
	 * @return array             Array of Categories with their respective events
	 */
	public function getCategoriesByCalendarID($calendarID) {
		$categories = $this->getAll($calendarID."-categories-events");
		return $categories;
	}

	/**
	 * Add Event Group
	 * @param string $description   The Event Group name
	 * @param array $events The event group events
	 */
	public function addGroup($name,$calendars,$categories,$events) {
		$uuid = Uuid::uuid4()->toString();
		$this->updateGroup($uuid,$name,$calendars,$categories,$events);
	}

	/**
	 * Update Event Group
	 * @param string $id The event group id
	 * @param string $description   The Event Group name
	 * @param array $events The event group events
	 */
	public function updateGroup($id,$name,$calendars,$categories,$events) {
		if(empty($id)) {
			throw new \Exception("Event ID can not be blank");
		}
		$event = array(
			"name" => $name,
			"calendars" => $calendars,
			"categories" => $categories,
			"events" => $events
		);
		$this->setConfig($id,$event,"groups");
	}

	/**
	 * Delete Event Group
	 * @param  string $id The event group id
	 */
	public function deleteGroup($id){
		$this->setConfig($id, false, 'groups');
	}

	/**
	 * Get an Event Group by ID
	 * @param  string $id The event group id
	 * @return array     Event Group array
	 */
	public function getGroup($id){
		$grp = $this->getConfig($id,'groups');
		$grp['id'] = $id;
		return $grp;
	}

	/**
	 * List all Event Groups
	 * @return array Even Groups
	 */
	public function listGroups(){
			return $this->getAll('groups');
	}

	/**
	 * Dial Plan Function
	 */
	public function ext_calendar_variable($calendarid,$timezone=null,$integer=false) {
		$timezone = empty($timezone) ? $this->systemtz : $timezone;
		$cal = $this->getCalendarByID($calendarid);
		if(empty($cal)) {
			throw new \Exception("Calendar $calendarid does not exist!");
		}
		$type = $integer ? 'integer' : 'boolean';
		return new \ext_agi('calendar.agi,calendar,'.$type.','.$calendarid.','.$timezone);
	}

	/**
	 * Dial Plan Function
	 */
	public function ext_calendar_group_variable($groupid,$timezone=null,$integer=false) {
		$timezone = empty($timezone) ? $this->systemtz : $timezone;
		$group = $this->getGroup($groupid);
		if(empty($group)) {
			throw new \Exception("Group $groupid does not exist!");
		}
		$type = $integer ? 'integer' : 'boolean';
		return new \ext_agi('calendar.agi,group,'.$type.','.$groupid.','.$timezone);
	}

	/**
	 * Dial Plan Function
	 */
	public function ext_calendar_goto($calendarid,$timezone=null,$true_dest,$false_dest) {
		$timezone = empty($timezone) ? $this->systemtz : $timezone;
		$cal = $this->getCalendarByID($calendarid);
		if(empty($cal)) {
			throw new \Exception("Calendar $calendarid does not exist!");
		}
		return new \ext_agi('calendar.agi,calendar,goto,'.$calendarid.','.$timezone.','.base64_encode($true_dest).','.base64_encode($false_dest));
	}

	/**
	 * Dial Plan Function
	 */
	public function ext_calendar_group_goto($groupid,$timezone=null,$true_dest,$false_dest) {
		$timezone = empty($timezone) ? $this->systemtz : $timezone;
		$group = $this->getGroup($groupid);
		if(empty($group)) {
			throw new \Exception("Group $groupid does not exist!");
		}
		return new \ext_agi('calendar.agi,group,goto,'.$groupid.','.$timezone.','.base64_encode($true_dest).','.base64_encode($false_dest));
	}

	/**
	 * Dial Plan Function
	 */
	public function ext_calendar_execif($calendarid,$timezone=null,$true,$false) {
		$timezone = empty($timezone) ? $this->systemtz : $timezone;
		$cal = $this->getCalendarByID($calendarid);
		if(empty($cal)) {
			throw new \Exception("Calendar $calendarid does not exist!");
		}
		return new \ext_agi('calendar.agi,calendar,execif,'.$calendarid.','.$timezone.','.base64_encode($true).','.base64_encode($false));
	}

	/**
	 * Dial Plan Function
	 */
	public function ext_calendar_group_execif($groupid,$timezone=null,$true,$false) {
		$timezone = empty($timezone) ? $this->systemtz : $timezone;
		$group = $this->getGroup($groupid);
		if(empty($group)) {
			throw new \Exception("Group $groupid does not exist!");
		}
		return new \ext_agi('calendar.agi,group,execif,'.$groupid.','.$timezone.','.base64_encode($true).','.base64_encode($false));
	}

	public function matchCategory($calendarID,$category) {

	}

	/**
	 * Checks if any event in said calendar matches the current time
	 * @param  string $calendarID The Calendar ID
	 * @return boolean          True if match, False if no match
	 */
	public function matchCalendar($calendarID) {
		//move back 1 min and forward 1 min to extend our search
		//TODO: Check full hour?
		$start = $this->now->copy()->subMinute();
		$stop = $this->now->copy()->addMinute();
		$events = $this->listEvents($calendarID, $start, $stop);
		foreach($events as $event) {
			if($event['now']) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Checks if a specific event in a calendar matches the current time
	 * @param  string $calendarID The Calendar ID
	 * @param  string $eventID    The Event ID
	 * @return boolean          True if match, False if no match
	 */
	public function matchEvent($calendarID,$eventID) {
		$event = $this->getEvent($calendarID,$eventID);
		$start = Carbon::createFromTimeStamp($event['starttime'],$this->systemtz);
		$end = Carbon::createFromTimeStamp($event['endtime'],$this->systemtz);
		return $this->now->between($start,$end);
	}

	/**
	 * Checks if the Group Matches the current time
	 * @param  string $groupID The Group ID
	 * @return boolean          True if match, False if no match
	 */
	public function matchGroup($groupID) {
		//move back 1 min and forward 1 min to extend our search
		//TODO: Check full hour?
		$start = $this->now->copy()->subMinute();
		$stop = $this->now->copy()->addMinute();
		//1 query for each calendar instead of 1 query for each event
		$calendars = $this->listCalendars();
		$group = $this->getGroup($groupID);
		if(empty($group)) {
			return false;
		}
		$events = array();
		foreach($calendars as $cid => $calendar) {
			$events = $this->listEvents($cid, $start, $stop);
			if(!empty($group['events'])) {
				foreach($group['events'] as $eventid) {
					$parts = explode("_",$eventid,2);
					$eid = $parts[1]; //eventid is second part, calendarid is first
					if(isset($events[$eid]) && $events[$eid]['now']) {
						return true;
					}
				}
			}
			if(!empty($data['categories'])) {
			}
			if(!empty($data['calendars'])) {
			}
		}
		return false;
	}

	public function getActionBar($request) {
		$buttons = array();
		switch($request['display']) {
			case 'calendar':
				$action = !empty($_GET['action']) ? $_GET['action'] : '';
				switch($action) {
					case "add":
						$buttons = array(
							'reset' => array(
								'name' => 'reset',
								'id' => 'reset',
								'value' => _('Reset')
							),
							'submit' => array(
								'name' => 'submit',
								'id' => 'submit',
								'value' => _('Submit')
							)
						);
					break;
					case "edit":
						$buttons = array(
							'delete' => array(
								'name' => 'delete',
								'id' => 'delete',
								'value' => _('Delete')
							),
							'reset' => array(
								'name' => 'reset',
								'id' => 'reset',
								'value' => _('Reset')
							),
							'submit' => array(
								'name' => 'submit',
								'id' => 'submit',
								'value' => _('Submit')
							)
						);
					break;
				}
			break;
			case 'calendargroups':
			$action = !empty($_GET['action']) ? $_GET['action'] : '';
			switch($action) {
				case "add":
					$buttons = array(
						'reset' => array(
							'name' => 'reset',
							'id' => 'reset',
							'value' => _('Reset')
						),
						'submit' => array(
							'name' => 'submit',
							'id' => 'submit',
							'value' => _('Submit')
						)
					);
				break;
				case "edit":
					$buttons = array(
						'delete' => array(
							'name' => 'delete',
							'id' => 'delete',
							'value' => _('Delete')
						),
						'reset' => array(
							'name' => 'reset',
							'id' => 'reset',
							'value' => _('Reset')
						),
						'submit' => array(
							'name' => 'submit',
							'id' => 'submit',
							'value' => _('Submit')
						)
					);
				break;
			}
			break;
		}
		return $buttons;
	}

	public function getRightNav($request) {
		$request['action'] = !empty($request['action']) ? $request['action'] : '';
		switch($request['action']) {
			case "add":
			case "edit":
			case "view":
				return load_view(__DIR__."/views/rnav.php",array());
			break;
		}
	}

	//UCP STUFF
	public function ucpConfigPage($mode, $user, $action) {
		if(empty($user)) {
			$enabled = ($mode == 'group') ? true : null;
		} else {
			if($mode == 'group') {
				$allowedcals = $this->FreePBX->Ucp->getSettingByGID($user['id'],'Calendar','allowedcals');
				$allowedgroups = $this->FreePBX->Ucp->getSettingByGID($user['id'],'Calendar','allowedgroups');
				$enabled = $this->FreePBX->Ucp->getSettingByGID($user['id'],'Calendar','enabled');
				$enabled = !($enabled) ? false : true;
			} else {
				$allowedcals = $this->FreePBX->Ucp->getSettingByID($user['id'],'Calendar','allowedcals');
				$allowedgroups = $this->FreePBX->Ucp->getSettingByID($user['id'],'Calendar','allowedgroups');
				$enabled = $this->FreePBX->Ucp->getSettingByID($user['id'],'Calendar','enabled');
			}
		}
		$allowedcals = (!empty($allowedcals))?$allowedcals:array();
		$allowedgroups = (!empty($allowedgroups))?$allowedgroups:array();
		$calopts = '';
		if($mode != 'group'){
			$calopts = '<option value="inherit">'._("Inherit").'</option>';
		}
		foreach ($this->listCalendars() as $key => $value) {
			$selected = (in_array($key, $allowedcals))?'SELECTED':'';
			$calopts .= '<option value="'.$key.'" '.$selected.'>'.$value['name'].'</option>';
		}
		$grpopts = '';
		if($mode != 'group'){
			$grpopts = '<option value="inherit">'._("Inherit").'</option>';
		}
		foreach ($this->listGroups() as $key => $value) {
			$selected = (in_array($key, $allowedgroups))?'SELECTED':'';
			$grpopts .= '<option value="'.$key.'" '.$selected.'>'.$value['name'].'</option>';
		}

		$config = array(
			'mode' => $mode,
			'enabled' => $enabled,
			'calopts' => $calopts,
			'grpopts' => $grpopts,
		);
		$html = array();
		$html[0] = array(
			"title" => _("Calendar"),
			"rawname" => "calendar",
			"content" => load_view(dirname(__FILE__)."/views/ucp_config.php",$config)
		);
		return $html;
	}
	public function ucpAddUser($id, $display, $ucpStatus, $data) {
		$this->ucpUpdateUser($id, $display, $ucpStatus, $data);
	}
	public function ucpUpdateUser($id, $display, $ucpStatus, $data) {
		if($display == 'userman' && isset($_POST['type']) && $_POST['type'] == 'user') {
			if(isset($_POST['calendar_enable']) && $_POST['calendar_enable'] == 'yes') {
				$this->FreePBX->Ucp->setSettingByID($id,'Calendar','enabled',true);
			}elseif(isset($_POST['calendar_enable']) && $_POST['calendar_enable'] == 'no') {
				$this->FreePBX->Ucp->setSettingByID($id,'Calendar','enabled',false);
			} elseif(isset($_POST['calendar_enable']) && $_POST['calendar_enable'] == 'inherit') {
				$this->FreePBX->Ucp->setSettingByID($id,'Calendar','enabled',null);
			}
			if(isset($_POST['calendar_allowedcalendars'])){
				$data = (is_array($_POST['calendar_allowedcalendars']))?$_POST['calendar_allowedcalendars']:array($_POST['calendar_allowedcalendars']);
				$this->FreePBX->Ucp->setSettingByID($id,'Calendar','allowedcals',$data);
			}
			if(isset($_POST['calendar_allowedgroups'])){
				$data = (is_array($_POST['calendar_allowedgroups']))?$_POST['calendar_allowedgroups']:array($_POST['calendar_allowedgroups']);
				$this->FreePBX->Ucp->setSettingByID($id,'Calendar','allowedgroups',$data);
			}
		}
	}
	public function ucpDelUser($id, $display, $ucpStatus, $data) {}
	public function ucpAddGroup($id, $display, $data) {
		$this->ucpUpdateGroup($id,$display,$data);
	}
	public function ucpUpdateGroup($id,$display,$data) {
		if($display == 'userman' && isset($_POST['type']) && $_POST['type'] == 'group') {
			if(isset($_POST['calendar_enable']) && $_POST['calendar_enable'] == 'yes') {
				$this->FreePBX->Ucp->setSettingByGID($id,'Calendar','enabled',true);
			} else {
				$this->FreePBX->Ucp->setSettingByGID($id,'Calendar','enabled',false);
			}
			if(isset($_POST['calendar_allowedcalendars'])){
				$data = (is_array($_POST['calendar_allowedcalendars']))?$_POST['calendar_allowedcalendars']:array($_POST['calendar_allowedcalendars']);
				$this->FreePBX->Ucp->setSettingByGID($id,'Calendar','allowedcals',$data);
			}
			if(isset($_POST['calendar_allowedgroups'])){
				$data = (is_array($_POST['calendar_allowedgroups']))?$_POST['calendar_allowedgroups']:array($_POST['calendar_allowedgroups']);
				$this->FreePBX->Ucp->setSettingByGID($id,'Calendar','allowedgroups',$data);
			}
		}
	}
	public function ucpDelGroup($id,$display,$data) {

	}
	/**
	 * Gets the next event in a Calendar.
	 * @param  str $calendar calendar id
	 * @return array  the Found event or empty
	 */
	public function getNextEvent($calendar,$timezone=null){
		$this->setTimezone($timezone);
		$dates = array(
			$this->now->copy()->endOfWeek(),
			$this->now->copy()->endOfMonth(),
			$this->now->copy()->addMonth(),
			$this->now->copy()->addMonths(2),
			$this->now->copy()->addMonths(4),
			$this->now->copy()->addMonths(6),
			$this->now->copy()->addYear(),
			$this->now->copy()->addYears(2),
			$this->now->copy()->addYears(4),
			$this->now->copy()->addYears(6),
			$this->now->copy()->addYears(10)
		);
		foreach($dates as $date){
			$events = $this->listEvents($calendar, $this->now, $date);
			if(!empty($events)){
				return reset($events);
			}
		}
		return array();
	}

	/**
	 * Gets the next event in a Calendar.
	 * @param  str $groupid groupid id
	 * @return array  the Found event or empty
	 */
	public function getNextEventByGroup($groupid,$timezone=null){
		$group = $this->getGroup($groupid);
		if(empty($group)) {
			return array();
		}
		$events = array();
		foreach ($group['calendars'] as $cal) {
			$ev = $this->getNextEvent($cal,$timezone);
			if(!empty($ev)){
				$events[$ev['startdate']] = $ev;
			}
		}
		ksort($events);
		return reset($events);
	}

	/**
	 * Takes in a carbon object and command and returns a cron line.
	 * @param  object $obj     Carbon object
	 * @param  string $command command to run
	 * @return string         formatted cronline
	 */
	public function objToCron($obj, $command){
		try {
			$reflect = new \ReflectionClass($obj);
			$name = $reflect->getShortName();
		} catch (\ReflectionException $e) {
			if(is_string($obj)){
				$name = "text";
			}else{
				$name = "unknown";
			}
		}
		switch ($name) {
			case 'Moment':
				$obj->setTimezone($this->getSystemTimezone());
				$min = (int)$obj->format("i"); //remove leading zeros
				$cronstring = $min." ".$obj->format("G j n *");
			break;
			case 'Carbon':
			case 'DateTime':
				$obj->setTimezone(new \DateTimeZone($this->getSystemTimezone()));
				$min = (int)$obj->format("i"); //remove leading zeros
				$cronstring = $min." ".$obj->format("G j n *");
			break;
			case 'text':
				if(is_numeric($obj)){
					$date = new \DateTime();
					try {
						$date->setTimestamp($obj);
					} catch (\Exception $e) {
						return false;
					}
				}else{
					try {
						$date = new \DateTime($obj);
					} catch (\Exception $e) {
						return false;
					}
				}
				$date->setTimezone(new \DateTimeZone($this->getSystemTimezone()));
				$min = (int)$date->format("i"); //remove leading zeros
				$cronstring = $min." ".$date->format("G j n *");
			break;
			default:
				return false;
			break;
		}
		return sprintf("%s %s", $cronstring,$command);
	}

	private function getSystemTimezone() {
		$timezone = '';
		if (is_link('/etc/localtime')) {
			// Mac OS X (and older Linuxes)
			// /etc/localtime is a symlink to the
			// timezone in /usr/share/zoneinfo.
			$filename = readlink('/etc/localtime');
			$pathpos = strpos($filename, '/usr/share/zoneinfo/');
			if ($pathpos !== false) {
				$timezone = trim(substr($filename,($pathpos + 20)));
			}
		} elseif (file_exists('/etc/timezone')) {
			// Ubuntu / Debian.
			$data = file_get_contents('/etc/timezone');
			if (!empty($data)) {
				$timezone = trim($data);
			}
		} elseif (file_exists('/etc/sysconfig/clock')) {
			// RHEL / CentOS
			$data = @parse_ini_file('/etc/sysconfig/clock');
			if (!empty($data['ZONE'])) {
				$timezone = trim($data['ZONE']);
			}
		}
		if(empty($timezone)) {
			throw new \Exception("Unable to determine system timezone");
		}
		return $timezone;
	}

	/**
	 * Update Calendar By ID
	 *
	 * For remote calendars, updates the local calendar to match
	 * the remote.
	 *
	 * @param string $calendarid
	 *
	 * @return void
	 */
	public function refreshCalendarById($calendarid) {
		$calendar = $this->getConfig($calendarid, 'calendars');
		if (is_array($calendar) && $calendar['type'] !== "local") {
			$calendar['id'] = $calendarid;
			return $this->processCalendar($calendar);
		}
		return "Unable to update calendar";
	}
}
