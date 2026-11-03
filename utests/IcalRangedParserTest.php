<?php
use Carbon\Carbon;
use Carbon\CarbonInterval;
use FreePBX\modules\Calendar\IcalParser\IcalRangedParser;

include dirname(__DIR__).'/vendor/autoload.php';
/**
* https://blogs.kent.ac.uk/webdev/2011/07/14/phpunit-and-unserialized-pdo-instances/
* @backupGlobals disabled
*/
class IcalRangedParserTest extends PHPUnit_Framework_TestCase {
	public static function setUpBeforeClass() {
		include dirname(__DIR__)."/IcalParser/IcalRangedParser.php";
	}

	public function testFREEPBX18903() {
		$cal = new IcalRangedParser();

		$cal->setStartRange(Carbon::parse("2019-01-27"));

		$cal->setEndRange(Carbon::parse("2019-03-10"));

		$raw = file_get_contents(__DIR__.'/icals/FREEPBX18903.ics');
		$cal->parseString($raw);

		$events = $cal->getSortedEvents();

		$this->assertNotEmpty($events, 'No events could be found');
	}

	public function testFREEPBX17873() {
		$cal = new IcalRangedParser();

		$cal->setStartRange(Carbon::parse("2018-07-01"));

		$cal->setEndRange(Carbon::parse("2018-07-31"));

		$raw = file_get_contents(__DIR__.'/icals/FREEPBX17873.ics');
		$cal->parseString($raw);

		$events = $cal->getSortedEvents();

		$this->assertNotEmpty($events, 'No events could be found');

		$this->assertFalse(Carbon::createFromDate('2018', '07', '03')->between(Carbon::instance($events[0]['DTSTART']),Carbon::instance($events[0]['DTEND'])));
		$this->assertTrue(Carbon::createFromDate('2018', '07', '04')->between(Carbon::instance($events[0]['DTSTART']),Carbon::instance($events[0]['DTEND'])));
		$this->assertFalse(Carbon::createFromDate('2018', '07', '05')->between(Carbon::instance($events[0]['DTSTART']),Carbon::instance($events[0]['DTEND'])));
	}

	public function testFREEPBX17809() {
		$cal = new IcalRangedParser();

		$cal->setStartRange(Carbon::parse("2018-07-01"));

		$cal->setEndRange(Carbon::parse("2018-07-31"));

		$raw = file_get_contents(__DIR__.'/icals/FREEPBX17809.ics');
		$cal->parseString($raw);

		$events = $cal->getSortedEvents();

		$this->assertNotEmpty($events, 'No events could be found');
	}

	public function testFREEPBX17403() {
		$raw = file_get_contents(__DIR__.'/icals/FREEPBX17403.ics');

		$cal = new IcalRangedParser();
		$cal->setStartRange(Carbon::parse("2018-07-01"));
		$cal->setEndRange(Carbon::parse("2018-07-31"));
		$cal->parseString($raw);
		$events = $cal->getSortedEvents();
		$this->assertNotEmpty($events, 'No events could be found');
		$this->assertEquals(count($events), 2);

		$assertEvents = [
			[
				'2018', '07', '23','10','5','0'
			],
			[
				'2018', '07', '30','10','5','0'
			]
		];

		$this->assertEvents($assertEvents, $events);

		$cal = new IcalRangedParser();
		$cal->setStartRange(Carbon::parse("2018-09-01"));
		$cal->setEndRange(Carbon::parse("2018-09-31"));
		$cal->parseString($raw);
		$events = $cal->getSortedEvents();

		$this->assertNotEmpty($events, 'No events could be found');

		$assertEvents = [
			[
				'2018',
				'09',
				'3',
				'10',
				'5',
				'0'
			],
			[
				'2018',
				'09',
				'10',
				'10',
				'5',
				'0'
			],
			[
				'2018',
				'09',
				'11',
				'10',
				'5',
				'0'
			],
			[
				'2018',
				'09',
				'13',
				'14',
				'35',
				'0'
			],
			[
				'2018',
				'09',
				'17',
				'10',
				'5',
				'0'
			],
			[
				'2018',
				'09',
				'18',
				'10',
				'5',
				'0'
			],
			[
				'2018',
				'09',
				'20',
				'14',
				'35',
				'0'
			],
			[
				'2018',
				'09',
				'24',
				'10',
				'5',
				'0'
			],
			[
				'2018',
				'09',
				'25',
				'10',
				'5',
				'0'
			],
			[
				'2018',
				'09',
				'27',
				'14',
				'35',
				'0'
			],
		];

		$this->assertEvents($assertEvents, $events);

		$cal = new IcalRangedParser();
		$cal->setStartRange(Carbon::parse("2019-03-01"));
		$cal->setEndRange(Carbon::parse("2019-03-31"));
		$cal->parseString($raw);
		$events = $cal->getSortedEvents();

		$this->assertNotEmpty($events, 'No events could be found');

				$assertEvents = [
			[
				'2019',
				'03',
				'4',
				'10',
				'5',
				'0'
			],
			[
				'2019',
				'03',
				'5',
				'10',
				'5',
				'0'
			],
			[
				'2019',
				'03',
				'7',
				'14',
				'35',
				'0'
			],
			[
				'2019',
				'03',
				'11',
				'10',
				'5',
				'0'
			],
			[
				'2019',
				'03',
				'14',
				'14',
				'35',
				'0'
			],
			[
				'2019',
				'03',
				'18',
				'10',
				'5',
				'0'
			],
			[
				'2019',
				'03',
				'21',
				'14',
				'35',
				'0'
			],
			[
				'2019',
				'03',
				'25',
				'10',
				'5',
				'0'
			],
			[
				'2019',
				'03',
				'28',
				'14',
				'35',
				'0'
			],
		];

		$this->assertEvents($assertEvents, $events);
	}

