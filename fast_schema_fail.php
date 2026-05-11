<?php
require __DIR__ . '/connect.php';

$sqlFile = __DIR__ . '/database/attsystem.sql';
$sql = file_get_contents($sqlFile);
if ($sql === false) { echo "Failed to read SQL file\n"; exit(1); }

$dbName = 'attsystem';

mysqli_query($link, "SET FOREIGN_KEY_CHECKS=0");
mysqli_query($link, "DROP DATABASE IF EXISTS `{$dbName}`");
mysqli_query($link, "CREATE DATABASE `{$dbName}`");
mysqli_query($link, "USE `{$dbName}`");
mysqli_query($link, "SET FOREIGN_KEY_CHECKS=0");

$statements = preg_split('/;\s*(\r?\n)/', $sql);
$counter = 0;

foreach ($statements as $stmt) {
  $stmt = trim($stmt);
  if ($stmt === '') continue;

  $upper = strtoupper($stmt);
  // skip non-executable / header-ish statements
  if (strpos($upper, 'START TRANSACTION') === 0) continue;
  if ($upper === 'COMMIT') continue;
  if (strpos($upper, 'SET ') === 0) continue;
  if (strpos($upper, '--') === 0) continue;
  if (strpos($upper, '/*') === 0) continue;
  if (strpos($upper, 'CREATE DATABASE') === 0) continue;
  if (strpos($upper, 'DROP DATABASE') === 0) continue;
  if (strpos($upper, 'USE ') === 0) continue;
  if (strpos($upper, '/*!') === 0) continue;
  if (strpos($upper, 'DATABASE:') !== false) continue;

  $counter++;

  if (mysqli_query($link, $stmt) === false) {
    echo "FAIL at stmt#{$counter}\n";
    echo "Error: " . mysqli_error($link) . "\n";
    echo "SQL: " . substr($stmt, 0, 500) . "\n";
    exit(1);
  }
}

echo "DONE. Executed statements: {$counter}\n";
