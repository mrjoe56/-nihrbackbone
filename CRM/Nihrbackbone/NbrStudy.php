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
  public static function getRecallGroupList($studyId = NULL): array {
    $result = [];
    if ($studyId) {
      $query = "SELECT DISTINCT(c.recall_group)
        FROM civicrm_value_nbr_participation_data a
        JOIN civicrm_case b ON a.entity_id = b.id
        JOIN civicrm_nbr_recall_group c ON b.id = c.case_id
        WHERE a.nvpd_study_id = %1 AND b.is_deleted = FALSE";
      $dao = CRM_Core_DAO::executeQuery($query, [1 => [$studyId, "Integer"]]);
    }
    else {
      $query = "SELECT DISTINCT(c.recall_group)
        FROM civicrm_value_nbr_participation_data a
        JOIN civicrm_case b ON a.entity_id = b.id
        JOIN civicrm_nbr_recall_group c ON b.id = c.case_id
        WHERE b.is_deleted = FALSE";
      $dao = CRM_Core_DAO::executeQuery($query);
    }
    while ($dao->fetch()) {
      $result[$dao->recall_group] = $dao->recall_group;
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
      try {
        $campaigns = \Civi\Api4\Campaign::get()
          ->addSelect('nbr_study_data.nsd_recall')
          ->addWhere('id', '=', $studyId)
          ->setLimit(1)
          ->setCheckPermissions(FALSE)
          ->execute();
        $campaign = $campaigns->first();
        if ($campaign['nbr_study_data.nsd_recall']) {
          return TRUE;
        }
      }
      catch (API_Exception $ex) {
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
      try {
        $campaigns = \Civi\Api4\Campaign::get()
          ->addSelect('nbr_study_data.nsd_online_study')
          ->addWhere('id', '=', $studyId)
          ->setLimit(1)
          ->setCheckPermissions(FALSE)
          ->execute();
        $campaign = $campaigns->first();
        if ($campaign['nbr_study_data.nsd_online_study']) {
          return TRUE;
        }
      }
      catch (API_Exception $ex) {
      }
    }
    return FALSE;
  }

  /**
   * Method to check if the study is data only
   *
   * @param int $studyId
   * @return bool
   */
  public static function isDataOnly(int $studyId) {
    if (!empty($studyId)) {
      try {
        $campaigns = \Civi\Api4\Campaign::get()
          ->addSelect('nbr_study_data.nsd_data_only')
          ->addWhere('id', '=', $studyId)
          ->setCheckPermissions(FALSE)
          ->execute();
        $study = $campaigns->first();
        if ($study['nbr_study_data.nsd_data_only']) {
          return TRUE;
        }
      }
      catch (API_Exception $ex) {
      }
    }
    return FALSE;
  }

  /**
   * Method to determine if the study has a status that does not allow any actions on volunteers
   *
   * @param $studyId
   * @return bool
   */
  public static function hasNoActionStatus($studyId) {
    $noActionStatusSetting = Civi::settings()->get('nbr_study_status_no_actions');
    if ($noActionStatusSetting) {
      $noActionStatuses = explode(",", $noActionStatusSetting);
      try {
        $campaigns = \Civi\Api4\Campaign::get()
          ->addSelect('status_id')
          ->addWhere('id', '=', (int) $studyId)
          ->setLimit(1)
          ->setCheckPermissions(FALSE)
          ->execute();
        $campaign = $campaigns->first();
        if (in_array((string) $campaign['status_id'], $noActionStatuses)) {
          return TRUE;
        }
      }
      catch (API_Exception $ex) {
        Civi::log()->error('Could not retrieve status of study/campaign with id ' . $studyId . ', error from API4 Campaign get: ' . $ex->getMessage());
      }
    }
    return FALSE;
  }

  /**
   * Delete participation cases from studies where study status_id = Not Progressed and study participant status = selected
   *
   * @return false|array
   */
  public static function deleteParticipationNotProgressed() {
    $result = [];
    try {
      $optionValues = \Civi\Api4\OptionValue::get()
        ->addSelect('value')
        ->addWhere('option_group_id:name', '=', 'campaign_status')
        ->addWhere('name', '=', 'Not Progressed')
        ->setCheckPermissions(FALSE)
        ->execute();
      $notProgressed = $optionValues->first();
    }
    catch (API_Exception $ex) {
      Civi::log()->error(E::ts("Could not find a campaign (study) status with name Not Progressed in")
        . __METHOD__ . E::ts(", error from API4 OptionValue get: ") . $ex->getMessage());
      return FALSE;
    }
    if ($notProgressed['value']) {
      $query = "SELECT DISTINCT(cc.id) AS case_id, st.id AS study_id
          FROM civicrm_case AS cc
            JOIN civicrm_value_nbr_participation_data AS pd ON cc.id = pd.entity_id
            JOIN civicrm_campaign AS st ON pd.nvpd_study_id = st.id
          WHERE st.status_id = %1 AND cc.case_type_id = %2 AND pd.nvpd_study_participation_status = %3 AND cc.is_deleted = %4";
      $dao = CRM_Core_DAO::executeQuery($query, [
        1 => [(int) $notProgressed['value'], "Integer"],
        2 => [(int) CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCaseTypeId(), "Integer"],
        3 => [Civi::service('nbrBackbone')->getSelectedParticipationStatusValue(), "String"],
        4 => [0, "Integer"],
      ]);
      while ($dao->fetch()) {
        try {
          civicrm_api3('Case', 'delete', ['id' => $dao->case_id]);
          $result[] = "Participation case with ID " . $dao->case_id . " from study with ID " . $dao->study_id . " deleted.";
        }
        catch (CiviCRM_API3_Exception $ex) {
          Civi::log()->error(E::ts("Could not delete participation case with ID ") . $dao->case_id . E::ts(" on study ")
            . $dao->study_id . E::ts(" in ") . __METHOD__
            . E::ts(", error message from API3 Case delete: ") . $ex->getMessage());
        }
      }
    }
    return $result;
  }

  /**
   * Method to set the participation status of a study to participated for the imported case on the study AND generate the
   * study participant ID (which will not be done automatically as the activity is not invited -> that is when in face to face the study
   * participant ID is generated. As this only needs to happen upon import for data only it is in here rather than in the post
   * hook on an open case activity.
   * It will only change the status of the cases where the volunteer status allows data only and data is not to be destroyed
   *
   * @param int $caseId
   * @param int $volunteerId
   * @return void
   */
  public static function processDataOnlyImport(int $caseId, int $volunteerId): void {
    if (CRM_Nihrbackbone_NihrVolunteer::isAvailableForDataOnly($volunteerId)) {
      $table = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationDataCustomGroup('table_name');
      $studyParticipationStatusColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_participation_status', 'column_name');
      $update = "UPDATE " . $table . " SET " . $studyParticipationStatusColumn . " = %1 WHERE entity_id = %2";
      $updateParams = [
        1 => [Civi::service('nbrBackbone')->getParticipatedParticipationStatusValue(), "String"],
        2 => [$caseId, "Integer"],
      ];
      CRM_Core_DAO::executeQuery($update, $updateParams);
      // and now create the study participant id for the case as there will never be an invite activity
      CRM_Nihrnumbergenerator_StudyParticipantNumberGenerator::createNewNumberForCase($caseId);
    }
  }

}
