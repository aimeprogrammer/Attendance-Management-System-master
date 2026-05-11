<?php
// Shared admin sidebar.
// Usage: set $activePage (e.g., basename($_SERVER['PHP_SELF'])) before including this file.
// If not set, we infer from request path.

if (empty($activePage)) {
  $activePage = basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH));
}
?>

<aside class="sidebar">
  <div class="sidebar-header">
    <div class="sidebar-logo">
      <i class="fas fa-cog"></i>
      <span>AMS Admin</span>
    </div>
  </div>

  <ul class="sidebar-menu">
    <li><a href="index.php" class="<?php echo $activePage === 'index.php' ? 'active' : ''; ?>"><i class="fas fa-home"></i> Dashboard</a></li>

    <li style="margin-top: 15px; padding: 0 15px; font-size: 11px; color: rgba(255,255,255,0.6); font-weight: bold;">DASHBOARDS</li>
    <li><a href="batch_attendance_analytics.php" class="<?php echo $activePage === 'batch_attendance_analytics.php' ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Batch Attendance Analytics</a></li>

    <li style="margin-top: 15px; padding: 0 15px; font-size: 11px; color: rgba(255,255,255,0.6); font-weight: bold;">STUDENTS</li>
    <li><a href="student_management.php" class="<?php echo $activePage === 'student_management.php' ? 'active' : ''; ?>"><i class="fas fa-users"></i> Manage Students</a></li>
    <li><a href="student_management.php?action=add" class="<?php echo $activePage === 'student_management.php' && isset($_GET['action']) && $_GET['action'] === 'add' ? 'active' : ''; ?>"><i class="fas fa-user-plus"></i> Add Student</a></li>
    <li><a href="student_import.php" class="<?php echo $activePage === 'student_import.php' ? 'active' : ''; ?>"><i class="fas fa-file-csv"></i> Bulk Import</a></li>

    <li style="margin-top: 15px; padding: 0 15px; font-size: 11px; color: rgba(255,255,255,0.6); font-weight: bold;">ACADEMICS</li>
    <li><a href="programs.php" class="<?php echo $activePage === 'programs.php' ? 'active' : ''; ?>"><i class="fas fa-book"></i> Programs</a></li>
    <li><a href="batches.php" class="<?php echo $activePage === 'batches.php' ? 'active' : ''; ?>"><i class="fas fa-graduation-cap"></i> Batches</a></li>
    <li><a href="subjects.php" class="<?php echo $activePage === 'subjects.php' ? 'active' : ''; ?>"><i class="fas fa-list"></i> Subjects</a></li>
    <li><a href="exams.php" class="<?php echo $activePage === 'exams.php' ? 'active' : ''; ?>"><i class="fas fa-pencil-alt"></i> Exams</a></li>
    <li><a href="results.php" class="<?php echo $activePage === 'results.php' ? 'active' : ''; ?>"><i class="fas fa-chart-bar"></i> Results</a></li>

    <li style="margin-top: 15px; padding: 0 15px; font-size: 11px; color: rgba(255,255,255,0.6); font-weight: bold;">FINANCE</li>
    <li><a href="payments.php" class="<?php echo $activePage === 'payments.php' ? 'active' : ''; ?>"><i class="fas fa-credit-card"></i> Payments</a></li>
    <li><a href="financial_report.php" class="<?php echo $activePage === 'financial_report.php' ? 'active' : ''; ?>"><i class="fas fa-wallet"></i> Finance Report</a></li>

    <li style="margin-top: 15px; padding: 0 15px; font-size: 11px; color: rgba(255,255,255,0.6); font-weight: bold;">USERS</li>
    <li><a href="users.php" class="<?php echo $activePage === 'users.php' ? 'active' : ''; ?>"><i class="fas fa-user-cog"></i> Manage Users</a></li>
    <li><a href="signup.php" class="<?php echo $activePage === 'signup.php' ? 'active' : ''; ?>"><i class="fas fa-user-shield"></i> Create User</a></li>

    <li style="margin-top: 15px; padding: 0 15px; font-size: 11px; color: rgba(255,255,255,0.6); font-weight: bold;">COMMUNICATION</li>
    <li><a href="announcements.php" class="<?php echo $activePage === 'announcements.php' ? 'active' : ''; ?>"><i class="fas fa-bullhorn"></i> Announcements</a></li>
    <li><a href="sms_gateway.php" class="<?php echo $activePage === 'sms_gateway.php' ? 'active' : ''; ?>"><i class="fas fa-envelope"></i> SMS Gateway</a></li>

    <li style="margin-top: 15px; padding: 0 15px; font-size: 11px; color: rgba(255,255,255,0.6); font-weight: bold;">SYSTEM</li>
    <li><a href="settings.php" class="<?php echo $activePage === 'settings.php' ? 'active' : ''; ?>"><i class="fas fa-cog"></i> Settings</a></li>
    <li><a href="activity_logs.php" class="<?php echo $activePage === 'activity_logs.php' ? 'active' : ''; ?>"><i class="fas fa-history"></i> Activity Logs</a></li>
    <li><a href="backup.php" class="<?php echo $activePage === 'backup.php' ? 'active' : ''; ?>"><i class="fas fa-download"></i> Backup</a></li>

    <li style="margin-top: 15px; padding: 0 15px; font-size: 11px; color: rgba(255,255,255,0.6); font-weight: bold;">REPORTS</li>
    <li><a href="reports.php" class="<?php echo $activePage === 'reports.php' ? 'active' : ''; ?>"><i class="fas fa-file-pdf"></i> Reports</a></li>

    <li><hr style="margin: 15px 0; border: none; border-top: 1px solid rgba(255,255,255,0.1);"></li>
    <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
  </ul>
</aside>