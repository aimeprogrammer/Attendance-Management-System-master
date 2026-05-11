<?php
require __DIR__ . '/../connect.php';

mysqli_query($link, "SET FOREIGN_KEY_CHECKS=0");

$subjectsSql = <<<SQL
CREATE TABLE IF NOT EXISTS `subjects` (
  `subject_id` varchar(30) NOT NULL,
  `program_id` varchar(30) NOT NULL,
  `subject_code` varchar(20) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  PRIMARY KEY (`subject_id`),
  UNIQUE KEY `uq_subject_code` (`subject_code`),
  KEY `program_id` (`program_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SQL;

$batchSubjectsSql = <<<SQL
CREATE TABLE IF NOT EXISTS `batch_subjects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `batch_id` varchar(30) NOT NULL,
  `subject_id` varchar(30) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_batch_subject` (`batch_id`, `subject_id`),
  KEY `batch_id` (`batch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SQL;

foreach ([
  'subjects' => $subjectsSql,
  'batch_subjects' => $batchSubjectsSql,
] as $name => $sql) {
  if (!mysqli_query($link, $sql)) {
    echo "FAIL {$name}: " . mysqli_error($link) . PHP_EOL;
    exit(1);
  }
  echo "OK {$name}" . PHP_EOL;
}

echo "Migration 003_subjects completed." . PHP_EOL;
