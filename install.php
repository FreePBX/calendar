<?php
$dbh = \FreePBX::Database();
$tableSQL = 'CREATE TABLE IF NOT EXISTS calendar_events (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  uid INT UNSIGNED,
  description TEXT,
  hookdata TEXT,
  active TINYINT,
  generatehint TINYINT,
  generatefc TINYINT,
  updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  eventtype TINYTEXT,
  weekdays TINYTEXT,
  monthdays TINYTEXT,
  months TINYTEXT,
  startdate INT,
  enddate INT,
  starttime INT,
  endtime INT,
  timezone TINYTEXT,
  repeatinterval TINYTEXT,
  frequency TINYTEXT,
  truedest TINYTEXT,
  falsedest TINYTEXT
) ENGINE=InnoDB';
out(_("Adding Table for calendar events"));
$stmt = $dbh->prepare($tableSQL);
try {
    $stmt->execute();
    out(_("done"));
} catch (\PDOException $e) {
    out($e->getMessage());
}
