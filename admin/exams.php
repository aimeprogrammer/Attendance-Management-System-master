<?php
ob_start();
session_start();

if(!isset($_SESSION['name']) || $_SESSION['name']!='oasis') {
  header('location: ../index.php');
  exit;
}

if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
}

include('connect.php');

$error_msg = '';
$success_msg = '';

// ===== EXAM CATEGORIES CRUD =====
if(isset($_POST['action']) && in_array($_POST['action'], ['add_category', 'edit_category'], true)) {
  if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $error_msg = "Security validation failed (CSRF).";
  } else {
    try {
      $cat_id = isset($_POST['exam_category_id']) ? htmlspecialchars(trim($_POST['exam_category_id'])) : '';
      $cat_name = htmlspecialchars(trim($_POST['exam_category_name'] ?? ''));

      if(empty($cat_id) || empty($cat_name)) throw new Exception("All fields are required.");

      if($_POST['action'] === 'add_category') {
        $check = mysqli_prepare($link, "SELECT exam_category_id FROM exam_categories WHERE exam_category_id=?");
        if(!$check) throw new Exception("DB prepare failed: ".mysqli_error($link));
        mysqli_stmt_bind_param($check, "s", $cat_id);
        mysqli_stmt_execute($check);
        $res = mysqli_stmt_get_result($check);
        mysqli_stmt_close($check);

        if($res && mysqli_fetch_assoc($res)) {
          throw new Exception("Exam Category ID already exists.");
        }

        $stmt = mysqli_prepare($link, "INSERT INTO exam_categories (exam_category_id, exam_category_name) VALUES (?, ?)");
        if(!$stmt) throw new Exception("DB prepare failed: ".mysqli_error($link));
        mysqli_stmt_bind_param($stmt, "ss", $cat_id, $cat_name);
        if(!mysqli_stmt_execute($stmt)) throw new Exception("DB insert failed: ".mysqli_error($link));
        mysqli_stmt_close($stmt);
        $success_msg = "✓ Exam category added successfully.";
      } else {
        $category_pk = intval($_POST['category_pk'] ?? 0);
        if($category_pk <= 0) throw new Exception("Invalid category id.");

        $stmt = mysqli_prepare($link, "UPDATE exam_categories SET exam_category_name=?, exam_category_id=? WHERE exam_category_id=?");
        if(!$stmt) throw new Exception("DB prepare failed: ".mysqli_error($link));
        // Update by current id (category_pk contains id string in UI hidden field? We'll instead use exam_category_id in POST)
        // Safer: use original id from category_pk as original id string
        $orig_id = htmlspecialchars(trim($_POST['category_original_id'] ?? ''));
        if(empty($orig_id)) throw new Exception("Missing original exam_category_id.");

        mysqli_stmt_close($stmt);
        $stmt = mysqli_prepare($link, "UPDATE exam_categories SET exam_category_name=?, exam_category_id=? WHERE exam_category_id=?");
        if(!$stmt) throw new Exception("DB prepare failed: ".mysqli_error($link));
        mysqli_stmt_bind_param($stmt, "sss", $cat_name, $cat_id, $orig_id);
        if(!mysqli_stmt_execute($stmt)) throw new Exception("DB update failed: ".mysqli_error($link));
        mysqli_stmt_close($stmt);

        $success_msg = "✓ Exam category updated successfully.";
      }
    } catch(Exception $e) {
      $error_msg = $e->getMessage();
    }
  }
}

