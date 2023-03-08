-- +--------------------------------------------------------------------+
-- | Copyright CiviCRM LLC. All rights reserved.                        |
-- |                                                                    |
-- | This work is published under the GNU AGPLv3 license with some      |
-- | permitted exceptions and without any warranty. For full license    |
-- | and copyright information, see https://civicrm.org/licensing       |
-- +--------------------------------------------------------------------+
--
-- Generated from schema.tpl
-- DO NOT EDIT.  Generated by CRM_Core_CodeGen
--


-- +--------------------------------------------------------------------+
-- | Copyright CiviCRM LLC. All rights reserved.                        |
-- |                                                                    |
-- | This work is published under the GNU AGPLv3 license with some      |
-- | permitted exceptions and without any warranty. For full license    |
-- | and copyright information, see https://civicrm.org/licensing       |
-- +--------------------------------------------------------------------+
--
-- Generated from drop.tpl
-- DO NOT EDIT.  Generated by CRM_Core_CodeGen
--
-- /*******************************************************
-- *
-- * Clean up the exisiting tables
-- *
-- *******************************************************/

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `civicrm_nbr_study_researcher`;
DROP TABLE IF EXISTS `civicrm_nbr_mailing`;
DROP TABLE IF EXISTS `civicrm_nbr_import_log`;
DROP TABLE IF EXISTS `civicrm_nbr_county`;

SET FOREIGN_KEY_CHECKS=1;
-- /*******************************************************
-- *
-- * Create new tables
-- *
-- *******************************************************/

-- /*******************************************************
-- *
-- * civicrm_nbr_county
-- *
-- * Table to map county syonyms (incl. original Starfish names)
-- *
-- *******************************************************/
CREATE TABLE `civicrm_nbr_county` (


     `id` int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'Unique NbrCounty ID',
     `state_province_id` int unsigned    COMMENT 'FK to State/Province',
     `synonym` varchar(256)    COMMENT 'County synonym' 
,
        PRIMARY KEY (`id`)
 
    ,     UNIQUE INDEX `index_synonym`(
        synonym
  )
  
,          CONSTRAINT FK_civicrm_nbr_county_state_province_id FOREIGN KEY (`state_province_id`) REFERENCES `civicrm_state_province`(`id`) ON DELETE CASCADE  
)  ENGINE=InnoDB  ;

-- /*******************************************************
-- *
-- * civicrm_nbr_import_log
-- *
-- * FIXME
-- *
-- *******************************************************/
CREATE TABLE `civicrm_nbr_import_log` (


     `id` int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'Unique NbrImportLog ID',
     `import_id` varchar(32)    COMMENT 'Unique ID of the import job',
     `filename` varchar(128)    COMMENT 'Name of the import file that is being logged',
     `message_type` varchar(128)    COMMENT 'Type of message (info, warning, error)',
     `message` text    COMMENT 'Message',
     `logged_date` date    COMMENT 'The date the message was logged' 
,
        PRIMARY KEY (`id`)
 
 
 
)  ENGINE=InnoDB  ;

-- /*******************************************************
-- *
-- * civicrm_nbr_mailing
-- *
-- * Mailing data specific for NIHR BioResource
-- *
-- *******************************************************/
CREATE TABLE `civicrm_nbr_mailing` (


     `id` int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'Unique NbrMailing ID',
     `mailing_id` int unsigned    COMMENT 'FK to Mailing',
     `group_id` int unsigned    COMMENT 'FK to Group',
     `study_id` int unsigned    COMMENT 'FK to Study (Campaign)',
     `nbr_mailing_type` varchar(32)    COMMENT 'Type of Mailing (invite initially)' 
,
        PRIMARY KEY (`id`)
 
 
,          CONSTRAINT FK_civicrm_nbr_mailing_mailing_id FOREIGN KEY (`mailing_id`) REFERENCES `civicrm_mailing`(`id`) ON DELETE CASCADE,          CONSTRAINT FK_civicrm_nbr_mailing_group_id FOREIGN KEY (`group_id`) REFERENCES `civicrm_group`(`id`) ON DELETE SET NULL,          CONSTRAINT FK_civicrm_nbr_mailing_study_id FOREIGN KEY (`study_id`) REFERENCES `civicrm_campaign`(`id`) ON DELETE CASCADE  
)  ENGINE=InnoDB  ;

-- /*******************************************************
-- *
-- * civicrm_nbr_study_researcher
-- *
-- * Table to link researcher(s) to study
-- *
-- *******************************************************/
CREATE TABLE `civicrm_nbr_study_researcher` (


     `id` int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'Unique NbrStudyResearcher ID',
     `researcher_contact_id` int unsigned    COMMENT 'FK to Contact',
     `nbr_study_id` int unsigned    COMMENT 'FK to Campaign' 
,
        PRIMARY KEY (`id`)
 
 
,          CONSTRAINT FK_civicrm_nbr_study_researcher_researcher_contact_id FOREIGN KEY (`researcher_contact_id`) REFERENCES `civicrm_contact`(`id`) ON DELETE CASCADE,          CONSTRAINT FK_civicrm_nbr_study_researcher_nbr_study_id FOREIGN KEY (`nbr_study_id`) REFERENCES `civicrm_campaign`(`id`) ON DELETE CASCADE  
)  ENGINE=InnoDB  ;

 