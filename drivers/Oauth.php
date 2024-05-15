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
		return ["name" => _("Remote Outlook Calendar using Oauth2")];
	}

	/**
	 * Get the "update" display
	 * @method getEditDisplay
	 * @param  array         $data Array of calendar information
	 * @return string               HTML to display
	 */
	public static function getEditDisplay($data) {
		$message = [];
        $ews = new EWSCalendar();
        return load_view(dirname(__DIR__)."/views/oauth_calendar_form.php",['action' => 'edit', 'data' => $data, 'message' => $message]);
	}

	/**
	 * Get the "Add" display
	 * @method getAddDisplay
	 * @return string              HTML to display
	 */
	public static function getAddDisplay($data = []) {
		$data['next'] = 86400;
		return load_view(dirname(__DIR__)."/views/oauth_calendar_form.php",['action' => 'add', 'data' => $data]);
	}

	/**
	 * Update calendar by uuid
	 * @method updateCalendar
	 * @param  string         $id   The uuid to update
	 * @param  array         $data Array of data about this calendar
	 * @return boolean               true or false
	 */
	public function updateCalendar($data) {
		$calendar = ["name" => $data['name'], "description" => $data['description'], "type" => "oauth", "email" => $data['email'], "version" => 'VERSION_2016', "url" => $data['url'] ??'', "username" => $data['username'], "password" => $data['password'] ?? '', "calendars" => !empty($data['calendars']) ? $data['calendars'] : '', "next" => !empty($data['next']) ? $data['next'] : 300, "auth_settings" => !empty($data['auth_settings']) ? $data['auth_settings'] : '', "timezone" => !empty($data['timezone']) ? $data['timezone'] : date_default_timezone_get()];
		$ret = parent::updateCalendar($calendar);
		$this->processCalendar();
		return $ret;
	}

	public function processCalendar($start = null, $end = null) {
        $calendarDetails = $this->calendarClass->getConfig($this->calendar['auth_settings'],'outlook-details');
        //token check
        if(isset($calendarDetails['token_expire_at']) && (time() > $calendarDetails['token_expire_at']) && $calendarDetails['refresh_token']) {
            $calendarDetails = $this->calendarClass->getOutlookTokenRefresh($calendarDetails);
        }
		if(isset($calendarDetails['access_token'])) {
			$eventsData = $this->getCalEvents($calendarDetails['access_token'],$this->calendar['username'],$this->calendar['timezone'],$this->calendar['calendars'],$start,$end);
			if(isset($eventsData['value'])) {
				$events = $eventsData['value'];
				if(isset($eventsData['@odata.nextLink']) && !empty($eventsData['@odata.nextLink'])) {
					$nextLink = $eventsData['@odata.nextLink'];
					$loadnext = true;
					while($loadnext){
						$nextevents = $nextEvents = $this->getCalEventsNextPage($calendarDetails['access_token'],$this->calendar['timezone'],$nextLink);
						if(isset($nextevents['value'])) {
							$events = array_merge($events,$nextevents['value']);
						}

						if(isset($nextevents['@odata.nextLink']) && !empty($nextevents['@odata.nextLink'])) {
							$nextLink = $nextevents['@odata.nextLink'];
						} else {
							$loadnext = false;
						}
					}
				}
				$ews = new EWSCalendar();
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

	private function getCalEvents($atoken,$caluser,$timezone,$calendarId,$start,$end) {
		try {
			if($start && $end) {
				$startstrpos = strpos((string) $start, '+');
				if ($startstrpos !== false) {
					$start = substr((string) $start, 0, $startstrpos);
				}

				$endstrpos = strpos((string) $end, '+');
				if ($endstrpos !== false) {
					$end = substr((string) $end, 0, $endstrpos);
				}
				$url = "https://graph.microsoft.com/v1.0/users/".$caluser."/calendars/".$calendarId."/calendarView?startDateTime=".$start."&endDateTime=".$end;
			} else {
				$url = "https://graph.microsoft.com/v1.0/users/".$caluser."/calendars/".$calendarId."/events";
			}
			$cpt = curl_init($url);
			curl_setopt($cpt, CURLOPT_HTTPHEADER,
					['Authorization: Bearer '.$atoken, 'Prefer: outlook.timezone="'.$timezone.'"']
				);
			curl_setopt($cpt, CURLOPT_RETURNTRANSFER, true);
			$result = curl_exec($cpt);
			return json_decode($result,true, 512, JSON_THROW_ON_ERROR);
		} catch(\Exception $e) {
			$message = [
				'type' => 'danger',
				'message' => $e->getMessage()
			];
		}
	}

	private function getCalEventsNextPage($atoken,$timezone,$nextLink) {
		try {
			$cpt = curl_init($nextLink);
			curl_setopt($cpt, CURLOPT_HTTPHEADER,
					['Authorization: Bearer '.$atoken, 'Prefer: outlook.timezone="'.$timezone.'"']
				);
			curl_setopt($cpt, CURLOPT_RETURNTRANSFER, true);
			$result = curl_exec($cpt);
			return json_decode($result,true, 512, JSON_THROW_ON_ERROR);
		} catch(\Exception $e) {
			$message = [
				'type' => 'danger',
				'message' => $e->getMessage()
			];
		}
	}
}
