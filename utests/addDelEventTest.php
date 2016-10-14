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
		$this->calid = 'ABB4AB7F-644D-48D4-A882-B5B599F7CD18';
		$this->evid = '89EB15CA-3469-4003-A4B2-919EE1D10460';
	}
	public function testPHPUnit() {
		$this->assertEquals("test", "test", "PHPUnit is broken.");
		$this->assertNotEquals("test", "nottest", "PHPUnit is broken.");
	}
	public function testCreate() {
		$this->assertTrue(is_object(self::$o), sprintf("Did not get a %s object",self::$module));
	}
	/**
 	* @covers Calendar::addEvent
 	* @covers Calendar::updateEvent
 	*/
	public function testEventAdd(){
		//addEvent($calendarID,$eventID=null,$name,$description,$starttime,$endtime,$timezone=null,$recurring=false,$rrules=array(),$categories=array())
		self::$o->addEvent($this->calid,$this->evid,"Utest","Unit Test",1476468713,1476468773,"America/Phoenix",false,array(),array());
		$event = self::$o->getEvent($this->calid,$this->evid);
		$this->assertEquals('Utest', $event['name'], "Name doesn't match");
		$this->assertEquals('Unit Test', $event['description'], "Description doesn't match");
	}

	/**
 	* @covers Calendar::deleteEvent
 	* @depends testEventAdd
 	*/
	public function testEventDelete(){
		self::$o->deleteEvent($this->calid,$this->evid);
		$this->assertFalse($event = self::$o->getEvent($this->calid,$this->evid), "Event still present");
	}
}
