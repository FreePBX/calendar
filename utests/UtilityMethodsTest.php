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

	public function testObjToCron(){
		$foo = \Carbon\Carbon::createFromTimestamp(1346901971,'America/Phoenix');
		$this->assertEquals("26 20 5 9 3 /foo/bar.baz", self::$o->objToCron($foo,'/foo/bar.baz'), "objToCron returned an unexpected string");
	}
}
