<?php
require_once __DIR__ . '/../connect.php';
$res = mysqli_query($link, "SELECT COUNT(*) c FROM announcements WHERE is_active=1");
$row = mysqli_fetch_assoc($res);
$count = (string)($row['c'] ?? '0');
file_put_contents(__DIR__ . '/announcements_count.txt', $count);
echo $count;
