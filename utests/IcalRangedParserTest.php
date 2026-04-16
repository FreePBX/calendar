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
	 * Test RECURRENCE-ID exception handling:
	 *   - Jan 26 occurrence moved to 10:00-18:00 (should appear at new time)
	 *   - Feb 2 occurrence cancelled (should not appear)
	 *   - Other Mondays should appear at the original 09:00-17:00
	 */
	function testRecurrenceIdExceptions() {
		$raw = file_get_contents(__DIR__.'/icals/recurrence-id-exceptions.ics');
		$cal = new IcalRangedParser();
		$cal->setStartRange(Carbon::parse("2026-01-12"));
		$cal->setEndRange(Carbon::parse("2026-02-09"));
		$cal->parseString($raw);
		$events = $cal->getSortedEvents();

		$this->assertNotEmpty($events, 'No events could be found');

		// Collect event summaries and start dates for easier assertion
		$parsed = [];
		foreach ($events as $event) {
			$parsed[] = [
				'date' => Carbon::instance($event['DTSTART'])->format('Y-m-d'),
				'time' => Carbon::instance($event['DTSTART'])->format('H:i'),
				'summary' => $event['SUMMARY'],
				'cancelled' => (strtoupper($event['STATUS'] ?? '') === 'CANCELLED'),
			];
		}

		// Build a lookup by date
		$byDate = [];
		foreach ($parsed as $p) {
			$byDate[$p['date']] = $p;
		}

		// Jan 12 — original occurrence, should exist at 09:00
		$this->assertArrayHasKey('2026-01-12', $byDate, 'Jan 12 occurrence missing');
		$this->assertEquals('09:00', $byDate['2026-01-12']['time']);

		// Jan 19 — original occurrence, should exist at 09:00
		$this->assertArrayHasKey('2026-01-19', $byDate, 'Jan 19 occurrence missing');
		$this->assertEquals('09:00', $byDate['2026-01-19']['time']);

		// Jan 26 — moved to 10:00, should appear at 10:00 with modified summary
		$this->assertArrayHasKey('2026-01-26', $byDate, 'Jan 26 modified occurrence missing');
		$this->assertEquals('10:00', $byDate['2026-01-26']['time']);
		$this->assertStringContainsString('moved to 10am', $byDate['2026-01-26']['summary']);

		// Feb 2 — cancelled, should NOT appear
		$this->assertArrayNotHasKey('2026-02-02', $byDate, 'Feb 2 cancelled occurrence should not appear');

		// Feb 9 — original occurrence, should exist at 09:00
		$this->assertArrayHasKey('2026-02-09', $byDate, 'Feb 9 occurrence missing');
		$this->assertEquals('09:00', $byDate['2026-02-09']['time']);
	}

	/**
	 * Test RECURRENCE-ID with all-day events (VALUE=DATE format).
	 */
	function testRecurrenceIdAllDay() {
		$raw = file_get_contents(__DIR__.'/icals/recurrence-id-allday.ics');
		$cal = new IcalRangedParser();
		$cal->setStartRange(Carbon::parse("2026-01-05"));
		$cal->setEndRange(Carbon::parse("2026-02-02"));
		$cal->parseString($raw);
		$events = $cal->getSortedEvents();

		$this->assertNotEmpty($events, 'No events could be found');

		$dates = [];
		$summaries = [];
		foreach ($events as $event) {
			$d = Carbon::instance($event['DTSTART'])->format('Y-m-d');
			$dates[] = $d;
			$summaries[$d] = $event['SUMMARY'];
		}

		// Jan 5 — original
		$this->assertContains('2026-01-05', $dates, 'Jan 5 occurrence missing');

		// Jan 12 — original
		$this->assertContains('2026-01-12', $dates, 'Jan 12 occurrence missing');

		// Jan 19 — modified title (should appear)
		$this->assertContains('2026-01-19', $dates, 'Jan 19 modified occurrence missing');
		$this->assertStringContainsString('modified title', $summaries['2026-01-19']);

		// Jan 26 — cancelled (should NOT appear)
		$this->assertNotContains('2026-01-26', $dates, 'Jan 26 cancelled occurrence should not appear');
	}

	/**
	 * Test RECURRENCE-ID handling in fast mode (getEventsNow).
	 * Verifies that:
	 *   - A modified occurrence is matched at its new time
	 *   - A cancelled occurrence is not matched
	 *   - Original (non-excepted) occurrences still match at original time
	 */
	function testRecurrenceIdFastMode() {
		$raw = file_get_contents(__DIR__.'/icals/recurrence-id-exceptions.ics');

		// Check that Jan 26 at 14:00 EST matches (moved occurrence: 10:00-18:00)
		$cal = new IcalRangedParser(true);
		$jan26_2pm = Carbon::parse("2026-01-26 14:00:00", "America/New_York");
		$cal->setStartRange($jan26_2pm->copy()->subDay());
		$cal->setEndRange($jan26_2pm->copy()->addDay());
		$cal->parseString($raw);
		$events = $cal->getEventsNow($jan26_2pm->getTimestamp());
		$this->assertNotEmpty($events, 'Jan 26 2pm should match the moved occurrence');

		// Check that Jan 26 at 08:30 EST does NOT match (old time, occurrence was moved)
		$cal2 = new IcalRangedParser(true);
		$jan26_830 = Carbon::parse("2026-01-26 08:30:00", "America/New_York");
		$cal2->setStartRange($jan26_830->copy()->subDay());
		$cal2->setEndRange($jan26_830->copy()->addDay());
		$cal2->parseString($raw);
		$events2 = $cal2->getEventsNow($jan26_830->getTimestamp());
		$this->assertFalse($events2, 'Jan 26 8:30am should NOT match (occurrence was moved to 10am)');

		// Check that Feb 2 at 12:00 EST does NOT match (cancelled)
		$cal3 = new IcalRangedParser(true);
		$feb2_noon = Carbon::parse("2026-02-02 12:00:00", "America/New_York");
		$cal3->setStartRange($feb2_noon->copy()->subDay());
		$cal3->setEndRange($feb2_noon->copy()->addDay());
		$cal3->parseString($raw);
		$events3 = $cal3->getEventsNow($feb2_noon->getTimestamp());
		$this->assertFalse($events3, 'Feb 2 noon should NOT match (occurrence was cancelled)');

		// Check that Jan 19 at 12:00 EST still matches (unmodified occurrence)
		$cal4 = new IcalRangedParser(true);
		$jan19_noon = Carbon::parse("2026-01-19 12:00:00", "America/New_York");
		$cal4->setStartRange($jan19_noon->copy()->subDay());
		$cal4->setEndRange($jan19_noon->copy()->addDay());
		$cal4->parseString($raw);
		$events4 = $cal4->getEventsNow($jan19_noon->getTimestamp());
		$this->assertNotEmpty($events4, 'Jan 19 noon should match (unmodified occurrence)');
	}

	function assertEvents($assertEvents, $events) {
		$this->assertEquals(is_countable($events) ? count($events) : 0, is_countable($assertEvents) ? count($assertEvents) : 0);
		foreach($assertEvents as $k => $args) {
			$msg = 'Failed asserting that '.$k.'('.implode(",",$args).") is between ".Carbon::instance($events[$k]['DTSTART'])->format('c')." and ".Carbon::instance($events[$k]['DTEND'])->format('c');
			$this->assertTrue(call_user_func_array('Carbon\Carbon::create',$args)->between(Carbon::instance($events[$k]['DTSTART']),Carbon::instance($events[$k]['DTEND'])),$msg);
		}
	}
}
