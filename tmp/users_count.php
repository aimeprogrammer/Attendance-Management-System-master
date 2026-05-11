<?php
require __DIR__ . '/../connect.php';

$r = mysqli_query($link, "SELECT COUNT(*) AS c FROM users");
if (!$r) { echo "error: " . mysqli_error($link) . PHP_EOL; exit(1); }
$row = mysqli_fetch_assoc($r);
echo "users_count=" . $row['c'] . PHP_EOL;

$r2 = mysqli_query($link, "SELECT user_type, COUNT(*) AS c FROM users GROUP BY user_type");
if (!$r2) { echo "error2: " . mysqli_error($link) . PHP_EOL; exit(1); }

while ($row2 = mysqli_fetch_assoc($r2)) {
  echo $row2['user_type'] . ":" . $row2['c'] . PHP_EOL;
}
