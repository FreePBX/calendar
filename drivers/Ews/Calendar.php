<?php

namespace FreePBX\modules\Calendar\drivers\Ews;
use \jamesiarmes\PhpEws\Request\FindItemType;
use \jamesiarmes\PhpEws\Enumeration\ItemQueryTraversalType;
use \jamesiarmes\PhpEws\Type\ItemResponseShapeType;
use \jamesiarmes\PhpEws\Enumeration\DefaultShapeNamesType;
use \jamesiarmes\PhpEws\Type\CalendarViewType;
use \jamesiarmes\PhpEws\ArrayType\NonEmptyArrayOfBaseFolderIdsType;
use \jamesiarmes\PhpEws\Type\DistinguishedFolderIdType;
use \jamesiarmes\PhpEws\Enumeration\DistinguishedFolderIdNameType;
use \jamesiarmes\PhpEws\Client;
use \jamesiarmes\PhpEws\Request\FindFolderType;
use \jamesiarmes\PhpEws\Enumeration\ContainmentComparisonType;
use \jamesiarmes\PhpEws\Enumeration\ContainmentModeType;
use \jamesiarmes\PhpEws\Enumeration\FolderQueryTraversalType;
use \jamesiarmes\PhpEws\Enumeration\ResponseClassType;
use \jamesiarmes\PhpEws\Enumeration\UnindexedFieldURIType;
use \jamesiarmes\PhpEws\Type\ConstantValueType;
use \jamesiarmes\PhpEws\Type\FolderResponseShapeType;
use \jamesiarmes\PhpEws\Type\PathToUnindexedFieldType;
use \jamesiarmes\PhpEws\Type\RestrictionType;
use \jamesiarmes\PhpEws\Request\SyncFolderItemsType;
use \jamesiarmes\PhpEws\Type\TargetFolderIdType;
use \jamesiarmes\PhpEws\Type\EmailAddressType;
use \jamesiarmes\PhpEws\Type\FolderIdType;
use \jamesiarmes\PhpEws\Type\ContainsExpressionType;
use Carbon\Carbon;

use Eluceo\iCal\Component\Calendar as iCalendar;
use Eluceo\iCal\Component\Event;
use Eluceo\iCal\Property\Event\RecurrenceRule;

class Calendar {
	private $server;
	private $username;
	private $password;
	private $vesion;

	public function __construct($server, $username, $password, $version) {
		$this->server = $server;
		$this->username = $username;
		$this->password = $password;
		$this->version = $version;
	}

	public static function autoDiscoverSettings($email, $password) {
		// changes as per new microsoft Oauth 2.0 

		$ad = new Autodiscover($email, $password);
		$ad->discover();
		$data = $ad->discoveredSettings();
		if($data === false) {
			throw new \Exception(_("No settings were discovered"));
		}
		$server = false;
		$version = null;
		// Pick out the host from the EXPR (Exchange RPC over HTTP).
		foreach ($data['Account']['Protocol'] as $protocol) {
			if (($protocol['Type'] == 'EXCH' || $protocol['Type'] == 'EXPR') && isset($protocol['ServerVersion'])) {
				if ($version === null) {
					$sv = $ad->parseServerVersion($protocol['ServerVersion']);
					if ($sv !== false) {
						$version = $sv;
					}
				}
			}
			if ($protocol['Type'] == 'EXPR' && isset($protocol['Server'])) {
				$server = $protocol['Server'];
			}
		}
		if ($server) {
			if ($version === null) {
				// EWS class default.
				$version = Client::VERSION_2007;
			}
			return array("version" => $version, "server" => $server, "username" => (!empty($_POST['username']) ? $_POST['username'] : $_POST['email']));
		} else {
			throw new \Exception(_("Unable to determine server URL"));
		}

	}

