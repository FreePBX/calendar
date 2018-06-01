<?php
namespace FreePBX\modules\Calendar;
use FreePBX\modules\Backup as Base;
class Backup extends Base\BackupBase{
  public function runBackup($id, $transaction){
    $kvstoreids = $this->FreePBX->Calendar->getAllids();
    $kvstoreids[] = 'noid';
    $settings = [];
    foreach ($kvstoreids as $value) {
      $settings[$value] = $this->FreePBX->Calendar->getAll($value);
    }
    $this->addConfigs($settings);
  }
}