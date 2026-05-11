<?php
set_time_limit(0);
require __DIR__ . '/connect.php';

$sqlFile = __DIR__ . '/database/schema_extensions.sql';
$sql = file_get_contents($sqlFile);
if ($sql === false) {
  echo "Failed to read schema file\n";
  exit(1);
}

$needle = "CREATE TABLE IF NOT EXISTS `student_payments`";
$start = strpos($sql, $needle);
if ($start === false) {
  echo "Could not find CREATE statement for student_payments\n";
  exit(1);
}

// Find the end of that CREATE TABLE statement by looking for the next ';' after ENGINE=
$enginePos = strpos($sql, "ENGINE=", $start);
if ($enginePos === false) {
  echo "Could not find ENGINE= in student_payments CREATE block\n";
  exit(1);
}
$semiPos = strpos($sql, ";", $enginePos);
if ($semiPos === false) {
  echo "Could not find end ';' for student_payments CREATE block\n";
  exit(1);
}

$createSql = trim(substr($sql, $start, ($semiPos - $start) + 1));

$ok = mysqli_query($link, "DROP TABLE IF EXISTS `student_payments`");
if ($ok === false) {
  // not fatal; continue
}

$ok = mysqli_query($link, $createSql);
if ($ok === false) {
  echo "FAIL creating student_payments: " . mysqli_error($link) . "\n";
  exit(1);
}

echo "OK created student_payments\n";
