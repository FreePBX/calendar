<?php

namespace FreePBX\modules\Calendar\PhpEws;
use FreePBX\modules\Calendar\PhpEws\Autodiscover;
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

include __DIR__."/Autodiscover.php";

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
		$contains = new ContainsExpressionType();
		$contains->FieldURI = new PathToUnindexedFieldType();
		$contains->FieldURI->FieldURI = UnindexedFieldURIType::FOLDER_DISPLAY_NAME;
		$contains->ContainmentComparison = ContainmentComparisonType::EXACT;
		$contains->ContainmentMode = ContainmentModeType::SUBSTRING;
		$request->Restriction->Contains = $contains;
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
		$request->CalendarView->StartDate = date("c");
		$request->CalendarView->EndDate = date("c", strtotime("+1 months"));
		$request->ParentFolderIds = new NonEmptyArrayOfBaseFolderIdsType();

		$request->ParentFolderIds->FolderId = new FolderIdType;
		$request->ParentFolderIds->FolderId->Id = $folderID;
		$response = $client->FindItem($request);

		$es = array();
		foreach($response->ResponseMessages->FindItemResponseMessage as $item) {
			// Loop through each item if event(s) were found in the timeframe specified
			if ($item->RootFolder->TotalItemsInView > 0){
				//$icsdetails = "BEGIN:VCALENDAR".chr(10);
				//$icsdetails = $icsdetails."VERSION:2.0".chr(10);
				//$icsdetails = $icsdetails."PRODID:-//cybermonde.org/handcal//NONSGML v1.0//EN".chr(10);

				$events = $item->RootFolder->Items->CalendarItem;
				foreach ($events as $event){
					$id = $event->ItemId->Id;
					$uid = $event->UID;

					$es[$id] = array(
						"subject" => $event->Subject,
						"start" => new \DateTime($event->Start),
						"end" => new \DateTime($event->End),
						"type" => $event->CalendarItemType,
						"location" => $event->Location,
						"categories" => (isset($event->Categories->String)) ? $event->Categories->String : array()
					);

					/*
					// ics detail
					$icsdetails = $icsdetails."BEGIN:VEVENT".chr(10);
					// clean date
					$cleandate = array("-", ":");
					$icsdetails = $icsdetails."UID:".$id."-".$changekey.chr(10);
					$icsdetails = $icsdetails."DTSTART:".str_replace($cleandate, "", $start).chr(10);
					$icsdetails = $icsdetails."DTEND:".str_replace($cleandate, "", $end).chr(10);
					$icsdetails = $icsdetails."SUMMARY:".$subject.chr(10);
					$icsdetails = $icsdetails."LOCATION:".$location.chr(10);
					$icsdetails = $icsdetails."END:VEVENT".chr(10);
					*/
				}
				//$icsdetails = $icsdetails."END:VCALENDAR".chr(10);
			} else {
				// No items returned
			}
		}
		//echo "END";
		//
		return $es;
	}

	public function getEventByEventID($id) {

	}

	public function formatiCal($events) {
		$icsdetails = "BEGIN:VCALENDAR\n";
		$icsdetails .= "VERSION:2.0\n";
		$icsdetails .= "PRODID:-//freepbx.org//NONSGML v1.0//EN\n";
		foreach($events as $id => $event) {
			$icsdetails .= "BEGIN:VEVENT\n";
			$icsdetails .= "UID:".$id."\n";
			$icsdetails .= "DTSTART:".$event['start']->format('Ymd\THis\Z')."\n";
			$icsdetails .= "DTEND:".$event['end']->format('Ymd\THis\Z')."\n";
			$icsdetails .= "SUMMARY:".$event['subject']."\n";
			$icsdetails .= "LOCATION:".$event['location']."\n";
			$icsdetails .= "CATEGORIES:".implode(",",$event['categories'])."\n";
			$icsdetails .= "END:VEVENT\n";
		}
		$icsdetails .= "END:VCALENDAR\n";
		return $icsdetails;
	}
}
