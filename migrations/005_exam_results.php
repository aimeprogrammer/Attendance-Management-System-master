<?php
require __DIR__ . '/../connect.php';

mysqli_query($link, "SET FOREIGN_KEY_CHECKS=0");

$examSubjectsSql = <<<SQL
CREATE TABLE IF NOT EXISTS `exam_subjects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `exam_id` varchar(30) NOT NULL,
  `subject_id` varchar(30) NOT NULL,
  `mcq_allocation` decimal(10,2) NOT NULL DEFAULT 0,
  `written_allocation` decimal(10,2) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_exam_subject` (`exam_id`, `subject_id`),
  KEY `exam_id` (`exam_id`),
  KEY `subject_id` (`subject_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SQL;

$marksEntriesSql = <<<SQL
CREATE TABLE IF NOT EXISTS `marks_entries` (
  `entry_id` int(11) NOT NULL AUTO_INCREMENT,
  `exam_id` varchar(30) NOT NULL,
  `subject_id` varchar(30) NOT NULL,
  `st_id` varchar(20) NOT NULL,
  `mcq_obtained` decimal(10,2) NOT NULL DEFAULT 0,
  `written_obtained` decimal(10,2) NOT NULL DEFAULT 0,
  `total_obtained` decimal(10,2) NOT NULL DEFAULT 0,
  `grading` varchar(20) DEFAULT NULL,
  `entered_by` varchar(20) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`entry_id`),
  UNIQUE KEY `uq_entry` (`exam_id`, `subject_id`, `st_id`),
  KEY `exam_id` (`exam_id`),
  KEY `subject_id` (`subject_id`),
  KEY `st_id` (`st_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SQL;

$resultsPublishSql = <<<SQL
CREATE TABLE IF NOT EXISTS `results_publish` (
  `publish_id` int(11) NOT NULL AUTO_INCREMENT,
  `exam_id` varchar(30) NOT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `published` tinyint(1) NOT NULL DEFAULT 0,
  `published_by` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`publish_id`),
  UNIQUE KEY `uq_exam_publish` (`exam_id`),
  KEY `exam_id` (`exam_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SQL;

foreach ([
  'exam_subjects' => $examSubjectsSql,
  'marks_entries' => $marksEntriesSql,
  'results_publish' => $resultsPublishSql,
] as $name => $sql) {
  if (!mysqli_query($link, $sql)) {
    echo "FAIL {$name}: " . mysqli_error($link) . PHP_EOL;
    exit(1);
  }
  echo "OK {$name}" . PHP_EOL;
}

echo "Migration 005_exam_results completed." . PHP_EOL;
