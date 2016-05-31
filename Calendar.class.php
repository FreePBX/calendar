<?php
namespace FreePBX\modules;
use \Moment\Moment;
use \Moment\CustomFormats\MomentJs;
$setting = array('authenticate' => true, 'allowremote' => false);

class Calendar extends \DB_Helper implements \BMO {
	public function __construct($freepbx = null) {
		if ($freepbx == null) {
			throw new Exception("Not given a FreePBX Object");
		}
		$this->FreePBX = $freepbx;
		$this->db = $freepbx->Database;
		$this->systemtz = $this->getSystemTZ();
	}

	public function backup() {}
	public function restore($backup) {}
  public function install(){}
  public function uninstall(){}
	public function doConfigPageInit($page) {}
	public function getSystemTZ(){
		$tz = \FreePBX::Config()->get('PHPTIMEZONE');
		if($tz){
			return $tz;
		}
		$tz = date_default_timezone_get();
		if($tz){
			return $tz;
		}
		return 'UTC';
	}
	public function ajaxRequest($req, &$setting) {
    switch($req){
      case 'events':
			case 'eventform':
        return true;
      break;
      default:
        return false;
      break;
    }
	}
	public function ajaxHandler() {
    switch ($_REQUEST['command']) {
      case 'events':
				$return = array();
				foreach($this->listEvents($_REQUEST['start'],$_REQUEST['end']) as $event){
					$return[] = $event;
				}
				foreach($this->parseTimeConditions($_REQUEST['start'],$_REQUEST['end']) as $event){
					$return[] = $event;
				}

        return $return;
      break;
			case 'eventform':
				if(isset($_REQUEST['id']) && $_REQUEST['id'] == 'new'){
					return $this->addEvent($_REQUEST);
				}else{
					return $this->updateEvent($_REQUEST);
				}
			break;
    }
  }

	Public function myDialplanHooks(){
		return '490';
	}
	public function doDialplanHook(&$ext, $engine, $priority){}
	public function getEvent($id){
		return $this->getConfig($id,'events');
	}

	public function listEvents($start = '', $stop = '', $tz = '', $filter = array('type' => '', 'data'=>'')){
		$start = !empty($start)?$start:false;
		$stop = !empty($stop)?$stop:false;
		$tz = !empty($tz)?$tz:false;
		$rawEvents = $this->getAll('events');
		switch ($filter['type']) {
			case 'user':
				$events = $this->eventFilterUser($rawEvents, $filter['data']);
			break;
			case 'type':
				$events = $this->eventFilterType($rawEvents, $filter['data']);
			break;
			default:
				$events = $rawEvents;
			break;
		}
		unset($rawEvents);
		if($start && $stop){
			$events = $this->eventFilterDates($events, $start, $stop);
		}
		return $events;
	}
	public function addEvent($eventOBJ){
		if(empty($eventOBJ)){
			return array('status' => false, 'message' => _('Event object can not be empty'));
		}
		if(!is_array($eventOBJ)){
			return array('status' => false, 'message' => _('Event object must be an array'));
		}
		$eventDefaults = array(
			'uid' => uniqid('fpcal_'),
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
		$insertOBJ = array();
		foreach($eventDefaults as $K => $V){
			$value = isset($eventOBJ[$K])?$eventOBJ[$K]:$V;
			switch($K){
				case 'truedest':
					$insertOBJ[$K] = isset($eventOBJ['goto0'])?$this->getGoto('goto0', $eventOBJ):'';
				break;
				case 'falsedest':
					$insertOBJ[$K] = isset($eventOBJ['goto1'])?$this->getGoto('goto1', $eventOBJ):'';
				break;
				default:
					$insertOBJ[$K] = $value;
				break;
			}
		}
			$this->setConfig($insertOBJ['uid'],$insertOBJ,'events');
			return array('status' => true, 'message' => _("Event added"),'id' => $insertOBJ['uid']);
	}
	public function enableEvent($id){
		$event = $this->getConfig($id,'events');
		$event['active'] = true;
		$this->setConfig($id,$event,'events');
	}
	public function disableEvent($id){
		$event = $this->getConfig($id,'events');
		$event['active'] = false;
		$this->setConfig($id,$event,'events');
	}
	public function deleteEventById($id){
		$this->delById($id);
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
	public function updateEvent($eventOBJ){
		if(!isset($eventOBJ['id']) || empty($eventOBJ['id'])){
			return array('status' => false, 'message' => _("No event ID received"));
		}
		$id = $eventOBJ['id'];
		$event = $this->getConfig($id,'events');
		$valid_keys = array(
			'uid',
			'user',
			'description',
			'hookdata',
			'active',
			'generatehint',
			'generatefc',
			'eventtype',
			'weekdays',
			'monthdays',
			'months',
			'timezone',
			'startdate',
			'enddate',
			'starttime',
			'endtime',
			'repeatinterval',
			'frequency',
			'truedest',
			'falsedest'
		);
		foreach($eventOBJ as $key => $val){
			switch ($key) {
				case 'truedest':
					$val = isset($eventOBJ['goto0'])?$this->getGoto('goto0', $eventOBJ):'';
					$event[$key] = $val;
				break;
				case 'falsedest':
					$val = isset($eventOBJ['goto1'])?$this->getGoto('goto1', $eventOBJ):'';
					$event[$key] = $val;
				break;
				default:
				if(in_array($key, $valid_keys)){
					$event[$key] = $val;
				}
				break;
			}
		}
			$this->setConfig($id,$event,'events');
			return array('status' => true, 'message' => _("Event Updated"), 'count' => $stmt->rowCount());
	}

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
						'start' => sprintf('%s %s',date('Y-m-d', $istart),$stime),
						'end' => sprintf('%s %s',date('Y-m-d', $istart),$etime),
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
		$mStart = new Moment($start);
		$mEnd = new Moment($end);
		foreach ($data as $key => $value) {
			$startdate = new Moment($value['startdate']);
			$stattdate = $startdate->format();
			$enddate = new Moment($value['enddate']);
			$enddate = $enddate->format();
			if(!isset($startdate) || !isset($enddate)){
				unset($data[$key]);
				continue;
			}

			//Is the start date and start the same
			$sSame = (!$mStart->isAfter($startdate, 'day') && !$mStart->isBefore($startdate, 'day'));
			//Is the end date and end the same
			$eSame = (!$mEnd->isAfter($enddate, 'day') && !$mEnd->isBefore($enddate, 'day'));
			//If either start or end are a match we are in the range, move on.
			if($eSame || $sSame){
				continue;
			}
			//Now check if the dates are in range.
			if((!$mStart->isAfter($startdate, 'day') || !$mEnd->isBefore($enddate, 'day'))){
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
}
