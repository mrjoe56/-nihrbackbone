CREATE TABLE `civicrm_nbr_county` (
    `id` int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'Unique NbrCounty ID',
    `state_province_id` int unsigned    COMMENT 'FK to State/Province',
    `synonym` varchar(256)    COMMENT 'County synonym',
    PRIMARY KEY (`id`),
    INDEX `index_synonym`(synonym),
    CONSTRAINT FK_civicrm_nbr_county_state_province_id FOREIGN KEY (`state_province_id`) REFERENCES `civicrm_state_province`(`id`) ON DELETE CASCADE
);
