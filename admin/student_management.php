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

// Handle add/edit student
if(isset($_POST['action']) && in_array($_POST['action'], ['add', 'edit'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_msg = "Security validation failed (CSRF).";
    } else {
        try {
            $st_id = htmlspecialchars($_POST['st_id']);
            $st_name = htmlspecialchars($_POST['st_name']);
            $st_dept = htmlspecialchars($_POST['st_dept']);
            $st_batch = intval($_POST['st_batch']);
            $st_sem = intval($_POST['st_sem']);
            $st_email = htmlspecialchars($_POST['st_email']);

            if(empty($st_id) || empty($st_name) || empty($st_dept) || empty($st_email)) {
                throw new Exception("All fields are required.");
            }

            if($_POST['action'] == 'add') {
                $stmt = mysqli_prepare($link, "INSERT INTO students (st_id, st_name, st_dept, st_batch, st_sem, st_email, leave_balance) VALUES (?, ?, ?, ?, ?, ?, 15)");
                mysqli_stmt_bind_param($stmt, "sssiis", $st_id, $st_name, $st_dept, $st_batch, $st_sem, $st_email);
                if (mysqli_stmt_execute($stmt)) {
                    $success_msg = "✓ Student added successfully.";
                } else {
                    throw new Exception("Error adding student.");
                }
                mysqli_stmt_close($stmt);
            } else {
                $stmt = mysqli_prepare($link, "UPDATE students SET st_name=?, st_dept=?, st_batch=?, st_sem=?, st_email=? WHERE st_id=?");
                mysqli_stmt_bind_param($stmt, "ssiiis", $st_name, $st_dept, $st_batch, $st_sem, $st_email, $st_id);
                if (mysqli_stmt_execute($stmt)) {
                    $success_msg = "✓ Student updated successfully.";
                } else {
                    throw new Exception("Error updating student.");
                }
                mysqli_stmt_close($stmt);
            }
        } catch(Exception $e) {
            $error_msg = $e->getMessage();
        }
    }
}

// Handle delete student
if(isset($_GET['delete']) && isset($_GET['csrf_token']) && $_GET['csrf_token'] === $_SESSION['csrf_token']) {
    $st_id = htmlspecialchars($_GET['delete']);
    $stmt = mysqli_prepare($link, "DELETE FROM students WHERE st_id=?");
    mysqli_stmt_bind_param($stmt, "s", $st_id);
    if (mysqli_stmt_execute($stmt)) {
        $success_msg = "✓ Student deleted successfully.";
    }
    mysqli_stmt_close($stmt);
}

// Get student details for editing
$edit_student = null;
if(isset($_GET['edit'])) {
    $st_id = htmlspecialchars($_GET['edit']);
    $stmt = mysqli_prepare($link, "SELECT * FROM students WHERE st_id=?");
    mysqli_stmt_bind_param($stmt, "s", $st_id);
    mysqli_stmt_execute($stmt);
    $edit_student = mysqli_stmt_get_result($stmt)->fetch_assoc();
    mysqli_stmt_close($stmt);
}

// Get all students with search/filter
$search = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';
$filter_dept = isset($_GET['dept']) ? htmlspecialchars($_GET['dept']) : '';

$query = "SELECT * FROM students WHERE 1=1";
$params = [];
$types = "";

if($search) {
    $query .= " AND (st_id LIKE ? OR st_name LIKE ? OR st_email LIKE ?)";
    $search_param = "%$search%";
    $params = array_fill(0, 3, $search_param);
    $types = "sss";
}

if($filter_dept) {
    $query .= " AND st_dept = ?";
    $params[] = $filter_dept;
    $types .= "s";
}

$query .= " ORDER BY st_id DESC LIMIT 100";

$stmt = mysqli_prepare($link, $query);
if($params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$students = [];
while($row = mysqli_fetch_assoc($result)) {
    $students[] = $row;
}
mysqli_stmt_close($stmt);

// Get departments for filter
$deptQuery = "SELECT DISTINCT st_dept FROM students ORDER BY st_dept";
$deptResult = mysqli_query($link, $deptQuery);
$departments = [];
while($row = mysqli_fetch_assoc($deptResult)) {
    $departments[] = $row['st_dept'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management - Admin Dashboard</title>
    <link rel="stylesheet" type="text/css" href="../css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
        .modal.active { display: flex; align-items: center; justify-content: center; }
        .modal-content { background-color: white; padding: 30px; border-radius: 8px; width: 90%; max-width: 500px; }
        .close-modal { float: right; font-size: 28px; cursor: pointer; }
        .action-buttons { display: flex; gap: 10px; }
        .action-buttons a, .action-buttons button { padding: 6px 12px; font-size: 12px; text-decoration: none; border: none; border-radius: 4px; cursor: pointer; }
        .btn-edit { background: #3498db; color: white; }
        .btn-delete { background: #e74c3c; color: white; }
        .btn-edit:hover { background: #2980b9; }
        .btn-delete:hover { background: #c0392b; }
    </style>
</head>
<body>
<div class="dashboard-container">
    <?php
        $activePage = 'student_management.php';
        include('sidebar.php');
    ?>

    <div class="main-content">
        <div class="top-header">
            <div>
                <h1><i class="fas fa-users"></i> Student Management</h1>
                <p style="color: var(--muted); margin: 0;">View, add, edit, and manage student records</p>
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

            <!-- Search & Filter -->
            <div class="card">
                <div class="card-header"><i class="fas fa-filter"></i> Search & Filter</div>
                <div class="card-body">
                    <form method="get" action="" style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 15px; align-items: flex-end;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label><i class="fas fa-search"></i> Search (ID/Name/Email)</label>
                            <input type="text" name="search" class="form-control" placeholder="Enter search term" value="<?php echo $search; ?>" />
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label><i class="fas fa-building"></i> Department</label>
                            <select name="dept" class="form-control">
                                <option value="">All Departments</option>
                                <?php foreach($departments as $dept): ?>
                                    <option value="<?php echo $dept; ?>" <?php echo ($filter_dept === $dept) ? 'selected' : ''; ?>><?php echo $dept; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
                        <a href="student_management.php" class="btn btn-light"><i class="fas fa-redo"></i> Reset</a>
                    </form>
                </div>
            </div>

            <!-- Add/Edit Student -->
            <div class="card" style="margin-top: 30px;">
                <div class="card-header"><i class="fas fa-user-plus"></i> <?php echo isset($edit_student) ? 'Edit Student' : 'Add New Student'; ?></div>
                <div class="card-body">
                    <form method="post" action="" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="action" value="<?php echo isset($edit_student) ? 'edit' : 'add'; ?>">

                        <div class="form-group">
                            <label for="st_id">Registration No. *</label>
                            <input type="text" name="st_id" id="st_id" class="form-control" value="<?php echo $edit_student['st_id'] ?? ''; ?>" <?php echo isset($edit_student) ? 'readonly' : ''; ?> required />
                        </div>

                        <div class="form-group">
                            <label for="st_name">Full Name *</label>
                            <input type="text" name="st_name" id="st_name" class="form-control" value="<?php echo $edit_student['st_name'] ?? ''; ?>" placeholder="Student's full name" required />
                        </div>

                        <div class="form-group">
                            <label for="st_dept">Department *</label>
                            <input type="text" name="st_dept" id="st_dept" class="form-control" value="<?php echo $edit_student['st_dept'] ?? ''; ?>" placeholder="e.g., CSE" required />
                        </div>

                        <div class="form-group">
                            <label for="st_batch">Batch *</label>
                            <input type="number" name="st_batch" id="st_batch" class="form-control" value="<?php echo $edit_student['st_batch'] ?? ''; ?>" placeholder="e.g., 2020" required />
                        </div>

                        <div class="form-group">
                            <label for="st_sem">Semester *</label>
                            <input type="number" name="st_sem" id="st_sem" class="form-control" value="<?php echo $edit_student['st_sem'] ?? ''; ?>" placeholder="e.g., 4" required />
                        </div>

                        <div class="form-group">
                            <label for="st_email">Email Address *</label>
                            <input type="email" name="st_email" id="st_email" class="form-control" value="<?php echo $edit_student['st_email'] ?? ''; ?>" placeholder="student@example.com" required />
                        </div>

                        <div style="grid-column: 1 / -1;">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo isset($edit_student) ? 'Update Student' : 'Add Student'; ?></button>
                            <?php if(isset($edit_student)): ?>
                                <a href="student_management.php" class="btn btn-light"><i class="fas fa-times"></i> Cancel</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Students Table -->
            <div class="card" style="margin-top: 30px;">
                <div class="card-header"><i class="fas fa-list"></i> Students List (<?php echo count($students); ?> records)</div>
                <div class="card-body">
                    <div style="overflow-x: auto;">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Reg. No.</th>
                                    <th>Name</th>
                                    <th>Department</th>
                                    <th>Batch</th>
                                    <th>Semester</th>
                                    <th>Email</th>
                                    <th style="width: 120px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($students) > 0): ?>
                                    <?php foreach($students as $student): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($student['st_id']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($student['st_name']); ?></td>
                                            <td><?php echo htmlspecialchars($student['st_dept']); ?></td>
                                            <td><?php echo $student['st_batch']; ?></td>
                                            <td><?php echo $student['st_sem']; ?></td>
                                            <td><a href="mailto:<?php echo htmlspecialchars($student['st_email']); ?>"><?php echo htmlspecialchars($student['st_email']); ?></a></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="?edit=<?php echo urlencode($student['st_id']); ?>" class="btn-edit"><i class="fas fa-edit"></i> Edit</a>
                                                    <a href="?delete=<?php echo urlencode($student['st_id']); ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" onclick="return confirm('Delete this student?');" class="btn-delete"><i class="fas fa-trash"></i> Delete</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="7" class="text-center text-muted"><i class="fas fa-inbox"></i> No students found</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
