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
		$this->eventfull = array(
			uid => '1234',
			user => '1234',
			description => 'Unit Test',
			hookdata => json_encode(array('unitTest'=>true)),
			active => true,
			generatehint => false,
			generatefc => false,
			eventtype => 'unitTest',
			weekdays => 'mon',
			monthdays => '1',
			months => '1',
			startdate => '1',
			enddate => '1',
			starttime => '1',
			endtime => '1',
			timezone => '1',
			repeatinterval => '1',
			frequency => '1',
			truedest => '1',
			falsedest => '1'
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