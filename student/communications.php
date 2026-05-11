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

/*
  NOTE:
  Your current schema extensions (schema_extensions.sql) define:
  - announcements
  - sms_messages
  But there is no explicit "chat/messages" table in the provided schema.
  
  So this page is stub-safe:
  - Shows announcements feed (via announcements table) as "communications feed"
  - Provides a "message to admin/teachers" UI stub that stores nothing (until chat table exists)
*/

$feed = [];
$feedSql = "SELECT announcement_id, title, content, announcement_type, priority, published_by, published_date
            FROM announcements
            WHERE is_active = 1
              AND (expiry_date IS NULL OR expiry_date >= CURDATE())
            ORDER BY published_date DESC
            LIMIT 30";
$feedRes = mysqli_query($link, $feedSql);
if ($feedRes) {
  while ($row = mysqli_fetch_assoc($feedRes)) $feed[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Communications - Student Dashboard</title>
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
      <li><a href="communications.php" class="active"><i class="fas fa-comments"></i> Communications</a></li>

      <li style="margin-top: 15px; padding: 0 15px; font-size: 11px; color: rgba(255,255,255,0.6); font-weight: bold;">MY RECORDS</li>
      <li><a href="account.php"><i class="fas fa-user-circle"></i> My Profile</a></li>
      <li><a href="report.php"><i class="fas fa-chart-bar"></i> Attendance Report</a></li>
      <li><a href="students.php"><i class="fas fa-users"></i> Class Directory</a></li>
      <li><a href="leave.php"><i class="fas fa-calendar"></i> Leave Requests</a></li>

      <li style="margin-top: 15px; padding: 0 15px; font-size: 11px; color: rgba(255,255,255,0.6); font-weight: bold;">NOTIFICATIONS</li>
      <li><a href="notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>

      <li><hr style="margin: 15px 0; border: none; border-top: 1px solid rgba(255,255,255,0.1);"></li>
      <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
  </aside>

  <div class="main-content">
    <div class="top-header">
      <div>
        <h1><i class="fas fa-comments"></i> Communications</h1>
        <p style="color: var(--muted); margin: 0;">Message requests + announcements feed</p>
      </div>
      <div class="user-menu">
        <span>👨‍🎓 <?php echo htmlspecialchars($stId); ?></span>
        <a href="../logout.php">Logout</a>
      </div>
    </div>

    <div class="page-content">

      <div class="card">
        <div class="card-header"><i class="fas fa-paper-plane"></i> Send a Message (Stub)</div>
        <div class="card-body">
          <div class="alert alert-warning">
            Messaging (chat) tables are not present in the current schema files you provided.
            This UI is intentionally stub-safe.
          </div>

          <form method="post" action="">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 16px;">
              <div class="form-group">
                <label><i class="fas fa-user-tie"></i> To</label>
                <select class="form-control" name="to_role" required>
                  <option value="admin">Admin</option>
                  <option value="teacher">Teacher</option>
                </select>
              </div>
              <div class="form-group">
                <label><i class="fas fa-book"></i> Subject/Topic (optional)</label>
                <input class="form-control" name="topic" placeholder="e.g., Exam timetable, Payment issue">
              </div>
            </div>
            <div class="form-group" style="margin-top: 16px;">
              <label><i class="fas fa-edit"></i> Message</label>
              <textarea class="form-control" name="message" rows="4" required placeholder="Write your message..."></textarea>
            </div>
            <div style="display:flex; gap:12px; flex-wrap:wrap; margin-top: 12px;">
              <button type="submit" class="btn btn-primary" disabled><i class="fas fa-paper-plane"></i> Send</button>
              <button type="button" class="btn btn-light" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
            </div>
          </form>
        </div>
      </div>

      <div class="card" style="margin-top: 30px;">
        <div class="card-header"><i class="fas fa-bullhorn"></i> Announcements Feed</div>
        <div class="card-body">
          <?php if (count($feed) === 0): ?>
            <div class="alert alert-info">No announcements available.</div>
          <?php else: ?>
            <?php foreach ($feed as $n): ?>
              <div style="border: 1px solid var(--border-light); padding: 16px; border-radius: var(--radius); margin-bottom: 16px;">
                <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; align-items:flex-start;">
                  <div>
                    <h3 style="margin: 0 0 6px 0; font-size: var(--font-xl);">
                      <i class="fas fa-flag"></i> <?php echo htmlspecialchars($n['title']); ?>
                    </h3>
                    <div style="color: var(--muted); font-size: var(--font-sm);">
                      Published: <?php echo htmlspecialchars($n['published_date']); ?> • By: <?php echo htmlspecialchars($n['published_by']); ?>
                    </div>
                  </div>
                </div>
                <div style="margin-top: 10px; white-space: pre-wrap;">
                  <?php echo htmlspecialchars($n['content']); ?>
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