	function testCountLimit() {
		$raw = file_get_contents(__DIR__.'/icals/countlimit.ics');
		$cal = new IcalRangedParser();
		$cal->setStartRange(Carbon::parse("2019-03-01"));
		$cal->setEndRange(Carbon::parse("2019-03-31"));
		$cal->parseString($raw);
		$events = $cal->getSortedEvents();

		$assertEvents = [
			[
				'2019',
				'03',
				'5',
				'12',
				'5',
				'0'
			],
		];

		$this->assertEvents($assertEvents, $events);
	}

	function testFREEPBX18919() {
		$raw = file_get_contents(__DIR__.'/icals/FREEPBX18919.ics');
		$cal = new IcalRangedParser();
		$cal->setStartRange(Carbon::parse("2019-03-03"));
		$cal->setEndRange(Carbon::parse("2019-03-05"));
		$cal->parseString($raw);
		$events = $cal->getSortedEvents();

		$assertEvents = [
			[
				'2019',
				'03',
				'4',
				'15',
				'46',
				'30'
			],
		];

		$this->assertEvents($assertEvents, $events);
	}

	/**
	 * https://github.com/FreePBX/issue-tracker/issues/538
	 * A weekly event must not lose (or move to midnight) the first occurrence after the clocks fall back
	 * (Nov 3 2024 in America/New_York).
	 */
	public function testIssue538WeeklyAfterFallBack() {
		$this->assertEquals(
			['2024-10-21 09:00', '2024-10-28 09:00', '2024-11-04 09:00', '2024-11-11 09:00', '2024-11-18 09:00'],
			$this->issue538Starts('weekly-monday', '2024-10-20', '2024-11-20')
		);
	}

	/**
	 * Same event, checked the way calendar.agi does it (fast mode cache + getEventsNow).
	 */
	public function testIssue538WeeklyAfterFallBackFastMode() {
		$this->assertTrue($this->issue538IsNow('weekly-monday', '2024-11-04 09:30'), 'Event should be active on Nov 4 2024 at 09:30');
		$this->assertFalse($this->issue538IsNow('weekly-monday', '2024-11-04 00:30'), 'Event should not be active on Nov 4 2024 at 00:30');
		$this->assertTrue($this->issue538IsNow('weekly-monday', '2024-10-28 09:30'), 'Event should be active on Oct 28 2024 at 09:30');
	}

	/**
	 * "2nd Monday" must not get an extra occurrence on a month that starts on a Monday (July 1 2024),
	 * in both the calendar view and the calendar.agi check.
	 */
	public function testNthWeekdayNoExtraOccurrence() {
		$this->assertEquals(
			['2024-06-10 10:00', '2024-07-08 10:00', '2024-08-12 10:00'],
			$this->issue538Starts('second-monday', '2024-06-01', '2024-08-31')
		);
		$this->assertFalse($this->issue538IsNow('second-monday', '2024-07-01 00:30'), 'Event should not be active on Jul 1 2024 at 00:30');
		$this->assertTrue($this->issue538IsNow('second-monday', '2024-07-08 10:30'), 'Event should be active on Jul 8 2024 at 10:30');
	}

	/**
	 * All-day "1st Monday" must keep the occurrence on a month that starts on a Monday (July 1 2024).
	 */
	public function testAllDayFirstWeekdayKeepsFirstOfMonth() {
		$this->assertEquals(
			['2024-06-03 00:00', '2024-07-01 00:00', '2024-08-05 00:00'],
			$this->issue538Starts('allday-first-monday', '2024-06-01', '2024-08-31')
		);
	}

	/**
	 * All-day "1st Monday of the year" (no BYMONTH): no extra Jan 1 occurrences, and Jan 1 is kept
	 * when it is the 1st Monday (2029). The calendar.agi check uses past dates, because fast mode only
	 * looks two months ahead of the real clock.
	 */
	public function testAllDayFirstWeekdayOfYear() {
		$this->assertEquals(
			['2028-01-03 00:00', '2029-01-01 00:00'],
			$this->issue538Starts('allday-first-monday-of-year', '2027-06-01', '2029-06-01')
		);
		$this->assertFalse($this->issue538IsNow('allday-first-monday-of-year', '2026-01-01 12:00'), 'Event should not be active on Thu Jan 1 2026');
		$this->assertTrue($this->issue538IsNow('allday-first-monday-of-year', '2026-01-05 12:00'), 'Event should be active on Mon Jan 5 2026');
	}

