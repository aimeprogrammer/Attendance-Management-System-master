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

// Handle add/edit batch
if(isset($_POST['action']) && in_array($_POST['action'], ['add', 'edit'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_msg = "Security validation failed (CSRF).";
    } else {
        try {
            $batch_id = htmlspecialchars($_POST['batch_id']);
            $program_id = htmlspecialchars($_POST['program_id']);
            $batch_name = htmlspecialchars($_POST['batch_name']);
            $start_year = intval($_POST['start_year']);
            $end_year = intval($_POST['end_year']);

            if(empty($batch_id) || empty($batch_name) || empty($program_id)) {
                throw new Exception("All fields are required.");
            }

            if($_POST['action'] == 'add') {
                $stmt = mysqli_prepare($link, "INSERT INTO batches (batch_id, program_id, batch_name, start_year, end_year) VALUES (?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "sssii", $batch_id, $program_id, $batch_name, $start_year, $end_year);
                if (mysqli_stmt_execute($stmt)) {
                    $success_msg = "✓ Batch created successfully.";
                }
                mysqli_stmt_close($stmt);
            } else {
                $stmt = mysqli_prepare($link, "UPDATE batches SET batch_name=?, start_year=?, end_year=? WHERE batch_id=?");
                mysqli_stmt_bind_param($stmt, "siii", $batch_name, $start_year, $end_year, $batch_id);
                if (mysqli_stmt_execute($stmt)) {
                    $success_msg = "✓ Batch updated successfully.";
                }
                mysqli_stmt_close($stmt);
            }
        } catch(Exception $e) {
            $error_msg = $e->getMessage();
        }
    }
}

// Handle delete
if(isset($_GET['delete']) && isset($_GET['csrf_token']) && $_GET['csrf_token'] === $_SESSION['csrf_token']) {
    $batch_id = htmlspecialchars($_GET['delete']);
    $stmt = mysqli_prepare($link, "DELETE FROM batches WHERE batch_id=?");
    mysqli_stmt_bind_param($stmt, "s", $batch_id);
    if (mysqli_stmt_execute($stmt)) {
        $success_msg = "✓ Batch deleted successfully.";
    }
    mysqli_stmt_close($stmt);
}

// Get edit data
$edit_batch = null;
if(isset($_GET['edit'])) {
    $batch_id = htmlspecialchars($_GET['edit']);
    $stmt = mysqli_prepare($link, "SELECT * FROM batches WHERE batch_id=?");
    mysqli_stmt_bind_param($stmt, "s", $batch_id);
    mysqli_stmt_execute($stmt);
    $edit_batch = mysqli_stmt_get_result($stmt)->fetch_assoc();
    mysqli_stmt_close($stmt);
}

// Get programs for dropdown
$program_result = mysqli_query($link, "SELECT program_id, program_name FROM programs ORDER BY program_name");
$programs = [];
while($row = mysqli_fetch_assoc($program_result)) {
    $programs[] = $row;
}

// Get all batches with student count
$result = mysqli_query($link, "SELECT b.*, p.program_name, COUNT(DISTINCT se.st_id) as student_count FROM batches b LEFT JOIN programs p ON b.program_id = p.program_id LEFT JOIN student_enrollments se ON b.batch_id = se.batch_id GROUP BY b.batch_id ORDER BY b.batch_name");
$batches = [];
while($row = mysqli_fetch_assoc($result)) {
    $batches[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Batches Management - Admin Dashboard</title>
    <link rel="stylesheet" type="text/css" href="../css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .action-buttons { display: flex; gap: 10px; }
        .action-buttons a { padding: 6px 12px; font-size: 12px; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; }
        .btn-edit { background: #3498db; color: white; }
        .btn-delete { background: #e74c3c; color: white; }
        .btn-edit:hover { background: #2980b9; }
        .btn-delete:hover { background: #c0392b; }
        .stat-badge { display: inline-block; background: #27ae60; color: white; padding: 6px 10px; border-radius: 4px; }
    </style>
</head>
<body>
<div class="dashboard-container">
    <!-- Shared Sidebar Navigation -->
    <?php $activePage = 'batches.php'; include('sidebar.php'); ?>

    <div class="main-content">
        <div class="top-header">
            <div>
                <h1><i class="fas fa-graduation-cap"></i> Batches/Classes Management</h1>
                <p style="color: var(--muted); margin: 0;">Create and manage student batches/classes</p>
            </div>
            <div class="user-menu">
                <span>👨‍💼 Admin</span>
                <a href="../logout.php">Logout</a>
            </div>
        </div>

        <div class="page-content">
            <?php if(isset($success_msg)): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success_msg; ?></div>
            <?php endif; ?>
            <?php if(isset($error_msg)): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?></div>
            <?php endif; ?>

            <!-- Add/Edit Batch Form -->
            <div class="card">
                <div class="card-header"><i class="fas fa-plus"></i> <?php echo isset($edit_batch) ? 'Edit Batch' : 'Create New Batch'; ?></div>
                <div class="card-body">
                    <form method="post" action="" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="action" value="<?php echo isset($edit_batch) ? 'edit' : 'add'; ?>">

                        <div class="form-group">
                            <label for="batch_id">Batch ID (Code) *</label>
                            <input type="text" name="batch_id" id="batch_id" class="form-control" value="<?php echo $edit_batch['batch_id'] ?? ''; ?>" placeholder="e.g., BATCH2020-CSE" <?php echo isset($edit_batch) ? 'readonly' : ''; ?> required />
                        </div>

                        <div class="form-group">
                            <label for="program_id">Program *</label>
                            <select name="program_id" id="program_id" class="form-control" required>
                                <option value="">-- Select Program --</option>
                                <?php foreach($programs as $prog): ?>
                                    <option value="<?php echo htmlspecialchars($prog['program_id']); ?>" <?php echo (isset($edit_batch) && $edit_batch['program_id'] === $prog['program_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($prog['program_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="batch_name">Batch Name *</label>
                            <input type="text" name="batch_name" id="batch_name" class="form-control" value="<?php echo $edit_batch['batch_name'] ?? ''; ?>" placeholder="e.g., 2020-2024" required />
                        </div>

                        <div class="form-group">
                            <label for="start_year">Start Year</label>
                            <input type="number" name="start_year" id="start_year" class="form-control" value="<?php echo $edit_batch['start_year'] ?? date('Y'); ?>" placeholder="2020" />
                        </div>

                        <div class="form-group">
                            <label for="end_year">End Year</label>
                            <input type="number" name="end_year" id="end_year" class="form-control" value="<?php echo $edit_batch['end_year'] ?? (date('Y')+4); ?>" placeholder="2024" />
                        </div>

                        <div style="grid-column: 1 / -1;">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo isset($edit_batch) ? 'Update Batch' : 'Create Batch'; ?></button>
                            <?php if(isset($edit_batch)): ?>
                                <a href="batches.php" class="btn btn-light"><i class="fas fa-times"></i> Cancel</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Batches Table -->
            <div class="card" style="margin-top: 30px;">
                <div class="card-header"><i class="fas fa-list"></i> Batches List (<?php echo count($batches); ?> total)</div>
                <div class="card-body">
                    <div style="overflow-x: auto;">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Batch ID</th>
                                    <th>Batch Name</th>
                                    <th>Program</th>
                                    <th>Year</th>
                                    <th>Students</th>
                                    <th style="width: 120px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($batches) > 0): ?>
                                    <?php foreach($batches as $batch): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($batch['batch_id']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($batch['batch_name']); ?></td>
                                            <td><?php echo htmlspecialchars($batch['program_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo $batch['start_year'] . '-' . $batch['end_year']; ?></td>
                                            <td><span class="stat-badge"><?php echo $batch['student_count'] ?? 0; ?></span></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="?edit=<?php echo urlencode($batch['batch_id']); ?>" class="btn-edit"><i class="fas fa-edit"></i> Edit</a>
                                                    <a href="?delete=<?php echo urlencode($batch['batch_id']); ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" onclick="return confirm('Delete this batch?');" class="btn-delete"><i class="fas fa-trash"></i> Delete</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="text-center text-muted"><i class="fas fa-inbox"></i> No batches found</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const currentPage = window.location.pathname.split('/').pop() || 'index.php';
        document.querySelectorAll('.sidebar-menu a').forEach(link => {
            if (link.getAttribute('href').split('?')[0] === currentPage) {
                link.classList.add('active');
            }
        });
    });
</script>
</body>
</html>
