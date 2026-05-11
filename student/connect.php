<?php

$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'attsystem';

// Use mysqli (mysql_* functions are removed in newer PHP versions)
$link = mysqli_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if (!$link) {
  die('Cannot connect to server: ' . mysqli_connect_error());
}

// --- Compatibility wrappers for legacy mysql_* calls ---
if (!function_exists('mysql_query')) {
  function mysql_query($query) { global $link; return mysqli_query($link, $query); }
}
if (!function_exists('mysql_num_rows')) {
  function mysql_num_rows($result) { return mysqli_num_rows($result); }
}
if (!function_exists('mysql_fetch_array')) {
  function mysql_fetch_array($result) { return mysqli_fetch_array($result); }
}
if (!function_exists('mysql_fetch_assoc')) {
  function mysql_fetch_assoc($result) { return mysqli_fetch_assoc($result); }
}

?>