// Delete category
if(isset($_GET['delete_category']) && isset($_GET['csrf_token']) && $_GET['csrf_token'] === $_SESSION['csrf_token']) {
  $delete_id = htmlspecialchars(trim($_GET['delete_category']));
  if(!empty($delete_id)) {
    $stmt = mysqli_prepare($link, "DELETE FROM exam_categories WHERE exam_category_id=?");
    mysqli_stmt_bind_param($stmt, "s", $delete_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    $success_msg = "✓ Exam category deleted successfully.";
  }
}

// Load category edit data
$edit_category = null;
if(isset($_GET['edit_category'])) {
  $category_id = htmlspecialchars(trim($_GET['edit_category']));
  $stmt = mysqli_prepare($link, "SELECT * FROM exam_categories WHERE exam_category_id=?");
  mysqli_stmt_bind_param($stmt, "s", $category_id);
  mysqli_stmt_execute($stmt);
  $edit_category = mysqli_stmt_get_result($stmt)->fetch_assoc();
  mysqli_stmt_close($stmt);
}

// List categories
$categories = [];
$cat_result = mysqli_query($link, "SELECT * FROM exam_categories ORDER BY exam_category_name ASC");
if($cat_result) {
  while($row = mysqli_fetch_assoc($cat_result)) $categories[] = $row;
}

// ===== EXAMS CRUD =====
if(isset($_POST['action']) && in_array($_POST['action'], ['add_exam', 'edit_exam'], true)) {
  if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $error_msg = "Security validation failed (CSRF).";
  } else {
    try {
      $exam_id = htmlspecialchars(trim($_POST['exam_id'] ?? ''));
      $exam_category_id = htmlspecialchars(trim($_POST['exam_category_id'] ?? ''));
      $program_id = htmlspecialchars(trim($_POST['program_id'] ?? ''));
      $batch_id = htmlspecialchars(trim($_POST['batch_id'] ?? ''));
      $exam_name = htmlspecialchars(trim($_POST['exam_name'] ?? ''));
      $exam_date = htmlspecialchars(trim($_POST['exam_date'] ?? ''));

      $mcq_marks = isset($_POST['mcq_marks']) ? floatval($_POST['mcq_marks']) : 0;
      $written_marks = isset($_POST['written_marks']) ? floatval($_POST['written_marks']) : 0;

      if(empty($exam_id) || empty($exam_category_id) || empty($program_id) || empty($batch_id) || empty($exam_name) || empty($exam_date)) {
        throw new Exception("All exam fields are required.");
      }

      if($_POST['action'] === 'add_exam') {
        $check = mysqli_prepare($link, "SELECT exam_id FROM exams WHERE exam_id=?");
        mysqli_stmt_bind_param($check, "s", $exam_id);
        mysqli_stmt_execute($check);
        $res = mysqli_stmt_get_result($check);
        mysqli_stmt_close($check);

        if($res && mysqli_fetch_assoc($res)) throw new Exception("Exam ID already exists.");

        $stmt = mysqli_prepare($link, "INSERT INTO exams (exam_id, exam_category_id, program_id, batch_id, exam_name, exam_date, mcq_marks, written_marks) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if(!$stmt) throw new Exception("DB prepare failed: ".mysqli_error($link));
        mysqli_stmt_bind_param($stmt, "ssssssdd", $exam_id, $exam_category_id, $program_id, $batch_id, $exam_name, $exam_date, $mcq_marks, $written_marks);
        if(!mysqli_stmt_execute($stmt)) throw new Exception("DB insert failed: ".mysqli_error($link));
        mysqli_stmt_close($stmt);

        $success_msg = "✓ Exam added successfully.";
      } else {
        $orig_exam_id = htmlspecialchars(trim($_POST['exam_original_id'] ?? ''));
        if(empty($orig_exam_id)) throw new Exception("Missing original exam id.");

        $stmt = mysqli_prepare($link, "UPDATE exams SET exam_category_id=?, program_id=?, batch_id=?, exam_name=?, exam_date=?, mcq_marks=?, written_marks=?, exam_id=? WHERE exam_id=?");
        if(!$stmt) throw new Exception("DB prepare failed: ".mysqli_error($link));
        mysqli_stmt_bind_param($stmt, "ssssddss", $exam_category_id, $program_id, $batch_id, $exam_name, $exam_date, $mcq_marks, $written_marks, $exam_id, $orig_exam_id);
        if(!mysqli_stmt_execute($stmt)) throw new Exception("DB update failed: ".mysqli_error($link));
        mysqli_stmt_close($stmt);

        $success_msg = "✓ Exam updated successfully.";
      }
    } catch(Exception $e) {
      $error_msg = $e->getMessage();
    }
  }
}

// Delete exam
if(isset($_GET['delete_exam']) && isset($_GET['csrf_token']) && $_GET['csrf_token'] === $_SESSION['csrf_token']) {
  $delete_id = htmlspecialchars(trim($_GET['delete_exam']));
  if(!empty($delete_id)) {
    $stmt = mysqli_prepare($link, "DELETE FROM exams WHERE exam_id=?");
    mysqli_stmt_bind_param($stmt, "s", $delete_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    $success_msg = "✓ Exam deleted successfully.";
  }
}

// Load exam edit data
$edit_exam = null;
if(isset($_GET['edit_exam'])) {
  $exam_id = htmlspecialchars(trim($_GET['edit_exam']));
  $stmt = mysqli_prepare($link, "SELECT * FROM exams WHERE exam_id=?");
  mysqli_stmt_bind_param($stmt, "s", $exam_id);
  mysqli_stmt_execute($stmt);
  $edit_exam = mysqli_stmt_get_result($stmt)->fetch_assoc();
  mysqli_stmt_close($stmt);
}

// Dropdown data
$programs = [];
$program_result = mysqli_query($link, "SELECT program_id, program_name FROM programs ORDER BY program_name");
while($r = mysqli_fetch_assoc($program_result)) $programs[] = $r;

$batches = [];
$batch_result = mysqli_query($link, "SELECT batch_id, batch_name, program_id FROM batches ORDER BY batch_name ASC");
while($r = mysqli_fetch_assoc($batch_result)) $batches[] = $r;

$exam_categories = [];
$ec_result = mysqli_query($link, "SELECT exam_category_id, exam_category_name FROM exam_categories ORDER BY exam_category_name ASC");
while($r = mysqli_fetch_assoc($ec_result)) $exam_categories[] = $r;

$exams = [];
$exam_list_result = mysqli_query($link, "
  SELECT e.*, c.exam_category_name, p.program_name, b.batch_name
  FROM exams e
  LEFT JOIN exam_categories c ON e.exam_category_id=c.exam_category_id
  LEFT JOIN programs p ON e.program_id=p.program_id
  LEFT JOIN batches b ON e.batch_id=b.batch_id
  ORDER BY e.exam_date DESC, e.exam_name ASC
");
if($exam_list_result) {
  while($r = mysqli_fetch_assoc($exam_list_result)) $exams[] = $r;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Exams - Admin Dashboard</title>
  <link rel="stylesheet" type="text/css" href="../css/main.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .grid-2 { display:grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .action-buttons { display:flex; gap:10px; flex-wrap:wrap; }
    .btn-edit { background:#3498db; color:white; padding:6px 12px; border-radius:4px; text-decoration:none; font-size:12px; }
    .btn-delete { background:#e74c3c; color:white; padding:6px 12px; border-radius:4px; text-decoration:none; font-size:12px; }
    .btn-light { background:#f4f6f8; color:#1f2937; padding:6px 12px; border-radius:4px; text-decoration:none; font-size:12px; border:1px solid #e5e7eb; }
    .btn-primary { background:#3498db; color:white; padding:10px 14px; border-radius:6px; border:none; cursor:pointer; }
  </style>
</head>
<body>
<div class="dashboard-container">
  <?php $activePage = 'exams.php'; include('sidebar.php'); ?>
  <div class="main-content">
    <div class="top-header">
      <div>
        <h1><i class="fas fa-pencil-alt"></i> Exams</h1>
        <p style="color: var(--muted); margin: 0;">Manage exam categories and exams</p>
      </div>
      <div class="user-menu">
        <span>👨‍💼 Admin</span>
        <a href="../logout.php">Logout</a>
      </div>
    </div>

    <div class="page-content">
      <?php if(isset($success_msg) && $success_msg): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success_msg; ?></div>
      <?php endif; ?>
      <?php if(isset($error_msg) && $error_msg): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?></div>
      <?php endif; ?>

      <!-- Exam Categories -->
      <div class="card">
        <div class="card-header"><i class="fas fa-tag"></i> Exam Categories</div>
        <div class="card-body">
          <form method="post" action="">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="action" value="<?php echo isset($edit_category) && $edit_category ? 'edit_category' : 'add_category'; ?>">
            <?php if(isset($edit_category) && $edit_category): ?>
              <input type="hidden" name="category_original_id" value="<?php echo htmlspecialchars($edit_category['exam_category_id']); ?>">
            <?php endif; ?>
            <div class="grid-2">
              <div class="form-group">
                <label>Category ID</label>
                <input type="text" name="exam_category_id" class="form-control" required value="<?php echo htmlspecialchars($edit_category['exam_category_id'] ?? ''); ?>" <?php echo (isset($edit_category) && $edit_category) ? 'readonly' : ''; ?>>
              </div>
              <div class="form-group">
                <label>Category Name</label>
                <input type="text" name="exam_category_name" class="form-control" required value="<?php echo htmlspecialchars($edit_category['exam_category_name'] ?? ''); ?>">
              </div>
            </div>

            <div style="margin-top:15px; display:flex; gap:12px; justify-content:flex-end;">
              <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo (isset($edit_category) && $edit_category) ? 'Update Category' : 'Add Category'; ?></button>
              <?php if(isset($edit_category) && $edit_category): ?>
                <a href="exams.php" class="btn btn-light"><i class="fas fa-times"></i> Cancel</a>
              <?php endif; ?>
            </div>
          </form>

          <div style="margin-top:25px;">
            <div class="card-header" style="background:transparent; padding:0;"><i class="fas fa-list"></i> Categories List (<?php echo count($categories); ?>)</div>
            <div style="overflow-x:auto; margin-top:10px;">
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th style="width:170px;">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach($categories as $c): ?>
                    <tr>
                      <td><strong><?php echo htmlspecialchars($c['exam_category_id']); ?></strong></td>
                      <td><?php echo htmlspecialchars($c['exam_category_name']); ?></td>
                      <td>
                        <div class="action-buttons">
                          <a class="btn-edit" href="?edit_category=<?php echo urlencode($c['exam_category_id']); ?>"><i class="fas fa-edit"></i> Edit</a>
                          <a class="btn-delete" href="?delete_category=<?php echo urlencode($c['exam_category_id']); ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" onclick="return confirm('Delete this category?');"><i class="fas fa-trash"></i> Delete</a>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                  <?php if(count($categories)===0): ?>
                    <tr><td colspan="3" class="text-center text-muted">No categories found</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- Exams -->
      <div class="card" style="margin-top:30px;">
        <div class="card-header"><i class="fas fa-list"></i> Exams</div>
        <div class="card-body">
          <form method="post" action="">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="action" value="<?php echo isset($edit_exam) && $edit_exam ? 'edit_exam' : 'add_exam'; ?>">
            <?php if(isset($edit_exam) && $edit_exam): ?>
              <input type="hidden" name="exam_original_id" value="<?php echo htmlspecialchars($edit_exam['exam_id']); ?>">
            <?php endif; ?>

            <div class="grid-2">
              <div class="form-group">
                <label>Exam ID</label>
                <input type="text" name="exam_id" class="form-control" required value="<?php echo htmlspecialchars($edit_exam['exam_id'] ?? ''); ?>" <?php echo (isset($edit_exam) && $edit_exam) ? 'readonly' : ''; ?>>
              </div>
              <div class="form-group">
                <label>Exam Category</label>
                <select name="exam_category_id" class="form-control" required>
                  <option value="">-- Select Category --</option>
                  <?php foreach($exam_categories as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat['exam_category_id']); ?>" <?php echo (($edit_exam['exam_category_id'] ?? '') === $cat['exam_category_id']) ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($cat['exam_category_name']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="form-group">
                <label>Program</label>
                <select name="program_id" class="form-control" required>
                  <option value="">-- Select Program --</option>
                  <?php foreach($programs as $p): ?>
                    <option value="<?php echo htmlspecialchars($p['program_id']); ?>" <?php echo (($edit_exam['program_id'] ?? '') === $p['program_id']) ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($p['program_name']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="form-group">
                <label>Batch</label>
                <select name="batch_id" class="form-control" required>
                  <option value="">-- Select Batch --</option>
                  <?php foreach($batches as $b): ?>
                    <option value="<?php echo htmlspecialchars($b['batch_id']); ?>" <?php echo (($edit_exam['batch_id'] ?? '') === $b['batch_id']) ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($b['batch_name']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="form-group" style="grid-column:1/-1;">
                <label>Exam Name</label>
                <input type="text" name="exam_name" class="form-control" required value="<?php echo htmlspecialchars($edit_exam['exam_name'] ?? ''); ?>">
              </div>

              <div class="form-group">
                <label>Exam Date</label>
                <input type="date" name="exam_date" class="form-control" required value="<?php echo htmlspecialchars($edit_exam['exam_date'] ?? date('Y-m-d')); ?>">
              </div>

              <div class="form-group">
                <label>MCQ Marks</label>
                <input type="number" step="0.01" name="mcq_marks" class="form-control" value="<?php echo htmlspecialchars($edit_exam['mcq_marks'] ?? 0); ?>">
              </div>

              <div class="form-group" style="grid-column:1/-1;">
                <label>Written Marks</label>
                <input type="number" step="0.01" name="written_marks" class="form-control" value="<?php echo htmlspecialchars($edit_exam['written_marks'] ?? 0); ?>">
              </div>
            </div>

            <div style="margin-top:15px; display:flex; gap:12px; justify-content:flex-end;">
              <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo (isset($edit_exam) && $edit_exam) ? 'Update Exam' : 'Add Exam'; ?></button>
              <?php if(isset($edit_exam) && $edit_exam): ?>
                <a href="exams.php" class="btn btn-light"><i class="fas fa-times"></i> Cancel</a>
              <?php endif; ?>
            </div>
          </form>

          <div style="margin-top:25px; overflow-x:auto;">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Exam ID</th>
                  <th>Name</th>
                  <th>Category</th>
                  <th>Program</th>
                  <th>Batch</th>
                  <th>Date</th>
                  <th>MCQ/Written</th>
                  <th style="width:170px;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($exams as $e): ?>
                  <tr>
                    <td><strong><?php echo htmlspecialchars($e['exam_id']); ?></strong></td>
                    <td><?php echo htmlspecialchars($e['exam_name']); ?></td>
                    <td><?php echo htmlspecialchars($e['exam_category_name'] ?? $e['exam_category_id']); ?></td>
                    <td><?php echo htmlspecialchars($e['program_name'] ?? $e['program_id']); ?></td>
                    <td><?php echo htmlspecialchars($e['batch_name'] ?? $e['batch_id']); ?></td>
                    <td><?php echo htmlspecialchars($e['exam_date']); ?></td>
                    <td><?php echo htmlspecialchars($e['mcq_marks']); ?>/<?php echo htmlspecialchars($e['written_marks']); ?></td>
                    <td>
                      <div class="action-buttons">
                        <a class="btn-edit" href="?edit_exam=<?php echo urlencode($e['exam_id']); ?>"><i class="fas fa-edit"></i> Edit</a>
                        <a class="btn-delete" href="?delete_exam=<?php echo urlencode($e['exam_id']); ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" onclick="return confirm('Delete this exam?');"><i class="fas fa-trash"></i> Delete</a>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
                <?php if(count($exams)===0): ?>
                  <tr><td colspan="8" class="text-center text-muted">No exams found</td></tr>
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
