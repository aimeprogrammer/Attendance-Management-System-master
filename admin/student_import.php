<?php
ob_start();
session_start();

if(!isset($_SESSION['name']) || $_SESSION['name']!='oasis') {
  header('location: ../index.php');
  exit;
}

// Stub: bulk import is not implemented in this repo version.
// Redirect to existing student management page.
header('location: student_management.php');
exit;

