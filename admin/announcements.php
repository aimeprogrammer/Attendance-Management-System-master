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

// Handle add/edit announcement
if(isset($_POST['action']) && in_array($_POST['action'], ['add', 'edit'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_msg = "Security validation failed (CSRF).";
    } else {
        try {
            $title = htmlspecialchars($_POST['title']);
            $content = htmlspecialchars($_POST['content']);
            $announcement_type = htmlspecialchars($_POST['announcement_type']);
            $priority = htmlspecialchars($_POST['priority']);
            $visibility = htmlspecialchars($_POST['visibility']);
            $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : NULL;
            $program_id = !empty($_POST['program_id']) ? $_POST['program_id'] : NULL;

            if(empty($title) || empty($content)) {
                throw new Exception("Title and content are required.");
            }

            if($_POST['action'] == 'add') {
                $published_by = 'oasis'; // assuming admin username from session
                $stmt = mysqli_prepare(
                    $link,
                    "INSERT INTO announcements (title, content, announcement_type, priority, visibility, published_by, expiry_date, program_id)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                );
                mysqli_stmt_bind_param(
                    $stmt,
                    "ssssssss",
                    $title,
                    $content,
                    $announcement_type,
                    $priority,
                    $visibility,
                    $published_by,
                    $expiry_date,
                    $program_id
                );
                if (mysqli_stmt_execute($stmt)) {
                    $success_msg = "✓ Announcement posted successfully.";
                }
                mysqli_stmt_close($stmt);
            } else {
                $announcement_id = intval($_POST['announcement_id']);
                $stmt = mysqli_prepare(
                    $link,
                    "UPDATE announcements
                     SET title=?, content=?, announcement_type=?, priority=?, visibility=?, expiry_date=?, program_id=?
                     WHERE announcement_id=?"
                );
                mysqli_stmt_bind_param(
                    $stmt,
                    "sssssssi",
                    $title,
                    $content,
                    $announcement_type,
                    $priority,
                    $visibility,
                    $expiry_date,
                    $program_id,
                    $announcement_id
                );
                if (mysqli_stmt_execute($stmt)) {
                    $success_msg = "✓ Announcement updated successfully.";
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
    $announcement_id = intval($_GET['delete']);
    $stmt = mysqli_prepare($link, "DELETE FROM announcements WHERE announcement_id=?");
    mysqli_stmt_bind_param($stmt, "i", $announcement_id);
    if (mysqli_stmt_execute($stmt)) {
        $success_msg = "✓ Announcement deleted successfully.";
    }
    mysqli_stmt_close($stmt);
}

// Handle publish toggle
if(isset($_GET['toggle_publish']) && isset($_GET['csrf_token']) && $_GET['csrf_token'] === $_SESSION['csrf_token']) {
    $announcement_id = intval($_GET['toggle_publish']);
    $stmt = mysqli_prepare($link, "SELECT is_active FROM announcements WHERE announcement_id=?");
    mysqli_stmt_bind_param($stmt, "i", $announcement_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if($row) {
        $new_status = ($row['is_active']) ? 0 : 1;
        $stmt = mysqli_prepare($link, "UPDATE announcements SET is_active=? WHERE announcement_id=?");
        mysqli_stmt_bind_param($stmt, "ii", $new_status, $announcement_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

// Get edit data
$edit_announcement = null;
if(isset($_GET['edit'])) {
    $announcement_id = intval($_GET['edit']);
    $stmt = mysqli_prepare($link, "SELECT * FROM announcements WHERE announcement_id=?");
    mysqli_stmt_bind_param($stmt, "i", $announcement_id);
    mysqli_stmt_execute($stmt);
    $edit_announcement = mysqli_stmt_get_result($stmt)->fetch_assoc();
    mysqli_stmt_close($stmt);
}

 // Programs list for scoping announcements
$programs = [];
$progRes = mysqli_query($link, "SELECT program_id, program_name FROM programs ORDER BY program_name ASC");
if ($progRes) {
    while ($p = mysqli_fetch_assoc($progRes)) {
        $programs[] = $p;
    }
}

// Get all announcements
$result = mysqli_query($link, "SELECT * FROM announcements ORDER BY published_date DESC LIMIT 100");
$announcements = [];
while($row = mysqli_fetch_assoc($result)) {
    $announcements[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements - Admin Dashboard</title>
    <link rel="stylesheet" type="text/css" href="../css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .action-buttons { display: flex; gap: 10px; }
        .action-buttons a { padding: 6px 12px; font-size: 12px; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; }
        .btn-edit { background: #3498db; color: white; }
        .btn-delete { background: #e74c3c; color: white; }
        .btn-publish { background: #27ae60; color: white; }
        .btn-edit:hover { background: #2980b9; }
        .btn-delete:hover { background: #c0392b; }
        .btn-publish:hover { background: #1e8449; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; margin: 2px; }
        .badge-success { background: #27ae60; color: white; }
        .badge-danger { background: #e74c3c; color: white; }
        .badge-warning { background: #f39c12; color: white; }
        .badge-info { background: #3498db; color: white; }
        .badge-high { background: #e74c3c; color: white; }
        .badge-medium { background: #f39c12; color: white; }
        .badge-low { background: #95a5a6; color: white; }
        textarea { font-family: Arial, sans-serif; font-size: 14px; }
    </style>
</head>
<body>
<div class="dashboard-container">
    <!-- Shared Sidebar Navigation -->
    <?php $activePage = 'announcements.php'; include('sidebar.php'); ?>

    <div class="main-content">
        <div class="top-header">
            <div>
                <h1><i class="fas fa-bullhorn"></i> Announcements & Notices</h1>
                <p style="color: var(--muted); margin: 0;">Post, edit, and manage system announcements</p>
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

            <!-- Add/Edit Announcement Form -->
            <div class="card">
                <div class="card-header"><i class="fas fa-plus"></i> <?php echo isset($edit_announcement) ? 'Edit Announcement' : 'Post New Announcement'; ?></div>
                <div class="card-body">
                    <form method="post" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="action" value="<?php echo isset($edit_announcement) ? 'edit' : 'add'; ?>">
                        <?php if(isset($edit_announcement)): ?>
                            <input type="hidden" name="announcement_id" value="<?php echo $edit_announcement['announcement_id']; ?>">
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="title"><i class="fas fa-heading"></i> Title *</label>
                            <input type="text" name="title" id="title" class="form-control" value="<?php echo $edit_announcement['title'] ?? ''; ?>" placeholder="Announcement title" required />
                        </div>

                        <div class="form-group">
                            <label for="content"><i class="fas fa-file-alt"></i> Content *</label>
                            <textarea name="content" id="content" class="form-control" rows="8" placeholder="Announcement content" required><?php echo $edit_announcement['content'] ?? ''; ?></textarea>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 15px;">
                            <div class="form-group">
                                <label for="program_id"><i class="fas fa-book"></i> Program</label>
                                <select name="program_id" id="program_id" class="form-control">
                                    <option value="" <?php echo empty($edit_announcement['program_id'] ?? '') ? 'selected' : ''; ?>>All Programs (Global)</option>
                                    <?php foreach ($programs as $p): ?>
                                        <option value="<?php echo htmlspecialchars($p['program_id']); ?>" <?php echo (($edit_announcement['program_id'] ?? '') === $p['program_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($p['program_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="announcement_type"><i class="fas fa-tag"></i> Type</label>
                                <select name="announcement_type" id="announcement_type" class="form-control">
                                    <option value="announcement" <?php echo (($edit_announcement['announcement_type'] ?? '') === 'announcement') ? 'selected' : ''; ?>>Announcement</option>
                                    <option value="notice" <?php echo (($edit_announcement['announcement_type'] ?? '') === 'notice') ? 'selected' : ''; ?>>Notice</option>
                                    <option value="alert" <?php echo (($edit_announcement['announcement_type'] ?? '') === 'alert') ? 'selected' : ''; ?>>Alert</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="priority"><i class="fas fa-exclamation-triangle"></i> Priority</label>
                                <select name="priority" id="priority" class="form-control">
                                    <option value="low" <?php echo (($edit_announcement['priority'] ?? '') === 'low') ? 'selected' : ''; ?>>Low</option>
                                    <option value="medium" <?php echo (($edit_announcement['priority'] ?? '') === 'medium') ? 'selected' : ''; ?>>Medium</option>
                                    <option value="high" <?php echo (($edit_announcement['priority'] ?? '') === 'high') ? 'selected' : ''; ?>>High</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="visibility"><i class="fas fa-eye"></i> Visibility</label>
                                <select name="visibility" id="visibility" class="form-control">
                                    <option value="all" <?php echo (($edit_announcement['visibility'] ?? '') === 'all') ? 'selected' : ''; ?>>All Users</option>
                                    <option value="students" <?php echo (($edit_announcement['visibility'] ?? '') === 'students') ? 'selected' : ''; ?>>Students Only</option>
                                    <option value="teachers" <?php echo (($edit_announcement['visibility'] ?? '') === 'teachers') ? 'selected' : ''; ?>>Teachers Only</option>
                                    <option value="admin" <?php echo (($edit_announcement['visibility'] ?? '') === 'admin') ? 'selected' : ''; ?>>Admin Only</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="expiry_date"><i class="fas fa-calendar"></i> Expiry Date (Optional)</label>
                                <input type="date" name="expiry_date" id="expiry_date" class="form-control" value="<?php echo $edit_announcement['expiry_date'] ?? ''; ?>" />
                            </div>
                        </div>

                        <div style="margin-top: 20px;">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo isset($edit_announcement) ? 'Update' : 'Post'; ?> Announcement</button>
                            <?php if(isset($edit_announcement)): ?>
                                <a href="announcements.php" class="btn btn-light"><i class="fas fa-times"></i> Cancel</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Announcements Table -->
            <div class="card" style="margin-top: 30px;">
                <div class="card-header"><i class="fas fa-list"></i> All Announcements (<?php echo count($announcements); ?> total)</div>
                <div class="card-body">
                    <div style="overflow-x: auto;">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Type</th>
                                    <th>Priority</th>
                                    <th>Visibility</th>
                                    <th>Posted</th>
                                    <th>Expiry</th>
                                    <th>Status</th>
                                    <th style="width: 140px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($announcements) > 0): ?>
                                    <?php foreach($announcements as $ann): ?>
                                        <tr>
                                            <td><strong><?php echo substr(htmlspecialchars($ann['title']), 0, 50); ?>...</strong></td>
                                            <td><span class="badge badge-info"><?php echo ucfirst($ann['announcement_type']); ?></span></td>
                                            <td><span class="badge badge-<?php echo $ann['priority']; ?>"><?php echo ucfirst($ann['priority']); ?></span></td>
                                            <td><span class="badge badge-info"><?php echo ucfirst($ann['visibility']); ?></span></td>
                                            <td><?php echo date('M d, Y', strtotime($ann['published_date'])); ?></td>
                                            <td><?php echo $ann['expiry_date'] ? date('M d, Y', strtotime($ann['expiry_date'])) : 'No limit'; ?></td>
                                            <td><span class="badge <?php echo $ann['is_active'] ? 'badge-success' : 'badge-danger'; ?>"><?php echo $ann['is_active'] ? 'Active' : 'Inactive'; ?></span></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="?edit=<?php echo $ann['announcement_id']; ?>" class="btn-edit"><i class="fas fa-edit"></i> Edit</a>
                                                    <a href="?delete=<?php echo $ann['announcement_id']; ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" onclick="return confirm('Delete this announcement?');" class="btn-delete"><i class="fas fa-trash"></i> Delete</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="8" class="text-center text-muted"><i class="fas fa-inbox"></i> No announcements posted</td></tr>
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
