<?php
require __DIR__ . '/../connect.php';

mysqli_query($link, "SET FOREIGN_KEY_CHECKS=0");

$programsSql = <<<SQL
CREATE TABLE IF NOT EXISTS `programs` (
  `program_id` varchar(30) NOT NULL,
  `program_name` varchar(100) NOT NULL,
  PRIMARY KEY (`program_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SQL;

$feesSql = <<<SQL
CREATE TABLE IF NOT EXISTS `program_fees` (
  `fee_id` int(11) NOT NULL AUTO_INCREMENT,
  `program_id` varchar(30) NOT NULL,
  `fee_type` ENUM('one-time', 'monthly') NOT NULL,
  `fee_amount` decimal(10,2) NOT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'USD',
  PRIMARY KEY (`fee_id`),
  KEY `program_id` (`program_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SQL;

foreach (['programs' => $programsSql, 'program_fees' => $feesSql] as $name => $sql) {
  if (!mysqli_query($link, $sql)) {
    echo "FAIL {$name}: " . mysqli_error($link) . PHP_EOL;
    exit(1);
  }
  echo "OK {$name}" . PHP_EOL;
}

echo "Migration 001_programs completed." . PHP_EOL;
