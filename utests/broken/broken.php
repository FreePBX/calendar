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
	/**
	 * @covers Calendar::objToCron
	 */
	public function testObjToCronCarbon(){
		$foo = \Carbon\Carbon::createFromTimestamp(1346901971,'America/Phoenix');
		$this->assertEquals("26 20 5 9 * /foo/bar.baz", self::$o->objToCron($foo,'/foo/bar.baz'), "objToCron returned an unexpected string for Carbon object");
	}
	/**
	 * @covers Calendar::objToCron
	 */
	public function testObjToCronMoment(){
		$timestamp = '@1346901971';
		$foo = new \Moment\Moment($timestamp,'America/Phoenix');
		$this->assertEquals("26 20 5 9 * /foo/bar.baz", self::$o->objToCron($foo,'/foo/bar.baz'), "objToCron returned an unexpected string for Moment object");
	}
	/**
	 * @covers Calendar::objToCron
	 */
	public function testObjToCronDateTime(){
		$foo = new DateTime('NOW', new DateTimeZone('America/Phoenix'));
		$foo->setTimestamp(1346901971);
		$this->assertEquals("26 20 5 9 * /foo/bar.baz", self::$o->objToCron($foo,'/foo/bar.baz'), "objToCron returned an unexpected string for Moment object");
	}
	/**
	 * @covers Calendar::objToCron
	 */
	public function testObjToCronStringUnixtime(){
		$this->assertEquals("26 20 5 9 * /foo/bar.baz", self::$o->objToCron('1346901971','/foo/bar.baz'), "objToCron returned an unexpected string for Moment object");
	}
	/**
	 * @covers Calendar::objToCron
	 */
	public function testObjToCronStringDate(){
		$this->assertEquals("26 20 5 9 * /foo/bar.baz", self::$o->objToCron('05 Sep 2012 20:26:11','/foo/bar.baz'), "objToCron returned an unexpected string for Moment object");
	}
	/**
		* @expectedException Exception
		* @expectedExceptionMessage Calendar ID can not be empty!
		*/
	public function testProcessCalendarEmpty(){
		self::$o->processCalendar('sss');
	}
}
