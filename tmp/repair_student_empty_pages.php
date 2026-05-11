<?php
declare(strict_types=1);

/**
 * Repairs empty/broken student dashboard pages by inserting a minimal,
 * consistent student portal UI placeholder.
 *
 * Safety rules:
 * - Only modifies files that are "empty-like" using heuristics:
 *   * too small
 *   * missing DOCTYPE/html tags
 * - Never overwrites files that already contain a full HTML document.
 */

function isEmptyLike(string $content): bool {
  $len = strlen($content);

  // If already looks like a valid HTML page, don't touch it.
  $hasDoctype = stripos($content, '<!DOCTYPE html>') !== false;
  $hasHtmlClose = stripos($content, '</html>') !== false;
  if ($hasDoctype && $hasHtmlClose) return false;

  // Heuristic: extremely short or only PHP boilerplate.
  if ($len < 400) return true;

  // If content has almost nothing but PHP tags/comments.
  $nonPhp = preg_replace('/<\?(php)?[\s\S]*?\?>/i', '', $content);
  if ($nonPhp === null) return false;
  $nonPhpLen = strlen(trim($nonPhp));
  if ($nonPhpLen < 50 && $len < 2500) return true;

  return false;
}

function getPlaceholder(string $title = 'Student Portal'): string {
  // NOTE: No gradients; keep consistent with existing brutalist/flat UI.
  // Uses main.css + font-awesome like other student pages.
  return <<<HTML
<?php
ob_start();
session_start();

// If not logged in as student, redirect to login.
if (empty(\$_SESSION['role']) || \$_SESSION['role'] !== 'student') {
  header('location: ../index.php');
  exit;
}

if (empty(\$_SESSION['csrf_token'])) {
  \$_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
}

\$stId = \$_SESSION['st_id'] ?? '';
if (\$stId === '') {
  \$stId = 'Student';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{$title} - Attendance Management</title>
  <link rel="stylesheet" type="text/css" href="../css/main.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="dashboard-container">
  <aside class="sidebar">
    <div class="sidebar-header">
      <div class="sidebar-logo">
        <i class="fas fa-graduation-cap"></i>
        <span>Student Portal</span>
      </div>
    </div>
    <ul class="sidebar-menu">
      <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>

      <li style="margin-top: 15px; padding: 0 15px; font-size: 11px; color: rgba(255,255,255,0.6); font-weight: bold;">ACADEMICS</li>
      <li><a href="academics.php"><i class="fas fa-book-open"></i> Academic Information</a></li>
      <li><a href="exams_results.php"><i class="fas fa-sticky-note"></i> Exam & Results</a></li>
      <li><a href="attendance_report_page.php"><i class="fas fa-calendar-check"></i> Attendance</a></li>
      <li><a href="payments.php"><i class="fas fa-credit-card"></i> Payment & Fees</a></li>
      <li><a href="notices.php"><i class="fas fa-bullhorn"></i> Notices & Announcements</a></li>
      <li><a href="communications.php"><i class="fas fa-comments"></i> Communications</a></li>

      <li style="margin-top: 15px; padding: 0 15px; font-size: 11px; color: rgba(255,255,255,0.6); font-weight: bold;">MY RECORDS</li>
      <li><a href="account.php"><i class="fas fa-user-circle"></i> My Profile</a></li>
      <li><a href="report.php"><i class="fas fa-chart-bar"></i> Attendance Report</a></li>
      <li><a href="students.php"><i class="fas fa-users"></i> Class Directory</a></li>
      <li><a href="leave.php"><i class="fas fa-calendar"></i> Leave Requests</a></li>

      <li style="margin-top: 15px; padding: 0 15px; font-size: 11px; color: rgba(255,255,255,0.6); font-weight: bold;">NOTIFICATIONS</li>
      <li><a href="notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>

      <li><hr style="margin: 15px 0; border: none; border-top: 1px solid rgba(255,255,255,0.1);"></li>
      <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
  </aside>

  <div class="main-content">
    <div class="top-header">
      <div>
        <h1><i class="fas fa-circle-info"></i> {$title}</h1>
        <p style="color: var(--muted); margin: 0;">This page was empty and has been auto-repaired.</p>
      </div>
      <div class="user-menu">
        <span>👨‍🎓 <?= htmlspecialchars(\$stId) ?></span>
        <a href="../logout.php">Logout</a>
      </div>
    </div>

    <div class="page-content">
      <div class="card">
        <div class="card-header"><i class="fas fa-wand-magic-sparkles"></i> Page Auto-Repair</div>
        <div class="card-body">
          <div class="alert alert-warning">
            This student dashboard page did not contain valid UI markup.
            A placeholder UI has been inserted so the page is no longer empty.
          </div>

          <div class="stats-grid">
            <div class="stat-card info">
              <div class="stat-icon"><i class="fas fa-bolt"></i></div>
              <div class="stat-content">
                <div class="stat-label">Status</div>
                <div class="stat-value">Repaired</div>
              </div>
            </div>

            <div class="stat-card success">
              <div class="stat-icon"><i class="fas fa-check"></i></div>
              <div class="stat-content">
                <div class="stat-label">Next</div>
                <div class="stat-value">UI Integration</div>
              </div>
            </div>
          </div>

          <div style="margin-top: 16px; display:flex; gap:12px; flex-wrap:wrap;">
            <a class="btn btn-primary" href="dashboard.php"><i class="fas fa-home"></i> Back to Dashboard</a>
            <button class="btn btn-secondary" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>
</body>
</html>
HTML;
}

$baseDir = __DIR__ . '/../student';
$pattern = $baseDir . '/*.php';

$files = glob($pattern);
if ($files === false) {
  echo "No student pages found.\n";
  exit(0);
}

$modified = [];

foreach ($files as $filePath) {
  $content = file_get_contents($filePath);
  if (!is_string($content)) continue;

  // Never touch the DB connector/helper files.
  $basename = basename($filePath);
  if ($basename === 'connect.php') continue;

  if (!isEmptyLike($content)) continue;

  // Title heuristic: use filename.
  $name = basename($filePath, '.php');
  $title = ucwords(str_replace('_', ' ', $name));

  $placeholder = getPlaceholder($title);

  // Backup
  $backupPath = $filePath . '.bak_' . date('Ymd_His');
  @copy($filePath, $backupPath);

  file_put_contents($filePath, $placeholder);
  $modified[] = basename($filePath);
}

echo "Repair completed.\n";
echo "Modified pages: " . (count($modified) ? implode(', ', $modified) : 'none') . "\n";
echo "Total student pages scanned: " . count($files) . "\n";
?>
