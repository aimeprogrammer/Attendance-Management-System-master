<?php
set_time_limit(30);
require __DIR__ . '/connect.php';

function fail(string $msg): void {
  echo "FAIL: {$msg}\n";
  exit(1);
}

echo "Reading SQL file...\n";
$sqlFile = __DIR__ . '/database/attsystem.sql';
$sql = file_get_contents($sqlFile);
if ($sql === false) fail("Could not read SQL file");

$dbName = 'attsystem';
echo "Reset DB...\n";
mysqli_query($link, "SET FOREIGN_KEY_CHECKS=0");
if (!mysqli_query($link, "DROP DATABASE IF EXISTS `{$dbName}`")) fail(mysqli_error($link));
if (!mysqli_query($link, "CREATE DATABASE `{$dbName}`")) fail(mysqli_error($link));
if (!mysqli_query($link, "USE `{$dbName}`")) fail(mysqli_error($link));
mysqli_query($link, "SET FOREIGN_KEY_CHECKS=0");

echo "Extracting programs blocks...\n";
$programsStart = strpos($sql, "CREATE TABLE `programs`");
$feesStart = strpos($sql, "CREATE TABLE `program_fees`");
if ($programsStart === false || $feesStart === false) fail("Could not find CREATE TABLE blocks");

function extractCreateBlock(string $sql, int $startPos): string {
  // Find the end of CREATE TABLE by locating the first "ENGINE=" after start, then the next ';'
  $enginePos = strpos($sql, "ENGINE=", $startPos);
  if ($enginePos === false) fail("ENGINE= not found after startPos");
  $semiPos = strpos($sql, ";", $enginePos);
  if ($semiPos === false) fail("';' not found after ENGINE=");
  return trim(substr($sql, $startPos, ($semiPos - $startPos) + 1));
}

$programsCreate = extractCreateBlock($sql, $programsStart);
$feesCreate = extractCreateBlock($sql, $feesStart);

echo "Create programs SQL size: " . strlen($programsCreate) . "\n";
echo "Create program_fees SQL size: " . strlen($feesCreate) . "\n";

echo "Creating programs...\n";
if (!mysqli_query($link, $programsCreate)) fail("programs create: " . mysqli_error($link));
echo "OK programs\n";

echo "Creating program_fees...\n";
if (!mysqli_query($link, $feesCreate)) fail("program_fees create: " . mysqli_error($link));

echo "OK program_fees\nDONE.\n";
