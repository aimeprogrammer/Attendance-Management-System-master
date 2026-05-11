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

// Load existing gateway settings (single row, but we’ll tolerate multiple)
$settings = [];
$settings_result = mysqli_query($link, "SELECT * FROM sms_gateway_settings ORDER BY id DESC LIMIT 5");
if ($settings_result) {
  while ($row = mysqli_fetch_assoc($settings_result)) {
    $settings[] = $row;
  }
}

// Handle save settings
if(isset($_POST['action']) && $_POST['action'] === 'save_settings') {
  if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $error_msg = "Security validation failed (CSRF).";
  } else {
    $provider = htmlspecialchars($_POST['provider'] ?? '');
    $api_key = htmlspecialchars($_POST['api_key'] ?? '');
    $sender_id = htmlspecialchars($_POST['sender_id'] ?? '');
    $status = htmlspecialchars($_POST['status'] ?? 'inactive');

    if(empty($provider) || empty($api_key)) {
      $error_msg = "Provider and API key are required.";
    } else {
      $stmt = mysqli_prepare(
        $link,
        "INSERT INTO sms_gateway_settings (provider, api_key, sender_id, status) VALUES (?, ?, ?, ?)"
      );
      if (!$stmt) {
        $error_msg = "DB prepare failed: " . mysqli_error($link);
      } else {
        mysqli_stmt_bind_param($stmt, "ssss", $provider, $api_key, $sender_id, $status);
        if (!mysqli_stmt_execute($stmt)) {
          $error_msg = "DB insert failed: " . mysqli_error($link);
        } else {
          $success_msg = "✓ SMS gateway settings saved.";
        }
        mysqli_stmt_close($stmt);
      }
    }
  }
}

// Handle send test SMS
if(isset($_POST['action']) && $_POST['action'] === 'send_test') {
  if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $error_msg = "Security validation failed (CSRF).";
  } else {
    $recipient_phone = htmlspecialchars($_POST['recipient_phone'] ?? '');
    $message = htmlspecialchars($_POST['message'] ?? '');

    if(empty($recipient_phone) || empty($message)) {
      $error_msg = "Recipient phone and message are required.";
    } else {
      // In this repo we don’t integrate with a real provider; we queue the message.
      $stmt = mysqli_prepare(
        $link,
        "INSERT INTO sms_outbox (recipient_phone, message, status, provider_message_id, sent_at)
         VALUES (?, ?, 'queued', NULL, NULL)"
      );
      if (!$stmt) {
        $error_msg = "DB prepare failed: " . mysqli_error($link);
      } else {
        mysqli_stmt_bind_param($stmt, "ss", $recipient_phone, $message);
        if (!mysqli_stmt_execute($stmt)) {
          $error_msg = "DB insert failed: " . mysqli_error($link);
        } else {
          $success_msg = "✓ Test SMS queued in outbox.";
        }
        mysqli_stmt_close($stmt);
      }
    }
  }
}

