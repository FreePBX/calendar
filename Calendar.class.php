<?php
namespace FreePBX\modules;
$setting = array('authenticate' => true, 'allowremote' => false);

class Calendar implements \BMO {
	public function __construct($freepbx = null) {
		if ($freepbx == null) {
			throw new Exception("Not given a FreePBX Object");
		}
		$this->FreePBX = $freepbx;
		$this->db = $freepbx->Database;
	}

	public function backup() {}
	public function restore($backup) {}
  public function install(){}
  public function uninstall(){}
	public function doConfigPageInit($page) {}
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
		$sql = 'SELECT * FROM calendar_events WHERE id = ? LIMIT 1';
		$stmt = $this->db->prepare($sql);
		$stmt->execute(array(':id' => $id));
		$ret = $stmt->fetch(\PDO::FETCH_ASSOC);
		return $ret;
	}

	public function listEvents($start = '', $stop = ''){
		$start = !empty($start)?strtotime($start):false;
		$stop = !empty($stop)?strtotime($stop):false;
		$params = array();
		$sql = 'SELECT * FROM calendar_events ';
		if($start && $stop){
			$sql .= 'WHERE startdate >= ? AND enddate <= ?';
			$params = array($start,$stop);
		}elseif($start){
			$sql .= 'WHERE startdate >= ?';
			$params = array($start);
		}elseif($stop){
			$sql .= 'WHERE enddate <= ?';
			$params = array($stop);
		}

		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);
		$ret = $stmt->fetchall(\PDO::FETCH_ASSOC);
		foreach ($ret as $key => $value) {
			if(isset($value['description'])){
				$ret[$key]['title'] = $value['description'];
			}
			if(isset($value['startdate'])){
				$ret[$key]['startdate'] = date("Y-m-d\TH:i:sO",$value['startdate']);
				$ret[$key]['start'] = date("Y-m-d\TH:i:sO",$value['startdate']);
			}
			if(isset($value['enddate'])){
				$ret[$key]['enddate'] = date("Y-m-d\TH:i:sO",$value['enddate']);
				$ret[$key]['end'] = date("Y-m-d\TH:i:sO",$value['enddate']);
			}
		}
		return $ret;
	}
	public function addEvent($eventOBJ){
		if(empty($eventOBJ)){
			return array('status' => false, 'message' => _('Event object can not be empty'));
		}
		if(!is_array($eventOBJ)){
			return array('status' => false, 'message' => _('Event object must be an array'));
		}
		$eventDefaults = array(
			'uid' => '',
			'description' => '',
			'hookdata' => '',
			'active' => true,
			'generatehint' => false,
			'generatefc' => false,
			'eventtype' => 'calendaronly',
			'weekdays' => '',
			'monthdays' => '',
			'months' => '',
			'startdate' => '',
			'enddate' => '',
			'repeatinterval' => '',
			'frequency' => '',
			'truedest' => '',
			'falsedest' => ''
		);
		$insertOBJ = array();
		foreach($eventDefaults as $K => $V){
			$value = isset($eventOBJ[$K])?$eventOBJ[$K]:$V;
			switch($K){
				case 'startdate':
				case 'enddate':
					$value = strtotime($value);
					$insertOBJ[':'.$K] = $value;
				break;
				default:
					$insertOBJ[':'.$K] = $value;
				break;
			}
		}
		$sql = 'INSERT INTO calendar_events ('.implode(',',array_keys($eventDefaults)).') VALUES ('.implode(',',array_keys($insertOBJ)).')';
		$stmt = $this->db->prepare($sql);
		if($stmt->execute($insertOBJ)){
			return array('status' => true, 'message' => _("Event added"),'id' => $this->db->lastInsertId());
		}else{
			return array('status' => false, 'message' => _("Failed to add event"), 'error' => $stmt->errorInfo());
		}
	}
	public function enableEvent(){}
	public function disableEvent(){}
	public function deleteEventById($id){
		$sql = 'DELETE FROM calendar_events WHERE id = :id LIMIT 1';
		$stmt = $this->db->prepare($sql);
		if($stmt->execute(array(':id' => $id))){
			return array('status' => true, 'message' => _("Event Deleted"));
		}else{
			return array('status' => false, 'message' => _("Failed to delete event"), 'error' => $stmt->errorInfo());
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
		$valid_keys = array(
			'uid',
			'description',
			'hookdata',
			'active',
			'generatehint',
			'generatefc',
			'eventtype',
			'weekdays',
			'monthdays',
			'months',
			'startdate',
			'enddate',
			'repeatinterval',
			'frequency',
			'truedest',
			'falsedest'
		);
		$insertObJ = array();
		$params = array();
		foreach($eventOBJ as $key => $val){
			if(in_array($key, $valid_keys)){
				$insertOBJ[':'.$key] = $val;
				$params[] = sprintf('%s=:%s ',$key,$key);
			}
			$insertOBJ[':id'] = $id;
			$sql = sprintf('UPDATE calander_events set %s WHERE id = :id',implode(',', $params));
			$stmt = $this->db->prepare($sql);
			if($stmt->execute($insertOBJ)){
				return array('status' => true, 'message' => _("Event Updated"), 'count' => $stmt->rowCount());
			}else{
				return array('status' => false, 'message' => _("Failed to update event"), 'error' => $stmt->errorInfo());
			}
		}
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
					'end' => $item['end'],
					'type' => 'callflow',
					'canedit' => false,
					'truedest' => $tc['truegoto'],
					'falsedest' => $tc['falsegoto'],
				);
			}
		}
		return $results;
	}
}
