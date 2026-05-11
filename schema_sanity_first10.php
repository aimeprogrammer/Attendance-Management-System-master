<?php
set_time_limit(0);
require __DIR__ . '/connect.php';

$sqlFile = __DIR__ . '/database/attsystem.sql';
$sql = file_get_contents($sqlFile);
if ($sql === false) { echo "Failed to read SQL file\n"; exit(1); }

$tablesToCreate = [
  'admininfo',
  'attendance',
  'reports',
  'students',
  'teachers',
  'courses',
  'class_sections',
  'leave_requests',
  'notifications',
  'system_logs'
];

mysqli_query($link, "SET FOREIGN_KEY_CHECKS=0");

echo "Dropping legacy tables (if exist)...\n";
foreach ($tablesToCreate as $t) {
  $ok = mysqli_query($link, "DROP TABLE IF EXISTS `{$t}`");
  if ($ok === false) {
    echo "DROP FAIL for {$t}: " . mysqli_error($link) . "\n";
    exit(1);
  }
  echo "Dropped {$t} (or none existed)\n";
}

$created = 0;

foreach ($tablesToCreate as $t) {
  $needle = "CREATE TABLE `{$t}`";
  $start = strpos($sql, $needle);
  if ($start === false) { echo "Missing CREATE TABLE for {$t}\n"; continue; }

  $enginePos = strpos($sql, "ENGINE=", $start);
  if ($enginePos === false) { echo "ENGINE not found for {$t}\n"; exit(1); }

  $semiPos = strpos($sql, ";", $enginePos);
  if ($semiPos === false) { echo "SEMICOLON not found for {$t}\n"; exit(1); }

  $createSql = trim(substr($sql, $start, ($semiPos - $start) + 1));

  echo "Creating {$t}...\n";
  if (mysqli_query($link, $createSql) === false) {
    echo "FAIL creating {$t}: " . mysqli_error($link) . "\n";
    exit(1);
  }
  echo "OK {$t}\n";
  $created++;
}

echo "Sanity import done. Created {$created} tables.\n";
