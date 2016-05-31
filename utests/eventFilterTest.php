<?php
/**
* https://blogs.kent.ac.uk/webdev/2011/07/14/phpunit-and-unserialized-pdo-instances/
* @backupGlobals disabled
*/
class eventFilterMethods extends PHPUnit_Framework_TestCase{
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
  public function testFilterDates(){
    $data = array();
    $data[] = array('startdate' => '2016-01-01', 'enddate'=> '2016-01-31');
    $data[] = array('startdate' => '2016-01-02', 'enddate'=> '2016-01-31');
    $data[] = array('startdate' => '2016-01-03', 'enddate'=> '2016-01-31');
    $data[] = array('startdate' => '2016-02-01', 'enddate'=> '2016-02-28');
    $data[] = array('startdate' => '2016-02-02', 'enddate'=> '2016-02-28');
    $data[] = array('startdate' => '2016-02-03', 'enddate'=> '2016-02-28');
    $data[] = array('startdate' => '2016-03-01', 'enddate'=> '2016-03-31');
    $data[] = array('startdate' => '2016-03-02', 'enddate'=> '2016-03-31');
    $data[] = array('startdate' => '2016-03-03', 'enddate'=> '2016-03-31');
    $expect = array();
    $expect[] = array('startdate' => '2016-01-01', 'enddate'=> '2016-01-31');
    $expect[] = array('startdate' => '2016-01-02', 'enddate'=> '2016-01-31');
    $expect[] = array('startdate' => '2016-01-03', 'enddate'=> '2016-01-31');
    $expect[] = array('startdate' => '2016-02-01', 'enddate'=> '2016-02-28');
    $expect[] = array('startdate' => '2016-02-02', 'enddate'=> '2016-02-28');
    $expect[] = array('startdate' => '2016-02-03', 'enddate'=> '2016-02-28');
    $output = self::$o->eventFilterDates($data, '2016-01-01', '2016-02-28');
    $this->assertTrue((count(array_diff(array_merge($expect, $output), array_intersect($expect, $output))) === 0), _("Returned date range was not as expected"));
  }

  public function testFilterUser(){
    $data = array();
    $data[] = array('user' => 'foo');
    $data[] = array('user' => 'foo');
    $data[] = array('user' => 'bar');
    $data[] = array('user' => 'bar');
    $data[] = array('user' => 'baz');
    $data[] = array('user' => 'baz');
    $expect = array();
    $expect[] = array('user' => 'bar');
    $expect[] = array('user' => 'bar');
    $output = self::$o->eventFilterUser($data, 'bar');
    $this->assertTrue((count(array_diff(array_merge($expect, $output), array_intersect($expect, $output))) === 0), _("Returned user data is incorrect"));
  }

  public function testFilterEventType(){
    $data = array();
    $data[] = array('eventtype' => 'dialplan');
    $data[] = array('eventtype' => 'calendaronly');
    $data[] = array('eventtype' => 'presence');

    $expect = array();
    $expect[] = array('user' => 'dialplan');
    $output = self::$o->eventFilterType($data, 'dialplan');
    $this->assertTrue((count(array_diff(array_merge($expect, $output), array_intersect($expect, $output))) === 0), _("Returned event type data is incorrect"));
  }
}