	public function getAllCalendars() {
		$client = new Client($this->server, $this->username, $this->password, $this->version);
		$request = new FindFolderType();
		$request->FolderShape = new FolderResponseShapeType();
		$request->FolderShape->BaseShape = DefaultShapeNamesType::ALL_PROPERTIES;
		$request->ParentFolderIds = new NonEmptyArrayOfBaseFolderIdsType();
		$request->Restriction = new RestrictionType();
		$request->Traversal = FolderQueryTraversalType::DEEP;
		$parent = new DistinguishedFolderIdType();
		$parent->Id = DistinguishedFolderIdNameType::ROOT;
		$request->ParentFolderIds->DistinguishedFolderId[] = $parent;
		$response = $client->FindFolder($request);
		$calendars = array();
		foreach($response->ResponseMessages->FindFolderResponseMessage as $item) {
			if ($item->RootFolder->TotalItemsInView > 0){
				$cals = $item->RootFolder->Folders->CalendarFolder;
				$chtml = '';
				foreach($cals as $calendar) {
					$id = (string)$calendar->FolderId->Id;
					$calendars[$id] = array(
						"id" => $id,
						"name" => (string)$calendar->DisplayName
					);
				}
			}
		}
		return $calendars;
	}

	public function getCalendarByID($id) {
		$calendars = $this->getAllCalendars();
		return !empty($calendars[$id]) ? $calendars[$id] : array();
	}

	public function getAllEventsByCalendarID($folderID) {
		$client = new Client($this->server, $this->username, $this->password, $this->version);
		$client->setTimezone('');
		$request = new FindItemType();
		$request->Traversal = ItemQueryTraversalType::SHALLOW;
		$request->ItemShape = new ItemResponseShapeType();
		$request->ItemShape->BaseShape = DefaultShapeNamesType::ALL_PROPERTIES;
		$request->CalendarView = new CalendarViewType();
		$request->CalendarView->StartDate = date("c", strtotime("-1 months"));
		$request->CalendarView->EndDate = date("c", strtotime("+1 months"));
		$request->ParentFolderIds = new NonEmptyArrayOfBaseFolderIdsType();

		$request->ParentFolderIds->FolderId = new FolderIdType;
		$request->ParentFolderIds->FolderId->Id = $folderID;
		$response = $client->FindItem($request);

		$es = array();
		foreach($response->ResponseMessages->FindItemResponseMessage as $item) {
			// Loop through each item if event(s) were found in the timeframe specified
			if ($item->RootFolder->TotalItemsInView > 0){
				$events = $item->RootFolder->Items->CalendarItem;
				foreach ($events as $event){
					$id = $event->ItemId->Id;
					$uid = $event->UID;

					if($event->IsAllDayEvent) {
						$end = new \DateTime($event->End);
						$end->sub(new \DateInterval('P1D'));
					} else {
						$end = new \DateTime($event->End);
					}

					$es[$id] = array(
						"subject" => $event->Subject,
						"start" => new \DateTime($event->Start),
						"end" => $end,
						"type" => $event->CalendarItemType,
						"location" => $event->Location,
						"categories" => (isset($event->Categories->String)) ? $event->Categories->String : array(),
						"allday" => $event->IsAllDayEvent
					);
				}
			} else {
				// No items returned
			}
		}
		return $es;
	}

	public function getEventByEventID($id) {

	}

	public function formatiCal($events) {
		$vCalendar = new iCalendar('freepbx.org');
		foreach($events as $id => $event) {
			$vEvent = new Event($id);
			$vEvent->setSummary($event['subject']);
			$vEvent->setLocation($event['location']);
			$vEvent->setCategories($event['categories']);
			$vEvent->setDtStart($event['start']);
			$vEvent->setDtEnd($event['end']);
			if($event['allday']) {
				$vEvent->setUseUtc(true);
				$vEvent->setNoTime(true);
			}
			$vCalendar->addComponent($vEvent);
		}
		return $vCalendar->render();
	}

