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

SET FOREIGN_KEY_CHECKS=1;
-- /*******************************************************
-- *
-- * Create new tables
-- *
-- *******************************************************/

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
     `title` varchar(128)    COMMENT 'Study Title',
     `description` text    COMMENT 'Study Description',
     `ethics_number` varchar(32)    COMMENT 'Ethics Number',
     `ethics_approved_id` int unsigned    COMMENT 'Ethics Approved',
     `requirements` text    COMMENT 'Requirements',
     `start_date` date    COMMENT 'Study start date',
     `end_date` date    COMMENT 'Study end date',
     `centre_study_origin_id` int unsigned    COMMENT 'FK to Contact for Study Centre Origin',
     `notes` text    COMMENT 'Notes',
     `status_id` int unsigned    COMMENT 'Study Status',
     `created_date` datetime    COMMENT 'Date Created',
     `created_by_id` int unsigned    COMMENT 'FK to Contact for Created By',
     `modified_date` datetime    COMMENT 'Date Modified',
     `modified_by_id` int unsigned    COMMENT 'FK to Contact for Modified By' 
,
        PRIMARY KEY (`id`)
 
 
,          CONSTRAINT FK_civicrm_nihr_study_investigator_id FOREIGN KEY (`investigator_id`) REFERENCES `civicrm_contact`(`id`) ON DELETE CASCADE,          CONSTRAINT FK_civicrm_nihr_study_centre_study_origin_id FOREIGN KEY (`centre_study_origin_id`) REFERENCES `civicrm_contact`(`id`) ON DELETE CASCADE,          CONSTRAINT FK_civicrm_nihr_study_created_by_id FOREIGN KEY (`created_by_id`) REFERENCES `civicrm_contact`(`id`) ON DELETE CASCADE,          CONSTRAINT FK_civicrm_nihr_study_modified_by_id FOREIGN KEY (`modified_by_id`) REFERENCES `civicrm_contact`(`id`) ON DELETE CASCADE  
)    ;

 
