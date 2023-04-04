CREATE TABLE IF NOT EXISTS `civicrm_nbr_recall_group` (
  `id` int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'Unique NbrRecallGroup ID',
  `case_id` int unsigned    COMMENT 'FK to Case',
  `recall_group` varchar(128)    COMMENT 'Recall Group',
  PRIMARY KEY (`id`),
  CONSTRAINT FK_civicrm_nbr_recall_group_case_id FOREIGN KEY (`case_id`) REFERENCES `civicrm_case`(`id`) ON DELETE CASCADE)
  ENGINE=InnoDB;
