<?php
namespace FreePBX\modules\Calendar;
use FreePBX\modules\Backup as Base;

class Restore extends Base\RestoreBase{
  public function runRestore($jobid){
    $settings = $this->getConfigs();
    foreach ($settings as $key => $value) {
      $this->FreePBX->Calendar->setMultiConfig($value, $key);
    }
  }

  public function processLegacy($pdo, $data, $tables, $unknownTables, $tmpfiledir){
    return $this->transformLegacyKV($pdo,'calendar', $this->FreePBX)
                ->transformNamespacedKV($pdo,'calendar', $this->FreePBX);
  }

}