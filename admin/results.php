<?php
ob_start();
session_start();

if(!isset($_SESSION['name']) || $_SESSION['name']!='oasis') {
  header('location: ../index.php');
  exit;
}

// Stub: results UI not implemented in this repo version.
header('location: exams.php');
exit;

