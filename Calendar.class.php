<?php
namespace FreePBX\modules;
use \Moment\Moment;
use \Moment\CustomFormats\MomentJs;
use \Ramsey\Uuid\Uuid;
use \Ramsey\Uuid\Exception\UnsatisfiedDependencyException;

include __DIR__."/includes/class.iCalReader.php";

class Calendar extends \DB_Helper implements \BMO {
	public function __construct($freepbx = null) {
		if ($freepbx == null) {
			throw new Exception("Not given a FreePBX Object");
		}
		$this->FreePBX = $freepbx;
		$this->db = $freepbx->Database;
		$this->systemtz = $this->FreePBX->View()->getTimezone();
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

	public function backup() {}
	public function restore($backup) {}
  public function install(){}
  public function uninstall(){}
	public function doConfigPageInit($page) {
		switch ($page) {
			case 'calendar':
				$action = isset($_REQUEST['action'])?$_REQUEST['action']:'';
				switch($action) {
					case "add":
						if(isset($_POST['name'])) {
							$name = $_POST['name'];
							$description = $_POST['description'];
							$url = $_POST['url'];
							$type = $_POST['type'];
							$this->addRemoteCalendar($name,$description,$type,$url);
						}
					break;
					case "edit":
						if(isset($_POST['name'])) {
							$name = $_POST['name'];
							$description = $_POST['description'];
							$url = $_POST['url'];
							$type = $_POST['type'];
							$id = $_POST['id'];
							$this->updateRemoteCalendar($id, $name,$description,$type,$url);
						}
					break;
					case "delete":
						$this->delCalendarByID($_REQUEST['id']);
					break;
				}
			break;
			case 'calendargroups':
				$action = isset($_REQUEST['action'])?$_REQUEST['action']:'';
				$_REQUEST['description'] = isset($_REQUEST['description'])?$_REQUEST['description']:'';
				$_REQUEST['events'] = isset($_REQUEST['events'])?$_REQUEST['events']:array();
				$_REQUEST['id'] = isset($_REQUEST['id'])?$_REQUEST['id']:'';
				switch ($action) {
					case 'add':
						$this->addGroup($_REQUEST['description'],$_REQUEST['events']);
						\need_reload();
						$_REQUEST['view'] = null;
						$_REQUEST['id'] = null;
					break;
					case 'edit':
						$this->updateGroup($_REQUEST['id'],$_REQUEST['description'], $_REQUEST['events']);
						\need_reload();
						$_REQUEST['view'] = null;
						$_REQUEST['id'] = null;
					break;
					case 'delete':
						$this->deleteGroup($_REQUEST['id']);
						\need_reload();
						$_REQUEST['view'] = null;
						$_REQUEST['id'] = null;
					break;
					default:
					break;
				}

			break;

			default:
				# code...
				break;
		}
	}
	public function ajaxRequest($req, &$setting) {
    switch($req){
			case 'grid':
      case 'events':
			case 'eventform':
			case 'getJSON':
        return true;
      break;
      default:
        return false;
      break;
    }
	}
	public function ajaxHandler() {
    switch ($_REQUEST['command']) {
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
				$return = array();
				$events = $this->listEvents($_REQUEST['calendarid'],$_REQUEST['start'],$_REQUEST['end']);
				foreach($events as $event){
					$return[] = $event;
				}
				//foreach($this->parseTimeConditions($_REQUEST['start'],$_REQUEST['end']) as $event){
					//$return[] = $event;
				//}
        return $return;
      break;
			case 'eventform':
				if(isset($_REQUEST['eventid']) && $_REQUEST['eventid'] == 'new'){
					return $this->addEvent($_REQUEST);
				}else{
					return $this->updateEvent($_REQUEST);
				}
			break;
			case 'getJSON':
				$ret = array();
				$groups =  $this->listGroups();
				foreach ($groups as $key => $value) {
					$ret[] = array(
						'id' => $key,
						'description' => $value['description'],
						'events' => $value['events']
					);
				}
				return $ret;
			break;
    }
  }

	public function showPage() {
		$action = !empty($_GET['action']) ? $_GET['action'] : '';
		switch($action) {
			case "add":
				$type = !empty($_GET['type']) ? $_GET['type'] : '';
				switch($type) {
					case "ical":
					case "outlook":
					case "caldav":
					case "google":
						return load_view(__DIR__."/views/remote_settings.php",array('action' => 'add', 'type' => $type));
					break;
					case "local":
						return load_view(__DIR__."/views/local_settings.php",array('action' => 'add', 'type' => $type));
					break;
				}
			break;
			case "edit":
				$data = $this->getCalendarByID($_GET['id']);
				switch($data['type']) {
					case "ical":
					case "outlook":
					case "caldav":
					case "google":
						return load_view(__DIR__."/views/remote_settings.php",array('action' => 'edit', 'type' => $data['type'], 'data' => $data));
					break;
					case "local":
						return load_view(__DIR__."/views/local_settings.php",array('action' => 'edit', 'type' => $data['type'], 'data' => $data));
					break;
				}
			break;
			case "view":
				$data = $this->getCalendarByID($_GET['id']);
				return load_view(__DIR__."/views/calendar.php",array('action' => 'view', 'type' => $data['type'], 'data' => $data));
			break;
			default:
				return load_view(__DIR__."/views/grid.php",array());
			break;
		}
	}

	Public function myDialplanHooks(){
		return '490';
	}
	public function doDialplanHook(&$ext, $engine, $priority){
		//Add on apply config.
		$file = \FreePBX::Config()->get('ASTVARLIBDIR').'/bin/calendar-update';
		$cmd = "[ -x $file ] && $file";
		// Ensure we instantiate cron with the correct user
		$c = \FreePBX::create()->Cron(\FreePBX::Config()->get('AMPASTERISKWEBUSER'));
		$c->addLine("* * * * * $cmd");
		//End Cron Job

		//Dialplan
		$dpapp = 'calendargroups';
		foreach ($this->listGroups() as $key => $value) {
			$ext->add($dpapp,$key,$key, new \ext_agi('calendar.agi,'.implode(',',$value['events'])));
		}
	}
	public function getEvent($id){
		return $this->getConfig($id,'events');
	}

	public function listCalendars() {
		$calendars = $this->getAll('calendars');
		return $calendars;
	}

	public function delCalendarByID($id) {
		$this->setConfig($id,false,'calendars');
		$this->delById($id."-events");
	}

	public function getCalendarByID($id) {
		$final = $this->getConfig($id,'calendars');
		$final['id'] = $id;
		return $final;
	}

	/**
	 * List Events
	 * @param  string $start  Start date
	 * @param  string $stop   End Date
	 * @param  string $tz     Timezone
	 * @param  bool $subevents Break date ranges in to daily events.
	 * @return array  an array of events
	 */
	public function listEvents($calendarID, $start = '', $stop = '', $tz = '', $subevents = true){
		$return = array();
		$start = !empty($start)?$start:false;
		$stop = !empty($stop)?$stop:false;
		$tz = !empty($tz)?$tz:false;
		$events = $this->getAll($calendarID.'-events');
		if(($start !== false) && ($stop !== false)){
			$events = $this->eventFilterDates($events, $start, $stop);
		}
		foreach($events as $uid => $event){
			$starttime = !empty($event['starttime'])?$event['starttime']:'00:00:00';
			$endtime = !empty($event['endtime'])?$event['endtime']:'23:59:59';
			$event['title'] = $event['name'];
			if(($event['starttime'] != $event['endtime']) && $subevents){
				$startrange = new \DateTime();
				$startrange->setTimestamp($event['starttime']);
				$endrange = new \DateTime();
				$endrange->setTimestamp($event['endtime']);
				$interval = new \DateInterval('P1D');
				$daterange = new \DatePeriod($startrange, $interval ,$endrange);
				$i = 0;
				foreach($daterange as $d) {
					$tempevent = $event;
					$tempevent['uid'] = $uid.'_'.$i;
					$tempevent['startdate'] = $d->format('Y-m-d');
					$tempevent['enddate'] = $d->format('Y-m-d');
					$tempevent['starttime'] = $d->format('H:i:s');
					$tempevent['endtime'] = $d->format('H:i:s');
					$tempevent['start'] = sprintf('%sT%s',$tempevent['startdate'],$tempevent['starttime']);
					$tempevent['end'] = sprintf('%sT%s',$tempevent['enddate'],$tempevent['endtime']);
					$tempevent['allDay'] = ($event['endtime'] - $event['starttime']) === 86400;
					$tempevent['parent'] = $event;
					$return[$tempevent['uid']] = $tempevent;
					$i++;
				}
			}else{
				$event['start'] = sprintf('%sT%s',$event['starttime'],$starttime);
				$event['end'] = sprintf('%sT%s',$event['endtime'],$endtime);
				$return[$event['uid']] = $event;
			}
		}
		return $return;
	}

	/**
	 * Add Event to specific calendar
	 * @param string $calendarID  The Calendar ID
	 * @param string $eventID     The Event ID, if null will auto generatefc
	 * @param string $name        The event name
	 * @param string $description The event description
	 * @param string $starttime   The event start timezone
	 * @param string $endtime     The event end time
	 */
	public function addEvent($calendarID,$eventID=null,$name,$description,$starttime,$endtime){
		$uuid = !is_null($eventID) ? $eventID : Uuid::uuid4();
		$this->updateEvent($calendarID,$uuid,$name,$description,$starttime,$endtime);
	}

	/**
	 * Update Event on specific calendar
	 * @param string $calendarID  The Calendar ID
	 * @param string $eventID     The Event ID, if null will auto generatefc
	 * @param string $name        The event name
	 * @param string $description The event description
	 * @param string $starttime   The event start timezone
	 * @param string $endtime     The event end time
	 */
	public function updateEvent($calendarID,$eventID,$name,$description,$starttime,$endtime) {
		$event = array(
			"name" => $name,
			"description" => $description,
			"starttime" => $starttime,
			"endtime" => $endtime
		);
		$this->setConfig($eventID,$event,$calendarID."-events");
		return $uuid;
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
	 * Add a Remote Calendar
	 * @param string $name        The Calendar name
	 * @param string $description The Calendar description
	 * @param string $type        The Calendar type
	 * @param string $url         The Calendar URL
	 */
	public function addRemoteCalendar($name,$description,$type,$url) {
		$uuid = Uuid::uuid4();
		$calendar = array(
			"name" => $name,
			"description" => $description,
			"type" => $type,
			"url" => $url
		);
		$this->setConfig($uuid,$calendar,'calendars');
		$calendar['id'] = $uuid;
		$this->processCalendar($calendar);
	}

	/**
	 * Update a Remote Calendar's settings
	 * @param string $id          The Calendar ID
	 * @param string $name        The Calendar name
	 * @param string $description The Calendar description
	 * @param string $type        The Calendar type
	 * @param string $url         The Calendar URL
	 */
	public function updateRemoteCalendar($id, $name,$description,$type,$url) {
		//TODO: make sure id exists!
		$calendar = array(
			"name" => $name,
			"description" => $description,
			"type" => $type,
			"url" => $url
		);
		$this->setConfig($id,$calendar,'calendars');
		$calendar['id'] = $id;
		$this->processCalendar($calendar);
	}

	public function processCalendar($calendar) {
		switch($calendar['type']) {
			case "ical":
				$ical = new \ICal($calendar['url']);
				$events = $ical->events();
				$processed = array();
				foreach($events as $event) {
					$event['UID'] = isset($event['UID']) ? $event['UID'] : 0;
					$event['DESCRIPTION'] = !empty($event['DESCRIPTION']) ? $event['DESCRIPTION'] : "";
					$this->updateEvent($calendar['id'],$event['UID'],htmlspecialchars_decode($event['SUMMARY'], ENT_QUOTES),htmlspecialchars_decode($event['DESCRIPTION'], ENT_QUOTES),$ical->iCalDateToUnixTimestamp($event['DTSTART']),$ical->iCalDateToUnixTimestamp($event['DTEND']));
				}
			break;
		}
	}

	/**
	 * Enable Evemt
	 * @param  string $id event id
	 * @return null
	 */
	public function enableEvent($id){
		$event = $this->getConfig($id,'events');
		$event['active'] = true;
		$this->setConfig($id,$event,'events');
	}

	/**
	 * Disable Event
	 * @param  string $id event id
	 * @return null
	 */
	public function disableEvent($id){
		$event = $this->getConfig($id,'events');
		$event['active'] = false;
		$this->setConfig($id,$event,'events');
	}

	public function deleteEventById($id){
		$this->setConfig($id, false, 'events');
		if($this->getFirst($id)){
			return array('status' => false, 'message' => _("Failed to delete event"));
		}else{
			return array('status' => true, 'message' => _("Event Deleted"));
		}
	}
	public function deleteEventByUser($uid){
		$sql = 'DELETE FROM calendar_events WHERE uid = :uid';
		$stmt = $this->db->prepare($sql);
		if($stmt->execute(array(':uid' => $uid))){
			return array('status' => true, 'message' => _("Events Deleted"), 'count' => $stmt->rowCount());
		}else{
			return array('status' => false, 'message' => _("Failed to delete events"), 'error' => $stmt->errorInfo());
		}
	}
	/*
	public function updateEvent($eventOBJ){
		if(!isset($eventOBJ['eventid']) || empty($eventOBJ['eventid'])){
			return array('status' => false, 'message' => _("No event ID received"));
		}
		$id = $eventOBJ['eventid'];
		$event = $this->getConfig($id,'events');

		foreach($eventOBJ as $key => $val){
			switch ($key) {
				case 'goto0':
					$val = isset($eventOBJ['goto0'])?$this->getGoto('goto0', $eventOBJ):'';
					$event['truedest'] = $val;
				break;
				case 'goto1':
					$val = isset($eventOBJ['goto1'])?$this->getGoto('goto1', $eventOBJ):'';
					$event['falsedest'] = $val;
				break;
				case 'weekdays':
					$event[$key] = array();
					$val = is_array($val)?$val:array($val);
					foreach ($val as $k => $value) {
						$event[$key][$value] = $value;
					}
				break;
				default:
				if(isset($this->eventDefaults[$key])){
					$event[$key] = $val;
				}
				break;
			}
		}
		dbug($event);
			$this->setConfig($id,$event,'events');
			return array('status' => true, 'message' => _("Event Updated"));
	}
	*/

	public function getEventTypes($showhidden = false){
		$ret = array();
		$ret['calendaronly'] = array('desc' => _("Calendar Only"), 'type' => 'all', 'visible' => true);
		$ret['presence'] = array('desc' => _("Presence"), 'type' => 'all', 'visible' => true);
		$ret['callflow'] = array('desc' => _("Call Flow"), 'type' => 'admin', 'visible' => true);
		if($showhidden){
			$ret['hook'] = array('desc' => _("Hook"), 'type' => 'all', 'visible' => false);
		}
		return $ret;
	}

	public function addGroup($desc, $events){
			$id = uniqid('fpgrp_');
			$events = is_array($events)?$events:array($events);
			$insert = array('description' => $desc, 'events' => $events);
			$this->setConfig($id,$insert,'groups');
	}
	public function updateGroup($id, $description, $events){
		$events = is_array($events)?$events:array($events);
		$insert = array('description' => $description, 'events' => $events);
		$this->setConfig($id, $insert, 'groups');
	}
	public function deleteGroup($id){
		$this->setConfig($id, false, 'groups');
	}
	public function getGroup($id){
		return $event = $this->getConfig($id,'groups');
	}
	public function listGroups(){
			return $this->getAll('groups');
	}
	public function buildRangeDays($start, $end, $hour, $days, $dom, $month){
		$days = ($days == '*')?'sun-sat':$days;
		$hour = ($hour == '*')?'00:00-23:59':$hour;
		$dom = ($dom == '*')?'1-31':$dom;
		$month = ($month == '*')?'jan-dec':$month;
		$days = explode('-',$days);
		$hour = explode('-',$hour);
		$dom = explode('-',$dom);
		$month = explode('-',$month);
		$daysrange = isset($days[1])?true:false;
		$hourrange = isset($hour[1])?true:false;
		$domrange = isset($dom[1])?true:false;
		$monthrange = isset($month[1])?true:false;
		$start = strtotime($start);
		$end = strtotime($end);
		$ret = array();
		if($daysrange){
			$sday = date('w', strtotime($days[0]));
			$eday = date('w', strtotime($days[1]));
			$dayArr = range($sday,$eday);
		}else{
			$dayArr = array(date('w', strtotime($days[0])));
		}
		if($monthrange){
			$smonth = date('n', strtotime($month[0]));
			$emonth = date('n', strtotime($month[1]));
			$monthArr = range($smonth,$emonth);
		}else{
			$monthArr = array(date('n', strtotime($month[0])));
		}
		if($domrange){
			$sdom = $dom[0];
			$edom = $dom[1];
			$domArr = range($sdom,$edom);
		}else{
			$domArr = array($dom[0]);
		}
		if($hourrange){
			$stime = $hour[0];
			$etime = $hour[1];
		}else{
			$stime = $hour[0];
			$etime = $hour[0];
		}
		foreach($dayArr as $day){
			$istart = $start;
			$iend = $end;
			//adapted from http://stackoverflow.com/a/4482605
			do{
				if(date("w", $istart) != $day){
					$istart += (24 * 3600); // add 1 day
				}
			} while(date("w", $istart) != $day);
				while($istart <= $iend){
					if(!in_array(date('n',$istart),$monthArr)){
						$istart += (7 * 24 * 3600);
						continue;
					}
					if(!in_array(date('j',$istart),$domArr)){
						$istart += (7 * 24 * 3600);
						continue;
					}
					$ret[] = array(
						'start' => sprintf('%sT%s',date('Y-m-d', $istart),$stime),
						'end' => sprintf('%sT%s',date('Y-m-d', $istart),$etime),
					);
					$istart += (7 * 24 * 3600); // add 7 days
				}
		}
		return $ret;
	}
	public function parseTimeConditions($start,$end){
		$sql = 'SELECT timeconditions.displayname, timegroups_details.time, timeconditions.timeconditions_id, timeconditions.truegoto, timeconditions.falsegoto FROM timegroups_groups INNER JOIN timegroups_details ON timegroups_groups.id=timegroups_details.timegroupid INNER JOIN timeconditions ON timeconditions.time=timegroups_groups.id';
		$stmt = $this->db->prepare($sql);
		$stmt->execute();
		$ret = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		$results = array();
		foreach ($ret as $tc) {
			list($hour, $dow, $dom, $month) = explode("|", $tc['time']);
			$info = $this->buildRangeDays($start, $end, $hour, $dow, $dom, $month);
			foreach ($info as $item) {
				$results[] = array(
					'id' => $tc['timeconditions_id'],
					'title' => $tc['displayname'],
					'start' => $item['start'],
					'startdate' => $item['start'],
					'enddate' => $item['end'],
					'end' => $item['end'],
					'eventtype' => 'callflow',
					'canedit' => false,
					'truedest' => $tc['truegoto'],
					'falsedest' => $tc['falsegoto'],
				);
			}
		}
		return $results;
	}
	public function getGoto($id,$request){
		if(isset($request[$id])){
			$idx = substr($id, -1, 1);
			return $request[$request[$id].$idx];
		}else{
			return false;
		}
	}
	public function eventFilterUser($data, $user){
		foreach ($data as $key => $value) {
			if(!isset($value['user'])){
				unset($data[$key]);
				continue;
			}
			if(isset($value['user']) && $value['user'] != $user){
				unset($data[$key]);
			}
		}
		return $data;
	}
	public function eventFilterDates($data, $start, $end){
		$mStart = new Moment($start,$this->systemtz);
		$mEnd = new Moment($end,$this->systemtz);
		foreach ($data as $key => $value) {
			if(!isset($value['starttime']) || !isset($value['endtime'])){
				unset($data[$key]);
				continue;
			}
			$timezone = isset($value['timezone'])?$value['timezone']:$this->systemtz;
			$startdate = new Moment('@'.$value['starttime'],$timezone);
			$enddate = new Moment('@'.$value['endtime'],$timezone);
			//Is the start date and start the same
			$sSame = (!$mStart->isAfter($startdate, 'day') && !$mStart->isBefore($startdate, 'day'));
			//Is the end date and end the same
			$eSame = (!$mEnd->isAfter($enddate, 'day') && !$mEnd->isBefore($enddate, 'day'));

			//If either start or end are a match we are in the range, move on.
			if($eSame || $sSame){
				continue;
			}
			//Now check if the dates are in range.
			if(!empty($mStart->isAfter($startdate, 'day')) || !empty($mEnd->isBefore($enddate, 'day'))){
				unset($data[$key]);
			}
		}
		return $data;
	}

	public function eventFilterType($data, $type){
		foreach ($data as $key => $value) {
			if(!isset($value['eventtype'])){
				unset($data[$key]);
				continue;
			}
			if($value['eventtype'] != $type){
				unset($data[$key]);
			}
		}
		return $data;
	}
	public function checkEvent($event){
		if(!is_array($event)){
			return false;
		}
		//Return False if not active
		if(isset($event['active']) && $event['active'] != '1'){
			return false;
		}

		$tz = isset($event['timezone'])?$event['timezone']:$this->systemtz;
		$m = new Moment('now', $tz);
		//Check if a start date is set and if we are before it.
		if(isset($event['starttime']) && !empty($event['starttime'])){
			$d = new Moment($event['starttime'], $tz);
			if($m->isBefore($d, 'day')){
				return false;
			}
		}

		//Check if a end date is set and if we are after it.
		if(isset($event['endtime']) && !empty($event['endtime'])){
			$d = new Moment($event['endtime'], $tz);
			if(!$m->isAfter($d, 'day')){
				return false;
			}
		}

		//If months are set Check the current month.
		//$m->getMonth() = 01-12
		if(isset($event['months']) && !empty($event['months'])){
			if(!isset($event['months'][$m->getMonth()])){
				return false;
			}
		}

		//Check If the days are set and if today is set.
		if(isset($event['monthdays']) && !empty($event['monthdays'])){
			if(!isset($event['monthdays'][$m->getDay()])){
				return false;
			}
		}

		//Check If the days are set and if today is set.
		if(isset($event['weekdays']) && !empty($event['weekdays'])){
			if(!isset($event['weekdays'][$m->format('N')])){
				return false;
			}
		}

		//Check if we are in range.
		if(isset($event['start']) && isset($event['end'])){
			$start = new Moment($event['start'], $tz);
			$end = new Moment($event['end'], $tz);
			if(!$m->isBetween($start,$end,true,'minute')){
				return false;
			}
		}
		return true;
	}
	public function getEventOptions($groupid = ''){
		$events = $this->listEvents('', '', '', array(), false);
		$selected = array();
		if($groupid !== ''){
			$group = $this->getGroup($groupid);
			$gevents = isset($group['events'])?$group['events']:array();
			foreach ($gevents as $key => $value) {
				$selected[$value] = $value;
			}
		}
		$ret = '';
		foreach($events as $key => $val){
			$s = isset($selected[$key])?'SELECTED':'';
			$ret .= '<option value='.$key.' '.$s.'>'.$val['title'].'</option>'.PHP_EOL;
		}
		return $ret;
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
				if (empty($request['id'])) {
					unset($buttons['delete']);
				}
				if(!isset($request['view'])){
					$buttons = array();
				}
			break;
		}
		return $buttons;
	}
	//UCP STUFF
	public function ucpConfigPage($mode, $user, $action) {
		if(empty($user)) {
			$enabled = ($mode == 'group') ? true : null;
		} else {
			if($mode == 'group') {
				$enabled = $this->FreePBX->Ucp->getSettingByGID($user['id'],'Calendar','enabled');
				$enabled = !($enabled) ? false : true;
			} else {
				$enabled = $this->FreePBX->Ucp->getSettingByID($user['id'],'Calendar','enabled');
			}
		}

		$html = array();
		$html[0] = array(
			"title" => _("Calendar"),
			"rawname" => "calendar",
			"content" => load_view(dirname(__FILE__)."/views/ucp_config.php",array("mode" => $mode, "enabled" => $enabled))
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
		}
	}
	public function ucpDelGroup($id,$display,$data) {}

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
}
