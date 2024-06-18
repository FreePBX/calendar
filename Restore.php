<?php
namespace FreePBX\modules\Calendar;
use FreePBX\modules\Backup as Base;

class Restore extends Base\RestoreBase{
	public function runRestore(){
		$settings = $this->getConfigs();
		foreach ($settings as $key => $value) {
			$this->FreePBX->Calendar->setMultiConfig($value, $key);
		}
	}

	public function processLegacy($pdo, $data, $tables, $unknownTables){
		$this->restoreLegacyKvstore($pdo);
		//process the kvstore and add new line instead of \n
		$selectsql = "SELECT * from kvstore_FreePBX_modules_Calendar where id='calendar-raw'";
		$kvstorecalendar = $this->FreePBX->Database->query($selectsql)->fetchAll(\PDO::FETCH_ASSOC);
		$this->FreePBX->Database->query("DELETE from kvstore_FreePBX_modules_Calendar where id='calendar-raw'");
		foreach($kvstorecalendar as $calendar) {
			$calevents = explode('\n',(string) $calendar['val']);
			$calendar['val'] = implode("\n", $calevents);
			$query = "INSERT INTO kvstore_FreePBX_modules_Calendar (`key`, `val`, `type`, `id`) VALUES (:key, :val, :type, :id)";
			$stmt = $this->FreePBX->Database->prepare($query);
			$stmt->bindParam(':key', $calendar['key']);
			$stmt->bindParam(':val', $calendar['val']);
			$stmt->bindParam(':type', $calendar['type']);
			$stmt->bindParam(':id', $calendar['id']);
			$stmt->execute();
		}
	}

}
