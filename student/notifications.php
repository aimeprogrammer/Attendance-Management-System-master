<?php
ob_start();
session_start();

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'student') {
  header('location: ../index.php');
  exit;
}

include('connect.php');

$stId = $_SESSION['st_id'] ?? '';
if ($stId === '') {
  header('location: dashboard.php');
  exit;
}

if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
}

// Mark all as read
if (isset($_POST['mark_all_read'])) {
  $upd = mysqli_prepare($link, "UPDATE notifications SET is_read = 1 WHERE user_id = ?");
  mysqli_stmt_bind_param($upd, "s", $stId);
  mysqli_stmt_execute($upd);
  mysqli_stmt_close($upd);
}

// Latest notifications
$notifications = [];
$sql = "SELECT notif_id, message, is_read, created_at
        FROM notifications
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 50";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_bind_param($stmt, "s", $stId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($res)) {
  $notifications[] = $row;
}
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notifications - Student Dashboard</title>
  <link rel="stylesheet" type="text/css" href="../css/main.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="dashboard-container">
  <aside class="sidebar">
    <div class="sidebar-header">
      <div class="sidebar-logo">
        <i class="fas fa-graduation-cap"></i>
        <span>Student Portal</span>
      </div>
    </div>
    <ul class="sidebar-menu">
      <li><a href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>

      <li style="margin-top: 15px; padding: 0 15px; font-size: 11px; color: rgba(255,255,255,0.6); font-weight: bold;">ACADEMICS</li>
      <li><a href="academics.php"><i class="fas fa-book-open"></i> Academic Information</a></li>
      <li><a href="exams_results.php"><i class="fas fa-sticky-note"></i> Exam & Results</a></li>
      <li><a href="attendance_report_page.php"><i class="fas fa-calendar-check"></i> Attendance</a></li>
      <li><a href="payments.php"><i class="fas fa-credit-card"></i> Payment & Fees</a></li>
      <li><a href="notices.php"><i class="fas fa-bullhorn"></i> Notices & Announcements</a></li>
      <li><a href="communications.php"><i class="fas fa-comments"></i> Communications</a></li>

      <li style="margin-top: 15px; padding: 0 15px; font-size: 11px; color: rgba(255,255,255,0.6); font-weight: bold;">MY RECORDS</li>
      <li><a href="account.php"><i class="fas fa-user-circle"></i> My Profile</a></li>
      <li><a href="report.php"><i class="fas fa-chart-bar"></i> Attendance Report</a></li>
      <li><a href="students.php"><i class="fas fa-users"></i> Class Directory</a></li>
      <li><a href="leave.php"><i class="fas fa-calendar"></i> Leave Requests</a></li>

      <li style="margin-top: 15px; padding: 0 15px; font-size: 11px; color: rgba(255,255,255,0.6); font-weight: bold;">NOTIFICATIONS</li>
      <li><a href="notifications.php" class="active"><i class="fas fa-bell"></i> Notifications</a></li>

      <li><hr style="margin: 15px 0; border: none; border-top: 1px solid rgba(255,255,255,0.1);"></li>
      <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
  </aside>

  <div class="main-content">
    <div class="top-header">
      <div>
        <h1><i class="fas fa-bell"></i> Notifications</h1>
        <p style="color: var(--muted); margin: 0;">Latest announcements and system alerts</p>
      </div>
      <div class="user-menu">
        <span>👨‍🎓 <?php echo htmlspecialchars($stId); ?></span>
        <a href="../logout.php">Logout</a>
      </div>
    </div>

    <div class="page-content">
      <div class="card">
        <div class="card-header"><i class="fas fa-clipboard-check"></i> Actions</div>
        <div class="card-body">
          <form method="post" style="display:inline-block;">
            <input type="hidden" name="mark_all_read" value="1" />
            <button type="submit" class="btn btn-default">
              Mark all as read
            </button>
          </form>
          <div style="margin-top: 12px; color: var(--muted); font-size: var(--font-sm);">
            Showing last <?php echo (int)count($notifications); ?> notifications
          </div>
        </div>
      </div>

      <div class="card" style="margin-top: 30px;">
        <div class="card-header"><i class="fas fa-inbox"></i> Notification List</div>
        <div class="card-body">
          <?php if (count($notifications) === 0): ?>
            <div class="alert alert-info">No notifications found.</div>
          <?php else: ?>
            <?php foreach ($notifications as $n): ?>
              <div style="border: 1px solid var(--border-light); padding: 14px; border-radius: var(--radius); margin-bottom: 14px;">
                <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap;">
                  <div style="font-weight: 800;">
                    <?php if ((int)$n['is_read'] === 1): ?>
                      <span class="label label-success">Read</span>
                    <?php else: ?>
                      <span class="label label-warning">Unread</span>
                    <?php endif; ?>
                  </div>
                  <div style="color: var(--muted); font-size: var(--font-sm);">
                    <?php echo htmlspecialchars((string)$n['created_at']); ?>
                  </div>
                </div>

                <div style="margin-top: 8px; white-space: pre-wrap;">
                  <?php echo nl2br(htmlspecialchars((string)$n['message'], ENT_QUOTES)); ?>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>
</div>
</body>
</html>
