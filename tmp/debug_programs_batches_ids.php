<?php
declare(strict_types=1);
require_once __DIR__ . '/../connect.php';

echo "PROGRAMS (first 10)\n";
$res = mysqli_query($link, "SELECT program_id, program_name FROM programs LIMIT 10");
if ($res) {
  while ($r = mysqli_fetch_assoc($res)) {
    $pid = (string)($r['program_id'] ?? '');
    echo "- program_id='". $pid ."' name='".($r['program_name'] ?? '')."'\n";
  }
}

echo "\nBATCHES (first 15)\n";
$res2 = mysqli_query($link, "SELECT batch_id, batch_name, program_id FROM batches LIMIT 15");
if ($res2) {
  while ($r = mysqli_fetch_assoc($res2)) {
    $bid = (string)($r['batch_id'] ?? '');
    $p = (string)($r['program_id'] ?? '');
    echo "- batch_id='". $bid ."' name='".($r['batch_name'] ?? '')."' program_id='". $p ."'\n";
  }
}
?>
