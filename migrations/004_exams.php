<?php
require __DIR__ . '/../connect.php';

mysqli_query($link, "SET FOREIGN_KEY_CHECKS=0");

$examCategoriesSql = <<<SQL
CREATE TABLE IF NOT EXISTS `exam_categories` (
  `exam_category_id` varchar(30) NOT NULL,
  `exam_category_name` varchar(100) NOT NULL,
  PRIMARY KEY (`exam_category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SQL;

$examsSql = <<<SQL
CREATE TABLE IF NOT EXISTS `exams` (
  `exam_id` varchar(30) NOT NULL,
  `exam_category_id` varchar(30) NOT NULL,
  `program_id` varchar(30) NOT NULL,
  `batch_id` varchar(30) NOT NULL,
  `exam_name` varchar(100) NOT NULL,
  `exam_date` date NOT NULL,
  `mcq_marks` decimal(10,2) NOT NULL DEFAULT 0,
  `written_marks` decimal(10,2) NOT NULL DEFAULT 0,
  PRIMARY KEY (`exam_id`),
  KEY `exam_category_id` (`exam_category_id`),
  KEY `program_id` (`program_id`),
  KEY `batch_id` (`batch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SQL;

foreach ([
  'exam_categories' => $examCategoriesSql,
  'exams' => $examsSql,
] as $name => $sql) {
  if (!mysqli_query($link, $sql)) {
    echo "FAIL {$name}: " . mysqli_error($link) . PHP_EOL;
    exit(1);
  }
  echo "OK {$name}" . PHP_EOL;
}

echo "Migration 004_exams completed." . PHP_EOL;
