<?php
session_start();
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header('location: ../index.php');
    exit;
}
include('connect.php');
$tcId = $_SESSION['tc_id'] ?? '';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
}

// Fetch subjects for the teacher's department to populate the dropdown
$tcDept = '';
$stmt = mysqli_prepare($link, "SELECT tc_dept FROM teachers WHERE tc_id = ?");
mysqli_stmt_bind_param($stmt, "s", $tcId);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $tcDept);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

$subjects = [];
$resSub = mysqli_query($link, "SELECT s.subject_id, s.subject_name, s.subject_code FROM subjects s JOIN programs p ON s.program_id = p.program_id WHERE p.program_id = '$tcDept' OR p.program_name LIKE '%$tcDept%'");
while($row = mysqli_fetch_assoc($resSub)) $subjects[] = $row;

// Fetch batches for targeting
$batches = [];
$resBatch = mysqli_query($link, "SELECT batch_id, batch_name FROM batches");
if ($resBatch) {
    while($row = mysqli_fetch_assoc($resBatch)) $batches[] = $row;
}

// Handle Deletion
if (isset($_GET['delete']) && isset($_GET['csrf_token']) && $_GET['csrf_token'] === $_SESSION['csrf_token']) {
    $matId = intval($_GET['delete']);
    $stmt = mysqli_prepare($link, "DELETE FROM course_materials WHERE material_id = ? AND uploaded_by = ?");
    mysqli_stmt_bind_param($stmt, "is", $matId, $tcId);
    if (mysqli_stmt_execute($stmt)) {
        $log_action = "Deleted course material ID: $matId";
        $log_stmt = mysqli_prepare($link, "INSERT INTO system_logs (user_id, action) VALUES (?, ?)");
        mysqli_stmt_bind_param($log_stmt, "ss", $tcId, $log_action);
        mysqli_stmt_execute($log_stmt);
        $success_msg = "Material deleted successfully!";
    }
    mysqli_stmt_close($stmt);
}

// Handle Upload
if (isset($_POST['upload_material'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_msg = "Security validation failed (CSRF).";
    } else {
    $title = htmlspecialchars($_POST['title']);
    $subject_id = $_POST['subject_id'];
    $ext_url = htmlspecialchars($_POST['external_url']);
    $target_batch = !empty($_POST['batch_id']) ? $_POST['batch_id'] : NULL;
    
    $file_path = NULL;
    if (!empty($_FILES['material_file']['name'])) {
        $target_dir = "../uploads/materials/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $file_path = "uploads/materials/" . time() . "_" . basename($_FILES["material_file"]["name"]);
        move_uploaded_file($_FILES["material_file"]["tmp_name"], "../" . $file_path);
    }

    $stmt = mysqli_prepare($link, "INSERT INTO course_materials (subject_id, batch_id, title, file_path, external_url, uploaded_by) VALUES (?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "ssssss", $subject_id, $target_batch, $title, $file_path, $ext_url, $tcId);
    
    if (mysqli_stmt_execute($stmt)) {
        // Log activity
        $log_action = "Uploaded new material: $title for Subject ID: $subject_id";
        $log_stmt = mysqli_prepare($link, "INSERT INTO system_logs (user_id, action) VALUES (?, ?)");
        mysqli_stmt_bind_param($log_stmt, "ss", $tcId, $log_action);
        mysqli_stmt_execute($log_stmt);
        $success_msg = "Material uploaded successfully!";
    }
    mysqli_stmt_close($stmt);
    }
}

// Fetch existing materials
$myMaterials = [];
$resM = mysqli_query($link, "SELECT m.*, s.subject_name FROM course_materials m JOIN subjects s ON m.subject_id = s.subject_id WHERE m.uploaded_by = '$tcId' ORDER BY m.uploaded_at DESC");
while($row = mysqli_fetch_assoc($resM)) $myMaterials[] = $row;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Course Materials - Teacher Dashboard</title>
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="dashboard-container">
    <div class="main-content" style="margin-left:0; width:100%;">
        <div class="top-header">
            <h1><i class="fas fa-folder-open"></i> Manage Course Materials</h1>
            <a href="index.php" class="btn btn-light">Back to Dashboard</a>
        </div>
        <div class="page-content">
            <?php if(isset($success_msg)) echo "<div class='alert alert-success'>$success_msg</div>"; ?>
            
            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px;">
                <!-- Upload Form -->
                <div class="card">
                    <div class="card-header">Upload New Resource</div>
                    <div class="card-body">
                        <form method="post" enctype="multipart/form-data">
                            <div class="form-group">
                                <label>Resource Title</label>
                                <input type="text" name="title" class="form-control" required placeholder="e.g. Week 1 Lecture Notes">
                            </div>
                            <div class="form-group">
                                <label>Subject</label>
                                <select name="subject_id" class="form-control" required>
                                    <?php foreach($subjects as $s): ?>
                                        <option value="<?php echo $s['subject_id']; ?>"><?php echo $s['subject_code'] . " - " . $s['subject_name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>File (Optional)</label>
                                <input type="file" name="material_file" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Link URL (Optional)</label>
                                <input type="url" name="external_url" class="form-control" placeholder="https://youtube.com/...">
                            </div>
                            <button type="submit" name="upload_material" class="btn btn-primary btn-block">Share with Students</button>
                        </form>
                    </div>
                </div>

                <!-- Materials List -->
                <div class="card">
                    <div class="card-header">Your Uploaded Resources</div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Subject</th>
                                    <th>Resource</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($myMaterials as $m): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($m['title']); ?></td>
                                    <td><?php echo htmlspecialchars($m['subject_name']); ?></td>
                                    <td>
                                        <?php if($m['file_path']): ?>
                                            <a href="../<?php echo $m['file_path']; ?>" target="_blank" class="btn btn-sm btn-info"><i class="fas fa-file-download"></i> File</a>
                                        <?php endif; ?>
                                        <?php if($m['external_url']): ?>
                                            <a href="<?php echo $m['external_url']; ?>" target="_blank" class="btn btn-sm btn-secondary"><i class="fas fa-link"></i> URL</a>
                                        <?php endif; ?>
                                    </td>
                                    <td><small><?php echo date('M d, Y', strtotime($m['uploaded_at'])); ?></small></td>
                                </tr>
                                <?php endforeach; ?>
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