	public function formatiCalNew($events) {
		$vCalendar = new iCalendar('freepbx.org');
		foreach($events as $id => $event) {
			$vEvent = new Event($id);
			$vEvent->setSummary($event['subject']);
			$vEvent->setLocation($event['location']['displayName']);
			$vEvent->setDescription($event['bodyPreview']);
			$vEvent->setCategories($event['categories']);
			$start = new \DateTime($event['start']['dateTime']);
			$vEvent->setDtStart($start);
			$end = new \DateTime($event['end']['dateTime']);
			$vEvent->setDtEnd($end);
			if(isset($event['isallday'])) {
				$vEvent->setUseUtc(true);
				$vEvent->setNoTime(true);
			}

			if(!empty($event['recurrence'])) {
				if(isset($event['recurrence']['pattern'])) {
					if(!empty($event['recurrence']['range']['startDate']) && !empty($event['recurrence']['range']['endDate'])) {
						$recurrenceRule = new RecurrenceRule();
						switch ($event['recurrence']['pattern']['type']) {
							case 'daily':
								$recurrenceRule->setFreq(RecurrenceRule::FREQ_DAILY);
								break;
							case 'weekly':
								if(isset($event['recurrence']['pattern']['daysOfWeek']) && !empty($event['recurrence']['pattern']['daysOfWeek']) && is_array($event['recurrence']['pattern']['daysOfWeek'])) {
									$days = array();
									foreach($event['recurrence']['pattern']['daysOfWeek'] as $day) {
										switch($day) {
											case "monday":
												$days[] = 'MO';
											break;
											case "tuesday":
												$days[] = 'TU';
											break;
											case "wednesday":
												$days[] = 'WE';
											break;
											case "thursday":
												$days[] = 'TH';
											break;
											case "friday":
												$days[] = 'FR';
											break;
											case "saturday":
												$days[] = 'SA';
											break;
											case "sunday":
												$days[] = 'SU';
											break;
											default:
											break;
										}
									}
									$recurrenceRule->setByDay(implode(",",$days));
									$recurrenceRule->setFreq(RecurrenceRule::FREQ_WEEKLY);
								}
								break;
							case 'absoluteMonthly':
								$recurrenceRule->setFreq(RecurrenceRule::FREQ_MONTHLY);
								$recurrenceRule->setByMonthDay($vEvent->getDtStart()->format('j'));
								break;
							case 'relativeMonthly':
								$recurrenceRule->setFreq(RecurrenceRule::FREQ_MONTHLY);
								if(isset($event['recurrence']['pattern']['index']) && $event['recurrence']['pattern']['index']) {
									$c = 0;
									switch ($event['recurrence']['pattern']['index']) {
										case 'first':
											$c = 1;
											break;
										case 'second':
											$c = 2;
											break;
										case 'third':
											$c = 3;
											break;
										case 'fourth':
											$c = 4;
											break;
										default:
											break;
									}
									$d = strtoupper(substr($vEvent->getDtStart()->format('D'), 0, -1));
									$recurrenceRule->setByDay($c.$d);
								}
								break;
							case 'absoluteYearly':
								$recurrenceRule->setFreq(RecurrenceRule::FREQ_YEARLY);
								$recurrenceRule->setByMonth((int)$vEvent->getDtStart()->format('n'));
								$recurrenceRule->setByMonthDay($vEvent->getDtStart()->format('j'));
								break;
							case 'relativeYearly':
								$recurrenceRule->setFreq(RecurrenceRule::FREQ_YEARLY);
								$recurrenceRule->setByMonth((int)$vEvent->getDtStart()->format('n'));
								if(isset($event['recurrence']['pattern']['index']) && $event['recurrence']['pattern']['index']) {
									$c = 0;
									switch ($event['recurrence']['pattern']['index']) {
										case 'first':
											$c = 1;
											break;
										case 'second':
											$c = 2;
											break;
										case 'third':
											$c = 3;
											break;
										case 'fourth':
											$c = 4;
											break;
										default:
											break;
									}
									$d = strtoupper(substr($vEvent->getDtStart()->format('D'), 0, -1));
									$recurrenceRule->setByDay($c.$d);
								}
								break;
							default:
								break;
						}

						if(isset($event['recurrence']['pattern']['interval'])) {
							$recurrenceRule->setInterval($event['recurrence']['pattern']['interval']);
						}

						if(isset($event['recurrence']['range']['numberOfOccurrences']) && $event['recurrence']['range']['numberOfOccurrences']) {
							$recurrenceRule->setCount($event['recurrence']['range']['numberOfOccurrences']);
						}

						if(isset($event['recurrence']['range']['type']) && $event['recurrence']['range']['type'] != 'noEnd') {
							$stop_date = date('Y-m-d H:i:s', strtotime($event['recurrence']['range']['endDate'] . ' +1 day'));
							$recurrenceRule->setUntil(new Carbon($stop_date, $event['end']['timeZone']));
						}
						$vEvent->setRecurrenceRule($recurrenceRule);
					}
				}
			}
			$vCalendar->addComponent($vEvent);
		}
		return $vCalendar->render();
	}
}
