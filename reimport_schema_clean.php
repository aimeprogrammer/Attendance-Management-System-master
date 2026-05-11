<?php
require __DIR__ . '/connect.php';

$sqlFile = __DIR__ . '/database/attsystem.sql';
if (!file_exists($sqlFile)) {
  echo "SQL file not found: {$sqlFile}\n";
  exit(1);
}

$sql = file_get_contents($sqlFile);
if ($sql === false) {
  echo "Failed to read SQL file\n";
  exit(1);
}

$dbName = 'attsystem';

mysqli_query($link, "SET FOREIGN_KEY_CHECKS=0");

// Drop & recreate database cleanly
$okDrop = mysqli_query($link, "DROP DATABASE IF EXISTS `{$dbName}`");
if (!$okDrop) {
  echo "DROP DATABASE failed: " . mysqli_error($link) . "\n";
  exit(1);
}

$okCreate = mysqli_query($link, "CREATE DATABASE `{$dbName}`");
if (!$okCreate) {
  echo "CREATE DATABASE failed: " . mysqli_error($link) . "\n";
  exit(1);
}

$okUse = mysqli_query($link, "USE `{$dbName}`");
if (!$okUse) {
  echo "USE failed: " . mysqli_error($link) . "\n";
  exit(1);
}

echo "Dropped & recreated `{$dbName}`. Current DB active.\n";

// Verify emptiness
$resTables = mysqli_query($link, "SHOW TABLES");
if ($resTables) {
  $count = 0;
  while (mysqli_fetch_row($resTables)) { $count++; }
  echo "Tables right after recreate: {$count}\n";
}

mysqli_query($link, "SET FOREIGN_KEY_CHECKS=0");

// Execute statements sequentially
$statements = preg_split('/;\s*(\n|\r\n)/', $sql);
$executed = 0;

foreach ($statements as $i => $stmt) {
  $stmt = trim($stmt);
  if ($stmt === '') continue;

  $upper = strtoupper($stmt);
  if (strpos($upper, 'START TRANSACTION') === 0) continue;
  if (strpos($upper, 'COMMIT') === 0) continue;
  if (strpos($upper, 'SET ') === 0) continue;

  if (mysqli_query($link, $stmt) === false) {
    echo "FAIL at statement #{$i}\n";
    echo "Error: " . mysqli_error($link) . "\n";
    echo "---- Statement (first 400 chars) ----\n";
    echo substr($stmt, 0, 400) . "\n";
    exit(1);
  }

  $executed++;
  if ($executed % 50 === 0) {
    echo "Executed: {$executed}\n";
  }
}

echo "DONE. Executed statements: {$executed}\n";
