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

// Handle add/edit subject
if(isset($_POST['action']) && in_array($_POST['action'], ['add', 'edit'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_msg = "Security validation failed (CSRF).";
    } else {
        try {
            $subject_id = htmlspecialchars($_POST['subject_id']);
            $program_id = htmlspecialchars($_POST['program_id']);
            $subject_code = htmlspecialchars($_POST['subject_code']);
            $subject_name = htmlspecialchars($_POST['subject_name']);

            if(empty($subject_id) || empty($subject_code) || empty($subject_name) || empty($program_id)) {
                throw new Exception("All fields are required.");
            }

            if($_POST['action'] == 'add') {
                $stmt = mysqli_prepare($link, "INSERT INTO subjects (subject_id, program_id, subject_code, subject_name) VALUES (?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "ssss", $subject_id, $program_id, $subject_code, $subject_name);
                if (mysqli_stmt_execute($stmt)) {
                    $success_msg = "✓ Subject added successfully.";
                }
                mysqli_stmt_close($stmt);
            } else {
                $stmt = mysqli_prepare($link, "UPDATE subjects SET subject_code=?, subject_name=? WHERE subject_id=?");
                mysqli_stmt_bind_param($stmt, "sss", $subject_code, $subject_name, $subject_id);
                if (mysqli_stmt_execute($stmt)) {
                    $success_msg = "✓ Subject updated successfully.";
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
    $subject_id = htmlspecialchars($_GET['delete']);
    $stmt = mysqli_prepare($link, "DELETE FROM subjects WHERE subject_id=?");
    mysqli_stmt_bind_param($stmt, "s", $subject_id);
    if (mysqli_stmt_execute($stmt)) {
        $success_msg = "✓ Subject deleted successfully.";
    }
    mysqli_stmt_close($stmt);
}

// Get edit data
$edit_subject = null;
if(isset($_GET['edit'])) {
    $subject_id = htmlspecialchars($_GET['edit']);
    $stmt = mysqli_prepare($link, "SELECT * FROM subjects WHERE subject_id=?");
    mysqli_stmt_bind_param($stmt, "s", $subject_id);
    mysqli_stmt_execute($stmt);
    $edit_subject = mysqli_stmt_get_result($stmt)->fetch_assoc();
    mysqli_stmt_close($stmt);
}

// Get programs for dropdown
$program_result = mysqli_query($link, "SELECT program_id, program_name FROM programs ORDER BY program_name");
$programs = [];
while($row = mysqli_fetch_assoc($program_result)) {
    $programs[] = $row;
}

// Get all subjects
$result = mysqli_query($link, "SELECT s.*, p.program_name FROM subjects s LEFT JOIN programs p ON s.program_id = p.program_id ORDER BY s.subject_code");
$subjects = [];
while($row = mysqli_fetch_assoc($result)) {
    $subjects[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subjects Management - Admin Dashboard</title>
    <link rel="stylesheet" type="text/css" href="../css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .action-buttons { display: flex; gap: 10px; }
        .action-buttons a { padding: 6px 12px; font-size: 12px; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; }
        .btn-edit { background: #3498db; color: white; }
        .btn-delete { background: #e74c3c; color: white; }
        .btn-edit:hover { background: #2980b9; }
        .btn-delete:hover { background: #c0392b; }
    </style>
</head>
<body>
<div class="dashboard-container">
    <!-- Shared Sidebar Navigation -->
    <?php $activePage = 'subjects.php'; include('sidebar.php'); ?>

    <div class="main-content">
        <div class="top-header">
            <div>
                <h1><i class="fas fa-list"></i> Subjects Management</h1>
                <p style="color: var(--muted); margin: 0;">Create and manage academic subjects</p>
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

            <!-- Add/Edit Subject Form -->
            <div class="card">
                <div class="card-header"><i class="fas fa-plus"></i> <?php echo isset($edit_subject) ? 'Edit Subject' : 'Add New Subject'; ?></div>
                <div class="card-body">
                    <form method="post" action="" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="action" value="<?php echo isset($edit_subject) ? 'edit' : 'add'; ?>">

                        <div class="form-group">
                            <label for="subject_id">Subject ID *</label>
                            <input type="text" name="subject_id" id="subject_id" class="form-control" value="<?php echo $edit_subject['subject_id'] ?? ''; ?>" placeholder="e.g., SBJ001" <?php echo isset($edit_subject) ? 'readonly' : ''; ?> required />
                        </div>

                        <div class="form-group">
                            <label for="program_id">Program *</label>
                            <select name="program_id" id="program_id" class="form-control" required>
                                <option value="">-- Select Program --</option>
                                <?php foreach($programs as $prog): ?>
                                    <option value="<?php echo htmlspecialchars($prog['program_id']); ?>" <?php echo (isset($edit_subject) && $edit_subject['program_id'] === $prog['program_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($prog['program_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="subject_code">Subject Code *</label>
                            <input type="text" name="subject_code" id="subject_code" class="form-control" value="<?php echo $edit_subject['subject_code'] ?? ''; ?>" placeholder="e.g., CS101" required />
                        </div>

                        <div class="form-group">
                            <label for="subject_name">Subject Name *</label>
                            <input type="text" name="subject_name" id="subject_name" class="form-control" value="<?php echo $edit_subject['subject_name'] ?? ''; ?>" placeholder="e.g., Introduction to Programming" required />
                        </div>

                        <div style="grid-column: 1 / -1;">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo isset($edit_subject) ? 'Update Subject' : 'Add Subject'; ?></button>
                            <?php if(isset($edit_subject)): ?>
                                <a href="subjects.php" class="btn btn-light"><i class="fas fa-times"></i> Cancel</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Subjects Table -->
            <div class="card" style="margin-top: 30px;">
                <div class="card-header"><i class="fas fa-list"></i> Subjects List (<?php echo count($subjects); ?> total)</div>
                <div class="card-body">
                    <div style="overflow-x: auto;">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Program</th>
                                    <th style="width: 120px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($subjects) > 0): ?>
                                    <?php foreach($subjects as $subject): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($subject['subject_code']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                                            <td><?php echo htmlspecialchars($subject['program_name'] ?? 'N/A'); ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="?edit=<?php echo urlencode($subject['subject_id']); ?>" class="btn-edit"><i class="fas fa-edit"></i> Edit</a>
                                                    <a href="?delete=<?php echo urlencode($subject['subject_id']); ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" onclick="return confirm('Delete this subject?');" class="btn-delete"><i class="fas fa-trash"></i> Delete</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center text-muted"><i class="fas fa-inbox"></i> No subjects found</td></tr>
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
