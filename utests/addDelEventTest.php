<?php
/**
* https://blogs.kent.ac.uk/webdev/2011/07/14/phpunit-and-unserialized-pdo-instances/
* @backupGlobals disabled
*/
class addDelEvent extends PHPUnit_Framework_TestCase{
	protected static $f;
	protected static $o;
	protected static $module = 'Calendar';
	public static function setUpBeforeClass() {
		include 'setuptests.php';
		self::$f = FreePBX::create();
		self::$o = self::$f->Calendar;
	}
	public function setup() {
		$date = date('Y-m-d');
		$this->eventfull = array(
					'uid' => '1234',
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
		$this->eventEmpty = array();
		$this->eventString = 'Foo';
	}
	public function testPHPUnit() {
		$this->assertEquals("test", "test", "PHPUnit is broken.");
		$this->assertNotEquals("test", "nottest", "PHPUnit is broken.");
	}
	public function testCreate() {
		$this->assertTrue(is_object(self::$o), sprintf("Did not get a %s object",self::$module));
	}

	public function testaddEventPass(){
		$return = self::$o->addEvent($this->eventfull);
		$this->assertArrayHasKey('status', $return, "Adding Event did not return an array with a status");
		$this->assertTrue($return['status']);
	}

	public function testAddEventEmpty(){
		$return = self::$o->addEvent($this->eventEmpty);
		$this->assertArrayHasKey('status', $return, "Adding Event did not return an array with a status");
		$this->assertFalse($return['status']);
	}
	public function testAddEventString(){
		$return = self::$o->addEvent($this->eventString);
		$this->assertArrayHasKey('status', $return, "Adding Event did not return an array with a status");
		$this->assertFalse($return['status']);
	}
	public function testDeletebyID(){
		$return = self::$o->deleteEventById('1234');
		$this->assertArrayHasKey('status', $return, "Deleting Event did not return an array with a status");
		$this->assertTrue($return['status']);
	}
}
