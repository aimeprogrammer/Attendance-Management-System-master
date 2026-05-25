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

include('connect.php');

$success_msg = '';
$error_msg = '';

if (isset($_POST['import_csv'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_msg = "Security validation failed (CSRF).";
    } else {
        $file = $_FILES['csv_file']['tmp_name'];
        if (empty($file)) {
            $error_msg = "Please select a CSV file.";
        } else {
            $handle = fopen($file, "r");
            $header = fgetcsv($handle); // Skip header row
            
            $importedCount = 0;
            $errorRows = [];
            $rowNum = 1;

            mysqli_query($link, "START TRANSACTION");

            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $rowNum++;
                if (count($data) < 6) {
                    $errorRows[] = "Row $rowNum: Insufficient columns.";
                    continue;
                }

                $st_id = trim($data[0]);
                $st_name = trim($data[1]);
                $st_dept = trim($data[2]);
                $st_batch = intval($data[3]);
                $st_sem = intval($data[4]);
                $st_email = trim($data[5]);

                if (empty($st_id) || empty($st_name)) {
                    $errorRows[] = "Row $rowNum: ID and Name are required.";
                    continue;
                }

                $stmt = mysqli_prepare($link, "INSERT INTO students (st_id, st_name, st_dept, st_batch, st_sem, st_email, leave_balance) 
                                             VALUES (?, ?, ?, ?, ?, ?, 15) 
                                             ON DUPLICATE KEY UPDATE 
                                             st_name=VALUES(st_name), 
                                             st_dept=VALUES(st_dept), 
                                             st_batch=VALUES(st_batch), 
                                             st_sem=VALUES(st_sem), 
                                             st_email=VALUES(st_email)");
                
                mysqli_stmt_bind_param($stmt, "sssiis", $st_id, $st_name, $st_dept, $st_batch, $st_sem, $st_email);
                
                if (mysqli_stmt_execute($stmt)) {
                    $importedCount++;
                } else {
                    $errorRows[] = "Row $rowNum: Database error.";
                }
                mysqli_stmt_close($stmt);
            }
            fclose($handle);

            if ($importedCount > 0) {
                mysqli_query($link, "COMMIT");
                $success_msg = "Successfully imported $importedCount student records.";
                
                $log_action = "Bulk imported $importedCount students via CSV";
                $actor = $_SESSION['name'] ?? 'admin';
                $log_stmt = mysqli_prepare($link, "INSERT INTO system_logs (user_id, action) VALUES (?, ?)");
                mysqli_stmt_bind_param($log_stmt, "ss", $actor, $log_action);
                mysqli_stmt_execute($log_stmt);
                mysqli_stmt_close($log_stmt);
            } else {
                mysqli_query($link, "ROLLBACK");
                $error_msg = "No records were imported. Please check your CSV format.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bulk Import - AMS</title>
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="dashboard-container">
    <?php $activePage = 'student_import.php'; include('sidebar.php'); ?>

    <div class="main-content">
        <div class="top-header">
            <h1><i class="fas fa-file-import"></i> Bulk Student Import</h1>
        </div>

        <div class="page-content">
            <?php if($success_msg): ?><div class="alert alert-success"><?php echo $success_msg; ?></div><?php endif; ?>
            <?php if($error_msg): ?><div class="alert alert-danger"><?php echo $error_msg; ?></div><?php endif; ?>

            <div class="card">
                <div class="card-header">Select CSV Source</div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> 
                        CSV Columns: <code>st_id, name, dept, batch, sem, email</code>
                    </div>
                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <div class="form-group">
                            <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                        </div>
                        <button type="submit" name="import_csv" class="btn btn-primary">Upload & Process</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
