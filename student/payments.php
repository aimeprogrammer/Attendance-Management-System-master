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

$programBalances = [];
$paymentHistory = [];
$hasDues = false;

// Fee balances per enrolled program/batch
$balanceSql = "SELECT sp.id,
                      sp.program_id,
                      sp.batch_id,
                      p.program_name,
                      sp.total_due,
                      sp.total_paid,
                      (sp.total_due - sp.total_paid) AS outstanding
               FROM student_fee_balances sp
               JOIN programs p ON p.program_id = sp.program_id
               WHERE sp.st_id = ?
               ORDER BY p.program_name ASC";

$stmt = mysqli_prepare($link, $balanceSql);
mysqli_stmt_bind_param($stmt, "s", $stId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($res)) {
  $programBalances[] = $row;
  if ((float)$row['outstanding'] > 0) $hasDues = true;
}
mysqli_stmt_close($stmt);

// Payment history (latest 50) - from student_payments
$historySql = "SELECT sp.payment_id,
                      sp.program_id,
                      sp.payment_date,
                      sp.payment_amount,
                      sp.payment_method,
                      sp.reference_number,
                      sp.status,
                      p.program_name
               FROM student_payments sp
               JOIN programs p ON sp.program_id = p.program_id
               WHERE sp.st_id = ?
               ORDER BY sp.payment_date DESC
               LIMIT 50";

$stmt = mysqli_prepare($link, $historySql);
mysqli_stmt_bind_param($stmt, "s", $stId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($res)) {
  $paymentHistory[] = $row;
}
mysqli_stmt_close($stmt);

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payment & Fees - Student Dashboard</title>
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
      <li><a href="payments.php" class="active"><i class="fas fa-credit-card"></i> Payment & Fees</a></li>
      <li><a href="notices.php"><i class="fas fa-bullhorn"></i> Notices & Announcements</a></li>
      <li><a href="communications.php"><i class="fas fa-comments"></i> Communications</a></li>

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
        <h1><i class="fas fa-credit-card"></i> Payment & Fees</h1>
        <p style="color: var(--muted); margin: 0;">Fee structure, due status, and your payment history</p>
      </div>
      <div class="user-menu">
        <span>👨‍🎓 <?php echo htmlspecialchars($stId); ?></span>
        <a href="../logout.php">Logout</a>
      </div>
    </div>

    <div class="page-content">

      <div class="card">
        <div class="card-header"><i class="fas fa-wallet"></i> Outstanding Dues Summary</div>
        <div class="card-body">
          <?php if (count($programBalances) === 0): ?>
            <div class="alert alert-info">No fee balance records found.</div>
          <?php else: ?>
            <div class="stats-grid">
              <div class="stat-card info">
                <div class="stat-icon"><i class="fas fa-layer-group"></i></div>
                <div class="stat-content">
                  <div class="stat-label">Programs/Batches</div>
                  <div class="stat-value"><?php echo count($programBalances); ?></div>
                </div>
              </div>
              <div class="stat-card primary">
                <div class="stat-icon"><i class="fas fa-exclamation-circle"></i></div>
                <div class="stat-content">
                  <div class="stat-label">Has Outstanding Dues</div>
                  <div class="stat-value"><?php echo $hasDues ? 'Yes' : 'No'; ?></div>
                </div>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="card" style="margin-top: 30px;">
        <div class="card-header"><i class="fas fa-receipt"></i> Your Fee Balances</div>
        <div class="card-body">
          <?php if (count($programBalances) === 0): ?>
            <div class="alert alert-info">No fee balances to show.</div>
          <?php else: ?>
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Program</th>
                  <th>Batch</th>
                  <th>Total Due</th>
                  <th>Total Paid</th>
                  <th>Outstanding</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($programBalances as $b): ?>
                <?php
                  $outstanding = (float)$b['outstanding'];
                  $status = $outstanding > 0 ? 'Due' : 'Paid';
                ?>
                <tr>
                  <td><?php echo htmlspecialchars($b['program_name']); ?></td>
                  <td><?php echo htmlspecialchars($b['batch_id']); ?></td>
                  <td><?php echo htmlspecialchars($b['total_due']); ?></td>
                  <td><?php echo htmlspecialchars($b['total_paid']); ?></td>
                  <td><?php echo htmlspecialchars($b['outstanding']); ?></td>
                  <td><?php echo $status; ?></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
      </div>

      <div class="card" style="margin-top: 30px;">
        <div class="card-header"><i class="fas fa-history"></i> Payment History</div>
        <div class="card-body">
          <?php if (count($paymentHistory) === 0): ?>
            <div class="alert alert-info">No payments found.</div>
          <?php else: ?>
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Program</th>
                  <th>Amount</th>
                  <th>Method</th>
                  <th>Reference</th>
                  <th>Status</th>
                  <th>Receipt</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($paymentHistory as $p): ?>
                <?php
                  $pid = (int)($p['payment_id'] ?? 0);
                  $amount = isset($p['payment_amount']) ? number_format((float)$p['payment_amount'], 2, '.', ',') : '0.00';
                  $method = (string)($p['payment_method'] ?? '-');
                  $reference = $p['reference_number'] !== null && $p['reference_number'] !== '' ? (string)$p['reference_number'] : '-';
                ?>
                <tr>
                  <td><?php echo htmlspecialchars((string)$p['payment_date']); ?></td>
                  <td><?php echo htmlspecialchars((string)($p['program_name'] ?? '-')); ?></td>
                  <td><?php echo htmlspecialchars($amount); ?></td>
                  <td><?php echo htmlspecialchars(str_replace('_', ' ', $method)); ?></td>
                  <td><?php echo htmlspecialchars($reference); ?></td>
                  <td><?php echo htmlspecialchars((string)$p['status']); ?></td>
                  <td>
                    <?php if ($pid > 0): ?>
                      <a class="btn btn-secondary" style="padding: 8px 12px; display:inline-block; border:1px solid rgba(0,0,0,0.2);"
                         href="receipt.php?payment_id=<?php echo $pid; ?>">
                        <i class="fas fa-download"></i> Download
                      </a>
                    <?php else: ?>
                      <span class="text-muted">-</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>
</div>
</body>
</html>
