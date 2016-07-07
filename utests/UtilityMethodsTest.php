<?php
/**
* https://blogs.kent.ac.uk/webdev/2011/07/14/phpunit-and-unserialized-pdo-instances/
* @backupGlobals disabled
*/
class UtilityMethods extends PHPUnit_Framework_TestCase{
	protected static $f;
	protected static $o;
	protected static $module = 'Calendar';

	public static function setUpBeforeClass() {
		include 'setuptests.php';
		self::$f = FreePBX::create();
		self::$o = self::$f->Calendar;
	}

	public function setup() {}

	public function testPHPUnit() {
		$this->assertEquals("test", "test", "PHPUnit is broken.");
		$this->assertNotEquals("test", "nottest", "PHPUnit is broken.");
	}

	public function testCreate() {;
		$this->assertTrue(is_object(self::$o), sprintf("Did not get a %s object",self::$module));
	}
	public function testBuildRangeDays(){
		$expect = array(
			array(
				'start' => '2015-01-01T01:00',
				'end' => '2015-01-01T23:59'
			),
			array(
				'start' => '2015-01-02T01:00',
				'end' => '2015-01-02T23:59'
			)
		);
		$output = self::$o->buildRangeDays('01/01/2015', '01/02/2015', '01:00-23:59', 'mon-sat', '1-31', 'jan-feb');
		$this->assertEquals($expect, $output, _("Time range not as expected"));
	}
	public function testCheckEventfail(){

		$event = array(
			'uid' => 'fpcal_574df432d74da_3',
			'user' => '',
			'description' => 'nintynint',
			'hookdata' => '',
			'active' => '1',
			'generatehint' => '',
			'generatefc' => '',
			'eventtype' => 'callflow',
			'weekdays' => '',
			'monthdays' => '',
			'months' => '',
			'timezone' => 'America/Phoenix',
			'startdate' => '2016-06-03',
			'enddate' => '2016-06-03',
			'starttime' => '',
			'endtime' => '',
			'repeatinterval' => '',
			'frequency' => '',
			'truedest' => 'app-announcement-1,s,1',
			'falsedest' => 'from-did-direct,1000,1',
			'title' => 'nintynint',
			'start' => '2016-06-03T00:00:00',
			'end' => '2016-06-03T23:59:59'
		);
		$this->assertFalse(self::$o->checkEvent($event), "checkEvent Should be FALSE here...");
	}
	public function testCheckEventpass(){
		$date = date('Y-m-d');
		$event = array(
			'uid' => 'fpcal_574df432d74da_3',
			'user' => '',
			'description' => 'nintynint',
			'hookdata' => '',
			'active' => '1',
			'generatehint' => '',
			'generatefc' => '',
			'eventtype' => 'callflow',
			'weekdays' => '',
			'monthdays' => '',
			'months' => '',
			'timezone' => 'America/Phoenix',
			'startdate' => $date,
			'enddate' => $date,
			'starttime' => '',
			'endtime' => '',
			'repeatinterval' => '',
			'frequency' => '',
			'truedest' => 'app-announcement-1,s,1',
			'falsedest' => 'from-did-direct,1000,1',
			'title' => 'nintynint',
			'start' => $date.'T00:00:00',
			'end' => $date.'T23:59:59'
		);
		$this->assertTrue(self::$o->checkEvent($event), "checkEvent Should be True  here...");
	}
}
