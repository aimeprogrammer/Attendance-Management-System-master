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

// Handle add/edit program
if(isset($_POST['action']) && in_array($_POST['action'], ['add', 'edit'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_msg = "Security validation failed (CSRF).";
    } else {
        try {
            $program_id = htmlspecialchars($_POST['program_id']);
            $program_name = htmlspecialchars($_POST['program_name']);

            if(empty($program_id) || empty($program_name)) {
                throw new Exception("All fields are required.");
            }

            if($_POST['action'] == 'add') {
                // Check if program exists
                $check = mysqli_prepare($link, "SELECT program_id FROM programs WHERE program_id=?");
                mysqli_stmt_bind_param($check, "s", $program_id);
                mysqli_stmt_execute($check);
                if(mysqli_stmt_get_result($check)->num_rows > 0) {
                    throw new Exception("Program ID already exists.");
                }
                mysqli_stmt_close($check);

                $stmt = mysqli_prepare($link, "INSERT INTO programs (program_id, program_name) VALUES (?, ?)");
                mysqli_stmt_bind_param($stmt, "ss", $program_id, $program_name);
                if (mysqli_stmt_execute($stmt)) {
                    $success_msg = "✓ Program added successfully.";
                }
                mysqli_stmt_close($stmt);
            } else {
                $stmt = mysqli_prepare($link, "UPDATE programs SET program_name=? WHERE program_id=?");
                mysqli_stmt_bind_param($stmt, "ss", $program_name, $program_id);
                if (mysqli_stmt_execute($stmt)) {
                    $success_msg = "✓ Program updated successfully.";
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
    $program_id = htmlspecialchars($_GET['delete']);
    $stmt = mysqli_prepare($link, "DELETE FROM programs WHERE program_id=?");
    mysqli_stmt_bind_param($stmt, "s", $program_id);
    if (mysqli_stmt_execute($stmt)) {
        $success_msg = "✓ Program deleted successfully.";
    }
    mysqli_stmt_close($stmt);
}

// Get edit data
$edit_program = null;
if(isset($_GET['edit'])) {
    $program_id = htmlspecialchars($_GET['edit']);
    $stmt = mysqli_prepare($link, "SELECT * FROM programs WHERE program_id=?");
    mysqli_stmt_bind_param($stmt, "s", $program_id);
    mysqli_stmt_execute($stmt);
    $edit_program = mysqli_stmt_get_result($stmt)->fetch_assoc();
    mysqli_stmt_close($stmt);
}

// Get all programs
$result = mysqli_query($link, "SELECT p.*, COUNT(b.batch_id) as batch_count, COUNT(s.subject_id) as subject_count FROM programs p LEFT JOIN batches b ON p.program_id = b.program_id LEFT JOIN subjects s ON p.program_id = s.program_id GROUP BY p.program_id ORDER BY p.program_name");
$programs = [];
while($row = mysqli_fetch_assoc($result)) {
    $programs[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Programs Management - Admin Dashboard</title>
    <link rel="stylesheet" type="text/css" href="../css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .action-buttons { display: flex; gap: 10px; }
        .action-buttons a { padding: 6px 12px; font-size: 12px; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; }
        .btn-edit { background: #3498db; color: white; }
        .btn-delete { background: #e74c3c; color: white; }
        .btn-edit:hover { background: #2980b9; }
        .btn-delete:hover { background: #c0392b; }
        .stat-badge { display: inline-block; background: #3498db; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; }
    </style>
</head>
<body>
<div class="dashboard-container">
    <!-- Shared Sidebar Navigation -->
    <?php $activePage = 'programs.php'; include('sidebar.php'); ?>

    <div class="main-content">
        <div class="top-header">
            <div>
                <h1><i class="fas fa-book"></i> Programs Management</h1>
                <p style="color: var(--muted); margin: 0;">Create and manage academic programs/courses</p>
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

            <!-- Add/Edit Program Form -->
            <div class="card">
                <div class="card-header"><i class="fas fa-plus"></i> <?php echo isset($edit_program) ? 'Edit Program' : 'Add New Program'; ?></div>
                <div class="card-body">
                    <form method="post" action="" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="action" value="<?php echo isset($edit_program) ? 'edit' : 'add'; ?>">

                        <div class="form-group">
                            <label for="program_id">Program ID (Code) *</label>
                            <input type="text" name="program_id" id="program_id" class="form-control" value="<?php echo $edit_program['program_id'] ?? ''; ?>" placeholder="e.g., CS101" <?php echo isset($edit_program) ? 'readonly' : ''; ?> required />
                        </div>

                        <div class="form-group">
                            <label for="program_name">Program Name *</label>
                            <input type="text" name="program_name" id="program_name" class="form-control" value="<?php echo $edit_program['program_name'] ?? ''; ?>" placeholder="e.g., Bachelor of Science in Computer Science" required />
                        </div>

                        <div style="grid-column: 1 / -1;">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo isset($edit_program) ? 'Update Program' : 'Add Program'; ?></button>
                            <?php if(isset($edit_program)): ?>
                                <a href="programs.php" class="btn btn-light"><i class="fas fa-times"></i> Cancel</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Programs Table -->
            <div class="card" style="margin-top: 30px;">
                <div class="card-header"><i class="fas fa-list"></i> Programs List (<?php echo count($programs); ?> total)</div>
                <div class="card-body">
                    <div style="overflow-x: auto;">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Program ID</th>
                                    <th>Program Name</th>
                                    <th>Batches</th>
                                    <th>Subjects</th>
                                    <th style="width: 120px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($programs) > 0): ?>
                                    <?php foreach($programs as $prog): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($prog['program_id']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($prog['program_name']); ?></td>
                                            <td><span class="stat-badge"><?php echo $prog['batch_count'] ?? 0; ?></span></td>
                                            <td><span class="stat-badge"><?php echo $prog['subject_count'] ?? 0; ?></span></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="?edit=<?php echo urlencode($prog['program_id']); ?>" class="btn-edit"><i class="fas fa-edit"></i> Edit</a>
                                                    <a href="?delete=<?php echo urlencode($prog['program_id']); ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" onclick="return confirm('Delete this program?');" class="btn-delete"><i class="fas fa-trash"></i> Delete</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="text-center text-muted"><i class="fas fa-inbox"></i> No programs found</td></tr>
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
