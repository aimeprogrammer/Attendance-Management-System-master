<?php
set_time_limit(0);
require __DIR__ . '/connect.php';

$sql = file_get_contents(__DIR__ . '/database/attsystem.sql');
if ($sql === false) { echo "Failed to read SQL\n"; exit(1); }

$tables = [
  'admininfo',
  'attendance',
  'reports',
  'students',
  'teachers',
  'courses',
  'class_sections',
  'leave_requests',
  'notifications',
  'system_logs',
  // extended ones after legacy:
  'programs',
  'program_fees',
  'batches'
];

mysqli_query($link, "SET FOREIGN_KEY_CHECKS=0");

function extractCreate(string $sql, string $table): ?string {
  $needle = "CREATE TABLE `{$table}`";
  $start = strpos($sql, $needle);
  if ($start === false) return null;
  $enginePos = strpos($sql, "ENGINE=", $start);
  if ($enginePos === false) return null;
  $semiPos = strpos($sql, ";", $enginePos);
  if ($semiPos === false) return null;
  return trim(substr($sql, $start, ($semiPos - $start) + 1));
}

foreach ($tables as $t) {
  echo "== {$t} ==\n";
  $createSql = extractCreate($sql, $t);
  if ($createSql === null) {
    echo "Missing CREATE TABLE for {$t}\n";
    continue;
  }
  if (!mysqli_query($link, "DROP TABLE IF EXISTS `{$t}`")) {
    echo "DROP failed for {$t}: " . mysqli_error($link) . "\n";
    exit(1);
  }
  $ok = mysqli_query($link, $createSql);
  if ($ok === false) {
    echo "FAIL creating {$t}: " . mysqli_error($link) . "\n";
    exit(1);
  }
  echo "OK created {$t}\n";
}

echo "Stepwise create done.\n";
