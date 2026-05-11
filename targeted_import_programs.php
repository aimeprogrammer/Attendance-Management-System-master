<?php
require __DIR__ . '/connect.php';

$sqlFile = __DIR__ . '/database/attsystem.sql';
$sql = file_get_contents($sqlFile);
if ($sql === false) {
  echo "Failed to read SQL file\n";
  exit(1);
}

$startToken = "-- ============================================================\n-- NEW: Core academic structure";
$pos = strpos($sql, $startToken);
if ($pos === false) {
  echo "Could not find start token in SQL\n";
  exit(1);
}

$sqlPart = substr($sql, $pos);

mysqli_query($link, "SET FOREIGN_KEY_CHECKS=0");

$statements = preg_split('/;\s*(\r?\n)/', $sqlPart);
$counter = 0;

foreach ($statements as $stmt) {
  $stmt = trim($stmt);
  if ($stmt === '') {
    continue;
  }

  $upper = strtoupper($stmt);
  if (strpos($upper, 'START TRANSACTION') === 0) continue;
  if ($upper === 'COMMIT') continue;
  if (strpos($upper, 'SET ') === 0) continue;
  if (strpos($upper, '--') === 0) continue;
  if (strpos($upper, '/*') === 0) continue;

  $counter++;

  if (mysqli_query($link, $stmt) === false) {
    echo "FAIL at stmt#{$counter}\n";
    echo "Error: " . mysqli_error($link) . "\n";
    echo "SQL (first 500 chars):\n" . substr($stmt, 0, 500) . "\n";
    exit(1);
  }

  echo "OK stmt#{$counter}\n";
}

echo "Targeted import completed. Statements executed: {$counter}\n";
