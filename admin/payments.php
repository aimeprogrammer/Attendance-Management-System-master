<?php
ob_start();
session_start();

if($_SESSION['name']!='oasis') {
  header('location: ../index.php');
  exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
}

include('connect.php');

// Handle record payment
if(isset($_POST['action']) && $_POST['action'] == 'record_payment') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_msg = "Security validation failed (CSRF).";
    } else {
        try {
            $st_id = htmlspecialchars($_POST['st_id']);
            $program_id = htmlspecialchars($_POST['program_id']);
            $payment_amount = floatval($_POST['payment_amount']);
            $payment_method = htmlspecialchars($_POST['payment_method']);
            $reference_number = htmlspecialchars($_POST['reference_number']);
            $payment_date = $_POST['payment_date'];

            if(empty($st_id) || $payment_amount <= 0 || empty($payment_date)) {
                throw new Exception("All fields are required with valid values.");
            }

            $recorded_by = 'oasis'; // admin username
            $stmt = mysqli_prepare($link, "INSERT INTO student_payments (st_id, program_id, payment_amount, payment_date, payment_method, reference_number, status, recorded_by) VALUES (?, ?, ?, ?, ?, ?, 'completed', ?)");
            mysqli_stmt_bind_param($stmt, "ssdssss", $st_id, $program_id, $payment_amount, $payment_date, $payment_method, $reference_number, $recorded_by);
            if (mysqli_stmt_execute($stmt)) {
                $success_msg = "✓ Payment recorded successfully.";
            }
            mysqli_stmt_close($stmt);
        } catch(Exception $e) {
            $error_msg = $e->getMessage();
        }
    }
}

// Get students for dropdown
$student_query = "SELECT st_id, st_name FROM students ORDER BY st_name";
$student_result = mysqli_query($link, $student_query);
$students = [];
while($row = mysqli_fetch_assoc($student_result)) {
    $students[] = $row;
}

// Get programs for dropdown
$program_query = "SELECT program_id, program_name FROM programs ORDER BY program_name";
$program_result = mysqli_query($link, $program_query);
$programs = [];
while($row = mysqli_fetch_assoc($program_result)) {
    $programs[] = $row;
}

// Get payment summary stats
$stats_query = "SELECT 
    COUNT(*) as total_payments,
    SUM(payment_amount) as total_collected,
    COUNT(CASE WHEN MONTH(payment_date) = MONTH(CURDATE()) THEN 1 END) as current_month_payments,
    SUM(CASE WHEN MONTH(payment_date) = MONTH(CURDATE()) THEN payment_amount ELSE 0 END) as current_month_amount
