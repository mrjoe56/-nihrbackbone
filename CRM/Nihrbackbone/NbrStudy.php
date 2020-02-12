<?php
use CRM_Nihrbackbone_ExtensionUtil as E;

/**
 * Class for NIHR BioResource NIHR BioResource Study
 *
 * @author Erik Hommel <erik.hommel@civicoop.org>
 * @date 12 Feb 2020
 * @license AGPL-3.0

 */
class CRM_Nihrbackbone_NbrStudy {

  private $_studyCampaignTypeId = NULL;

  /**
   * CRM_Nihrbackbone_NbrStudy constructor.
   */
  public function __construct() {
    $this->_studyCampaignTypeId = CRM_Nihrbackbone_BackboneConfig::singleton()->getStudyCampaignTypeId();
  }

  /**
   * Method to check if campaign is a study
   *
   * @param $campaignId
   * @return bool
   */
  public function isNbrStudy($campaignId) {
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
   * Method to check if a study exists
   *
   * @param $studyId
   * @return bool
   */
  public function studyExists($studyId) {
    try {
      $result = civicrm_api3('Campaign', 'getcount', [
        'campaign_type_id' => $this->_studyCampaignTypeId,
        'id' => $studyId,
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
   * Method to get the name of the study with the id
   *
   * @param $studyId
   * @return bool|string
   */
  public static function getStudyNameWithId($studyId) {
    if (!empty($studyId)) {
      try {
        return (string) civicrm_api3('Campaign', 'getvalue', [
          'return' => 'title',
          'id' => $studyId,
        ]);
      }
      catch (CiviCRM_API3_Exception $ex) {
      }
    }
    return FALSE;
  }

  /**
   * Method to get the study number with the id
   *
   * @param $studyId
   * @return bool|string
   */
  public static function getStudyNumberWithId($studyId) {
    if (!empty($studyId)) {
      $numberField = "custom_" . CRM_Nihrbackbone_BackboneConfig::singleton()->getStudyCustomField('nsd_study_number', 'id');
      try {
        return (string) civicrm_api3('Campaign', 'getvalue', [
          'return' => $numberField,
          'id' => $studyId,
        ]);
      }
      catch (CiviCRM_API3_Exception $ex) {
      }
    }
    return FALSE;
  }

  /**
   * Method to get the study selection criteria
   *
   * @param $studyId
   * @return array
   */
  public static function getSelectionCriteria($studyId) {
    $criteria = [];
    if (!empty($studyId)) {
      $returns = [];
      $customFields = CRM_Nihrbackbone_BackboneConfig::singleton()->getSelectionCriteriaCustomGroup('custom_fields');
      foreach ($customFields as $customField) {
        $returns[] = "custom_" . $customField['id'];
      }
      try {
        $customValues = civicrm_api3('Campaign', 'getsingle', [
          'id' => $studyId,
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
    $studyIdColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_project_id', 'column_name');
    $meetStatus = CRM_Nihrbackbone_BackboneConfig::singleton()->getCriteriaNotMetEligibleStatusId();
    $cases = CRM_Nihrbackbone_NbrVolunteerCase::getAllActiveParticipations();
    // for each of those, check if I need to add or remove the status
    foreach ($cases as $caseData) {
      if (!CRM_Nihrbackbone_NihrVolunteer::meetsProjectSelectionCriteria($caseData['contact_id'], $caseData[$studyIdColumn])) {
        CRM_Nihrbackbone_NbrVolunteerCase::removeEligibilityStatus($caseData['case_id'], $caseData[$studyIdColumn], $criteriaStatusId);
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
    $centreField = "custom_" . CRM_Nihrbackbone_BackboneConfig::singleton()->getStudyCustomField('nsd_centre_of_origin', 'id');
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
