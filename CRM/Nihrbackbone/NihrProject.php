<?php
use CRM_Nihrbackbone_ExtensionUtil as E;

/**
 * Class for NIHR BioResource NIHR BioResource Project
 *
 * @author Erik Hommel <erik.hommel@civicoop.org>
 * @date 27 Feb 2019
 * @license AGPL-3.0

 */
class CRM_Nihrbackbone_NihrProject {

  private $_projectCampaignTypeId = NULL;

  /**
   * CRM_Nihrbackbone_NihrProject constructor.
   */
  public function __construct() {
    $this->_projectCampaignTypeId = CRM_Nihrbackbone_BackboneConfig::singleton()->getProjectCampaignTypeId();
  }

  /**
   * Method to check if campaign is a project
   *
   * @param $campaignId
   * @return bool
   */
  public function isNihrProject($campaignId) {
    try {
      $campaignTypeId = (int) civicrm_api3('Campaign', 'getvalue', [
        'id' => $campaignId,
        'return' => 'campaign_type_id',
      ]);
      if ($campaignTypeId == $this->_projectCampaignTypeId) {
        return TRUE;
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
      Civi::log()->warning(E::ts('Unexpected error retrieving campaign data with Campaign API in ') . __METHOD__
        . ', error message from API Campaign getvalue: ' . $ex->getMessage());
    }
    return FALSE;
  }

  /**
   * Method to get the researchers on a project
   *
   * @param $projectId
   * @return array
   */
  public function getProjectResearchers($projectId) {
    $result = [];
    try {
      $researchers = civicrm_api3('NihrCampaignResearcher', 'get', [
        'sequential' => 1,
        'project_id' => $projectId,
        'options' => ['limit' => 0],
      ]);
      foreach ($researchers['values'] as $researcher) {
        $result[] = $researcher['researcher_id'];
      }
    } catch (CiviCRM_API3_Exception $ex) {
    }
    return $result;
  }

  /**
   * Method to determine if the campaign is a NIHR project
   *
   * @param $campaignId
   * @return bool
   */
  public function isProjectCampaign($campaignId) {
    try {
      $campaignTypeId = (int) civicrm_api3('Campaign', 'getvalue', [
        'id' => $campaignId,
        'return' => 'campaign_type_id',
      ]);
      if ($campaignTypeId && $campaignTypeId == $this->_projectCampaignTypeId) {
        return TRUE;
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
    }
    return FALSE;
  }

  /**
   * Method to get the study id of a project
   *
   * @param $projectId
   * @return bool|string|null
   */
  public static function getProjectStudyId($projectId) {
    $tableName = CRM_Nihrbackbone_BackboneConfig::singleton()->getProjectDataCustomGroup('table_name');
    $studyColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getProjectCustomField('npd_study_id', 'column_name');
    $query = "SELECT " . $studyColumn . " FROM " . $tableName . " WHERE entity_id = %1";
    $studyId = CRM_Core_DAO::singleValueQuery($query, [1 => [$projectId, 'Integer']]);
    if ($studyId) {
      return $studyId;
    }
    return FALSE;
  }

  /**
   * Method to set the study id in a project
   *
   * @param $studyId
   * @param $projectId
   * @return bool
   */
  public function setProjectStudyId($studyId, $projectId) {
    $tableName = CRM_Nihrbackbone_BackboneConfig::singleton()->getProjectDataCustomGroup('table_name');
    $studyColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getProjectCustomField('npd_study_id', 'column_name');
    $query = "UPDATE " . $tableName . " SET " . $studyColumn . " = %1 WHERE entity_id = %2";
    try {
      CRM_Core_DAO::executeQuery($query, [
        1 => [$studyId, 'Integer'],
        2 => [$projectId, 'Integer'],
      ]);
      return TRUE;
    }
    catch (Exception $ex) {
      Civi::log()->error(E::ts('Error when updating project with ID ') . $projectId . E::ts(' with study becoming ')
        . $studyId . E::ts(', error from CRM_Core_DAO::executeQuery: '). $ex->getMessage() . E::ts(' in ') . __METHOD__);
      return FALSE;
    }
  }

  /**
   * Method to check if a project exists
   *
   * @param $projectId
   * @return bool
   */
  public function projectExists($projectId) {
    try {
      $result = civicrm_api3('Campaign', 'getcount', [
        'campaign_type_id' => $this->_projectCampaignTypeId,
        'id' => $projectId,
      ]);
      if ($result == 0) {
        return FALSE;
      }
      else {
        return TRUE;
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
      Civi::log()->warning(E::ts('Unexpected error using API Campaign getcount in ') . __METHOD__ . E::ts(', error from API: ') . $ex->getMessage());
      return FALSE;
    }

  }

  /**
   * Method to get the project attribute with id
   *
   * @param int $projectId
   * @param string $attribute
   * @return bool|array
   */
  public function getProjectAttributeWithId($projectId, $attribute) {
    $customFieldId = CRM_Nihrbackbone_BackboneConfig::singleton()->getProjectCustomField($attribute, 'id');
    if ($customFieldId) {
      try {
        return civicrm_api3('Campaign', 'getvalue', [
          'id' => $projectId,
          'campaign_type_id' => $this->_projectCampaignTypeId,
          'return' => 'custom_' . $customFieldId,
        ]);
      }
      catch (CiviCRM_API3_Exception $ex) {
        Civi::log()->warning(E::ts('Error retrieving ') . $attribute . E::ts(' for project ') . $projectId . E::ts(' in ') . __METHOD__ . E::ts(', error from API Campaign getvalue"') . $ex->getMessage());
        return FALSE;
      }
    }
    else {
      Civi::log()->error(E::ts('No attribute with name ') . $attribute . E::ts(' for project in ') . __METHOD__);
      return FALSE;
    }
  }

  /**
   * Method to get the project ID with a case ID
   *
   * @param $caseId
   * @return bool|int
   */
  public static function getProjectIdWithCaseId($caseId) {
    if (!empty($caseId)) {
      try {
        return (int) civicrm_api3('Case', 'getvalue', [
          'return' => "custom_" . CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_project_id', 'id'),
          'id' => $caseId,
        ]);
      }
      catch (CiviCRM_API3_Exception $ex) {
      }
    }
    return FALSE;
  }

  /**
   * Method to get the project selection criteria
   *
   * @param $projectId
   * @return array
   */
  public static function getSelectionCriteria($projectId) {
    $criteria = [];
    if (!empty($projectId)) {
      $returns = [];
      $customFields = CRM_Nihrbackbone_BackboneConfig::singleton()->getSelectionCriteriaCustomGroup('custom_fields');
      foreach ($customFields as $customField) {
        $returns[] = "custom_" . $customField['id'];
      }
      try {
        $customValues = civicrm_api3('Campaign', 'getsingle', [
          'id' => $projectId,
          'return' => $returns,
        ]);
        foreach ($customFields as $customFieldId => $customField) {
          $elementName = "custom_" . $customFieldId;
          $columnName = $customField['column_name'];
          if (isset($customValues[$elementName])) {
            $criteria[$columnName] = $customValues[$elementName];
          }
          else {
            $criteria[$columnName] = NULL;
          }
        }
      }
      catch (CiviCRM_API3_Exception $ex) {
      }
    }
    return $criteria;
  }

}
