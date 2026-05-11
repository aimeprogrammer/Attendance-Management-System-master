<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$current = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$role = $_SESSION['role'] ?? ($_SESSION['name'] ?? '');
$teacherName = $_SESSION['teacher_name'] ?? ($_SESSION['name'] ?? 'Teacher');

function sidebar_link($file, $label, $icon = ''): string {
  $active = ($file === func_get_arg(0)); // placeholder, overwritten below
  return '';
}

// Active class mapping by current page
$active = function(string $file) use ($current): string {
  return $current === $file ? 'active' : '';
};
?>
<aside class="sidebar">
  <div class="sidebar-header">
    <div class="sidebar-logo">
      <i class="fas fa-chalkboard-teacher"></i>
      <span>Teacher Portal</span>
    </div>
  </div>

  <ul class="sidebar-menu">
    <li><a href="index.php" class="<?php echo $active('index.php'); ?>"><i class="fas fa-home"></i> Dashboard</a></li>

    <li style="margin-top: 15px; padding: 0 15px; font-size: 11px; color: rgba(255,255,255,0.6); font-weight: bold;">CLASSES</li>
    <li><a href="attendance.php" class="<?php echo $active('attendance.php'); ?>"><i class="fas fa-check-square"></i> Mark Attendance</a></li>
    <li><a href="report.php" class="<?php echo $active('report.php'); ?>"><i class="fas fa-chart-bar"></i> Reports</a></li>
    <li><a href="students.php" class="<?php echo $active('students.php'); ?>"><i class="fas fa-users"></i> Students</a></li>

    <li style="margin-top: 15px; padding: 0 15px; font-size: 11px; color: rgba(255,255,255,0.6); font-weight: bold;">MANAGEMENT</li>
    <li><a href="teachers.php" class="<?php echo $active('teachers.php'); ?>"><i class="fas fa-user-tie"></i> Teachers</a></li>
    <li><a href="leave_requests.php" class="<?php echo $active('leave_requests.php'); ?>"><i class="fas fa-clipboard-list"></i> Leave Requests</a></li>
    <li><a href="exams.php" class="<?php echo $active('exams.php'); ?>"><i class="fas fa-book"></i> Exams</a></li>
    <li><a href="reports.php" class="<?php echo $active('reports.php'); ?>"><i class="fas fa-file-pdf"></i> Attendance Analytics</a></li>

    <li><hr style="margin: 15px 0; border: none; border-top: 1px solid rgba(255,255,255,0.1);"></li>
    <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
  </ul>
</aside>
