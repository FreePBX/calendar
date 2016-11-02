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
		include dirname(dirname(__DIR__)).'/framework/amp_conf/htdocs/admin/libraries/Composer/vendor/autoload.php';

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
	 * @covers Calendar::eventFilterDates
	 */
  public function testFilterDates(){
    $data = array();
    $data[] = array('starttime' => date_timestamp_get(new DateTime('2016-01-01')), 'endtime'=> date_timestamp_get(new DateTime('2016-01-31')),'timezone' => 'America/Phoenix');
    $data[] = array('starttime' => date_timestamp_get(new DateTime('2016-01-02')), 'endtime'=> date_timestamp_get(new DateTime('2016-01-31')),'timezone' => 'America/Phoenix');
    $data[] = array('starttime' => date_timestamp_get(new DateTime('2016-01-03')), 'endtime'=> date_timestamp_get(new DateTime('2016-01-31')),'timezone' => 'America/Phoenix');
    $data[] = array('starttime' => date_timestamp_get(new DateTime('2016-02-01')), 'endtime'=> date_timestamp_get(new DateTime('2016-02-28')),'timezone' => 'America/Phoenix');
    $data[] = array('starttime' => date_timestamp_get(new DateTime('2016-02-02')), 'endtime'=> date_timestamp_get(new DateTime('2016-02-28')),'timezone' => 'America/Phoenix');
    $data[] = array('starttime' => date_timestamp_get(new DateTime('2016-02-03')), 'endtime'=> date_timestamp_get(new DateTime('2016-02-28')),'timezone' => 'America/Phoenix');
    $data[] = array('starttime' => date_timestamp_get(new DateTime('2016-03-01')), 'endtime'=> date_timestamp_get(new DateTime('2016-03-31')),'timezone' => 'America/Phoenix');
    $data[] = array('starttime' => date_timestamp_get(new DateTime('2016-03-02')), 'endtime'=> date_timestamp_get(new DateTime('2016-03-31')),'timezone' => 'America/Phoenix');
    $data[] = array('starttime' => date_timestamp_get(new DateTime('2016-03-03')), 'endtime'=> date_timestamp_get(new DateTime('2016-03-31')),'timezone' => 'America/Phoenix');
    $expect = array();
    $expect[] = array('starttime' => date_timestamp_get(new DateTime('2016-01-01')), 'endtime'=> date_timestamp_get(new DateTime('2016-01-31')),'timezone' => 'America/Phoenix');
    $expect[] = array('starttime' => date_timestamp_get(new DateTime('2016-01-02')), 'endtime'=> date_timestamp_get(new DateTime('2016-01-31')),'timezone' => 'America/Phoenix');
    $expect[] = array('starttime' => date_timestamp_get(new DateTime('2016-01-03')), 'endtime'=> date_timestamp_get(new DateTime('2016-01-31')),'timezone' => 'America/Phoenix');
    $expect[] = array('starttime' => date_timestamp_get(new DateTime('2016-02-01')), 'endtime'=> date_timestamp_get(new DateTime('2016-02-28')),'timezone' => 'America/Phoenix');
    $expect[] = array('starttime' => date_timestamp_get(new DateTime('2016-02-02')), 'endtime'=> date_timestamp_get(new DateTime('2016-02-28')),'timezone' => 'America/Phoenix');
    $expect[] = array('starttime' => date_timestamp_get(new DateTime('2016-02-03')), 'endtime'=> date_timestamp_get(new DateTime('2016-02-28')),'timezone' => 'America/Phoenix');
		$start = Carbon\Carbon::createFromDate(2016,01,01,'America/Phoenix');
		$end = Carbon\Carbon::createFromDate(2016,02,28,'America/Phoenix');
    $output = self::$o->eventFilterDates($data, $start, $end, 'America/Phoenix');
    $this->assertTrue((count(array_diff(array_merge($expect, $output), array_intersect($expect, $output))) === 0), _("Returned date range was not as expected"));
  }
}
