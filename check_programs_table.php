<?php
require __DIR__ . '/connect.php';

$res = mysqli_query($link, 'SHOW TABLES LIKE "programs"');
if ($res && mysqli_num_rows($res) > 0) {
  echo "programs_exists\n";
} else {
  echo "programs_missing\n";
}
