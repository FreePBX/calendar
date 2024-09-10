<?php

namespace FreePBX\modules\Calendar\drivers;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use FreePBX\modules\Calendar\IcalParser\IcalRangedParser;
use om\IcalParser;
use Eluceo\iCal\Component\Calendar as iCalendar;
use Eluceo\iCal\Component\Event;
use Eluceo\iCal\Property\Event\RecurrenceRule;
use Exception;

#[\AllowDynamicProperties]
abstract class Base
{
	protected $freepbx;
	protected $calendarClass;
	protected $calendar;
	protected $now;

	public function __construct($calendarClass, $calendar)
	{
		if (empty($calendar)) {
			throw new \Exception("Calendar is empty!");
		}
		$this->freepbx = $calendarClass->FreePBX;
		$this->calendarClass = $calendarClass;
		$this->setTimezone(!empty($calendar['timezone']) ? $calendar['timezone'] : $this->freepbx->View()->getTimezone());
		$this->calendar = $calendar;
	}

	/**
	 * Get Information about this driver
	 * @method getInfo
	 * @return array  array of information
	 */
	public static function getInfo()
	{
		return ["name" => _("Unknown Calendar")];
	}

	/**
	 * Get the "Add" display
	 * @method getAddDisplay
	 * @return string              HTML to display
	 */
	public static function getAddDisplay()
	{
		return '';
	}

	/**
	 * Get the "update" display
	 * @method getEditDisplay
	 * @param  array         $data Array of calendar information
	 * @return string               HTML to display
	 */
	public static function getEditDisplay($data)
	{
		return '';
	}

	/**
	 * Add new calendar of this type
	 * @method addCalendar
	 * @param  array      $data Array of data about this calendar
	 */
	public static function addCalendar($data)
	{
		$class = static::class;
		$uuid = Uuid::uuid4()->toString();
		$data['id'] = $uuid;
		$self = new $class(\FreePBX::create()->Calendar, $data);
		$self->updateCalendar($data);
		return $self;
	}

	public function setCalendar($calendar)
	{
		$self = clone $this;
		$self->calendar = $calendar;
		return $self;
	}

	/**
	 * Set Timezone
	 * This will also set or update now
	 * @method setTimezone
	 * @param  [type]      $timezone [description]
	 */
	public function setTimezone($timezone)
	{
		if (empty($timezone)) {
			return false;
		}
		$this->timezone = $timezone;
		if (isset($this->now)) {
			$this->now =  Carbon::createFromTimestamp($this->now->getTimestamp(), $this->timezone);
		} else {
			$this->setNow(time());
		}
	}

	/**
	 * Get Timezone
	 * @method getTimezone
	 * @return [type]      Timezone
	 */
	public function getTimezone()
	{
		return $this->timezone;
	}

	/**
	 * Set NOW
	 *
	 * This should only be called during debugging
	 *
	 * @method setNow
	 * @param  [type] $timestamp [description]
	 */
	public function setNow($timestamp)
	{
		if (empty($timestamp)) {
			return;
		}
		$this->now = Carbon::createFromTimestamp($timestamp, $this->timezone);
	}

	/**
	 * Get NOW
	 * @method getNow
	 * @return [type] [description]
	 */
	public function getNow()
	{
		return $this->now;
	}

	/**
	 * Update calendar by uuid
	 * @method updateCalendar
	 * @param  array         $data	Array of data about this calendar
	 * @return true
	 */
	public function updateCalendar($data)
	{
		if (empty($this->calendar['id'])) {
			throw new \Exception("Calendar ID is empty");
		}

		$this->calendarClass->setConfig($this->calendar['id'], $data, 'calendars');
		$data['id'] = $this->calendar['id'];
		$this->calendar = $data;

		//Make sure to rebuild the cache to reflect the changes.
		//This works automatically on each sync and when calendar settings changes. Works for local calendars too when adding or updating events.
		$this->buildCache();

		return true;
	}