// Load recent outbox
$outbox = [];
$outbox_result = mysqli_query(
  $link,
  "SELECT * FROM sms_outbox ORDER BY sms_id DESC LIMIT 50"
);
if ($outbox_result) {
  while ($row = mysqli_fetch_assoc($outbox_result)) {
    $outbox[] = $row;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SMS Gateway - Admin Dashboard</title>
  <link rel="stylesheet" type="text/css" href="../css/main.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .divider { height: 1px; background: rgba(0,0,0,0.08); margin: 20px 0; }
    .btn { cursor: pointer; }
    .badge { display: inline-block; padding: 6px 10px; border-radius: 999px; font-weight: 700; font-size: 12px; border: 2px solid #000; background: #fff; }
    .badge-queued { background: #f39c12; }
    .badge-sent { background: #27ae60; }
    .badge-failed { background: #e74c3c; }
    .badge-pending { background: #95a5a6; }
    .table-wrap { overflow-x: auto; }
    code { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
  </style>
</head>
<body>
<div class="dashboard-container">
  <?php $activePage = 'sms_gateway.php'; include('sidebar.php'); ?>

  <div class="main-content">
    <div class="top-header">
      <div>
        <h1><i class="fas fa-envelope"></i> SMS Gateway</h1>
        <p style="color: var(--muted); margin: 0;">Configure provider settings and queue outbound SMS</p>
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

      <div class="card">
        <div class="card-header"><i class="fas fa-sliders"></i> Gateway Settings</div>
        <div class="card-body">
          <form method="post" action="">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="action" value="save_settings">

            <div class="form-grid-2">
              <div class="form-group">
                <label for="provider">Provider *</label>
                <input type="text" name="provider" id="provider" class="form-control" value="<?php echo htmlspecialchars($settings[0]['provider'] ?? 'custom'); ?>" required>
              </div>

              <div class="form-group">
                <label for="sender_id">Sender ID</label>
                <input type="text" name="sender_id" id="sender_id" class="form-control" value="<?php echo htmlspecialchars($settings[0]['sender_id'] ?? ''); ?>">
              </div>

              <div class="form-group" style="grid-column: 1 / -1;">
                <label for="api_key">API Key *</label>
                <input type="text" name="api_key" id="api_key" class="form-control" value="<?php echo htmlspecialchars($settings[0]['api_key'] ?? ''); ?>" required>
              </div>

              <div class="form-group" style="grid-column: 1 / -1;">
                <label for="status">Status</label>
                <select name="status" id="status" class="form-control">
                  <option value="active" <?php echo (($settings[0]['status'] ?? '') === 'active') ? 'selected' : ''; ?>>Active</option>
                  <option value="inactive" <?php echo (($settings[0]['status'] ?? '') !== 'active') ? 'selected' : ''; ?>>Inactive</option>
                </select>
              </div>
            </div>

            <div style="margin-top: 20px;">
              <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Settings</button>
            </div>
          </form>
        </div>
      </div>

      <div class="divider"></div>

      <div class="card">
        <div class="card-header"><i class="fas fa-paper-plane"></i> Send Test SMS</div>
        <div class="card-body">
          <form method="post" action="">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="action" value="send_test">

            <div class="form-grid-2">
              <div class="form-group">
                <label for="recipient_phone">Recipient Phone *</label>
                <input type="text" name="recipient_phone" id="recipient_phone" class="form-control" placeholder="+27..." required>
              </div>

              <div class="form-group">
                <label for="message_chars">Message</label>
                <input type="text" id="message_chars" class="form-control" placeholder="(preview)"/>
              </div>

              <div class="form-group" style="grid-column: 1 / -1;">
                <label for="message">Message *</label>
                <textarea name="message" id="message" class="form-control" rows="4" placeholder="Type your test message..." required></textarea>
              </div>
            </div>

            <div style="margin-top: 20px;">
              <button type="submit" class="btn btn-primary"><i class="fas fa-bolt"></i> Queue SMS</button>
            </div>
          </form>
        </div>
      </div>

      <div class="divider"></div>

      <div class="card">
        <div class="card-header"><i class="fas fa-list"></i> Outbox (Last 50)</div>
        <div class="card-body">
          <div class="table-wrap">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Recipient</th>
                  <th>Message</th>
                  <th>Status</th>
                  <th>Provider Msg ID</th>
                  <th>Sent At</th>
                  <th>Created</th>
                </tr>
              </thead>
              <tbody>
                <?php if(count($outbox) > 0): ?>
                  <?php foreach($outbox as $row): ?>
                    <?php
                      $status = strtolower($row['status'] ?? 'queued');
                      $badgeClass = 'badge-queued';
                      if ($status === 'sent') $badgeClass = 'badge-sent';
                      if ($status === 'failed') $badgeClass = 'badge-failed';
                      if ($status === 'pending') $badgeClass = 'badge-pending';
                    ?>
                    <tr>
                      <td><strong><?php echo (int)($row['sms_id'] ?? 0); ?></strong></td>
                      <td><?php echo htmlspecialchars($row['recipient_phone'] ?? '-'); ?></td>
                      <td><?php echo htmlspecialchars(substr($row['message'] ?? '', 0, 60)); ?><?php echo (strlen($row['message'] ?? '') > 60) ? '…' : ''; ?></td>
                      <td><span class="badge <?php echo $badgeClass; ?>"><?php echo ucfirst($status); ?></span></td>
                      <td><?php echo htmlspecialchars($row['provider_message_id'] ?? '-'); ?></td>
                      <td><?php echo !empty($row['sent_at']) ? htmlspecialchars($row['sent_at']) : '-'; ?></td>
                      <td><?php echo !empty($row['created_at']) ? htmlspecialchars($row['created_at']) : '-'; ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr><td colspan="7" class="text-center text-muted"><i class="fas fa-inbox"></i> Outbox is empty</td></tr>
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
  (function(){
    const msg = document.getElementById('message');
    const preview = document.getElementById('message_chars');
    if(!msg || !preview) return;
    const update = () => { preview.value = msg.value ? msg.value.slice(0, 40) + (msg.value.length > 40 ? '…' : '') : ''; };
    msg.addEventListener('input', update);
    update();
  })();
</script>
</body>
</html>
