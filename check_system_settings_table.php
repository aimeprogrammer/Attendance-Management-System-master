<?php
require __DIR__ . '/connect.php';

$res = mysqli_query($link, "SELECT 1 FROM system_settings LIMIT 1");
if ($res === false) {
  echo "system_settings FAILED: " . mysqli_error($link) . PHP_EOL;
  exit(1);
}
echo "system_settings OK" . PHP_EOL;
