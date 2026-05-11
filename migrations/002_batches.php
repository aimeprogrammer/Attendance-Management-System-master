<?php
require __DIR__ . '/../connect.php';

mysqli_query($link, "SET FOREIGN_KEY_CHECKS=0");

$batchesSql = <<<SQL
CREATE TABLE IF NOT EXISTS `batches` (
  `batch_id` varchar(30) NOT NULL,
  `program_id` varchar(30) NOT NULL,
  `batch_name` varchar(50) NOT NULL,
  `start_year` int(11) DEFAULT NULL,
  `end_year` int(11) DEFAULT NULL,
  PRIMARY KEY (`batch_id`),
  KEY `program_id` (`program_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SQL;

if (!mysqli_query($link, $batchesSql)) {
  echo "FAIL batches: " . mysqli_error($link) . PHP_EOL;
  exit(1);
}

echo "OK batches" . PHP_EOL;
echo "Migration 002_batches completed." . PHP_EOL;
