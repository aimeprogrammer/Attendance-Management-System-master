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

// Normalize type to storage columns
function normalize_setting_type(string $type): string {
  $type = strtolower(trim($type));
  if (!in_array($type, ['string','number','boolean','json'], true)) return 'string';
  return $type;
}

// Handle add/edit
if(isset($_POST['action']) && in_array($_POST['action'], ['add', 'edit'], true)) {
  if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $error_msg = "Security validation failed (CSRF).";
  } else {
    try {
      $key_name = htmlspecialchars(trim($_POST['key_name'] ?? ''));
      $type = normalize_setting_type($_POST['setting_type'] ?? 'string');
      $value_text = htmlspecialchars(trim($_POST['value_text'] ?? ''));
      $value_int_raw = trim($_POST['value_int'] ?? '');

      if(empty($key_name)) throw new Exception("Key name is required.");

      $value_int = null;
      $value_text_to_store = null;

      if($type === 'number') {
        if ($value_int_raw === '') throw new Exception("Value (number) is required.");
        $value_int = (int)$value_int_raw;
        $value_text_to_store = null;
      } else {
        // boolean/json/string all go to text
        if ($type === 'boolean') {
          $value_text_to_store = ($value_text === '1' || strtolower($value_text) === 'true') ? 'true' : 'false';
        } else {
          $value_text_to_store = $value_text;
        }
        $value_int = null;
      }

      if($_POST['action'] === 'add') {
        $stmt = mysqli_prepare(
          $link,
          "INSERT INTO system_settings (key_name, value_text, value_int) VALUES (?, ?, ?)"
        );
        if(!$stmt) throw new Exception("DB prepare failed: " . mysqli_error($link));
        mysqli_stmt_bind_param($stmt, "ssi", $key_name, $value_text_to_store, $value_int);
        if(!mysqli_stmt_execute($stmt)) throw new Exception("DB insert failed: " . mysqli_error($link));
        mysqli_stmt_close($stmt);
        $success_msg = "✓ Setting added successfully.";
      } else {
        $old_key = htmlspecialchars(trim($_POST['old_key_name'] ?? ''));
        if(empty($old_key)) throw new Exception("Missing original key name.");

        $stmt = mysqli_prepare(
          $link,
          "UPDATE system_settings SET key_name=?, value_text=?, value_int=? WHERE key_name=?"
        );
        if(!$stmt) throw new Exception("DB prepare failed: " . mysqli_error($link));
        mysqli_stmt_bind_param($stmt, "ssis", $key_name, $value_text_to_store, $value_int, $old_key);
        if(!mysqli_stmt_execute($stmt)) throw new Exception("DB update failed: " . mysqli_error($link));
        mysqli_stmt_close($stmt);
        $success_msg = "✓ Setting updated successfully.";
      }
    } catch(Exception $e) {
      $error_msg = $e->getMessage();
    }
  }
}

// Handle delete
if(isset($_GET['delete']) && isset($_GET['csrf_token']) && $_GET['csrf_token'] === $_SESSION['csrf_token']) {
  $key_name = htmlspecialchars(trim($_GET['delete']));
  if($key_name !== '') {
    $stmt = mysqli_prepare($link, "DELETE FROM system_settings WHERE key_name=?");
    mysqli_stmt_bind_param($stmt, "s", $key_name);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    $success_msg = "✓ Setting deleted successfully.";
  }
}

// Load edit data
$edit = null;
if(isset($_GET['edit'])) {
  $key_name = htmlspecialchars(trim($_GET['edit']));
  $stmt = mysqli_prepare($link, "SELECT key_name, value_text, value_int FROM system_settings WHERE key_name=?");
  mysqli_stmt_bind_param($stmt, "s", $key_name);
  mysqli_stmt_execute($stmt);
  $edit = mysqli_stmt_get_result($stmt)->fetch_assoc();
  mysqli_stmt_close($stmt);
}

// Get all settings (recent first)
$settings = [];
$res = mysqli_query($link, "SELECT key_name, value_text, value_int, updated_at FROM system_settings ORDER BY updated_at DESC, key_name ASC LIMIT 200");
if($res) {
  while($row = mysqli_fetch_assoc($res)) $settings[] = $row;
}

