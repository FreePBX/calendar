<?php

namespace FreePBX\modules;

include __DIR__ . "/vendor/autoload.php";

use Moment\Moment;
use Moment\CustomFormats\MomentJs;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use it\thecsea\simple_caldav_client\SimpleCalDAVClient;
use om\IcalParser;
use \jamesiarmes\PhpEws\Client;
use \FreePBX\modules\Calendar\drivers\Ews\Calendar as EWSCalendar;
use malkusch\lock\mutex\FlockMutex;
use BMO;
use DB_Helper;
use \FreePBX\modules\Calendar\Oauth;

#[\AllowDynamicProperties]
class Calendar extends \DB_Helper implements \BMO
{
	private ?array $guimessage = null;
	private $oauth = null;

	public function __construct($freepbx = null)
	{
		if ($freepbx == null) {
			throw new Exception("Not given a FreePBX Object");
		}
		$this->FreePBX = $freepbx;
		$this->db = $freepbx->Database;
	}

	public function install()
	{
		$allCalendars = $this->listCalendars();
		$calendars = $this->getAll('calendar-ical');
		foreach ($calendars as $id => $ical) {
			outn("Migrating " . $allCalendars[$id]['name'] . "...");
			if (isset($allCalendars[$id])) {
				$driver = $this->getDriverById($id);
				$driver->saveiCal($ical);
			}
			$this->setConfig($id, false, 'calendar-ical');
			$this->delById($id . "-raw");
			$this->delById($id . "-events");
			$this->delById($id . "-linked-events");
			$this->delById($id . "-categories-events");
			out("Done. Please double check your calendars and calendar groups!");
		}
	}
	public function uninstall()
	{
		$crons = $this->FreePBX->Cron->getAll();
		foreach ($crons as $c) {
			if (preg_match('/fwconsole calendar --sync/', (string) $c, $matches)) {
				$this->FreePBX->Cron->remove($c);
			}
		}
	}
	public function doConfigPageInit($page)
	{
		switch ($page) {
			case 'calendar':
				$action = $_REQUEST['action'] ?? '';
				switch ($action) {
					case "add":
						if (isset($_POST['name'])) {
							$type = $_POST['type'];
							$this->getDriverByAdd($type, $_POST);
							try {
								return ["status" => true];
							} catch (\Exception $e) {
								$this->guimessage = ["type" => "danger", "message" => $e->getMessage()];
							}
						}
						break;
					case "edit":
						if (isset($_POST['name'])) {
							$id = $_POST['id'];
							$driver = $this->getDriverById($id);
							try {
								return $driver->updateCalendar($_POST);
							} catch (\Exception $e) {
								$this->guimessage = ["type" => "danger", "message" => $e->getMessage()];
							}
						}
						break;
					case "delete":
						$this->delCalendarByID($_REQUEST['id']);
						break;
					case "deletesettings":
						$this->delCalendarSettingByID($_REQUEST['id']);
						break;
				}
				break;
			case 'calendargroups':
				$action = $_REQUEST['action'] ?? '';
				$description = $_REQUEST['description'] ?? '';
				$events = $_REQUEST['events'] ?? [];
				switch ($action) {
					case "add":
						if (isset($_POST['name']) && !empty($_POST['name'])) {
							$name = !empty($_POST['name']) ? $_POST['name'] : [];
							$calendars = !empty($_POST['calendars']) ? $_POST['calendars'] : [];
							$categories = !empty($_POST['categories']) ? $_POST['categories'] : [];
							$events = !empty($_POST['events']) ? $_POST['events'] : [];
							$expand = isset($_POST['expand']) && $_POST['expand'] === 'on' ? true : false;
							$this->addGroup($name, $calendars, $categories, $events, $expand);
						}
						break;
					case "edit":
						if (isset($_POST['name']) && !empty($_POST['name'])) {
							$id = $_POST['id'];
							$name = !empty($_POST['name']) ? $_POST['name'] : "";
							$calendars = !empty($_POST['calendars']) ? $_POST['calendars'] : [];
							$categories = !empty($_POST['categories']) ? $_POST['categories'] : [];
							$events = !empty($_POST['events']) ? $_POST['events'] : [];
							$expand = isset($_POST['expand']) && $_POST['expand'] === 'on' ? true : false;
							$this->updateGroup($id, $name, $calendars, $categories, $events, $expand);
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

	public function getAllDriversInfo()
	{
		$drivers = [];
		foreach (glob(__DIR__ . "/drivers/*.php") as $driver) {
			$name = basename($driver);
			$name = explode(".", $name);
			$name = $name[0];
			$name = ucfirst(strtolower($name));
			if ($name === 'Base') {
				continue;
			}
			$class = $this->prepareDriverClass($name);
			$drivers[strtolower($name)] = $class::getInfo();
		}
		return $drivers;
	}

	/**
	 * Get Calendar Driver
	 * @method getDriver
	 * @param  string    $driver The driver name
	 * @return object            The object
	 */
	public function getDriverDisplayEdit($driver, $data)
	{
		$class = $this->prepareDriverClass($driver);
		return $class::getEditDisplay($data);
	}

	public function getDriverDisplayAdd($driver, $data = [])
	{
		$class = $this->prepareDriverClass($driver);
		if ($driver == 'oauth') {
			return $class::getAddDisplay($data);
		}
		return $class::getAddDisplay();
	}

	public function getDriverByAdd($driver, $data)
	{
		$class = $this->prepareDriverClass($driver);
		return $class::addCalendar($data);
	}

	public function getDriverById($id)
	{
		if (empty($id)) {
			throw new \Exception("Cant except empty calendar ID");
		}
		$final = $this->getConfig($id, 'calendars');
		$final['id'] = $id;
		if (empty($final)) {
			return false;
		}
		$driver = $final['type'];
		$class = $this->prepareDriverClass($driver);
		return new $class($this, $final);
	}

	private function prepareDriverClass($driver)
	{
		$driver = basename((string) $driver);
		$driver = ucfirst(strtolower($driver));
		if (!file_exists(__DIR__ . "/drivers/" . $driver . ".php") || $driver === 'Base') {
			throw new \Exception("Driver [$driver] does not exist!");
		}
		return "\FreePBX\modules\Calendar\drivers\\" . $driver;
	}

	public function ajaxRequest($req, &$setting)
	{
		switch ($req) {
			case 'grid':
			case 'events':
			case 'eventform':
			case 'delevent':
			case 'groupsgrid':
			case 'groupeventshtml':
			case 'getcaldavcals':
			case 'getewscals':
			case 'updatesource':
			case 'duplicate':
			case 'generateical':
			case 'checkical':
			case "saveoutlooksettings":
			case 'saveOauth':
			case 'getToken':
			case 'oauthsettings':
			case 'oauthListCalendars':
				return true;
			case 'ical':
				//be aware
				$setting['authenticate'] = false;
				$setting['allowremote'] = true;
				return true;
			default:
				return false;
		}
	}

	public function ajaxCustomHandler()
	{
		switch ($_REQUEST['command']) {
			case "ical":
				$calendar = $this->getCalendarByMappingToken($_REQUEST['token']);
				if (empty($calendar)) {
					return false;
				}
				header('Content-Type: text/calendar; charset=utf-8');
				header('Content-Disposition: attachment; filename=' . $calendar['name'] . '.ics');
				echo $calendar['calendar']->getIcal();
				return true;
				break;
		}
		return false;
	}

	public function getCalendarByMappingToken($mappingToken)
	{
		$mapping = $this->getConfig('ical-mapping');
		$calid = null;
		foreach ($mapping as $id => $token) {
			if ($mappingToken === $token) {
				$calid = $id;
				break;
			}
		}
		if (!empty($calid)) {
			return $this->getCalendarById($calid);
		}
	}

	public function ajaxHandler()
	{
		switch ($_REQUEST['command']) {
			case 'generateical':
				$uuid = Uuid::uuid4()->toString();
				$cal = $this->getDriverById($_REQUEST['id']);
				$cal->updateiCalMapping($uuid);
				return ["status" => true, "href" => "ajax.php?module=calendar&command=ical&token=" . $uuid];
				break;
			case 'getcaldavcals':
				$caldavClient = new SimpleCalDAVClient();
				try {
					$caldavClient->connect($_POST['purl'], $_POST['username'], $_POST['password']);
				} catch (\Exception $e) {
					$chtml = $e->getMessage() . '<input type="hidden" id="urlerror" value="error">';
					return ["calshtml" => $chtml, 'status' => false];
				}
				$calendars = $caldavClient->findCalendars();
				$chtml = '';
				foreach ($calendars as $calendar) {
					$chtml .= '<option value="' . $calendar->getCalendarID() . '">' . $calendar->getDisplayName() . '</option>';
				}
				return ["calshtml" => $chtml, 'status' => true];
				break;
			case 'checkical':
				$req = \FreePBX::Curl()->requests($_POST['url']);
				try {
					$finalical = $req->get($_POST['url'])->body;
				} catch (\Exception $e) {
					dbug($e->getMessage());
					return ["message" => $e->getMessage(), 'status' => false];
				}
				return ['status' => true];
				break;
			case 'groupeventshtml':
				$allCalendars = $this->listCalendars();
				$calendars = !empty($_POST['calendars']) ? $_POST['calendars'] : [];
				$dcategories = !empty($_POST['categories']) ? $_POST['categories'] : [];
				$expand = $_POST['expand'] === 'false' ? false : true;
				$categories = [];
				$now = Carbon::now();
				$searchStart = $now->copy()->subYear();
				$searchEnd = $now->copy()->addYear();
				foreach ($dcategories as $cat) {
					$parts = explode("_", (string) $cat, 2);
					$categories[$parts[0]][] = $parts[1];
				}
				$chtml = '';
				foreach ($calendars as $calendarID) {
					$cal = $this->getDriverById($calendarID);
					$cats = $cal->getCategories($searchStart, $searchEnd);
					if (empty($cats)) {
						$chtml .= '<optgroup label="' . sprintf(_("No Categories for %s"), $allCalendars[$calendarID]['name']) . '">';
						continue;
					}
					$chtml .= '<optgroup label="' . $allCalendars[$calendarID]['name'] . '">';
					foreach ($cats as $name) {
						$chtml .= '<option value="' . $calendarID . '_' . $name . '">' . $name . '</option>';
					}
					$chtml .= '</optgroup>';
				}
				$ehtml = '';
				foreach ($calendars as $calendarID) {
					$cal = $this->getDriverById($calendarID);
					$events = $cal->getEventsBetween($searchStart, $searchEnd, $expand);
					if (empty($events)) {
						$ehtml .= '<optgroup label="' . sprintf(_("No Events for %s"), $allCalendars[$calendarID]['name']) . '">';
						continue;
					}
					if (!empty($categories[$calendarID])) {
						$valid = [];
						$cats = $categories[$calendarID];
						$events = array_filter($events, function ($event) use ($cats) {
							if (empty($event['categories'])) {
								return false;
							}
							foreach ($event['categories'] as $c) {
								if (in_array($c, $cats)) {
									return true;
								}
							}
							return false;
						});
					} elseif (!empty($categories)) {
						$events = [];
					}
					$ehtml .= '<optgroup label="' . $allCalendars[$calendarID]['name'] . '">';
					foreach ($events as $event) {
						$extended = $event['allDay'] ? $event['startdate'] : $event['startdate'] . ' ' . _('to') . ' ' . $event['enddate'];
						$ehtml .= '<option value="' . $calendarID . '_' . $event['uid'] . '">' . $event['name'] . ' (' . $extended . ')</option>';
					}
					$ehtml .= '</optgroup>';
				}
				return ["eventshtml" => $ehtml, "categorieshtml" => $chtml];
				break;
			case 'delevent':
				$calendarID = $_POST['calendarid'];
				$eventID = $_POST['eventid'];
				$calendar = $this->getCalendarById($calendarID);
				if ($calendar['type'] !== 'local') {
					return ["status" => false, "message" => _("You can only edit local calendars")];
				}
				$calendar['calendar']->deleteEvent($eventID);
				break;
			case 'duplicate':
				$name = $_REQUEST['value'];
				$id = $_REQUEST['id'];
				$calendars = $this->listCalendars();
				//check whether its edit or add
				if (array_key_exists($id, $calendars)) {
					// its an edit check name changed or duplicated
					// so unset the array key and before doing the duplicate check
					unset($calendars[$id]);
				}
				$calnames = [];
				foreach ($calendars as $cal) {
					$calnames[] = trim((string) $cal['name']);
				}
				if (in_array($name, $calnames)) {
					return ['value' => 1];
				} else {
					return ['value' => 0];
				}
				break;
			case 'grid':
				$calendars = $this->listCalendars();
				$final = [];
				foreach ($calendars as $id => $data) {
					$data['id'] = $id;
					$final[] = $data;
				}
				return $final;
				break;
			case 'events':
				$start = new Carbon($_GET['start'], $_GET['timezone']);
				$end = new Carbon($_GET['end'], $_GET['timezone']);
				$calendar = $this->getCalendarById($_REQUEST['calendarid']);
				$calendar['calendar']->processCalendar($start->toIso8601String(), $end->toIso8601String());
				$events = $calendar['calendar']->getEventsBetween($start, $end);
				return array_values($events);
				break;
			case 'eventform':
				$calendar = $this->getCalendarById($_POST['calendarid']);
				if ($calendar['type'] !== 'local') {
					return ["status" => false, "message" => _("You can only edit local calendars")];
				}
				$calendar['calendar']->updateEvent($_POST);
				return ["status" => true, "message" => _("Successfully updated event")];
				break;
			case 'groupsgrid':
				$groups =  $this->listGroups();
				$final = [];
				foreach ($groups as $id => $data) {
					$data['id'] = $id;
					$final[] = $data;
				}
				return $final;
				break;
			case 'updatesource':
				$cal = $this->getDriverByID($_REQUEST['calendarid']);
				return $cal->refreshCalendar();
				break;
			case "saveoutlooksettings":
				if ($_REQUEST['id']) {
					$uuid = $_REQUEST['id'];
				} else {
					$uuid = Uuid::uuid4()->toString();
					$_REQUEST['id'] = $uuid;
				}
				//name and application id validation to avoid duplication
				if (!$this->checkConfigExists($_REQUEST, $uuid)) {
					return ["status" => false, "message" => _("Config name already exists.")];
				}
				$this->setConfig($uuid, $_REQUEST, 'outlook-details');
				$oauth = new Oauth($_REQUEST['tenantid'], $_REQUEST['consumerkey'], $_REQUEST['consumersecret'], null, null, $_REQUEST['pbxurl'], $_REQUEST['outlookurl']);
				$authUrl =  $oauth->getAuthURL($uuid);
				return ["status" => true, "message" => _("Data saved"), "authurl" => $authUrl ?: ''];
				break;
			case 'saveOauth':
				$outlookdata = $this->getConfig($_REQUEST['id'], 'outlook-details');
				$outlookdata['auth_code'] = $_REQUEST['auth_code'];
				$this->setConfig($_REQUEST['id'], $outlookdata, 'outlook-details');
				return ["status" => true, "message" => _("saved auth code")];
				break;
			case 'getToken':
				return $this->outlookToken($_REQUEST['id']);
				break;
			case 'oauthsettings':
				$settings = $this->getAll('outlook-details');
				$final = [];
				foreach ($settings as $id => $data) {
					$data['id'] = $id;
					$final[] = $data;
				}
				return $final;
				break;
			case 'oauthListCalendars':
				$username = $_REQUEST['username'];
				$configId = $_REQUEST['configId'];
				$outlookdata = $this->getConfig($configId, 'outlook-details');
				if (isset($outlookdata['access_token'])) {
					$oauth = new Oauth($outlookdata['tenantid'], $outlookdata['consumerkey'], $outlookdata['consumersecret'], null, null, $outlookdata['pbxurl'], $outlookdata['outlookurl']);
					$calendars =  $oauth->getUserCalendars($username, $outlookdata['access_token']);
					if (isset($calendars['error'])) {
						$msg = ($calendars['error']['code'] == 'ResourceNotFound' || $calendars['error']['code'] == 'ErrorInvalidUser') ? _("Invalid user name/config") : _("Please generated the auth token to list the user calendars.");
						return ["status" => false, "message" => $msg];
					} else if (isset($calendars['value'])) {
						$calendarList = [];
						foreach ($calendars['value'] as $_cal) {
							$calendarList[] = [
								'id' => $_cal['id'],
								'name' => $_cal['name']
							];
						}
						return ["status" => true, "message" => _("found user calendars"), "calendars" => $calendarList];
					}
				} else {
					return ["status" => false, "message" => _("Please generated the auth token to list the user calendars.")];
				}
				break;
		}
	}

	public function showCalendarGroupsPage()
	{
		$action = !empty($_GET['action']) ? $_GET['action'] : '';
		switch ($action) {
			case "add":
				$calendars = $this->listCalendars();
				return load_view(__DIR__ . "/views/calendargroups.php", ["calendars" => $calendars, "action" => _("Add")]);
				break;
			case "edit":
				$calendars = $this->listCalendars();
				$group = $this->getGroup($_REQUEST['id']);
				return load_view(__DIR__ . "/views/calendargroups.php", ["calendars" => $calendars, "group" => $group, 'id' => $_GET['id'], "action" => _("Edit")]);
				break;
			case "view":
				break;
			default:
				return load_view(__DIR__ . "/views/calendargroupgrid.php", []);
				break;
		}
	}

	public function showCalendarPage()
	{
		$action = !empty($_GET['action']) ? $_GET['action'] : '';
		switch ($action) {
			case "add":
				$type = !empty($_GET['type']) ? $_GET['type'] : '';
				if ($type == 'oauth') {
					$data['configs_list'] = $this->getallOauthSettings();
					return $this->getDriverDisplayAdd($type, $data);
				}
				return $this->getDriverDisplayAdd($type);
				break;
			case "edit":
				$data = $this->getCalendarByID($_GET['id']);
				if ($data['type'] == 'oauth') {
					$data['configs_list'] = $this->getallOauthSettings();
				}
				return $this->getDriverDisplayEdit($data['type'], $data);
				break;
			case "view":
				$data = $this->getCalendarByID($_GET['id']);
				\Moment\Moment::setLocale('en_US'); //get this from freepbx...
				$locale = \Moment\MomentLocale::getLocaleContent();
				$icallink = $data['calendar']->getMappingToken();
				return load_view(__DIR__ . "/views/calendar.php", ['action' => 'view', 'type' => $data['type'], 'data' => $data, 'locale' => $locale, 'icallink' => (!empty($icallink) ? 'ajax.php?module=calendar&command=ical&token=' . $icallink : '')]);
				break;
			case 'editoutlooksettings':
			case 'outlooksettings':
				//load settings view
				if (isset($_GET['id'])) {
					$outlookdata = $this->getConfig($_GET['id'], 'outlook-details');
					$oauth = new Oauth($outlookdata['tenantid'], $outlookdata['consumerkey'], $outlookdata['consumersecret'], null, null, $outlookdata['pbxurl'], $outlookdata['outlookurl']);
					$outlookdata['authurl'] = $oauth->getAuthURL($_GET['id']);
					$outlookdata['id'] = $_GET['id'];
				} else {
					$outlookdata = [];
				}
				return load_view(__DIR__ . "/views/outlook_config_form.php", ['outlookdata' => $outlookdata]);
				break;
			case 'oauthsettings':
				return load_view(__DIR__ . "/views/outlook_configs_grid.php");
				break;
			default:
				$dropdown = [];
				$drivers = $this->getAllDriversInfo();
				foreach ($drivers as $driver => $data) {
					$dropdown[$driver] = $data['name'];
				}
				$authType = $this->FreePBX->Config->get_conf_setting('OUTLOOK_AUTH_METHOD');
				return load_view(__DIR__ . "/views/grid.php", ['message' => $this->guimessage, 'dropdown' => $dropdown, 'auth_type' => $authType]);
				break;
		}
	}

	/**
	 * List Calendars
	 * @return array The returned calendar array
	 */
	public function listCalendars()
	{
		$calendars = $this->getAll('calendars');
		return $calendars;
	}

	public function getCalendarNames()
	{
		$cals = $this->listCalendars();
		$ret = [];
		$cals = is_array($cals) ? $cals : [];
		foreach ($cals as $cal) {
			if (isset($cal['name'])) {
				$ret[] = $cal['name'];
			}
		}
		return $ret;
	}
	/**
	 * Delete Calendar by ID
	 * @param  string $id The calendar ID
	 */
	public function delCalendarByID($id)
	{
		$this->setConfig($id, false, 'calendars');
		$this->setConfig($id, false, 'calendar-raw');
		$this->setConfig($id, false, 'calendar-sync');
	}

	/**
	 * Delete outlook Calendar settings by ID
	 * @param  string $id The settings calendar ID
	 */
	public function delCalendarSettingByID($id)
	{
		$this->setConfig($id, false, 'outlook-details');
	}

	/**
	 * Get Calendar by ID
	 * @param  string $id The Calendar ID
	 * @return array     Calendar data
	 */
	public function getCalendarByID($id)
	{
		if (empty($id)) {
			throw new \Exception("Cant except empty calendar ID");
		}
		$final = $this->getConfig($id, 'calendars');
		if (empty($final)) {
			return false;
		}
		$final['id'] = $id;
		if (isset($final['calendars']) && !is_array($final['calendars'])) {
			$final['usercalendar'] = $final['calendars'];
		}
		if (!isset($final['calendars']) || !is_array($final['calendars'])) {
			$final['calendars'] = [];
		}
		$final['calendar'] = $this->getDriverByID($id);
		$final['timezone'] = $final['calendar']->getTimezone();
		return $final;
	}

	/**
	 * Sync and cache calendars
	 */
	public function sync($output, $force = false)
	{
		$cal = $this;
		$mutex = new FlockMutex(fopen(__FILE__, "r"));
		$mutex->synchronized(function () use ($cal, $output, $force) {
			$calendars = $cal->listCalendars();
			foreach ($calendars as $id => $calendar) {
				if ($calendar['type'] === "local")
					continue;
				$output->write("\tSyncing " . $calendar['name'] . "...");
				$last = $cal->getConfig($id, 'calendar-sync');
				$last = !empty($last) ? $last : 0;
				$next = !empty($calendar['next']) ? $calendar['next'] : 300;
				if ($force || ($last + $next) < time()) {
					$calendar['id'] = $id;
					$c = $cal->getDriverById($id);
					$c->processCalendar($calendar);
					$cal->setConfig($id, time(), 'calendar-sync');
					$output->writeln("Done");
					$this->FreePBX->Hooks->processHooks($calendar['id']);
				} else
					$output->writeln("Skipping");
			}
		});
	}

	/**
	 * Add Event Group
	 * @param string $description   The Event Group name
	 * @param array $events The event group events
	 */
	public function addGroup($name, $calendars, $categories, $events, $expand)
	{
		$uuid = Uuid::uuid4()->toString();
		$this->updateGroup($uuid, $name, $calendars, $categories, $events, $expand);
	}

	/**
	 * Update Event Group
	 * @param string $id The event group id
	 * @param string $description   The Event Group name
	 * @param array $events The event group events
	 */
	public function updateGroup($id, $name, $calendars, $categories, $events, $expand)
	{
		if (empty($id)) {
			throw new \Exception("Event ID can not be blank");
		}
		$event = ["name" => $name, "calendars" => $calendars, "categories" => $categories, "events" => $events, "expand" => $expand];
		$this->setConfig($id, $event, "groups");
	}

	/**
	 * Delete Event Group
	 * @param  string $id The event group id
	 */
	public function deleteGroup($id)
	{
		$this->setConfig($id, false, 'groups');
	}

	/**
	 * Get an Event Group by ID
	 * @param  string $id The event group id
	 * @return array     Event Group array
	 */
	public function getGroup($id)
	{
		$grp = $this->getConfig($id, 'groups');
		$grp['id'] = $id;
		$grp['expand'] ??= true;
		return $grp;
	}

	/**
	 * List all Event Groups
	 * @return array Even Groups
	 */
	public function listGroups()
	{
		return $this->getAll('groups');
	}

	/**
	 * Dial Plan Function
	 */
	public function ext_calendar_variable($calendarid, $timezone = null, $integer = false)
	{
		$timezone = empty($timezone) ? $this->systemtz : $timezone;
		$cal = $this->getCalendarByID($calendarid);
		if (empty($cal)) {
			throw new \Exception("Calendar $calendarid does not exist!");
		}
		$type = $integer ? 'integer' : 'boolean';
		return new \ext_agi('calendar.agi,calendar,' . $type . ',' . $calendarid . ',' . $timezone);
	}

	/**
	 * Dial Plan Function
	 */
	public function ext_calendar_group_variable($groupid, $timezone = null, $integer = false)
	{
		$timezone = empty($timezone) ? $this->systemtz : $timezone;
		$group = $this->getGroup($groupid);
		if (empty($group)) {
			throw new \Exception("Group $groupid does not exist!");
		}
		$type = $integer ? 'integer' : 'boolean';
		return new \ext_agi('calendar.agi,group,' . $type . ',' . $groupid . ',' . $timezone);
	}

	/**
	 * Dial Plan Function
	 */
	public function ext_calendar_goto($calendarid,$timezone=null,$true_dest='',$false_dest='') {
		$timezone = empty($timezone) ? $this->systemtz : $timezone;
		$cal = $this->getCalendarByID($calendarid);
		if (empty($cal)) {
			throw new \Exception("Calendar $calendarid does not exist!");
		}
		return new \ext_agi('calendar.agi,calendar,goto,' . $calendarid . ',' . $timezone . ',' . base64_encode((string) $true_dest) . ',' . base64_encode((string) $false_dest));
	}

	/**
	 * Dial Plan Function
	 */
	public function ext_calendar_group_goto($groupid,$timezone=null,$true_dest='',$false_dest='') {
		$timezone = empty($timezone) ? $this->systemtz : $timezone;
		$group = $this->getGroup($groupid);
		if (empty($group)) {
			throw new \Exception("Group $groupid does not exist!");
		}
		return new \ext_agi('calendar.agi,group,goto,' . $groupid . ',' . $timezone . ',' . base64_encode((string) $true_dest) . ',' . base64_encode((string) $false_dest));
	}

	/**
	 * Dial Plan Function
	 */
	public function ext_calendar_execif($calendarid,$timezone=null,$true = '', $false = '') {
		$timezone = empty($timezone) ? $this->systemtz : $timezone;
		$cal = $this->getCalendarByID($calendarid);
		if (empty($cal)) {
			throw new \Exception("Calendar $calendarid does not exist!");
		}
		return new \ext_agi('calendar.agi,calendar,execif,' . $calendarid . ',' . $timezone . ',' . base64_encode((string) $true) . ',' . base64_encode((string) $false));
	}

	/**
	 * Dial Plan Function
	 */
	public function ext_calendar_group_execif($groupid,$timezone=null, $true = '', $false = '') {
		$timezone = empty($timezone) ? $this->systemtz : $timezone;
		$group = $this->getGroup($groupid);
		if (empty($group)) {
			throw new \Exception("Group $groupid does not exist!");
		}
		return new \ext_agi('calendar.agi,group,execif,' . $groupid . ',' . $timezone . ',' . base64_encode((string) $true) . ',' . base64_encode((string) $false));
	}

	public function matchGroupVerbose($groupID, $now = null, $timezone = null)
	{
		$group = $this->getGroup($groupID);

		if (empty($group) || empty($group['calendars']))
			return [];

		$now = !empty($now) ? $now : time();
		$matchingEvents = [];

		if (empty($group['events']) && empty($group['categories'])) { //calendars only
			foreach ($group['calendars'] as $cid) {
				//initialize the driver
				$cal = $this->getDriverById($cid);
				$cal->setTimezone($timezone);
				$cal->setNow($now);
				$events = $cal->fastHandler(); //get the events

				//continue if there are no matching events in this calendar
				if (!$events)
					continue;

				//fastHandler automatically filter matching events. No need to further check if the timew is right. And the calendar is obviously the one we requested
				// so copying the entire array is enough
				$matchingEvents = array_merge($matchingEvents, $events);
			}
		} else if (empty($group['events']) && !empty($group['categories'])) { //categories only
			//first extract the categories we are searching for and the calendar associated with it
			$groupCategories = [];
			foreach ($group['categories'] as $categoryid) {
				$parts = explode("_", (string) $categoryid, 2);

				if (!is_array($groupCategories[$parts[0]]))
					$groupCategories[$parts[0]] = [];

				array_push($groupCategories[$parts[0]], $parts[1]); //category is second part, calendarid is first
			}

			//now search in every calendar
			$calendars = $this->listCalendars();
			foreach ($calendars as $cid => $calendar) {
				if (!in_array($cid, $group['calendars']))
					continue; //this calendar is not in the group

				//initialize the driver
				$cal = $this->getDriverById($cid);
				$cal->setTimezone($timezone);
				$cal->setNow($now);
				$events = $cal->fastHandler();

				//continue if there are no matching events or no categories in this calendar
				if (!$events || !isset($groupCategories[$cid]))
					continue;

				//fastHandler automatically filter events happening now, this check is skipped. We must instead check if categories matches
				//not using array_filter for faster speed (https://www.levijackson.xyz/posts/are-array_-functions-faster-than-loops/)
				$filtered = [];
				foreach ($events as $event) {
					if (empty($event['CATEGORIES']))
						continue;

					foreach ($event['CATEGORIES'] as $c) {
						foreach ($groupCategories[$cid] as $cat) { //no need to check is_array($groupCategories[$cid]) beacuse it is always an array
							if ($cat === $c) {
								array_push($filtered, $event);
								continue 3;
							}
						}
					}
				}

				$matchingEvents = array_merge($matchingEvents, $filtered);
			}
		} else if (!empty($group['events'])) { //events only
			//first extract the events we are searching for and the calendar associated with it
			$groupEvents = [];
			foreach ($group['events'] as $eventid) {
				$parts = explode("_", (string) $eventid, 3);
				$groupEvents[$parts[1]] = [$parts[0], $parts[2]]; //$parts[0] = calendar id - $parts[1] = event uid - $parts[2] = event recurrence number (0 for non recurring events)
			}

			//now search in every calendar
			$calendars = $this->listCalendars();
			foreach ($calendars as $cid => $calendar) {
				if (!in_array($cid, $group['calendars']))
					continue; //this calendar is not in the group

				//initialize the driver
				$cal = $this->getDriverById($cid);
				$cal->setTimezone($timezone);
				$cal->setNow($now);
				$events = $cal->fastHandler();

				//continue if there are no matching events in this calendar
				if (!$events)
					continue;

				//fastHandler automatically filter events happening now, this check is skipped. We must instead check if the event UID is matching
				//not using array_filter for faster speed (https://www.levijackson.xyz/posts/are-array_-functions-faster-than-loops/)
				$filtered = [];
				foreach ($events as $event) {
					//TODO recurrence instance is not checked because I didn't come up with a solution to calculate it (fast) in fastHandler. Is this important at all? Calendar always worked without it anyway (as a consequence "Expand Reccuring Dates" in Calendar group does not stictly match the event selected but all the ones from the begin)
					if (isset($groupEvents[$event['UID']])/* && $event['RECURRENCE_INSTANCE'] >= $parts[2]*/)
						array_push($filtered, $event);
				}

				$matchingEvents = [...$matchingEvents, ...$filtered];
			}
		}

		return $matchingEvents;
	}

	public function getActionBar($request)
	{
		$buttons = [];
		switch ($request['display']) {
			case 'calendar':
				$action = !empty($_GET['action']) ? $_GET['action'] : '';
				switch ($action) {
					case "view":
						$calendar = $this->getCalendarByID($_REQUEST['id']);
						$buttons = ['link' => ['name' => 'link', 'id' => 'link', 'value' => _('Edit Settings')]];
						if ($calendar['type'] !== 'local') {
							$buttons['updatecal'] = ['name' => 'updatecal', 'id' => 'updatecal', 'value' => _("Update from Source")];
						}
						break;
					case "add":
						$buttons = ['reset' => ['name' => 'reset', 'id' => 'reset', 'value' => _('Reset')], 'submit' => ['name' => 'submit', 'id' => 'submit', 'value' => _('Submit')]];
						break;
					case "edit":
						$buttons = ['delete' => ['name' => 'delete', 'id' => 'delete', 'value' => _('Delete')], 'reset' => ['name' => 'reset', 'id' => 'reset', 'value' => _('Reset')], 'submit' => ['name' => 'submit', 'id' => 'submit', 'value' => _('Submit')]];
						break;
				}
				break;
			case 'calendargroups':
				$action = !empty($_GET['action']) ? $_GET['action'] : '';
				switch ($action) {
					case "add":
						$buttons = ['reset' => ['name' => 'reset', 'id' => 'reset', 'value' => _('Reset')], 'submit' => ['name' => 'submit', 'id' => 'submit', 'value' => _('Submit')]];
						break;
					case "edit":
						$buttons = ['delete' => ['name' => 'delete', 'id' => 'delete', 'value' => _('Delete')], 'reset' => ['name' => 'reset', 'id' => 'reset', 'value' => _('Reset')], 'submit' => ['name' => 'submit', 'id' => 'submit', 'value' => _('Submit')]];
						break;
				}
				break;
		}
		return $buttons;
	}

	public function getRightNav($request)
	{
		$request['action'] = !empty($request['action']) ? $request['action'] : '';
		switch ($request['action']) {
			case "add":
			case "edit":
			case "view":
			case "outlooksettings":
			case "oauthsettings":
			case "editoutlooksettings":
				return load_view(__DIR__ . "/views/rnav.php", []);
				break;
		}
	}

	//UCP STUFF
	public function ucpConfigPage($mode, $user, $action)
	{
		if (empty($user)) {
			$enabled = ($mode == 'group') ? true : null;
		} else {
			if ($mode == 'group') {
				$allowedcals = $this->FreePBX->Ucp->getSettingByGID($user['id'], 'Calendar', 'allowedcals');
				$allowedgroups = $this->FreePBX->Ucp->getSettingByGID($user['id'], 'Calendar', 'allowedgroups');
				$enabled = $this->FreePBX->Ucp->getSettingByGID($user['id'], 'Calendar', 'enabled');
				$enabled = !($enabled) ? false : true;
			} else {
				$allowedcals = $this->FreePBX->Ucp->getSettingByID($user['id'], 'Calendar', 'allowedcals');
				$allowedgroups = $this->FreePBX->Ucp->getSettingByID($user['id'], 'Calendar', 'allowedgroups');
				$enabled = $this->FreePBX->Ucp->getSettingByID($user['id'], 'Calendar', 'enabled');
			}
		}
		$allowedcals = (!empty($allowedcals)) ? $allowedcals : [];
		$allowedgroups = (!empty($allowedgroups)) ? $allowedgroups : [];
		$calopts = '';
		if ($mode != 'group') {
			$calopts = '<option value="inherit">' . _("Inherit") . '</option>';
		}
		foreach ($this->listCalendars() as $key => $value) {
			$selected = (in_array($key, $allowedcals)) ? 'SELECTED' : '';
			$calopts .= '<option value="' . $key . '" ' . $selected . '>' . $value['name'] . '</option>';
		}
		$grpopts = '';
		if ($mode != 'group') {
			$grpopts = '<option value="inherit">' . _("Inherit") . '</option>';
		}
		foreach ($this->listGroups() as $key => $value) {
			$selected = (in_array($key, $allowedgroups)) ? 'SELECTED' : '';
			$grpopts .= '<option value="' . $key . '" ' . $selected . '>' . $value['name'] . '</option>';
		}

		$config = ['mode' => $mode, 'enabled' => $enabled, 'calopts' => $calopts, 'grpopts' => $grpopts];
		$html = [];
		$html[0] = ["title" => _("Calendar"), "rawname" => "calendar", "content" => load_view(__DIR__ . "/views/ucp_config.php", $config)];
		return $html;
	}
	public function ucpAddUser($id, $display, $ucpStatus, $data)
	{
		$this->ucpUpdateUser($id, $display, $ucpStatus, $data);
	}
	public function ucpUpdateUser($id, $display, $ucpStatus, $data)
	{
		if ($display == 'userman' && isset($_POST['type']) && $_POST['type'] == 'user') {
			if (isset($_POST['calendar_enable']) && $_POST['calendar_enable'] == 'yes') {
				$this->FreePBX->Ucp->setSettingByID($id, 'Calendar', 'enabled', true);
			} elseif (isset($_POST['calendar_enable']) && $_POST['calendar_enable'] == 'no') {
				$this->FreePBX->Ucp->setSettingByID($id, 'Calendar', 'enabled', false);
			} elseif (isset($_POST['calendar_enable']) && $_POST['calendar_enable'] == 'inherit') {
				$this->FreePBX->Ucp->setSettingByID($id, 'Calendar', 'enabled', null);
			}
			if (isset($_POST['calendar_allowedcalendars'])) {
				$data = (is_array($_POST['calendar_allowedcalendars'])) ? $_POST['calendar_allowedcalendars'] : [$_POST['calendar_allowedcalendars']];
				$this->FreePBX->Ucp->setSettingByID($id, 'Calendar', 'allowedcals', $data);
			}
			if (isset($_POST['calendar_allowedgroups'])) {
				$data = (is_array($_POST['calendar_allowedgroups'])) ? $_POST['calendar_allowedgroups'] : [$_POST['calendar_allowedgroups']];
				$this->FreePBX->Ucp->setSettingByID($id, 'Calendar', 'allowedgroups', $data);
			}
		}
	}
	public function ucpDelUser($id, $display, $ucpStatus, $data)
	{
	}
	public function ucpAddGroup($id, $display, $data)
	{
		$this->ucpUpdateGroup($id, $display, $data);
	}
	public function ucpUpdateGroup($id, $display, $data)
	{
		if ($display == 'userman' && isset($_POST['type']) && $_POST['type'] == 'group') {
			if (isset($_POST['calendar_enable']) && $_POST['calendar_enable'] == 'yes') {
				$this->FreePBX->Ucp->setSettingByGID($id, 'Calendar', 'enabled', true);
			} else {
				$this->FreePBX->Ucp->setSettingByGID($id, 'Calendar', 'enabled', false);
			}
			if (isset($_POST['calendar_allowedcalendars'])) {
				$data = (is_array($_POST['calendar_allowedcalendars'])) ? $_POST['calendar_allowedcalendars'] : [$_POST['calendar_allowedcalendars']];
				$this->FreePBX->Ucp->setSettingByGID($id, 'Calendar', 'allowedcals', $data);
			}
			if (isset($_POST['calendar_allowedgroups'])) {
				$data = (is_array($_POST['calendar_allowedgroups'])) ? $_POST['calendar_allowedgroups'] : [$_POST['calendar_allowedgroups']];
				$this->FreePBX->Ucp->setSettingByGID($id, 'Calendar', 'allowedgroups', $data);
			}
		}
	}
	public function ucpDelGroup($id, $display, $data)
	{
	}

	/**
	 * Checks if the Group Matches the time provided. This will use fast if possible.
	 * @param  string	$groupID		The Group ID
	 * @param  int		$now			Time to check against
	 * @param  int		$timezone		Timezone to check against
	 * @return boolean        			True if match, False if no match
	 */
	public function matchGroup($groupID, $now = null, $timezone = null)
	{
		return !empty($this->matchGroupVerbose($groupID, $now, $timezone));
	}

	/**
	 * Checks if the Calendar matches the time provided. This will use fast if possible.
	 *
	 * @param [type] $calendarid
	 * @param [type] $now
	 * @param [type] $timezone
	 * @return void
	 */
	public function matchCalendar($calendarid, $now = null, $timezone = null)
	{
		$driver = $this->getDriverById($calendarid);
		$driver->setTimezone($timezone);
		$driver->setNow($now);
		return $driver->fastHandler();
	}

	public function getNextEvent($calendarid, $now = null, $timezone = null)
	{
		$driver = $this->getDriverById($calendarid);
		$driver->setTimezone($timezone);
		$driver->setNow($now);
		return $driver->getNextEvent();
	}

	public function matchEvent($calendarid, $eventid, $now = null, $timezone = null)
	{
		$driver = $this->getDriverById($calendarid);
		$driver->setTimezone($timezone);
		$driver->setNow($now);
		return $driver->matchEvent($eventid);
	}


	/**
	 * Gets the next event in a Calendar.
	 * @param  str $groupid groupid id
	 * @return array  the Found event or empty
	 */
	public function getNextEventByGroup($groupid, $now = null, $timezone = null)
	{
		$group = $this->getGroup($groupid);
		if (empty($group)) {
			return [];
		}
		$events = [];
		foreach ($group['calendars'] as $calid) {
			$cal = $this->getDriverById($calid);
			$cal->setTimezone($timezone);
			$cal->setNow($now);
			$ev = $cal->getNextEvent($cal);
			if (!empty($ev)) {
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
	public function objToCron($obj, $command)
	{
		try {
			$reflect = new \ReflectionClass($obj);
			$name = $reflect->getShortName();
		} catch (\ReflectionException) {
			if (is_string($obj)) {
				$name = "text";
			} else {
				$name = "unknown";
			}
		}
		switch ($name) {
			case 'Moment':
				$obj->setTimezone($this->getSystemTimezone());
				$min = (int)$obj->format("i"); //remove leading zeros
				$cronstring = $min . " " . $obj->format("G j n *");
				break;
			case 'Carbon':
			case 'DateTime':
				$obj->setTimezone(new \DateTimeZone($this->getSystemTimezone()));
				$min = (int)$obj->format("i"); //remove leading zeros
				$cronstring = $min . " " . $obj->format("G j n *");
				break;
			case 'text':
				if (is_numeric($obj)) {
					$date = new \DateTime();
					try {
						$date->setTimestamp($obj);
					} catch (\Exception) {
						return false;
					}
				} else {
					try {
						$date = new \DateTime($obj);
					} catch (\Exception) {
						return false;
					}
				}
				$date->setTimezone(new \DateTimeZone($this->getSystemTimezone()));
				$min = (int)$date->format("i"); //remove leading zeros
				$cronstring = $min . " " . $date->format("G j n *");
				break;
			default:
				return false;
				break;
		}
		return sprintf("%s %s", $cronstring, $command);
	}

	private function getSystemTimezone()
	{
		$timezone = '';
		if (is_link('/etc/localtime')) {
			// Mac OS X (and older Linuxes)
			// /etc/localtime is a symlink to the
			// timezone in /usr/share/zoneinfo.
			$filename = readlink('/etc/localtime');
			$pathpos = strpos($filename, '/usr/share/zoneinfo/');
			if ($pathpos !== false) {
				$timezone = trim(substr($filename, ($pathpos + 20)));
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
				$timezone = trim((string) $data['ZONE']);
			}
		}
		if (empty($timezone)) {
			throw new \Exception("Unable to determine system timezone");
		}
		return $timezone;
	}

	public function outlookToken($id)
	{
		$outlookdata = $this->getConfig($id, 'outlook-details');
		$oauth = new Oauth($outlookdata['tenantid'], $outlookdata['consumerkey'], $outlookdata['consumersecret'], null, null, $outlookdata['pbxurl'], $outlookdata['outlookurl']);
		$result = json_decode((string) $oauth->getAuthToken($outlookdata['auth_code']), true, 512, JSON_THROW_ON_ERROR);
		$outlooknewdata = [];
		if (isset($result['access_token'])) {
			$outlooknewdata = $outlookdata;
			$outlooknewdata['access_token'] = $result['access_token'];
			$outlooknewdata['token_expire_at'] = time() + $result['expires_in'];
			$outlooknewdata['refresh_token'] = $result['refresh_token'] ?? '';
			$this->setConfig($id, $outlooknewdata, 'outlook-details');
			return ["status" => true, "message" => _("saved access token")];
		} else {
			return ["status" => false, "message" => $result['error']];
		}
	}

	public function getOutlookTokenRefresh($outlookDetails)
	{
		if (isset($outlookDetails['refresh_token'])) {
			$oauth = new Oauth($outlookDetails['tenantid'], $outlookDetails['consumerkey'], $outlookDetails['consumersecret'], null, null, $outlookDetails['pbxurl'], $outlookDetails['outlookurl']);
			$result = json_decode((string) $oauth->getTokenRefresh($outlookDetails), true, 512, JSON_THROW_ON_ERROR);
			if (isset($result['access_token'])) {
				$outlookDetails['access_token'] = $result['access_token'];
				$outlookDetails['token_expire_at'] = time() + $result['expires_in'];
			}

			if (isset($result['refresh_token'])) {
				$outlookDetails['refresh_token'] = $result['refresh_token'];
			}

			$this->setConfig($outlookDetails['id'], $outlookDetails, 'outlook-details');
		}
		return $outlookDetails;
	}

	public function getallOauthSettings()
	{
		$allsettings = $this->getAll('outlook-details');
		$settings = [];
		foreach ($allsettings as $key => $_sett) {
			if ($key) {
				$settings[$key] = $_sett['name'];
			}
		}
		return $settings;
	}

	public function checkConfigExists($data, $id)
	{
		$allConfig = $this->getallOauthSettings();
		foreach ($allConfig as $key => $value) {
			if ($key == $id) {
				continue;
			}

			if ($value == $data['name']) {
				return false;
			}
		}
		return true;
	}
}

