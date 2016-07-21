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

class Calendar extends Modules{
	protected $module = 'Calendar';

	function __construct($Modules) {
		$this->Modules = $Modules;
		$this->user = $this->UCP->User->getUser();
		$allowed = array();
		$allowed = $this->UCP->getCombinedSettingByID($this->user['id'],$this->module,'calendars');
		if(!empty($allowed)) {
			$this->allowed = is_array($allowed)?$allowed:array($allowed);
		}
	}
	function getDisplay() {
		return $this->load_view(__DIR__.'/views/calendar.php' , array('user' => $this->user, 'allowed' => $allowed));
	}
	public function getSettingsDisplay($ext) {
		$out = array(
			array(
				"title" => _('Calendar'),
				"content" => $this->load_view(__DIR__.'/views/settings.php',array('enabled' => true)),
				"size" => 6
			)
		);
		return $out;
	}
	//Left Menu
	public function getMenuItems() {
		$menu = array();
		$menu = array(
			"rawname" => "calendar",
			"name" => _("Calendar"),
			"badge" => false
		);
		return $menu;
	}
	//top bar
	public function getNavItems() {
		$out[] = array(
			"rawname" => "calendar",
			"badge" => true,
			"icon" => "fa-hand-spock-o",
			"menu" => array(
				"html" => '<li><a class="hello">'._("HELLO").'</a></li><li><hr></li><li><a class="world">'._("WORLD").'</a></li><li><hr></li>'
			)
		);
		return $out;
	}
	public function poll(){
		$count = mt_rand(1,42);
		return array("status" => true, "total" => $count);
	}
	public function getHomeWidgets() {
		$out[] = array(
			"id" => 'hello',
			"title" => 'Hello World',
			"content" => '<h3>Hello World</h3>',
			"size" => '33.33%'
		);
		return $out;
	}
	public function ajaxRequest($command,$settings){
		switch($command) {
			case 'hello':
			case 'homeRefresh':
				return true;
			default:
				return false;
			break;
		}
	}
	public function ajaxHandler(){
		switch($_REQUEST['command']){
			case 'hello':
				return array("status" => true, "alert" => "success", "message" => _('HELLO UCP'));
			break;
			case 'homeRefresh':
			//when you hit refresh on the home page widget
				return array("status" => true, "content" => '<h3>This is refreshing</h3>');
			break;
			default:
				return array("status" => false, "message" => "");
			break;
		}
	}

}
