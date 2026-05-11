<?php
set_time_limit(0);
require __DIR__ . '/connect.php';

$sqlFile = __DIR__ . '/database/schema_extensions.sql';
$sql = file_get_contents($sqlFile);
if ($sql === false) {
  echo "Failed to read {$sqlFile}\n";
  exit(1);
}

$needle = "CREATE TABLE IF NOT EXISTS `announcements`";
$start = strpos($sql, $needle);
if ($start === false) {
  echo "Could not find CREATE TABLE for announcements\n";
  exit(1);
}

$enginePos = strpos($sql, "ENGINE=", $start);
if ($enginePos === false) {
  echo "Could not find ENGINE= for announcements\n";
  exit(1);
}
$semiPos = strpos($sql, ";", $enginePos);
if ($semiPos === false) {
  echo "Could not find end ';' for announcements block\n";
  exit(1);
}

$createSql = trim(substr($sql, $start, ($semiPos - $start) + 1));

if (!mysqli_query($link, "DROP TABLE IF EXISTS `announcements`")) {
  echo "DROP may have failed: " . mysqli_error($link) . "\n";
}

$ok = mysqli_query($link, $createSql);
if ($ok === false) {
  echo "FAIL creating announcements: " . mysqli_error($link) . "\n";
  exit(1);
}

echo "OK created announcements\n";
