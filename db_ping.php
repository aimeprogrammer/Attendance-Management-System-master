<?php
require __DIR__ . '/connect.php';
$res = mysqli_query($link, 'SELECT 1 AS x');
if ($res === false) {
  echo "QUERY FAIL: " . mysqli_error($link) . "\n";
  exit(1);
}
$row = mysqli_fetch_assoc($res);
echo "DB OK, x=" . ($row['x'] ?? 'null') . "\n";
