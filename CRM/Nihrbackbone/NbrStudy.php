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
    $centreField = "custom_" . CRM_Nihrbackbone_BackboneConfig::singleton()->getStudyCustomField('nsd_centre_origin', 'id');
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

  /**
   * Method to get all distinct recall groups (within a study)
   *
   * @param $studyId
   * @return array
   */
  public static function getRecallGroupList($studyId = NULL) {
    $result = [];
    $partTable = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationDataCustomGroup('table_name');
    $studyColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_id', 'column_name');
    $recallColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_recall_group', 'column_name');
    if ($studyId) {
      $query = "SELECT DISTINCT(" . $recallColumn . ") as recall FROM " . $partTable
        . " WHERE " . $studyColumn . " = %1";
      $dao = CRM_Core_DAO::executeQuery($query, [1 => [(int) $studyId, 'Integer']]);
    }
    else {
      $query = "SELECT DISTINCT(" . $recallColumn . ") as recall FROM " . $partTable;
      $dao = CRM_Core_DAO::executeQuery($query);
    }
    while ($dao->fetch()) {
      $result[$dao->recall] = $dao->recall;
    }
    return $result;
  }

  /**
   * Method to check if the study requires blood
   *
   * @param $studyId
   * @return bool
   */
  public static function requiresBlood($studyId) {
    if (empty($studyId)) {
      return FALSE;
    }
    $bloodColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getSelectionCriteriaCustomField('nsc_blood_required', 'column_name');
    $criteriaTable = CRM_Nihrbackbone_BackboneConfig::singleton()->getSelectionCriteriaCustomGroup('table_name');
    $query = "SELECT " . $bloodColumn . " FROM " . $criteriaTable . " WHERE entity_id = %1";
    $required = CRM_Core_DAO::singleValueQuery($query, [1 => [(int) $studyId, "Integer"]]);
    if ($required == 1) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Method to check if the study requires drugs
   *
   * @param $studyId
   * @return bool
   */
  public static function requiresDrugs($studyId) {
    if (empty($studyId)) {
      return FALSE;
    }
    $drugsColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getSelectionCriteriaCustomField('nsc_drug_required', 'column_name');
    $criteriaTable = CRM_Nihrbackbone_BackboneConfig::singleton()->getSelectionCriteriaCustomGroup('table_name');
    $query = "SELECT " . $drugsColumn . " FROM " . $criteriaTable . " WHERE entity_id = %1";
    $required = CRM_Core_DAO::singleValueQuery($query, [1 => [(int) $studyId, "Integer"]]);
    if ($required == 1) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Method to check if the study requires MRI
   *
   * @param $studyId
   * @return bool
   */
  public static function requiresMri($studyId) {
    if (empty($studyId)) {
      return FALSE;
    }
    $mriColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getSelectionCriteriaCustomField('nsc_mri_required', 'column_name');
    $criteriaTable = CRM_Nihrbackbone_BackboneConfig::singleton()->getSelectionCriteriaCustomGroup('table_name');
    $query = "SELECT " . $mriColumn . " FROM " . $criteriaTable . " WHERE entity_id = %1";
    $required = CRM_Core_DAO::singleValueQuery($query, [1 => [(int) $studyId, "Integer"]]);
    if ($required == 1) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Method to check if the study requires travel
   *
   * @param $studyId
   * @return bool
   */
  public static function requiresTravel($studyId) {
    if (empty($studyId)) {
      return FALSE;
    }
    $travelColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getSelectionCriteriaCustomField('nsc_travel_required', 'column_name');
    $criteriaTable = CRM_Nihrbackbone_BackboneConfig::singleton()->getSelectionCriteriaCustomGroup('table_name');
    $query = "SELECT " . $travelColumn . " FROM " . $criteriaTable . " WHERE entity_id = %1";
    $required = CRM_Core_DAO::singleValueQuery($query, [1 => [(int) $studyId, "Integer"]]);
    if ($required == 1) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Method to get the age range for the study
   *
   * @param $studyId
   * @return array
   */
  public static function requiresAgeRange($studyId) {
    $range = [];
    if (!empty($studyId)) {
      $fromColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getSelectionCriteriaCustomField('nsc_age_from', 'column_name');
      $toColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getSelectionCriteriaCustomField('nsc_age_to', 'column_name');
      $criteriaTable = CRM_Nihrbackbone_BackboneConfig::singleton()->getSelectionCriteriaCustomGroup('table_name');
      $query = "SELECT " . $fromColumn . ", " . $toColumn . " FROM " . $criteriaTable . " WHERE entity_id = %1";
      $dao = CRM_Core_DAO::executeQuery($query, [1 => [(int) $studyId, "Integer"]]);
      if ($dao->fetch()) {
        if ($dao->$fromColumn) {
          $range['age_from'] = $dao->$fromColumn;
        }
        if ($dao->$toColumn) {
          $range['age_to'] = $dao->$toColumn;
        }
      }
    }
    return $range;
  }

  /**
   * Method to get the required ethnicities for the study
   *
   * @param $studyId
   * @return array
   */
  public static function requiresEthnicities($studyId) {
    $ethnicities = [];
    if (!empty($studyId)) {
      $ethnicityColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getSelectionCriteriaCustomField('nsc_ethnicity_id', 'column_name');
      $criteriaTable = CRM_Nihrbackbone_BackboneConfig::singleton()->getSelectionCriteriaCustomGroup('table_name');
      $query = "SELECT " . $ethnicityColumn . " FROM " . $criteriaTable . " WHERE entity_id = %1";
      $required = CRM_Core_DAO::singleValueQuery($query, [1 => [(int) $studyId, "Integer"]]);
      if ($required) {
        $values = explode(CRM_Core_DAO::VALUE_SEPARATOR, $required);
        foreach ($values as $value) {
          if (!empty($value)) {
            $ethnicities[] = $value;
          }
        }
      }
    }
    return $ethnicities;
  }

  /**
   * Method to get the required bmi range for the study
   *
   * @param $studyId
   * @return array
   */
  public static function requiresBmiRange($studyId) {
    $range = [];
    if (!empty($studyId)) {
      $fromColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getSelectionCriteriaCustomField('nsc_bmi_from', 'column_name');
      $toColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getSelectionCriteriaCustomField('nsc_bmi_to', 'column_name');
      $criteriaTable = CRM_Nihrbackbone_BackboneConfig::singleton()->getSelectionCriteriaCustomGroup('table_name');
      $query = "SELECT " . $fromColumn . ", " . $toColumn . " FROM " . $criteriaTable . " WHERE entity_id = %1";
      $dao = CRM_Core_DAO::executeQuery($query, [1 => [(int) $studyId, "Integer"]]);
      if ($dao->fetch()) {
        if ($dao->$fromColumn) {
          $range['bmi_from'] = $dao->$fromColumn;
        }
        if ($dao->$toColumn) {
          $range['bmi_to'] = $dao->$toColumn;
        }
      }
    }
    return $range;
  }

  /**
   * Method to get the gender required for the study
   *
   * @param $studyId
   * @return string|null
   */
  public static function requiresGender($studyId) {
    $required = NULL;
    if (!empty($studyId)) {
      $genderColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getSelectionCriteriaCustomField('nsc_gender_id', 'column_name');
      $criteriaTable = CRM_Nihrbackbone_BackboneConfig::singleton()->getSelectionCriteriaCustomGroup('table_name');
      $query = "SELECT " . $genderColumn . " FROM " . $criteriaTable . " WHERE entity_id = %1";
      $required = CRM_Core_DAO::singleValueQuery($query, [1 => [(int) $studyId, "Integer"]]);
    }
    return $required;
  }

  /**
   * Method to determine if the study requires a specific panel
   *
   * @param $studyId
   * @return string|null
   */
  public static function requiresPanel($studyId) {
    $panel = NULL;
    if (!empty($studyId)) {
      $panelColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getSelectionCriteriaCustomField('nsc_panel', 'column_name');
      $criteriaTable = CRM_Nihrbackbone_BackboneConfig::singleton()->getSelectionCriteriaCustomGroup('table_name');
      $query = "SELECT " . $panelColumn . " FROM " . $criteriaTable . " WHERE entity_id = %1";
      $panel = CRM_Core_DAO::singleValueQuery($query, [1 => [(int) $studyId, "Integer"]]);
    }
    return $panel;
  }

  /**
   * Method to determine if commercial study
   *
   * @param $studyId
   * @return string|null
   */
  public static function isCommercial($studyId) {
    $commercial = NULL;
    if (!empty($studyId)) {
      $commercialColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getStudyCustomField('nsd_commercial', 'column_name');
      $studyTable = CRM_Nihrbackbone_BackboneConfig::singleton()->getStudyDataCustomGroup('table_name');
      $query = "SELECT " . $commercialColumn . " FROM " . $studyTable . " WHERE entity_id = %1";
      $commercial = CRM_Core_DAO::singleValueQuery($query, [1 => [(int) $studyId, "Integer"]]);
    }
    return $commercial;
  }

  /**
   * Method to determine is study is a face to face study
   *
   * @param $studyId
   * @return bool
   */
  public static function isFaceToFace($studyId) {
    if (!empty($studyId)) {
      $recallField = "custom_" . CRM_Nihrbackbone_BackboneConfig::singleton()->getStudyCustomField("nsd_recall", "id");
      try {
        $faceToFace = civicrm_api3('Campaign', 'getvalue', [
          'return' => $recallField,
          'id' => $studyId,
        ]);
        if ($faceToFace == TRUE) {
          return TRUE;
        }
      }
      catch (CiviCRM_API3_Exception $ex) {
      }
    }
    return FALSE;
  }

  /**
   * Method to determine is study is online study
   *
   * @param $studyId
   * @return bool
   */
  public static function isOnline($studyId) {
    if (!empty($studyId)) {
      $onlineField = "custom_" . CRM_Nihrbackbone_BackboneConfig::singleton()->getStudyCustomField("nsd_online_study", "id");
      try {
        $online = civicrm_api3('Campaign', 'getvalue', [
          'return' => $onlineField,
          'id' => $studyId,
        ]);
        if ($online == TRUE) {
          return TRUE;
        }
      }
      catch (CiviCRM_API3_Exception $ex) {
      }
    }
    return FALSE;
  }

}
