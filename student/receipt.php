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
  header('location: payments.php');
  exit;
}

if (empty($_GET['payment_id'])) {
  header('location: payments.php');
  exit;
}

$paymentId = (int)$_GET['payment_id'];
if ($paymentId <= 0) {
  header('location: payments.php');
  exit;
}

// Fetch payment with related program + student
$sql = "SELECT sp.payment_id,
               sp.st_id,
               sp.program_id,
               sp.payment_amount,
               sp.payment_date,
               sp.payment_method,
               sp.reference_number,
               sp.status,
               sp.recorded_by,
               p.program_name,
               s.st_name
        FROM student_payments sp
        JOIN programs p ON p.program_id = sp.program_id
        JOIN students s ON s.st_id = sp.st_id
        WHERE sp.payment_id = ?
          AND sp.st_id = ?";

$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_bind_param($stmt, "is", $paymentId, $stId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$row) {
  http_response_code(404);
  echo "Receipt not found.";
  exit;
}

$amount = number_format((float)$row['payment_amount'], 2, '.', ',');
$method = (string)$row['payment_method'];
$reference = $row['reference_number'] !== null && $row['reference_number'] !== '' ? (string)$row['reference_number'] : '-';
$status = (string)$row['status'];
$programName = (string)$row['program_name'];
$studentName = (string)$row['st_name'];
$paymentDate = (string)$row['payment_date'];

$filename = sprintf('Receipt_%s_%d_%s.pdf', $row['st_id'], (int)$row['payment_id'], $paymentDate);
$filename = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $filename);

// Simple “receipt” as printable HTML delivered with PDF-ish headers (browser print/save-as-PDF works)
// This repo currently has no PDF library, so we keep it dependency-free.
header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: attachment; filename="'.$filename.'"');

// Brutalist/plain HTML receipt (print-friendly)
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Receipt #<?php echo htmlspecialchars((string)$row['payment_id']); ?></title>
  <style>
    body { font-family: Arial, Helvetica, sans-serif; margin: 0; padding: 24px; color: #000; }
    .receipt { border: 3px solid #000; padding: 18px; }
    .row { display: flex; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
    .title { font-size: 26px; font-weight: 900; letter-spacing: 0.5px; }
    .badge { border: 2px solid #000; padding: 6px 10px; font-weight: 800; }
    .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 14px; }
    .kv { border: 2px solid #000; padding: 10px; }
    .k { font-size: 12px; font-weight: 800; text-transform: uppercase; }
    .v { font-size: 16px; font-weight: 700; margin-top: 4px; }
    .sep { height: 10px; }
    .footer { margin-top: 18px; border-top: 2px solid #000; padding-top: 12px; font-size: 12px; color: #000; font-weight: 700; }
    @media print { body { padding: 0; } }
  </style>
</head>
<body>
  <div class="receipt">
    <div class="row">
      <div>
        <div class="title">PAYMENT RECEIPT</div>
        <div style="font-weight:800; margin-top:6px;">Student Portal</div>
      </div>
      <div style="text-align:right;">
        <div class="badge">Receipt ID: <?php echo htmlspecialchars((string)$row['payment_id']); ?></div>
        <div style="margin-top:8px; font-weight:800;"><?php echo htmlspecialchars($status); ?></div>
      </div>
    </div>

    <div class="grid">
      <div class="kv">
        <div class="k">Student</div>
        <div class="v"><?php echo htmlspecialchars($studentName); ?></div>
        <div class="k" style="margin-top:8px;">Registration No.</div>
        <div class="v"><?php echo htmlspecialchars($row['st_id']); ?></div>
      </div>

      <div class="kv">
        <div class="k">Program</div>
        <div class="v"><?php echo htmlspecialchars($programName); ?></div>
        <div class="k" style="margin-top:8px;">Payment Date</div>
        <div class="v"><?php echo htmlspecialchars($paymentDate); ?></div>
      </div>

      <div class="kv">
        <div class="k">Amount Paid</div>
        <div class="v">ZAR <?php echo htmlspecialchars($amount); ?></div>
        <div class="k" style="margin-top:8px;">Method</div>
        <div class="v"><?php echo htmlspecialchars(str_replace('_', ' ', $method)); ?></div>
      </div>

      <div class="kv">
        <div class="k">Reference</div>
        <div class="v"><?php echo htmlspecialchars($reference); ?></div>
        <div class="k" style="margin-top:8px;">Recorded By</div>
        <div class="v"><?php echo htmlspecialchars((string)$row['recorded_by'] ?? '-'); ?></div>
      </div>
    </div>

    <div class="footer">
      Tip: Use your browser’s print dialog to “Save as PDF”.
    </div>
  </div>

  <script>
    // auto-open print dialog for better “download”
    // If browser blocks popups, user can still press Ctrl+P.
    setTimeout(function(){ try { window.print(); } catch(e){} }, 300);
  </script>
</body>
</html>
