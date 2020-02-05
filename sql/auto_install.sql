-- +--------------------------------------------------------------------+
-- | CiviCRM version 5                                                  |
-- +--------------------------------------------------------------------+
-- | Copyright CiviCRM LLC (c) 2004-2019                                |
-- +--------------------------------------------------------------------+
-- | This file is a part of CiviCRM.                                    |
-- |                                                                    |
-- | CiviCRM is free software; you can copy, modify, and distribute it  |
-- | under the terms of the GNU Affero General Public License           |
-- | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
-- |                                                                    |
-- | CiviCRM is distributed in the hope that it will be useful, but     |
-- | WITHOUT ANY WARRANTY; without even the implied warranty of         |
-- | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
-- | See the GNU Affero General Public License for more details.        |
-- |                                                                    |
-- | You should have received a copy of the GNU Affero General Public   |
-- | License and the CiviCRM Licensing Exception along                  |
-- | with this program; if not, contact CiviCRM LLC                     |
-- | at info[AT]civicrm[DOT]org. If you have questions about the        |
-- | GNU Affero General Public License or the licensing of CiviCRM,     |
-- | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
-- +--------------------------------------------------------------------+
--
-- Generated from schema.tpl
-- DO NOT EDIT.  Generated by CRM_Core_CodeGen
--


-- +--------------------------------------------------------------------+
-- | CiviCRM version 5                                                  |
-- +--------------------------------------------------------------------+
-- | Copyright CiviCRM LLC (c) 2004-2019                                |
-- +--------------------------------------------------------------------+
-- | This file is a part of CiviCRM.                                    |
-- |                                                                    |
-- | CiviCRM is free software; you can copy, modify, and distribute it  |
-- | under the terms of the GNU Affero General Public License           |
-- | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
-- |                                                                    |
-- | CiviCRM is distributed in the hope that it will be useful, but     |
-- | WITHOUT ANY WARRANTY; without even the implied warranty of         |
-- | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
-- | See the GNU Affero General Public License for more details.        |
-- |                                                                    |
-- | You should have received a copy of the GNU Affero General Public   |
-- | License and the CiviCRM Licensing Exception along                  |
-- | with this program; if not, contact CiviCRM LLC                     |
-- | at info[AT]civicrm[DOT]org. If you have questions about the        |
-- | GNU Affero General Public License or the licensing of CiviCRM,     |
-- | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
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

DROP TABLE IF EXISTS `civicrm_nihr_study`;
DROP TABLE IF EXISTS `civicrm_nbr_import_log`;

SET FOREIGN_KEY_CHECKS=1;
-- /*******************************************************
-- *
-- * Create new tables
-- *
-- *******************************************************/

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
 
 
 
)    ;

-- /*******************************************************
-- *
-- * civicrm_nihr_study
-- *
-- * NIHR BioResource Study
-- *
-- *******************************************************/
CREATE TABLE `civicrm_nihr_study` (


     `id` int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'Unique NihrStudy ID',
     `study_number` varchar(24)    COMMENT 'Specific Study Number in NIHR BioResource',
     `investigator_id` int unsigned    COMMENT 'FK to Contact for Principal Investigator',
     `short_name` varchar(64)    COMMENT 'Study Short Name',
     `long_name` varchar(256)    COMMENT 'Study Long Name',
     `description` text    COMMENT 'Study Description',
     `ethics_number` varchar(32)    COMMENT 'Ethics Number',
     `ethics_approved_id` int unsigned    COMMENT 'Ethics Approved',
     `ethics_approved_date` date    COMMENT 'Ethics Approved Date',
     `requirements` text    COMMENT 'Requirements',
     `valid_start_date` date    COMMENT 'Study valid start date',
     `valid_end_date` date    COMMENT 'Study valid end date',
     `centre_study_origin_id` int unsigned    COMMENT 'FK to Contact for Study Centre Origin',
     `notes` text    COMMENT 'Notes',
     `status_id` varchar(64)    COMMENT 'Study Status',
     `created_date` datetime    COMMENT 'Date Created',
     `created_by_id` int unsigned    COMMENT 'FK to Contact for Created By',
     `modified_date` datetime    COMMENT 'Date Modified',
     `modified_by_id` int unsigned    COMMENT 'FK to Contact for Modified By' 
,
        PRIMARY KEY (`id`)
 
 
,          CONSTRAINT FK_civicrm_nihr_study_investigator_id FOREIGN KEY (`investigator_id`) REFERENCES `civicrm_contact`(`id`) ON DELETE CASCADE,          CONSTRAINT FK_civicrm_nihr_study_centre_study_origin_id FOREIGN KEY (`centre_study_origin_id`) REFERENCES `civicrm_contact`(`id`) ON DELETE CASCADE,          CONSTRAINT FK_civicrm_nihr_study_created_by_id FOREIGN KEY (`created_by_id`) REFERENCES `civicrm_contact`(`id`) ON DELETE CASCADE,          CONSTRAINT FK_civicrm_nihr_study_modified_by_id FOREIGN KEY (`modified_by_id`) REFERENCES `civicrm_contact`(`id`) ON DELETE CASCADE  
)    ;

 