	/**
	 * Weekly on specific days with an interval: every 2nd week from Oct 7 2024, so the weeks of
	 * Oct 21, Nov 4 and Nov 18 only, in both the calendar view and the calendar.agi check.
	 */
	public function testWeeklyIntervalSkipsWeeks() {
		$this->assertEquals(
			['2024-10-21 09:00', '2024-10-25 09:00', '2024-11-04 09:00', '2024-11-08 09:00', '2024-11-18 09:00'],
			$this->issue538Starts('every-2nd-week-mon-fri', '2024-10-20', '2024-11-20')
		);
		$this->assertTrue($this->issue538IsNow('every-2nd-week-mon-fri', '2024-11-04 09:30'), 'Event should be active on Nov 4 2024 at 09:30');
		$this->assertFalse($this->issue538IsNow('every-2nd-week-mon-fri', '2024-10-28 09:30'), 'Event should not be active on Oct 28 2024 at 09:30');
	}

	/**
	 * Monthly on the 1st must keep going after Jan 1 (the old FREEI-1036 workaround jumped a year).
	 */
	public function testMonthlyOnThe1stContinuesAfterJan1() {
		$this->assertEquals(
			['2024-11-01 00:00', '2024-12-01 00:00', '2025-01-01 00:00', '2025-02-01 00:00', '2025-03-01 00:00'],
			$this->issue538Starts('allday-monthly-on-the-1st', '2024-11-01', '2025-03-31')
		);
		$this->assertTrue($this->issue538IsNow('allday-monthly-on-the-1st', '2025-02-01 12:00'), 'Event should be active on Feb 1 2025');
	}

	/**
	 * Yearly all-day Jan 1 must come back every year (what FREEI-1036 was for), whether the rule
	 * says Jan 1 by date or as day 1 of the year (BYYEARDAY=1, from imported calendars).
	 */
	public function testYearlyJan1KeepsRecurring() {
		foreach (['allday-yearly-jan-1', 'allday-yearday-1'] as $uid) {
			$this->assertEquals(
				['2025-01-01 00:00', '2026-01-01 00:00', '2027-01-01 00:00'],
				$this->issue538Starts($uid, '2024-06-01', '2027-06-01'),
				$uid
			);
			$this->assertTrue($this->issue538IsNow($uid, '2026-01-01 12:00'), "$uid should be active on Jan 1 2026");
		}
	}

	/**
	 * "1st Monday of the year" starting on Mon Jan 1 2024 must not also show on Wed Jan 1 2025.
	 */
	public function testFirstWeekdayOfYearNoExtraJan1() {
		$this->assertEquals(
			['2025-01-06 00:00'],
			$this->issue538Starts('allday-first-monday-of-year', '2024-06-01', '2025-06-01')
		);
	}

	private function issue538Starts($uid, $from, $to) {
		$tz = new \DateTimeZone('America/New_York');
		$cal = new IcalRangedParser();
		$cal->setStartRange(new \DateTime($from, $tz));
		$cal->setEndRange(new \DateTime($to, $tz));
		$cal->parseString(file_get_contents(__DIR__.'/icals/ISSUE538.ics'));
		$starts = [];
		foreach ($cal->getSortedEvents() as $event) {
			if ($event['UID'] === $uid) {
				$starts[] = $event['DTSTART']->format('Y-m-d H:i');
			}
		}
		return $starts;
	}

	private function issue538IsNow($uid, $when) {
		$now = new \DateTime($when, new \DateTimeZone('America/New_York'));
		$start = clone $now;
		$end = clone $now;
		$cal = new IcalRangedParser(true);
		$cal->setStartRange($start->sub(new \DateInterval('PT1H')));
		$cal->setEndRange($end->add(new \DateInterval('PT25H')));
		$cal->parseString(file_get_contents(__DIR__.'/icals/ISSUE538.ics'));
		foreach ($cal->getEventsNow($now->getTimestamp()) ?: [] as $event) {
			if ($event['UID'] === $uid) {
				return true;
			}
		}
		return false;
	}

	function assertEvents($assertEvents, $events) {
		$this->assertEquals(is_countable($events) ? count($events) : 0, is_countable($assertEvents) ? count($assertEvents) : 0);
		foreach($assertEvents as $k => $args) {
			$msg = 'Failed asserting that '.$k.'('.implode(",",$args).") is between ".Carbon::instance($events[$k]['DTSTART'])->format('c')." and ".Carbon::instance($events[$k]['DTEND'])->format('c');
			$this->assertTrue(call_user_func_array('Carbon\Carbon::create',$args)->between(Carbon::instance($events[$k]['DTSTART']),Carbon::instance($events[$k]['DTEND'])),$msg);
		}
	}
}
