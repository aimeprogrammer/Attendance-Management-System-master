<?php
ob_start();
session_start();

if (!isset($_SESSION['name']) || $_SESSION['name'] != 'oasis') {
  header('location: ../index.php');
  exit;
}

$error_msg = '';
$success_msg = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Backup - Admin Dashboard</title>
  <link rel="stylesheet" type="text/css" href="../css/main.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .muted { color: var(--muted); }
    .backup-box { padding: 18px; border: 2px solid #000; border-radius: 10px; background: #fff; box-shadow: 6px 6px 0 rgba(0,0,0,0.08); }
    .btn { display:inline-flex; align-items:center; gap:8px; }
    .btn-secondary { background:#f2f2f2; }
    .btn-danger { background:#e74c3c; color:#fff; border-color:#e74c3c; }
  </style>
</head>
<body>
<div class="dashboard-container">
  <?php
    $activePage = 'backup.php';
    include('sidebar.php');
  ?>

  <div class="main-content">
    <div class="top-header">
      <div>
        <h1><i class="fas fa-download"></i> Backup</h1>
        <p style="color: var(--muted); margin: 0;">Export your data / keep snapshots</p>
      </div>
      <div class="user-menu">
        <span>👨‍💼 Admin</span>
        <a href="../logout.php">Logout</a>
      </div>
    </div>

    <div class="page-content">
      <?php if ($success_msg): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success_msg; ?></div>
      <?php endif; ?>

      <?php if ($error_msg): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?></div>
      <?php endif; ?>

      <div class="card">
        <div class="card-header"><i class="fas fa-triangle-exclamation"></i> Backup feature status</div>
        <div class="card-body">
          <div class="backup-box">
            <p class="muted" style="margin:0 0 12px;">
              The backup page is currently not wired to an export routine.
              That’s why the previous version redirected you back to the dashboard immediately.
            </p>

            <p style="margin:0 0 16px; font-weight: 800;">
              Next step to finish Backup:
            </p>

            <ul style="margin:0; padding-left: 18px;">
              <li>Implement database dump (e.g. via <code>mysqldump</code>)</li>
              <li>Provide a “Download SQL” button</li>
              <li>Optionally include uploaded files (if any) in the archive</li>
            </ul>

            <div style="margin-top: 18px; display:flex; gap:12px; flex-wrap:wrap;">
              <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
              </a>
              <button type="button" class="btn btn-danger" onclick="alert('Backup export is not implemented yet.');">
                <i class="fas fa-download"></i> Download (Not implemented)
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="card" style="margin-top: 20px;">
        <div class="card-header"><i class="fas fa-info-circle"></i> Quick note</div>
        <div class="card-body">
          <div class="muted">
            If you want, I can implement a real backup download in this project—tell me whether your server has <code>mysqldump</code> available and what backup format you prefer (SQL file only, or zipped SQL + files).
          </div>
        </div>
      </div>

    </div>
  </div>
</div>
</body>
</html>