// Derive display type/value for editor
$editorType = 'string';
$editorValueText = '';
$editorValueInt = '';
if($edit) {
  if ($edit['value_int'] !== null) {
    $editorType = 'number';
    $editorValueInt = (string)$edit['value_int'];
  } else {
    // treat as string/json/boolean; simplest: string
    $editorType = 'string';
    $editorValueText = (string)($edit['value_text'] ?? '');
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Settings - Admin Dashboard</title>
  <link rel="stylesheet" type="text/css" href="../css/main.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="dashboard-container">
  <?php $activePage = 'settings.php'; include('sidebar.php'); ?>

  <div class="main-content">
    <div class="top-header">
      <div>
        <h1><i class="fas fa-cog"></i> System Settings</h1>
        <p style="color: var(--muted); margin: 0;">Manage application configuration</p>
      </div>
      <div class="user-menu">
        <span>👨‍💼 Admin</span>
        <a href="../logout.php">Logout</a>
      </div>
    </div>

    <div class="page-content">
      <?php if($success_msg): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_msg); ?></div>
      <?php endif; ?>
      <?php if($error_msg): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_msg); ?></div>
      <?php endif; ?>

      <div class="card">
        <div class="card-header"><i class="fas fa-plus"></i>
          <?php echo ($edit ? 'Edit Setting' : 'Add New Setting'); ?>
        </div>
        <div class="card-body">
          <form method="post" action="">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="action" value="<?php echo ($edit ? 'edit' : 'add'); ?>">
            <?php if($edit): ?>
              <input type="hidden" name="old_key_name" value="<?php echo htmlspecialchars($edit['key_name']); ?>">
            <?php endif; ?>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
              <div class="form-group" style="grid-column: 1 / -1;">
                <label for="key_name">Key name *</label>
                <input type="text" name="key_name" id="key_name" class="form-control"
                       value="<?php echo htmlspecialchars($edit['key_name'] ?? ''); ?>"
                       placeholder="e.g., sms_provider_status" required>
              </div>

              <div class="form-group">
                <label for="setting_type">Type *</label>
                <select name="setting_type" id="setting_type" class="form-control" required>
                  <?php foreach(['string','number','boolean','json'] as $t): ?>
                    <option value="<?php echo $t; ?>" <?php echo ($editorType === $t) ? 'selected' : ''; ?>>
                      <?php echo ucfirst($t); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="form-group">
                <label for="value_int">Number value</label>
                <input type="number" name="value_int" id="value_int" class="form-control"
                       value="<?php echo htmlspecialchars($editorValueInt); ?>"
                       placeholder="only for number type">
              </div>

              <div class="form-group" style="grid-column: 1 / -1;">
                <label for="value_text">Text value</label>
                <input type="text" name="value_text" id="value_text" class="form-control"
                       value="<?php echo htmlspecialchars($editorValueText); ?>"
                       placeholder="only for string/boolean/json type">
              </div>

              <div class="form-group" style="grid-column: 1 / -1; display:flex; gap:12px; align-items:flex-end;">
                <button type="submit" class="btn btn-primary">
                  <i class="fas fa-save"></i>
                  <?php echo ($edit ? 'Update Setting' : 'Add Setting'); ?>
                </button>
                <?php if($edit): ?>
                  <a href="settings.php" class="btn btn-light"><i class="fas fa-times"></i> Cancel</a>
                <?php endif; ?>
              </div>
            </div>
          </form>
        </div>
      </div>

      <div class="card" style="margin-top:30px;">
        <div class="card-header"><i class="fas fa-list"></i> Settings List (<?php echo count($settings); ?>)</div>
        <div class="card-body">
          <div style="overflow-x:auto;">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Key</th>
                  <th>Text</th>
                  <th>Int</th>
                  <th>Updated</th>
                  <th style="width:140px;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if(count($settings) > 0): ?>
                  <?php foreach($settings as $s): ?>
                    <tr>
                      <td><strong><?php echo htmlspecialchars($s['key_name'] ?? ''); ?></strong></td>
                      <td><?php echo htmlspecialchars($s['value_text'] ?? ''); ?></td>
                      <td><?php echo ($s['value_int'] === null) ? '-' : htmlspecialchars((string)$s['value_int']); ?></td>
                      <td><?php echo htmlspecialchars($s['updated_at'] ?? ''); ?></td>
                      <td>
                        <div class="action-buttons">
                          <a href="?edit=<?php echo urlencode($s['key_name']); ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" class="btn-edit">
                            <i class="fas fa-edit"></i> Edit
                          </a>
                          <a href="?delete=<?php echo urlencode($s['key_name']); ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" onclick="return confirm('Delete this setting?');" class="btn-delete">
                            <i class="fas fa-trash"></i> Delete
                          </a>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr><td colspan="5" class="text-center text-muted"><i class="fas fa-inbox"></i> No settings found</td></tr>
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
