<?php
/**
 * This is the User Control Panel Object.
 *
 * Copyright (C) 2016 Sangoma Communications
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.	If not, see <http://www.gnu.org/licenses/>.
 *
 * @package	 FreePBX Calendar
 * @author	 James Finstrom <jfinstrom@sangoma.com>
 * @license	 AGPL v3
 */
namespace UCP\Modules;
use \UCP\Modules as Modules;
use Carbon\Carbon;
use Carbon\CarbonInterval;

class Calendar extends Modules{
	protected $module = 'Calendar';

	function __construct($Modules) {
		$this->Modules = $Modules;
		$this->user = $this->UCP->User->getUser();
		$this->allowed = array();
		$allowed = $this->UCP->getCombinedSettingByID($this->user['id'],$this->module,'allowedcals');
		if(!empty($allowed)) {
			$this->allowed = is_array($allowed)?$allowed:array($allowed);
		}
	}

	public function getSettingsDisplay($ext) {
		$out = array(
			array(
				"title" => _('Calendar'),
				"content" => $this->load_view(__DIR__.'/views/settings.php',array('enabled' => true)),
			)
		);
		return $out;
	}
	public function allowedCalendars(){
		$cals = $this->UCP->FreePBX->Calendar->listCalendars();
		foreach ($cals as $key => $value) {
			if(!in_array($key, $this->allowed))
			unset($cals[$key]);
		}
		return $cals;
	}
	//page widget
	public function getWidgetList(){
		$cals = $this->allowedCalendars();
		if(empty($cals)){
			return [];
		}
		$widgets = [];
		foreach ($cals as $key => $value) {
			$widgets['calendar-'.$key] = [
				'display' => $value['description'],
				'defaultsize' => ["width" => 4, "height" => 10],
				'minsize' => ["width" => 4, "height" => 10],
			];
		}
		$menu = [
			"rawname" => "calendar",
			"display" => _("Calendar"),
			"icon" => "fa fa-calendar",
			"list" => $widgets,
			];
		return $menu;
	}

	//side widget
	/**
	public function getSimpleWidgetList() {
		$menu = [
			"rawname" => "calendar",
			"display" => _("Calendar"),
			"icon" => "fa fa-calendar",
			"list" => [
					"agenda-8675309" => [
						"display" => "Work Calendar",
						"defaultsize" => ["width" => 12, "height" => 6],
					]
				]
			];
		return $menu;
	}
	*/
	public function getWidgetDisplay($id){
		$listmode = (strpos($id, 'agenda') !== false);

		$id = ($listmode)?substr($id, 7):substr($id, 9);
		$ret = ['title' => 'Work Calendar',
						'html'  => $this->load_view(__DIR__.'/views/calendar.php' , ['id' => $id, 'listmode' => $listmode]),
		];
		return $ret;
	}


	public function ajaxRequest($command,$settings){
		switch($command) {
			case 'events':
			case 'eventform':
				return true;
			default:
				return false;
			break;
		}
	}
	public function ajaxHandler(){
		switch($_REQUEST['command']){
			case 'events':
				if(!isset($_REQUEST['calendarid'])){
					return ['status' => false, 'message' => 'Calendar ID not specified'];
				}
				if(!in_array($_REQUEST['calendarid'], $this->allowed)){
					return ['status' => false, 'message' => 'Unauthorized'];
				}
				$start = new Carbon($_GET['start'],$_GET['timezone']);
				$end = new Carbon($_GET['end'],$_GET['timezone']);
				$events = $this->UCP->FreePBX->Calendar->listEvents($_REQUEST['calendarid'],$start, $end);
				$events = is_array($events) ? $events : array();
				return array_values($events);
			break;
			case 'eventform':
				$vars = [];
				$vars['submitted_id'] = $_REQUEST['calendarid'];
				return load_view(__DIR__.'/views/eventModal.php',$vars);
			break;
			default:
				return array("status" => false, "message" => "");
			break;
		}
	}

}
