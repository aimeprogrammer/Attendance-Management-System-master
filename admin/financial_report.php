<?php
ob_start();
session_start();

if(!isset($_SESSION['name']) || $_SESSION['name']!='oasis') {
  header('location: ../index.php');
  exit;
}

// Stub: finance report UI not implemented.
header('location: payments.php');
exit;

