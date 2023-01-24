<?php
namespace FreePBX\modules\Calendar\drivers;
use FreePBX\modules\Calendar\drivers\Ews\Calendar as EWSCalendar;
use FreePBX\modules\Calendar\IcalParser\IcalRangedParser;
use Ramsey\Uuid\Uuid;
class Oauth extends Base {
	public $driver = 'Oauth';

	/**
	 * Get Information about this driver
	 * @method getInfo
	 * @return array  array of information
	 */
	public static function getInfo() {
		return array(
			"name" => _("Remote Outlook Calendar using Oauth2")
		);
	}

	/**
	 * Get the "update" display
	 * @method getEditDisplay
	 * @param  array         $data Array of calendar information
	 * @return string               HTML to display
	 */
	public static function getEditDisplay($data) {
		$message = [];
        $server = $data['url'];
        $username = $data['username'];
        $password = $data['password'];
        $version = constant('\jamesiarmes\PhpEws\Client::VERSION_2016');
        $ews = new EWSCalendar($server, $username, $password, $version);
        return load_view(dirname(__DIR__)."/views/oauth_calendar_form.php",array('action' => 'edit', 'data' => $data, 'message' => $message));
	}

	/**
	 * Get the "Add" display
	 * @method getAddDisplay
	 * @return string              HTML to display
	 */
	public static function getAddDisplay($data = array()) {
		$data['next'] = 86400;
		return load_view(dirname(__DIR__)."/views/oauth_calendar_form.php",array('action' => 'add', 'data' => $data));
	}

	/**
	 * Update calendar by uuid
	 * @method updateCalendar
	 * @param  string         $id   The uuid to update
	 * @param  array         $data Array of data about this calendar
	 * @return boolean               true or false
	 */
	public function updateCalendar($data) {
		$calendar = array(
			"name" => $data['name'],
			"description" => $data['description'],
			"type" => "oauth",
			"email" => $data['email'],
			"version" => 'VERSION_2016',
			"url" => $data['url'],
			"username" => $data['username'],
			"password" => $data['password'],
			"calendars" => !empty($data['calendars']) ? $data['calendars'] : '',
			"next" => !empty($data['next']) ? $data['next'] : 300,
			"auth_settings" => !empty($data['auth_settings']) ? $data['auth_settings'] : '',
			"timezone" => !empty($data['timezone']) ? $data['timezone'] : date_default_timezone_get(),
		);
		$ret = parent::updateCalendar($calendar);
		$this->processCalendar();
		return $ret;
	}

	public function processCalendar() {
        $calendarDetails = $this->calendarClass->getConfig($this->calendar['auth_settings'],'outlook-details');
        //token check
        if(isset($calendarDetails['token_expire_at']) && (time() > $calendarDetails['token_expire_at']) && $calendarDetails['refresh_token']) {
            $calendarDetails = $this->calendarClass->getOutlookTokenRefresh($calendarDetails);
        }
		if(isset($calendarDetails['access_token'])) {
			$eventsData = json_decode($this->getCalEvents($calendarDetails['access_token'],$this->calendar['username'],$this->calendar['timezone'],$this->calendar['calendars']),true);
			if(isset($eventsData['value'])) {
				$events = $eventsData['value'];
				$version = constant('\jamesiarmes\PhpEws\Client::VERSION_2016');
				$ews = new EWSCalendar($this->calendar['url'], $this->calendar['username'], $this->calendar['password'], $version);
				$finalical = $ews->formatiCalNew($events);
				$this->saveiCal($finalical);
			}
		} else {
			return $message = [
				'status' => false,
				'type' => 'danger',
				'message' =>  _("Something went wrong while generating token. Please regenerate the auth token by checking the respective outlook config and try again")
			];
		}
	}

	private function getCalEvents($atoken,$caluser,$timezone,$calendarId) {
		try {
			//$cpt = curl_init("https://graph.microsoft.com/v1.0/me/calendar/events");
			// $cpt = curl_init("https://graph.microsoft.com/v1.0/users/".$caluser."/events");
			$cpt = curl_init("https://graph.microsoft.com/v1.0/users/".$caluser."/calendars/".$calendarId."/events");
			curl_setopt($cpt, CURLOPT_HTTPHEADER,
					array(
						'Authorization: Bearer '.$atoken,
						'Prefer: outlook.timezone="'.$timezone.'"'
					)
				);
			curl_setopt($cpt, CURLOPT_RETURNTRANSFER, true);
			$result = curl_exec($cpt);
			return $result;
		} catch(\Exception $e) {
			$message = [
				'type' => 'danger',
				'message' => $e->getMessage()
			];
		}
	}

}
