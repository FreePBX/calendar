<?php

namespace FreePBX\modules\Calendar\IcalParser;

use om\Recurrence;
use om\IcalParser;
use om\EventsList;

use Carbon\CarbonPeriod;
use Exception;

#[\AllowDynamicProperties]
class IcalRangedParser extends \om\IcalParser
{
	private $ranges = [
		'start' => null,
		'end' => null
	];

	/**
	 * @param $fast	The request to parse is for scrpting purpose. Setting this to false takes much longer because it parses every 
	 * single recurring event from the start, else only the relevant one are extracted
	 */
	public function __construct(private $fast = false)
	{
		parent::__construct();
		$this->ranges['start'] = new \DateTime('now', new \DateTimeZone('UTC'));
		$this->ranges['start']->sub(new \DateInterval('P6M'));
		$this->ranges['end'] = new \DateTime('now', new \DateTimeZone('UTC'));
		$this->ranges['end']->add(new \DateInterval('P6M'));
	}

	public function setStartRange(\DateTime $start)
	{
		$this->ranges['start'] = $start;
	}

	public function setEndRange(\DateTime $end)
	{
		$this->ranges['end'] = $end;
	}

	/**
	 * @param $event
	 * @return array
	 * @throws \Exception
	 */
	public function parseRecurrences($event): array
	{
		$recurring = new Recurrence($event['RRULE']);
		$exclusions = [];
		$additions = [];

		if (!empty($event['EXDATES'])) {
			//no need to do any fancy check here. See Frequency (or Freq) and you will find out that dates are only removed if the timestamp is EXACTLY the same.
			//So it is enough to pass the array with timestamp without checking anything fancy
			foreach ($event['EXDATES'] as $ex)
				array_push($exclusions, $ex->getTimestamp());
		}

		if (!empty($event['RDATES'])) {
			//no need to do any fancy check here. See Frequency (or Freq) and you will find out that dates are added when needed in the timeframe defined.
			//So it is enough to pass the array with timestamp without checking anything fancy
			foreach ($event['RDATES'] as $add)
				array_push($additions, $add->getTimestamp());
		}

		date_default_timezone_set(($event['DTSTART']->getTimezone()->getName() !== 'Z') ? $event['DTSTART']->getTimezone()->getName() : 'UTC');

		$until = $recurring->getUntil();

		$byKeys = [
			'BYWEEKNO',
			'BYYEARDAY',
			'BYMONTHDAY',
			'BYDAY',
			'BYHOUR',
			'BYMINUTE',
			'BYSECOND'
		];
		foreach ($byKeys as $key) {
			if (isset($recurring->rrule[$key])) {
				$recurring->rrule[$key] = str_replace('"', '', (string) $recurring->rrule[$key]);
			}
		}

		if (!isset($recurring->rrule['COUNT'])) {
			$DTSTARTTimeStamp = $event['DTSTART']->getTimestamp();

			//calc end based on the shortest term
			$end = $this->ranges['end']->getTimestamp();
			if ($until !== false) {
				$untilTm = $until->getTimestamp();
				if ($untilTm < $end) $end = $untilTm;
			}

			if ($this->fast) {
				//end is not set on the recurring rule as it is not needed and would cause troubles in the current implementation
				$frequency = new Frequency($recurring->rrule, $DTSTARTTimeStamp, $exclusions, $additions);
				$startRange = $this->ranges['start']->getTimestamp();

				$recurrences = [];
				$startRange = $frequency->previousOccurrence($startRange);
				array_push($recurrences, $startRange); //push first recurrence into the array

				//keep pushing recurrences in the period
				while (true) {
					if ($startRange > $end) break; //we are out of maximum range, exit the loop

					$next = $frequency->nextOccurrence($startRange);

					if (is_int($next)) {
						if ($next < $startRange) break; //this may be an infinite loop! Escape right now!
						array_push($recurrences, $next);
						$startRange = $next + 1; //go to the next recurrence
					} else
						break; //retrieval failed, probably we are out of period or the recurrence is invalid
				}

				return $recurrences;
			} else {
				//setting end to the one defined above and generating a new Frequency object
				$recurring->setUntil($end);
				$frequency = new Frequency($recurring->rrule, $DTSTARTTimeStamp, $exclusions, $additions);

				//get all the timestamps
				$recurrenceTimestamps = $frequency->getAllOccurrences();
			}
		} elseif (class_exists('FreePBX')) {
			//fast is not implemented here for obvious reasons

			\FreePBX::Notifications()->add_warning('calendar', 'RRULECOUNT', _('Calendar using COUNT'), _('A calendar you have added has an event that has a reoccuring rule of COUNT. When COUNT is used this slows down Calendar drastically. Please change your rule to another format'), "", true, true);

			/*
			$period = CarbonPeriod::between($event['DTSTART'],$this->ranges['end']);
			switch ($event['RRULE']['FREQ']) {
				case 'DAILY':
					$period->setDateInterval(new \DateInterval("P1D"));
					break;
				case 'WEEKLY':
					$period->setDateInterval(new \DateInterval("P1W"));
					break;
				case 'MONTHLY':
					$period->setDateInterval(new \DateInterval("P1M"));
					break;
				case 'YEARLY':
					$period->setDateInterval(new \DateInterval("P1Y"));
					break;
				default:
					// We don't know how to handle anything else.
					throw new Exception("Cannot handle rrule frequency ".$event['RRULE']['FREQ']);
			}
			if(isset($event['RRULE']['COUNT'])) {
				$period->setRecurrences($event['RRULE']['COUNT']);
			}
			$period->filter(function($date) use ($event, $exclusions) {
				if(in_array($date->getTimestamp(),$exclusions)) {
					return false;
				}
				if($date->between($this->ranges['start'],$this->ranges['end'])) {
					return true;
				}
				return false;
			},'RRULE');

			$recurrenceTimestamps = [];
			foreach ($period as $date) {
				$recurrenceTimestamps[] = $date->format('U');
			}

			$recurrenceTimestamps = array_merge($recurrenceTimestamps,$additions);
			*/

			if ($until === false) {
				//forever... limit to 15 years
				$end = clone ($event['DTSTART']);
				$end->add(new \DateInterval('P15Y')); // + 15 years
				$recurring->setUntil($end);
			}

			$frequency = new Frequency($recurring->rrule, $event['DTSTART']->getTimestamp(), $exclusions, $additions);
			$recurrenceTimestamps = $frequency->getAllOccurrences();
		}

		$recurrences = [];
		foreach ($recurrenceTimestamps as $recurrenceTimestamp) {
			$tmp = new \DateTime('now', $event['DTSTART']->getTimezone());
			$tmp->setTimestamp($recurrenceTimestamp);

			$recurrenceIDDate = $tmp->format('Ymd');
			$recurrenceIDDateTime = $tmp->format('Ymd\THis');
			if (
				empty($this->data['_RECURRENCE_IDS'][$recurrenceIDDate]) &&
				empty($this->data['_RECURRENCE_IDS'][$recurrenceIDDateTime])
			) {
				$gmtCheck = new \DateTime("now", new \DateTimeZone('UTC'));
				$gmtCheck->setTimestamp($recurrenceTimestamp);
				$recurrenceIDDateTimeZ = $gmtCheck->format('Ymd\THis\Z');
				if (empty($this->data['_RECURRENCE_IDS'][$recurrenceIDDateTimeZ])) {
					$recurrences[] = $tmp;
				}
			}
		}

		return $recurrences;
	}

