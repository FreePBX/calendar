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
#[\AllowDynamicProperties]
class Calendar {
	private $vesion;

	public function __construct() {
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
			$start = new \DateTime($event['start']['dateTime'],new \DateTimeZone($event['start']['timeZone']));
			$vEvent->setDtStart($start);
			$end = new \DateTime($event['end']['dateTime'],new \DateTimeZone($event['end']['timeZone']));
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
									$days = [];
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
								$recurrenceRule->setByMonthDay((int) $vEvent->getDtStart()->format('j'));
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
									$d = strtoupper(substr((string) $vEvent->getDtStart()->format('D'), 0, -1));
									$recurrenceRule->setByDay($c.$d);
								}
								break;
							case 'absoluteYearly':
								$recurrenceRule->setFreq(RecurrenceRule::FREQ_YEARLY);
								$recurrenceRule->setByMonth((int)$vEvent->getDtStart()->format('n'));
								$recurrenceRule->setByMonthDay((int) $vEvent->getDtStart()->format('j'));
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
									$d = strtoupper(substr((string) $vEvent->getDtStart()->format('D'), 0, -1));
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
