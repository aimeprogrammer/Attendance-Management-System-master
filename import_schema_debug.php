<?php
$dsn = 'connect.php';
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

$statements = preg_split('/;\\s*(\\n|\\r\\n)/', $sql);
$errors = 0;
$executed = 0;

foreach ($statements as $i => $stmt) {
  $stmt = trim($stmt);
  if ($stmt === '') continue;

  // Skip BEGIN/COMMIT/SET/GROUP comments-only statements loosely
  $upper = strtoupper($stmt);
  if (strpos($upper, 'START TRANSACTION') === 0) continue;
  if (strpos($upper, 'COMMIT') === 0) continue;
  if (strpos($upper, 'SET ') === 0) continue;
  if (strpos($upper, '/*!40101') === 0) continue;
  if (strpos($stmt, '--') === 0) continue;

  if (mysqli_query($link, $stmt) === false) {
    $errors++;
    echo "FAIL at statement #{$i}\n";
    echo "Error: " . mysqli_error($link) . "\n";
    echo "---- Statement (first 500 chars) ----\n";
    echo substr($stmt, 0, 500) . "\n";
    exit(1);
  }

  $executed++;
  if ($executed % 50 === 0) {
    echo "Executed: {$executed}\n";
  }
}

echo "DONE. Executed statements: {$executed}, errors: {$errors}\n";
