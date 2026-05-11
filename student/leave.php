<?php
ob_start();
session_start();

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'student') {
  header('location: ../index.php');
  exit;
}

if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
}

include('connect.php');

$success_msg = "";
$error_msg = "";

$stIdForQueries = $_SESSION['st_id'] ?? '';

if (isset($_POST['submit_leave'])) {
  try {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
      throw new Exception("Security validation failed (CSRF).");
    }

    $leaveStId = isset($_POST['st_id']) ? trim((string)$_POST['st_id']) : '';
    $leaveDate = isset($_POST['leave_date']) ? trim((string)$_POST['leave_date']) : '';
    $reason = isset($_POST['reason']) ? trim((string)$_POST['reason']) : '';

    if ($leaveStId === '') throw new Exception("Registration ID is required.");
    if ($leaveDate === '') throw new Exception("Date is required.");
    if ($reason === '') throw new Exception("Reason is required.");

    $stmt = mysqli_prepare(
      $link,
      "INSERT INTO leave_requests (st_id, leave_date, reason, status) VALUES (?, ?, ?, 'pending')"
    );
    if (!$stmt) {
      throw new Exception("DB prepare failed: " . mysqli_error($link));
    }
    mysqli_stmt_bind_param($stmt, "sss", $leaveStId, $leaveDate, $reason);

    if (!mysqli_stmt_execute($stmt)) {
      mysqli_stmt_close($stmt);
      throw new Exception("Failed to submit request. Please verify your Registration ID.");
    }

    mysqli_stmt_close($stmt);
    $success_msg = "Leave request submitted successfully!";
  } catch (Exception $e) {
    $error_msg = $e->getMessage();
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Leave Requests - Student Dashboard</title>
  <link rel="stylesheet" type="text/css" href="../css/main.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="dashboard-container">
  <!-- Unified Sidebar -->
  <aside class="sidebar">
    <div class="sidebar-header">
      <div class="sidebar-logo">
        <i class="fas fa-graduation-cap"></i>
        <span>Student Portal</span>
      </div>
    </div>
    <ul class="sidebar-menu">
      <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>

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
      <li><a href="leave.php" class="active"><i class="fas fa-calendar"></i> Leave Requests</a></li>

      <li style="margin-top: 15px; padding: 0 15px; font-size: 11px; color: rgba(255,255,255,0.6); font-weight: bold;">NOTIFICATIONS</li>
      <li><a href="notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>

      <li><hr style="margin: 15px 0; border: none; border-top: 1px solid rgba(255,255,255,0.1);"></li>
      <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
  </aside>

  <div class="main-content">
    <div class="top-header">
      <div>
        <h1><i class="fas fa-calendar"></i> Leave Requests</h1>
        <p style="color: var(--muted); margin: 0;">Submit leave requests and view your leave history</p>
      </div>
      <div class="user-menu">
        <span>👨‍🎓 <?php echo htmlspecialchars($stIdForQueries); ?></span>
        <a href="../logout.php">Logout</a>
      </div>
    </div>

    <div class="page-content">

      <?php if ($success_msg): ?>
        <div class="alert alert-success">
          <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_msg); ?>
        </div>
      <?php endif; ?>

      <?php if ($error_msg): ?>
        <div class="alert alert-danger">
          <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_msg); ?>
        </div>
      <?php endif; ?>

      <div class="card">
        <div class="card-header"><i class="fas fa-paper-plane"></i> Submit Leave Request</div>
        <div class="card-body">
          <form method="post" action="">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 16px;">
              <div class="form-group">
                <label><i class="fas fa-id-card"></i> Registration No.</label>
                <input
                  type="text"
                  name="st_id"
                  class="form-control"
                  value="<?php echo htmlspecialchars($stIdForQueries); ?>"
                  required
                />
              </div>

              <div class="form-group">
                <label><i class="fas fa-calendar-alt"></i> Leave Date</label>
                <input type="date" name="leave_date" class="form-control" required />
              </div>
            </div>

            <div class="form-group" style="margin-top: 14px;">
              <label><i class="fas fa-edit"></i> Reason</label>
              <textarea name="reason" class="form-control" rows="4" required placeholder="State your reason"></textarea>
            </div>

            <div style="margin-top: 16px; display:flex; gap: 12px; flex-wrap:wrap;">
              <button type="submit" name="submit_leave" class="btn btn-primary">
                <i class="fas fa-calendar-check"></i> Submit Request
              </button>
              <button type="button" class="btn btn-light" onclick="window.print()">
                <i class="fas fa-print"></i> Print
              </button>
            </div>
          </form>
        </div>
      </div>

      <div class="card" style="margin-top: 30px;">
        <div class="card-header"><i class="fas fa-history"></i> My Recent Leave Requests</div>
        <div class="card-body">
          <?php
          if (isset($_GET['search_id'])) {
            $searchId = (string)$_GET['search_id'];
            $bal_query = mysqli_query($link, "SELECT leave_balance FROM students WHERE st_id='" . mysqli_real_escape_string($link, $searchId) . "'");
            $bal_data = mysqli_fetch_array($bal_query);
            if ($bal_data) {
              echo "<h4 style='margin:0 0 10px 0;'>Current Leave Balance: <span class='label label-info'>" . htmlspecialchars((string)$bal_data['leave_balance']) . " Days</span></h4>";
            }
          }
          ?>

          <form method="get" class="form-inline" style="margin-bottom: 14px;">
            <input type="text" name="search_id" class="form-control" placeholder="Enter Reg No. to view history" />
            <button type="submit" class="btn btn-default"><i class="fas fa-search"></i> View History</button>
          </form>

          <?php if (isset($_GET['search_id'])): ?>
            <?php
              $searchId = (string)$_GET['search_id'];
              $stmt = mysqli_prepare($link, "SELECT leave_date, reason, status FROM leave_requests WHERE st_id = ? ORDER BY leave_date DESC");
              mysqli_stmt_bind_param($stmt, "s", $searchId);
              mysqli_stmt_execute($stmt);
              $res = mysqli_stmt_get_result($stmt);
            ?>
            <table class="table table-striped table-bordered" style="width:100%; max-width: 980px;">
              <thead>
                <tr class="info">
                  <th>Date</th>
                  <th>Reason</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($row = mysqli_fetch_array($res)): ?>
                  <tr>
                    <td><?php echo htmlspecialchars((string)$row['leave_date']); ?></td>
                    <td><?php echo htmlspecialchars((string)$row['reason']); ?></td>
                    <td>
                      <?php
                        $status = (string)$row['status'];
                        $labelClass = ($status === 'approved') ? 'success' : (($status === 'rejected') ? 'danger' : 'warning');
                      ?>
                      <span class="label label-<?php echo $labelClass; ?>"><?php echo htmlspecialchars(ucfirst($status)); ?></span>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
            <?php mysqli_stmt_close($stmt); ?>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>
</div>
</body>
</html>