	/**
	 * Returns events inside the given range in the current data. Can only be used outside of fast mode
	 * @return	array
	 * @throws	Exception		If we are in fast mode
	 */
	public function getEvents(): EventsList
	{
		//fast uses a different data structure (for recurrences) so it is mandatory to call getEventsNow to properly handle it. Warn the developer to take care of this
		if ($this->fast)
			throw new Exception('This function can only be called when not in fast mode.');

		$events = [];
		$eventsList = new EventsList();
		if (isset($this->data['VEVENT'])) {
			for ($i = 0; $i < (is_countable($this->data['VEVENT']) ? count($this->data['VEVENT']) : 0); $i++) {
				$event = $this->data['VEVENT'][$i];

				if (empty($event['RECURRENCES'])) {
					if (!empty($event['RECURRENCE-ID']) && !empty($event['UID']) && isset($event['SEQUENCE'])) {
						/**
						 * You may ask why I abandoned all this code, I will try to explain as clearly as possible:
						 * 
						 * First problem -> the recurring rule could have many formats, but as it is now only a specific one can be interpreted. This is not a big problem, we could generate a warning if we were unable to decode it. Here are some exmaples:
						 * RECURRENCE-ID;RANGE=THISANDFUTURE:19960120T120000Z -> Becomes 19960120T120000Z (not matched becasue of the final "Z" + RANGE ignored)
						 * RECURRENCE-ID;VALUE=DATE:19960401 -> Becomes 19960401 (not matched beacuse it is only a date)
						 * RECURRENCE-ID;RANGE=THISANDFUTURE:TZID=UTC:20230531T120000 -> Becomes TZID=UTC:20230531T120000 (not matched because of the extra TZID)
						 * RECURRENCE-ID;TZID=UTC:20230531T120000 -> Becomes 20230531T120000 (matched)
						 * RECURRENCE-ID;VALUE=DATE-TIME:20210115T100000 -> Becomes 20230531T120000 (matched)
						 * 
						 * Second problem -> If we were able to overcome the first point and we get a match with the exact same DTSTART and an increased sequence (look at the first "if" below) the event is replaced entirely. Fine you'll say but there is a catch, recurrences are calculated on the original event and not updated causing all sort of problems (beside that, why don't you update the original event directly instead of using this quirks?)
						 * 
						 * Third problem -> If we instead fall into the second "if" we will search for recurrences to replace inside the original event. Perfect! But there is a catch! You see as soon as we find a recurrence that match we delete it and... nothing more! So the user is expecting this particular recurrence to be replaced by the new event but instead will find that recurrence completely deleted!
						 * 
						 * So all in all fixing all this is issues is too complicated, if someone wants to do it free to do so, but be careful of the speed too...
						 */

						/*
							$modifiedEventUID = $event['UID'];
							$modifiedEventRecurID = $event['RECURRENCE-ID'];
							$modifiedEventSeq = intval($event['SEQUENCE'], 10);

							if (isset($this->data["_RECURRENCE_COUNTERS_BY_UID"][$modifiedEventUID])) {
								$counter = $this->data["_RECURRENCE_COUNTERS_BY_UID"][$modifiedEventUID];

								$originalEvent = $this->data["VEVENT"][$counter];
								if (isset($originalEvent['SEQUENCE'])) {
									$originalEventSeq = intval($originalEvent['SEQUENCE'], 10);
									$originalEventFormattedStartDate = $originalEvent['DTSTART']->format('Ymd\THis');
									if ($modifiedEventRecurID === $originalEventFormattedStartDate && $modifiedEventSeq > $originalEventSeq) {
										// this modifies the original event
										$modifiedEvent = array_replace_recursive($originalEvent, $event);
										$this->data["VEVENT"][$counter] = $modifiedEvent;
										foreach ($events as $z => $event) {
											if ($events[$z]['UID'] === $originalEvent['UID'] &&
												$events[$z]['SEQUENCE'] === $originalEvent['SEQUENCE']) {
												// replace the original event with the modified event
												$events[$z] = $modifiedEvent;
												break;
											}
										}
										$event = null; // don't add this to the $events[] array again
									} else if (!empty($originalEvent['RECURRENCES'])) {
										for ($j = 0; $j < count($originalEvent['RECURRENCES']); $j++) {
											$recurDate = $originalEvent['RECURRENCES'][$j];
											$formattedStartDate = $recurDate->format('Ymd\THis');
											if ($formattedStartDate === $modifiedEventRecurID) {
												unset($this->data["VEVENT"][$counter]['RECURRENCES'][$j]);
												$this->data["VEVENT"][$counter]['RECURRENCES'] = array_values($this->data["VEVENT"][$counter]['RECURRENCES']);
												break;
											}
										}
									}
								}
							}
							*/

						\FreePBX::Notifications()->add_warning('calendar', 'RECURRENCEID', _('Calendar using RECURRENCE-ID'), str_replace('%event', $event['SUMMARY'], _('A calendar you have added has an event called "%event" that has a RECURRENCE-ID rule. Because there is no full support yet they are ignored, please consider changing this to Exceptions/Additions.')), "", true, true);
						$event = null; //this is an event that modifies another one. In every case (even if we weren't able to overwrite the original one for whatever reason) it should not end up in the output
					} else {
						//neither start nor end is within range so skip it
						if(isset($event['DTSTART']) && isset($event['DTEND'])) {
							if (!$this->eventRangeInCalendarRange($event['DTSTART'], $event['DTEND'])) {
								$event = null;
							}
						} else {
							$event = null;
						}
					}

					if (!empty($event)) {
						$eventsList[] = $event;
					}
				} else {
					$recurrences = $event['RECURRENCES'];
					$event['RECURRING'] = true;
					$event['DTEND'] = !empty($event['DTEND']) ? $event['DTEND'] : $event['DTSTART'];
					$eventInterval = $event['DTSTART']->diff($event['DTEND']);

					//TODO: at some point make the first event ALWAYS the earliest event as thats our starter
					//$event['RECURRENCE_INSTANCE'] = 0;
					//$events[] = $event;

					$firstEvent = true;
					foreach ($recurrences as $j => $recurDate) {
						$newEvent = $event;
						if (!$firstEvent) {
							$newEvent['ORIGINAL_VEVENT'] = [
								'DTSTART' => $newEvent['DTSTART'],
								'DTEND' => $newEvent['DTEND']
							];
							unset($newEvent['RECURRENCES']);
							$newEvent['DTSTART'] = $recurDate;
							$newEvent['DTEND'] = clone ($recurDate);
							$newEvent['DTEND']->add($eventInterval);
						}

						if ($this->eventRangeInCalendarRange($newEvent['DTSTART'], $newEvent['DTEND'])) {
							$newEvent['RECURRENCE_INSTANCE'] = $j;
							$eventsList[]=$newEvent;
						}

						$firstEvent = false;
					}
				}
			}
		}
		return $eventsList;
	}


