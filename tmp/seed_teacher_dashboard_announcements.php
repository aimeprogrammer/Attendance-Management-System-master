<?php
require_once __DIR__ . '/../connect.php';

$rows = [
  [
    'title' => 'Welcome to the Term',
    'content' => 'Please ensure attendance is marked daily.',
    'announcement_type' => 'announcement',
    'priority' => 'high',
    'visibility' => 'all',
    'published_by' => 'ADMIN',
  ],
  [
    'title' => 'Exam Schedule Update',
    'content' => 'Exams start next week. Check exam dates in Exams section.',
    'announcement_type' => 'notice',
    'priority' => 'medium',
    'visibility' => 'teachers',
    'published_by' => 'ADMIN',
  ],
];

$sql = "INSERT INTO announcements
  (title, content, announcement_type, priority, visibility, published_by, published_date, expiry_date, is_active)
  VALUES (?, ?, ?, ?, ?, ?, NOW(), NULL, 1)";

$stmt = mysqli_prepare($link, $sql);
if (!$stmt) {
  die('Prepare failed: ' . mysqli_error($link));
}

foreach ($rows as $r) {
  mysqli_stmt_bind_param(
    $stmt,
    "ssssss",
    $r['title'],
    $r['content'],
    $r['announcement_type'],
    $r['priority'],
    $r['visibility'],
    $r['published_by']
  );
  mysqli_stmt_execute($stmt);
}

mysqli_stmt_close($stmt);
echo "Inserted " . count($rows) . " announcements.\n";