	public function deleteiCal()
	{
		$this->calendarClass->setConfig($this->calendar['id'], false, 'calendar-raw');
		$this->calendarClass->delConfig($this->calendar['id'], 'calendar-cache');
		$this->calendarClass->delConfig($this->calendar['id'], 'calendar-cache_valid_notbefore');
		$this->calendarClass->delConfig($this->calendar['id'], 'calendar-cache_valid_notafter');
	}

	/**
	 * Save full raw calendar ical
	 * @method saveiCal
	 * @param  string   $ical The calendar ical
	 */
	public function saveiCal($ical)
	{
		$this->calendarClass->setConfig($this->calendar['id'], $ical, 'calendar-raw');

		//Make sure to rebuild the cache to reflect the changes.
		//This works automatically on each sync and when calendar settings changes. Works for local calendars too when adding or updating events.
		$this->buildCache();
	}

	//cache range
	final public const SUB_START = 'PT1H';
	final public const ADD_END = 'PT25H';

	/**
	 * Build and store the cache for the current calendar. If an old cache is there already it will be overwritten.
	 * The cache is calid for one day only, must be used for fastHandler only or the result won't be valid.
	 * @method buildCache
	 */
	public function buildCache()
	{
		$start = new \DateTime('now', new \DateTimeZone($this->timezone));
		$end = clone ($start);
		$start->sub(new \DateInterval(self::SUB_START));
		$end->add(new \DateInterval(self::ADD_END));
		$icalData = $this->getIcal();
		if ($icalData) {
			$cal = new IcalRangedParser(true);
			$cal->setStartRange($start);
			$cal->setEndRange($end);
			$raw = $cal->parseString($icalData);
			$this->calendarClass->setConfig($this->calendar['id'], serialize($raw), 'calendar-cache');
			$this->calendarClass->setConfig($this->calendar['id'], $start->getTimestamp(), 'calendar-cache_valid_notbefore');
			$this->calendarClass->setConfig($this->calendar['id'], $end->getTimestamp(), 'calendar-cache_valid_notafter');
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Retrieve the cache. Warning: the result must be used to replace $data in the IcalRangedParser/IcalParser class
	 * @method getCache
	 * @return string|null	The cache or null if not found
	 */
	public function getCache()
	{
		$raw = $this->calendarClass->getConfig($this->calendar['id'], 'calendar-cache');
		return $raw ? unserialize($raw) : null;
	}

	/**
	 * Utility method to check if the given timestamp is between the cache range.
	 * @method isInCacheRange
	 * @throws Exception	If the cache is not built already
	 */
	private function isInCacheRange(int $timestamp)
	{
		$start = $this->calendarClass->getConfig($this->calendar['id'], 'calendar-cache_valid_notbefore');
		$end = $this->calendarClass->getConfig($this->calendar['id'], 'calendar-cache_valid_notafter');

		if (!$start || !$end)
			throw new Exception('The cache is missing!');

		return ($timestamp >= $start && $timestamp <= $end);
	}

	/**
	 * Get full raw calendar ical
	 * @method getIcal
	 * @return string  The calendar ical
	 */
	public function getIcal()
	{
		return $this->calendarClass->getConfig($this->calendar['id'], 'calendar-raw');
	}

	/**
	 * Get all the Categories by Calendar ID
	 * @return array             Array of Categories with their respective events
	 */
	public function getCategories(\DateTime $start, \DateTime $end)
	{
		$raw = $this->getIcal();
		if (empty($raw)) {
			return [];
		}
		$cal = new IcalRangedParser();
		$cal->setStartRange($start);
		$cal->setEndRange($end);
		$cal->parseString($raw);
		$events = $cal->getSortedEvents();
		$categories = [];
		foreach ($events as $event) {
			if (!empty($event['CATEGORIES']) && is_array($event['CATEGORIES'])) {
				$categories = array_merge($categories, $event['CATEGORIES']);
			}
		}
		return array_unique($categories);
	}

	/**
	 * Get the Private ical mapping token
	 * @method getMappingToken
	 * @return [type]          Mapping Token
	 */
	public function getMappingToken()
	{
		$mapping = $this->calendarClass->getConfig('ical-mapping');
		return $mapping[$this->calendar['id']] ?? null;
	}

	/**
	 * [updateiCalMapping description]
	 * @method updateiCalMapping
	 * @param  [type]            $token [description]
	 * @return [type]                   [description]
	 */
	public function updateiCalMapping($token)
	{
		$mapping = $this->calendarClass->getConfig('ical-mapping');
		$mapping = !empty($mapping) ? $mapping : [];
		$mapping[$this->calendar['id']] = $token;
		$this->calendarClass->setConfig('ical-mapping', $mapping);
		return true;
	}

	/**
	 * Get all events based on NOW
	 * @method getEventsNow
	 * @return array       Array of Events
	 */
	public function getEventsNow()
	{
		$start = $this->getNow()->copy()->subWeek();
		$stop = $this->getNow()->copy()->addWeek();
		return $this->getEventsBetween($start, $stop, true, true);
	}

	/**
	 * Search for match happening now by searching inside the cache. This function must be used in agi and similar scripting class where the result must 
	 * come as fast as possible (imagine hanging the call flow only to check the calendar). When you want to display data use standard functions instead.
	 * Keep in mind that this function is fast only if the result is in cache (the time given is between cache range), else it will fallback to rebuilding the cache and wasting precious time. 
	 * @method fastHandler
	 * @return array|false	Array of events happening now. False if none found or if the cache is empty
	 * @throws Exception	If $now was not set
	 */
	public function fastHandler()
	{
		$cache = null;
  if ($this->now == null)
			throw new Exception('Now must be set before calling this method!');

		$cal = new IcalRangedParser(true);
		$now = $this->now->getTimestamp();

		try {
			if (!$this->isInCacheRange($now)) {
				//if the time is not in range we calculate a short period in a temporary cache (then discarded).
				//the side effect is that this slows down things very much like is not fast at all, but better than returning nothing.
				//anyway this may only happen if called by the Console class, calendar.agi (which is the important one) always asks for now which is obviously in range.
				$start = (new \DateTime())->setTimestamp($now);
				$end = clone ($start);
				$start->sub(new \DateInterval('PT1M'));
				$end->add(new \DateInterval('PT1H'));

				$cal = new IcalRangedParser(true);
				$cal->setStartRange($start);
				$cal->setEndRange($end);
				$cache = $cal->parseString($this->getIcal());
			} else {
				$cache = $this->getCache(); //retrieve the cache if in range
				if (!$cache) throw new Exception(); //should never happen because of isInCacheRange(). Anyway lets build the cache if this happens
			}
		} catch (Exception) {
			//cache not built yet, build it for the first time. This should happen only once, then sync will take care
			$this->processCalendar(); //we cannot rely on the return value of this call because every driver treat it differently (or return nothing at all)...

			if ($this->buildCache()) //...so we call buildCache() again to get a consistent result
				return $this->fastHandler();
			else {
				return false;
			}
		}

		$cal->data = $cache;
		$events = $cal->getEventsNow($now);

		return $events;
	}

	/**
	 * Get Events Between date range
	 * @method getEventsBetween
	 * @param  DateTime		$start				DateTime Starting Date
	 * @param  DateTime		$end				DateTime Ending Date
	 * @param  boolean		$expandRecurring	Whether to expand all recurring dates into individual events
	 * @param  boolean		$nowOnly			Only return events that are marked as NOW
	 * @return array							Array of Events
	 */
	public function getEventsBetween(\DateTime $start, \DateTime $end, $expandRecurring = true, $nowOnly = false)
	{
		$raw = $this->getIcal();
		if (empty($raw)) {
			return [];
		}
		$cal = new IcalRangedParser();
		$cal->setStartRange($start);
		$cal->setEndRange($end);
		$cal->parseString($raw);
		$events = $cal->getSortedEvents();
		$parsedEvents = [];
		$i = 0;
		//set timezone to null to use event specific timezones.
		//Not recommended though
		$calendarTimezone = new \DateTimeZone($this->timezone);
		foreach ($events as $event) {
			if (!$expandRecurring && isset($event['ORIGINAL_VEVENT'])) {
				continue;
			}
			$event['UID'] ??= $i;

			// If there is no end event, set it to the start time
			if (!isset($event['DTEND']) || !is_object($event['DTEND'])) {
				$event['DTEND'] = clone $event['DTSTART'];
			}

			if ($event['DTSTART']->getTimezone() != $event['DTEND']->getTimezone()) {
				throw new \Exception("Start timezone and end timezone are different! Not sure what to do here" . json_encode($event, JSON_THROW_ON_ERROR));
			}

			$event['DTSTART'] = Carbon::instance($event['DTSTART']);
			$event['DTEND'] = Carbon::instance($event['DTEND']);

			if (!empty($calendarTimezone) && $this->calendar['type'] != "local") {
				$event['DTSTART']->setTimezone($calendarTimezone);
				$event['DTEND']->setTimezone($calendarTimezone);
			}

			if (isset($event['ORIGINAL_VEVENT'])) {
				$event['ORIGINAL_VEVENT']['DTSTART'] = Carbon::instance($event['ORIGINAL_VEVENT']['DTSTART']);
				$event['ORIGINAL_VEVENT']['DTEND'] = Carbon::instance($event['ORIGINAL_VEVENT']['DTEND']);
				if (!empty($calendarTimezone)) {
					$event['ORIGINAL_VEVENT']['DTSTART']->setTimezone($calendarTimezone);
					$event['ORIGINAL_VEVENT']['DTEND']->setTimezone($calendarTimezone);
				}
			}

			if ($nowOnly && !$this->now->between($event['DTSTART'], $event['DTEND'])) {
				continue;
			}

			$event['RECURRENCE_INSTANCE'] ??= 0;

			$e = [
				'name' => $event['SUMMARY'] ?: "",
				'description' => $event['DESCRIPTION'] ?? '',
				'recurring' => $event['RECURRING'] ?? false,
				'rrules' => [],
				'categories' => (!empty($event['CATEGORIES']) && is_array($event['CATEGORIES'])) ? $event['CATEGORIES'] : [],
				'timezone' => null,
				'starttime' => $event['DTSTART']->format('H:i:s'),
				'endtime' => $event['DTEND']->format('H:i:s'),
				'linkedid' => $event['UID'],
				'uid' => $expandRecurring ? $event['UID'] . '_' . $event['RECURRENCE_INSTANCE'] : $event['UID'],
				'rstartdate' => isset($event['ORIGINAL_VEVENT']) ? $event['ORIGINAL_VEVENT']['DTSTART']->getTimestamp() : null,
				'renddate' => isset($event['ORIGINAL_VEVENT']) ? $event['ORIGINAL_VEVENT']['DTEND']->getTimestamp() : null,
				'ustarttime' => $event['DTSTART']->getTimestamp(),
				'uendtime' => $event['DTEND']->getTimestamp(),
				'title' => $event['SUMMARY'] ?: "",
				'startdate' => $event['DTSTART']->format('Y-m-d'),
				'enddate' => $event['DTEND']->format('Y-m-d'),
				'start' => sprintf('%sT%s', $event['DTSTART']->format('Y-m-d'), $event['DTSTART']->format('H:i:s')),
				'end' => sprintf('%sT%s', $event['DTEND']->format('Y-m-d'), $event['DTEND']->format('H:i:s')),
				'now' => $this->now->between($event['DTSTART'], $event['DTEND']),
				'allDay' => false
			];

			$tz = $event['DTSTART']->getTimezone();
			$timezone = $tz->getName();
			$e['timezone'] = ($timezone === 'Z') ? null : $timezone;

			if (!empty($event['RECURRING'])) {
				$e['rrules'] = [
					"frequency" => $event['RRULE']['FREQ'],
					"days" => !empty($event['RRULE']['BYDAY']) ? explode(",", str_replace('"', '', (string) $event['RRULE']['BYDAY'])) : [],
					"byday" => !empty($event['RRULE']['BYDAY']) ? str_replace('"', '', (string) $event['RRULE']['BYDAY']) : [],
					"interval" => !empty($event['RRULE']['INTERVAL']) ? $event['RRULE']['INTERVAL'] : "",
					"count" => !empty($event['RRULE']['COUNT']) ? $event['RRULE']['COUNT'] : "",
					"until" => !empty($event['RRULE']['UNTIL']) ? $event['RRULE']['UNTIL']->format('U') : ""
				];
			}

			//Check for all day
			if ($e['ustarttime'] === $e['enddate'] || ($e['starttime'] === $e['endtime']) && ($e['startdate'] !== $e['enddate'])) {
				$e['allDay'] = true;
			}

			//FREEPBX-17710 Google and others use the same UID when events are split.
			//Since we dont allow editing of remote events just add $i to the list
			//http://thomas.apestaart.org/log/?p=579
			if (isset($event['RECURRENCE-ID'])) {
				$parsedEvents[$e['uid'] . '_' . $event['RECURRENCE-ID']] = $e;
			} else {
				$parsedEvents[$e['uid']] = $e;
			}
			$i++;
		}
		return $parsedEvents;
	}

	/**
	 * Checks if any event in a category matches the current time. This will use fast if possible.
	 *
	 * @param string $category
	 * @return boolean          True if match, False if no match
	 */
	public function matchCategory($category)
	{
		$events = $this->fastHandler();

		if (!$events)
			return false;

		foreach ($events as $event)
			if (isset($event['CATEGORIES']) && in_array($category, $event['CATEGORIES'])) //now assured by fastHandler
				return true;

		return false;
	}

	/**
	 * Checks if any event matches the current time. This will use fast if possible.
	 * @return boolean          True if match, False if no match
	 */
	public function matchCalendar()
	{
		return !!$this->fastHandler();
	}

	/**
	 * Checks if a specific event matches the current time. This will use fast if possible.
	 * @param  string $eventID    The Event ID
	 * @return boolean          True if match, False if no match
	 */
	public function matchEvent($eventID)
	{
		$events = $this->fastHandler();

		if (!$events)
			return false;

		foreach ($events as $event)
			if ($event['UID'] === $eventID) //now assured by fastHandler. No check for UID isset
				return true;

		return false;
	}

	/**
	 * Gets the next event
	 * @return array  the Found event or empty
	 */
	public function getNextEvent()
	{
		$dates = [$this->now->copy()->endOfWeek(), $this->now->copy()->endOfMonth(), $this->now->copy()->addMonth(), $this->now->copy()->addMonths(2), $this->now->copy()->addMonths(4), $this->now->copy()->addMonths(6), $this->now->copy()->addYear(), $this->now->copy()->addYears(2), $this->now->copy()->addYears(4), $this->now->copy()->addYears(6), $this->now->copy()->addYears(10)];
		foreach ($dates as $date) {
			$events = $this->getEventsBetween($this->now, $date);
			if (!empty($events)) {
				return reset($events);
			}
		}
		return [];
	}

	/**
	 * Update Calendar By ID
	 *
	 * For remote calendars, updates the local calendar to match
	 * the remote.
	 *
	 * @param string $calendarid
	 *
	 * @return void
	 */
	public function refreshCalendar()
	{
		return $this->processCalendar();
	}

	/**
	 * Process calendar
	 * @method processCalendar
	 * @return void
	 */
	public function processCalendar()
	{
		return false;
	}
}
