CREATE TABLE IF NOT EXISTS `civicrm_nbr_mailing` (
    `id` int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'Unique NbrMailing ID',
    `mailing_id` int unsigned    COMMENT 'FK to Mailing',
    `group_id` int unsigned    COMMENT 'FK to Group',
    `study_id` int unsigned    COMMENT 'FK to Stduy (Campaign)',
    `nbr_mailing_type` varchar(32)    COMMENT 'Type of Mailing (invite initially)',
    PRIMARY KEY (`id`),
    CONSTRAINT FK_civicrm_nbr_mailing_mailing_id FOREIGN KEY (`mailing_id`) REFERENCES `civicrm_mailing`(`id`) ON DELETE CASCADE,
    CONSTRAINT FK_civicrm_nbr_mailing_group_id FOREIGN KEY (`group_id`) REFERENCES `civicrm_group`(`id`) ON DELETE CASCADE,
    CONSTRAINT FK_civicrm_nbr_mailing_study_id FOREIGN KEY (`study_id`) REFERENCES `civicrm_campaign`(`id`) ON DELETE CASCADE
);
