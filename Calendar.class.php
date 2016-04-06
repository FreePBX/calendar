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
      case 'tpl':
			case 'modal':
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
        $return[]= array(
          'id' => 23,
          'title' => 'TEST EVENT',
          'class' => 'event-important',
          'start' => '2016-04-06T16:02:36-07:00',
          'end' => '2016-04-07T16:12:36+00:00',
          'mydata' => 'foobar',
					'color' => 'blue',
					'textColor' => 'white'
        );
				$return = $return + $this->parseTimegroups($_REQUEST['start'],$_REQUEST['end']);

        return $return;
      break;
    }
  }
	public function ajaxCustomHandler() {
		switch($_REQUEST['command']) {
			case "tpl":
				$file = basename($_REQUEST['file']);
				show_view(__DIR__.'/views/tmpls/'.$file);
				return true;
			break;
			case "modal":
				$return = array(
					'id' => 23,
					'title' => 'TEST EVENT',
					'url' => '',
					'class' => 'event-important',
					'start' => 1459795958277,
					'stop' => 1459795959277,
					'mydata' => 'foobar',
					'url' => 'ajax.php?module=calendar&command=modal&id=23'
				);
				echo "<pre>".print_r($return)."</pre>";
				return true;
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
	public function listEvents($start = false, $stop = false){
		$sqlstart = $start?date('Y-m-d',floor($start/1000)):'';
		$sqlend = $stop?date('Y-m-d',floor($stop/1000)):'';
		$params = array();
		$sql = 'SELECT * FROM calendar_events ';
		if($start && $stop){
			$sql .= 'WHERE startdate >= ? AND enddate <= ?';
			$params = array($sqlstart,$sqlend);
		}elseif($start){
			$sql .= 'WHERE startdate >= ?';
			$params = array($sqlstart);
		}elseif($stop){
			$sql .= 'WHERE enddate <= ?';
			$params = array($sqlend);
		}

		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);
		$ret = $stmt->fetch(\PDO::FETCH_ASSOC);
		return $ret;
	}
	public function addEvent(){}
	public function enableEvent(){}
	public function disableEvent(){}
	public function deleteEvent(){}
	public function updateEvent(){}
	public function getEventTypes(){
		return array(
			'calendaronly',
			'presence',
			'callflow',
			'hook'
		);
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
			dbug(array($sday,$eday));
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
					if(!in_array(date('j',$istart)+1,$domArr)){
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
	public function parseTimegroups($start,$end){
		$sql = 'SELECT timegroups_groups.description, timegroups_details.time, timegroups_details.id FROM timegroups_groups INNER JOIN timegroups_details WHERE timegroups_groups.id=timegroups_details.timegroupid';
		$stmt = $this->db->prepare($sql);
		$stmt->execute();
		$ret = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		$results = array();
		foreach ($ret as $tc) {
			list($hour, $dow, $dom, $month) = explode("|", $tc['time']);
			$info = $this->buildRangeDays($start, $end, $hour, $dow, $dom, $month);
			foreach ($info as $item) {
				$results[] = array(
					'id' => $tc['id'],
					'title' => $tc['description'],
					'start' => $item['start'],
					'end' => $item['end'],
				);
			}
		}
		return $results;
	}
}