	/** Returns events matching the provided $now inside the current data. Can only be used in fast mode
	 * @param	int	$now		the now timestamp
	 * @return	array|false
	 * @throws	Exception		If we are not in fast mode
	 */
	public function getEventsNow($now = 0)
	{
		if (!$this->fast)
			throw new Exception('This function can only be called with fast bit set.');

		$events = [];
		if ($now == 0)
			$now = time();

		if (isset($this->data['VEVENT'])) {
			for ($i = 0; $i < (is_countable($this->data['VEVENT']) ? count($this->data['VEVENT']) : 0); $i++) {
				$event = $this->data['VEVENT'][$i];

				if (empty($event['RECURRENCES'])) {
					if (!empty($event['RECURRENCE-ID']) && !empty($event['UID']) && isset($event['SEQUENCE'])) {
						\FreePBX::Notifications()->add_warning('calendar', 'RECURRENCEID', _('Calendar using RECURRENCE-ID'), str_replace('%event', $event['SUMMARY'], _('A calendar you have added has an event called "%event" that has a RECURRENCE-ID rule. Because there is no full support yet they are ignored, please consider changing this to Exceptions/Additions.')), "", true, true);
					} else {
						if ($event['DTSTART']->getTimestamp() < $now && $now < $event['DTEND']->getTimestamp())
							array_push($events, $event); //event is now, keep it
					}
				} else {
					$recurrences = $event['RECURRENCES'];
					$event['RECURRING'] = true;
					$event['RECURRENCE_INSTANCE'] = 0; //TODO RECURRENCE_INSTANCE cannot be calculated in an easy and fast way here. But do we really need it? (see also Calendar.class.php)
					$eventDuration = $event['DTEND']->getTimestamp() - $event['DTSTART']->getTimestamp();

					foreach ($recurrences as $recurrenceTimestamp) {
						if ($now > $recurrenceTimestamp && $now < ($recurrenceTimestamp + $eventDuration)) {
							array_push($events, $event); //at least one recurrence is now, keep it
							continue 2; //go to the next event
						}
					}
				}
			}
		}

		return $events ?: false;
	}

	public function eventDateInCalendarRange(\DateTime $timestamp)
	{
		return ($timestamp->getTimestamp() > $this->ranges['start']->getTimestamp() && $timestamp->getTimestamp() < $this->ranges['end']->getTimestamp());
	}

	public function eventRangeInCalendarRange(\DateTime $eventStart, \DateTime $eventEnd)
	{
		$event = CarbonPeriod::between($eventStart, $eventEnd);
		foreach ($event as $date) {
			if ($date->between($this->ranges['start'], $this->ranges['end'])) {
				return true;
			}
		}
		return false;
	}
}
