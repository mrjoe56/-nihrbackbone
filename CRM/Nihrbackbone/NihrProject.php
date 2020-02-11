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

  private $_studyCampaignTypeId = NULL;

  /**
   * CRM_Nihrbackbone_NihrProject constructor.
   */
  public function __construct() {
    $this->_studyCampaignTypeId = CRM_Nihrbackbone_BackboneConfig::singleton()->getStudyCampaignTypeId();
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
      if ($campaignTypeId == $this->_studyCampaignTypeId) {
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
   * Method to check if a project exists
   *
   * @param $projectId
   * @return bool
   */
  public function projectExists($projectId) {
    try {
      $result = civicrm_api3('Campaign', 'getcount', [
        'campaign_type_id' => $this->_studyCampaignTypeId,
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
   * Method to get the name of the project with the id
   *
   * @param $projectId
   * @return bool|string
   */
  public static function getProjectNameWithId($projectId) {
    if (!empty($projectId)) {
      try {
        return (string) civicrm_api3('Campaign', 'getvalue', [
          'return' => 'title',
          'id' => $projectId,
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

  /**
   * Method to retrieve all active cases with eligibility does not meet criteria and
   * check if this still applies (mainly to check if volunteer involved meets age criteria)
   */
  public static function checkMeetsAge() {
    $criteriaStatusId = CRM_Nihrbackbone_BackboneConfig::singleton()->getCriteriaNotMetEligibleStatusId();
    $projectIdColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_project_id', 'column_name');
    $meetStatus = CRM_Nihrbackbone_BackboneConfig::singleton()->getCriteriaNotMetEligibleStatusId();
    $cases = CRM_Nihrbackbone_NbrVolunteerCase::getAllActiveParticipations();
    // for each of those, check if I need to add or remove the status
    foreach ($cases as $caseData) {
      if (!CRM_Nihrbackbone_NihrVolunteer::meetsProjectSelectionCriteria($caseData['contact_id'], $caseData[$projectIdColumn])) {
        CRM_Nihrbackbone_NbrVolunteerCase::removeEligibilityStatus($caseData['case_id'], $caseData[$projectIdColumn], $criteriaStatusId);
      }
      else {
        CRM_Nihrbackbone_NbrVolunteerCase::setEligibilityStatus($meetStatus, $caseData['case_id']);
      }
    }
  }

  /**
   * Method to get the centre of origin of a study
   *
   * @param $studyId
   * @return array|bool
   */
  public static function getCentreOfOrigin($studyId) {
    $centreField = "custom_" . CRM_Nihrbackbone_BackboneConfig::singleton()->getProjectCustomField('npd_centre_of_origin', 'id');
    try {
      return civicrm_api3('Campaign', 'getvalue', [
        'id' => $studyId,
        'return' => $centreField,
      ]);
    }
    catch (CiviCRM_API3_Exception $ex) {
    }
    return FALSE;
  }

}