FROM student_payments
WHERE status = 'completed'";
$stats_result = mysqli_query($link, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Get payment history
$history_query = "SELECT 
    sp.*,
    s.st_name,
    p.program_name
FROM student_payments sp
LEFT JOIN students s ON sp.st_id = s.st_id
LEFT JOIN programs p ON sp.program_id = p.program_id
ORDER BY sp.payment_date DESC
LIMIT 100";
$history_result = mysqli_query($link, $history_query);
$payment_history = [];
while($row = mysqli_fetch_assoc($history_result)) {
    $payment_history[] = $row;
}

// Get outstanding dues
$dues_query = "SELECT 
    COUNT(*) as total_students_with_dues,
    SUM(total_due - total_paid) as total_outstanding
FROM student_fee_balances
WHERE total_due > total_paid";
$dues_result = mysqli_query($link, $dues_query);
$dues = mysqli_fetch_assoc($dues_result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments Management - Admin Dashboard</title>
    <link rel="stylesheet" type="text/css" href="../css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* =============================================
           Payments Page Specific Styles
           ============================================= */
        
        /* Statistics Cards */
        .payment-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .payment-stat-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            border: 1px solid #e9ecef;
        }

        .payment-stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }

        .payment-stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
        }

        .payment-stat-card.success::before {
            background: linear-gradient(90deg, #27ae60, #2ecc71);
        }

        .payment-stat-card.info::before {
            background: linear-gradient(90deg, #3498db, #5dade2);
        }

        .payment-stat-card.danger::before {
            background: linear-gradient(90deg, #e74c3c, #ec7063);
        }

        .payment-stat-card.warning::before {
            background: linear-gradient(90deg, #f39c12, #f7dc6f);
        }

        .stat-icon-wrapper {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 48px;
            border-radius: 12px;
            margin-bottom: 15px;
            font-size: 22px;
        }

        .payment-stat-card.success .stat-icon-wrapper {
            background: rgba(39, 174, 96, 0.1);
            color: #27ae60;
        }

        .payment-stat-card.info .stat-icon-wrapper {
            background: rgba(52, 152, 219, 0.1);
            color: #3498db;
        }

        .payment-stat-card.danger .stat-icon-wrapper {
            background: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
        }

        .payment-stat-card.warning .stat-icon-wrapper {
            background: rgba(243, 156, 18, 0.1);
            color: #f39c12;
        }

        .stat-label {
            font-size: 12px;
            color: #7f8c8d;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 6px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 4px;
            line-height: 1.2;
        }

        .stat-subtitle {
            font-size: 12px;
            color: #95a5a6;
            font-weight: 500;
        }

        /* Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-success {
            background: rgba(39, 174, 96, 0.1);
            color: #27ae60;
        }

        .badge-pending {
            background: rgba(243, 156, 18, 0.1);
            color: #f39c12;
        }

        .badge-failed {
            background: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
        }

        .badge-info {
            background: rgba(52, 152, 219, 0.1);
            color: #3498db;
        }

        /* Payment Method Badge */
        .method-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            background: #f8f9fa;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            color: #2c3e50;
            border: 1px solid #e9ecef;
        }

        .method-badge i {
            color: #4b77be;
            font-size: 13px;
        }

        /* Amount Highlight */
        .amount-highlight {
            font-weight: 700;
            color: #27ae60;
            font-size: 14px;
        }

        /* Form Enhancements */
        .form-section-title {
            font-size: 13px;
            font-weight: 600;
            color: #7f8c8d;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e9ecef;
        }

        /* Quick Stats Row */
        .quick-stats-row {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .quick-stat {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
            font-size: 13px;
            font-weight: 500;
        }

        .quick-stat i {
            color: #4b77be;
            font-size: 16px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .payment-stats-grid {
                grid-template-columns: 1fr;
            }

            .stat-value {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
<div class="dashboard-container">
    <?php $activePage = 'payments.php'; include('sidebar.php'); ?>

    <div class="main-content">
        <div class="top-header">
            <div>
                <h1><i class="fas fa-credit-card"></i> Payments Management</h1>
                <p>Record payments, track dues, and view payment history</p>
            </div>
            <div class="user-menu">
                <span><i class="fas fa-user-circle"></i> Admin</span>
                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <div class="page-content">
            <!-- Status Messages -->
            <?php if(isset($success_msg)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success_msg; ?>
                </div>
            <?php endif; ?>
            <?php if(isset($error_msg)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="payment-stats-grid">
                <div class="payment-stat-card success">
                    <div class="stat-icon-wrapper">
                        <i class="fas fa-coins"></i>
                    </div>
                    <div class="stat-label">Total Collected</div>
                    <div class="stat-value">$<?php echo number_format($stats['total_collected'] ?? 0, 2); ?></div>
                    <div class="stat-subtitle">
                        <i class="fas fa-receipt"></i> <?php echo number_format($stats['total_payments'] ?? 0); ?> payments
                    </div>
                </div>

                <div class="payment-stat-card info">
                    <div class="stat-icon-wrapper">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-label">This Month</div>
                    <div class="stat-value">$<?php echo number_format($stats['current_month_amount'] ?? 0, 2); ?></div>
                    <div class="stat-subtitle">
                        <i class="fas fa-receipt"></i> <?php echo number_format($stats['current_month_payments'] ?? 0); ?> payments
                    </div>
                </div>

                <div class="payment-stat-card danger">
                    <div class="stat-icon-wrapper">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-label">Outstanding Dues</div>
                    <div class="stat-value">$<?php echo number_format($dues['total_outstanding'] ?? 0, 2); ?></div>
                    <div class="stat-subtitle">
                        <i class="fas fa-users"></i> <?php echo number_format($dues['total_students_with_dues'] ?? 0); ?> students
                    </div>
                </div>

                <div class="payment-stat-card warning">
                    <div class="stat-icon-wrapper">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <div class="stat-label">Collection Rate</div>
                    <div class="stat-value">
                        <?php 
                            $total_due = ($dues['total_outstanding'] ?? 0) + ($stats['total_collected'] ?? 0);
                            $collection_rate = $total_due > 0 ? round((($stats['total_collected'] ?? 0) / $total_due) * 100, 1) : 0;
                            echo $collection_rate . '%';
                        ?>
                    </div>
                    <div class="stat-subtitle">
                        <i class="fas fa-chart-line"></i> Overall collection rate
                    </div>
                </div>
            </div>

            <!-- Record Payment Form -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-plus-circle"></i> Record Manual Payment
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="action" value="record_payment">

                        <div class="form-section-title">
                            <i class="fas fa-info-circle"></i> Payment Details
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="form-group">
                                <label for="st_id"><i class="fas fa-user-graduate"></i> Student *</label>
                                <select name="st_id" id="st_id" class="form-control" required>
                                    <option value="">-- Select Student --</option>
                                    <?php foreach($students as $student): ?>
                                        <option value="<?php echo htmlspecialchars($student['st_id']); ?>">
                                            <?php echo htmlspecialchars($student['st_id'] . ' - ' . $student['st_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="program_id"><i class="fas fa-book"></i> Program *</label>
                                <select name="program_id" id="program_id" class="form-control" required>
                                    <option value="">-- Select Program --</option>
                                    <?php foreach($programs as $prog): ?>
                                        <option value="<?php echo htmlspecialchars($prog['program_id']); ?>">
                                            <?php echo htmlspecialchars($prog['program_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="payment_amount"><i class="fas fa-dollar-sign"></i> Payment Amount *</label>
                                <div style="position: relative;">
                                    <span style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #7f8c8d; font-weight: 600;">$</span>
                                    <input type="number" name="payment_amount" id="payment_amount" class="form-control" placeholder="0.00" step="0.01" min="0.01" style="padding-left: 28px;" required />
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="payment_method"><i class="fas fa-credit-card"></i> Payment Method *</label>
                                <select name="payment_method" id="payment_method" class="form-control" required>
                                    <option value="cash"><i class="fas fa-money-bill"></i> Cash</option>
                                    <option value="cheque">Cheque</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="credit_card">Credit Card</option>
                                    <option value="online">Online Payment</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="reference_number"><i class="fas fa-hashtag"></i> Reference Number</label>
                                <input type="text" name="reference_number" id="reference_number" class="form-control" placeholder="Cheque #, Transaction ID, etc." />
                            </div>

                            <div class="form-group">
                                <label for="payment_date"><i class="fas fa-calendar-alt"></i> Payment Date *</label>
                                <input type="date" name="payment_date" id="payment_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required />
                            </div>
                        </div>

                        <div style="margin-top: 25px; display: flex; gap: 10px;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Record Payment
                            </button>
                            <button type="reset" class="btn btn-light">
                                <i class="fas fa-redo"></i> Clear Form
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Payment History -->
            <div class="card" style="margin-top: 25px;">
                <div class="card-header">
                    <i class="fas fa-history"></i> Payment History
                    <span class="badge badge-info" style="margin-left: 10px;"><?php echo count($payment_history); ?> records</span>
                </div>
                <div class="card-body">
                    <?php if(count($payment_history) > 0): ?>
                        <div style="overflow-x: auto;">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th><i class="fas fa-calendar"></i> Date</th>
                                        <th><i class="fas fa-user"></i> Student</th>
                                        <th><i class="fas fa-book"></i> Program</th>
                                        <th><i class="fas fa-dollar-sign"></i> Amount</th>
                                        <th><i class="fas fa-credit-card"></i> Method</th>
                                        <th><i class="fas fa-hashtag"></i> Reference</th>
                                        <th><i class="fas fa-circle"></i> Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($payment_history as $payment): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo date('h:i A', strtotime($payment['payment_date'])); ?></small>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($payment['st_name'] ?? 'N/A'); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($payment['st_id']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($payment['program_name'] ?? 'N/A'); ?></td>
                                            <td>
                                                <span class="amount-highlight">
                                                    $<?php echo number_format($payment['payment_amount'], 2); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="method-badge">
                                                    <?php 
                                                        $method_icons = [
                                                            'cash' => 'fa-money-bill-wave',
                                                            'cheque' => 'fa-file-invoice',
                                                            'bank_transfer' => 'fa-university',
                                                            'credit_card' => 'fa-credit-card',
                                                            'online' => 'fa-globe'
                                                        ];
                                                        $method = $payment['payment_method'] ?? 'cash';
                                                        $icon = $method_icons[$method] ?? 'fa-credit-card';
                                                    ?>
                                                    <i class="fas <?php echo $icon; ?>"></i>
                                                    <?php echo ucfirst(str_replace('_', ' ', $method)); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if(!empty($payment['reference_number'])): ?>
                                                    <code style="background: #f8f9fa; padding: 2px 8px; border-radius: 4px; font-size: 12px;">
                                                        <?php echo htmlspecialchars($payment['reference_number']); ?>
                                                    </code>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                    $status = strtolower($payment['status'] ?? 'completed');
                                                    $status_badge_class = 'badge-success';
                                                    if ($status === 'pending') $status_badge_class = 'badge-pending';
                                                    if ($status === 'failed') $status_badge_class = 'badge-failed';
                                                ?>
                                                <span class="badge <?php echo $status_badge_class; ?>">
                                                    <i class="fas fa-<?php echo $status === 'completed' ? 'check-circle' : ($status === 'pending' ? 'clock' : 'times-circle'); ?>"></i>
                                                    <?php echo ucfirst($status); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; padding: 50px 20px;">
                            <div style="font-size: 48px; color: #dcdde1; margin-bottom: 15px;">
                                <i class="fas fa-inbox"></i>
                            </div>
                            <h3 style="color: #7f8c8d; margin-bottom: 8px;">No Payments Recorded</h3>
                            <p class="text-muted">Payment history will appear here once payments are recorded.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Set active sidebar link
        const currentPage = window.location.pathname.split('/').pop() || 'index.php';
        document.querySelectorAll('.sidebar-menu a').forEach(link => {
            if (link.getAttribute('href').split('?')[0] === currentPage) {
                link.classList.add('active');
            }
        });

        // Format currency input
        const amountInput = document.getElementById('payment_amount');
        if (amountInput) {
            amountInput.addEventListener('blur', function() {
                if (this.value) {
                    this.value = parseFloat(this.value).toFixed(2);
                }
            });
        }

        // Confirm before leaving if form is filled
        const form = document.querySelector('form');
        let formChanged = false;
        
        if (form) {
            const formInputs = form.querySelectorAll('input, select');
            formInputs.forEach(input => {
                input.addEventListener('change', function() {
                    formChanged = true;
                });
            });

            form.addEventListener('submit', function() {
                formChanged = false;
            });
        }

        window.addEventListener('beforeunload', function(e) {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
            }
        });
    });
</script>
</body>
</html>
