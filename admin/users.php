<?php
ob_start();
session_start();

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  header('location: ../index.php');
  exit;
}

if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
}

// If you later implement user CRUD, you can include DB connection here.
include('connect.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Management - Admin Dashboard</title>
  <link rel="stylesheet" type="text/css" href="../css/main.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="dashboard-container">
  <?php
    $activePage = 'users.php';
    include('sidebar.php');
  ?>

  <div class="main-content">
    <div class="top-header">
      <div>
        <h1><i class="fas fa-user-cog"></i> User Management</h1>
        <p style="color: var(--muted); margin: 0;">Manage admin users and access</p>
      </div>
      <div class="user-menu">
        <span>👨‍💼 Admin</span>
        <a href="../logout.php">Logout</a>
      </div>
    </div>

    <div class="page-content">
      <div class="card">
        <div class="card-header"><i class="fas fa-info-circle"></i> Coming Soon</div>
        <div class="card-body">
          <p style="margin:0; color: var(--muted);">
            The user management UI is not implemented in this repository version.
          </p>
          <p style="margin-top:14px;">
            Sidebar layout is now aligned with the other admin pages by using
            <code>admin/sidebar.php</code>.
          </p>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
